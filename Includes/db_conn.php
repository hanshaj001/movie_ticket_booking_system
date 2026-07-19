<?php
require_once __DIR__ . '/error_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $conn = mysqli_connect(
        'localhost',
        'root',
        '',
        'movie_ticket_booking_system'
    );

    if(!$conn)
    {
        error_log("Database Connection Failed: " . mysqli_connect_error());
        friendly_error_page();
    }
} catch (Exception $e) {
    error_log("Database Exception: " . $e->getMessage());
    friendly_error_page();
}
?>