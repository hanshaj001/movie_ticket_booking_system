<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "movie_ticket_booking_system";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn)
{
    die(
        "Database Connection Failed: "
        . mysqli_connect_error()
    );
}

mysqli_set_charset($conn, "utf8");

?>