<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = mysqli_connect(
    'localhost',
    'root',
    '',
    'movie_ticket_booking_system'
);

if(!$conn)
{
    die("Database Connection Failed");
}

?>