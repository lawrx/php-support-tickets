<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

/*
 * TODO 10:
 * Read and validate the submitted ticket ID and status.
 */

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$newStatus = trim($_POST['status'] ?? '');

if (!$id || !in_array($newStatus, allowedStatuses(), true)) {
    http_response_code(400);
    exit('Invalid ticket ID or status.');
}

$tickets = loadTickets();
$ticketFound = false;

foreach ($tickets as &$ticket) {
    if ((int)$ticket['id'] === $id) {
        $ticket['status'] = $newStatus;
        $ticketFound = true;
        break;
    }
}
unset($ticket);

if (!$ticketFound) {
    http_response_code(404);
    exit('Ticket not found.');
}

if (saveTickets($tickets)) {
    header('Location: detail.php?id=' . $id);
    exit;
} else {
    http_response_code(500);
    exit('Failed to save status update.');
}