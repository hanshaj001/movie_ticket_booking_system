<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Try to load environment variables
$env_path = __DIR__ . '/../.env';
$env = file_exists($env_path) ? parse_ini_file($env_path) : [];

$db_host = $env['DB_HOST'] ?? 'localhost';
$db_user = $env['DB_USER'] ?? 'root';
$db_pass = isset($env['DB_PASSWORD']) ? trim($env['DB_PASSWORD'], '"') : '';
$db_name = $env['DB_NAME'] ?? 'movie_ticket_booking_system';

$conn = mysqli_connect(
    $db_host,
    $db_user,
    $db_pass,
    $db_name
);

if (!$conn) {
    die("Database Connection Failed");
}
 
?>