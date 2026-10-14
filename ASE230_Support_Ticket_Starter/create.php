<?php
require_once __DIR__ . '/includes/functions.php';

$values = [
    'name' => '',
    'email' => '',
    'category' => '',
    'description' => ''
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim($_POST['name'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $values['category'] = trim($_POST['category'] ?? '');
    $values['description'] = trim($_POST['description'] ?? '');

    /*
     * TODO 6: Server-side validation
     */
    if ($values['name'] === '') {
        $errors[] = 'Customer name is required.';
    }

    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (!in_array($values['category'], allowedCategories(), true)) {
        $errors[] = 'Please select a valid category.';
    }

    if (strlen($values['description']) < 10) {
        $errors[] = 'Description must be at least 10 characters long.';
    }

    $attachment = null;

    /*
     * TODO 7: Optional file upload
     */
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'An error occurred during file upload.';
        } else {
            // Limit file size (e.g., 2MB max)
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $errors[] = 'The uploaded file exceeds the 2MB size limit.';
            }

            // Validate real file type using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowedTypes, true)) {
                $errors[] = 'Only JPEG, PNG, and WebP images are allowed.';
            }

            if (empty($errors)) {
                // Generate a safe, unique filename
                $extension = match ($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'bin'
                };
                
                $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;
                $uploadDir = __DIR__ . '/uploads/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destination = $uploadDir . $safeFilename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $attachment = $safeFilename;
                } else {
                    $errors[] = 'Failed to move the uploaded file.';
                }
            }
        }
    }

    if (empty($errors)) {
        $tickets = loadTickets();

        /*
         * TODO 8: Build the new ticket.
         */
        $newTicket = [
            'id' => nextTicketId($tickets),
            'name' => $values['name'],
            'email' => $values['email'],
            'category' => $values['category'],
            'description' => $values['description'],
            'status' => 'Open',
            'attachment' => $attachment
        ];

        $tickets[] = $newTicket;

        /*
         * TODO 9:
         * Save the updated ticket collection and redirect.
         */
        if (saveTickets($tickets)) {
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Failed to save the new ticket. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Support Ticket</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<main class="container">
    <p><a href="index.php">&larr; Back to tickets</a></p>

    <section class="panel">
        <h1>Create Support Ticket</h1>
        <p>Complete the form below. Important validation must occur on the server.</p>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="create.php" enctype="multipart/form-data">
            <label for="name">Customer Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= e($values['name']) ?>"
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($values['email']) ?>"
            >

            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">Choose a category</option>
                <?php foreach (allowedCategories() as $category): ?>
                    <option
                        value="<?= e($category) ?>"
                        <?= $values['category'] === $category ? 'selected' : '' ?>
                    >
                        <?= e(ucfirst($category)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="7"><?= e($values['description']) ?></textarea>

            <label for="attachment">Optional Image</label>
            <input
                type="file"
                id="attachment"
                name="attachment"
                accept="image/jpeg,image/png,image/webp"
            >

            <button type="submit">Submit Ticket</button>
        </form>
    </section>
</main>
</body>
</html>