<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die("CSRF Token Validation Failed.");
}

require_once '../Includes/db_conn.php';

// checking if there is id in url
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid show ID.";
    header("Location: add_show.php");
    exit();
}

$show_id = (int)$_GET['id'];

//fetching show 
$stmt = mysqli_prepare($conn, "
SELECT show_status,
       show_date,
       show_time
FROM shows
WHERE show_id = ?
");

mysqli_stmt_bind_param($stmt, "i", $show_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "Show not found.";
    header("Location: add_show.php");
    exit();
}

$show = mysqli_fetch_assoc($result);

// checking if already cancelled 
if ($show['show_status'] == 'CANCELLED') {
    $_SESSION['error_message'] = "Show is already cancelled.";
    header("Location: add_show.php");
    exit();
}

// checking if already completed
if ($show['show_status'] == 'COMPLETED') {
    $_SESSION['error_message'] = "Completed shows cannot be cancelled.";
    header("Location: add_show.php");
    exit();
}

// checking if show has already started
$showDateTime = strtotime(
    $show['show_date'] . ' ' . $show['show_time']
);

if (time() >= $showDateTime) {
    $_SESSION['error_message'] = "Show has already started.";
    header("Location: add_show.php");
    exit();
}

//checking if there exist bookings for that particular show
$stmt = mysqli_prepare($conn, "
SELECT COUNT(*) AS total
FROM bookings
WHERE show_id = ?
AND booking_status='CONFIRMED'
");

mysqli_stmt_bind_param($stmt, "i", $show_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row['total'] > 0) {
    $_SESSION['error_message'] = "Cannot cancel. Customers have already booked tickets.";
    header("Location: add_show.php");
    exit();
}

// if all clear then change the state of the show
$stmt = mysqli_prepare($conn,"
UPDATE shows
SET show_status='CANCELLED'
WHERE show_id=?
");

mysqli_stmt_bind_param($stmt,"i",$show_id);

if(mysqli_stmt_execute($stmt)){
    $_SESSION['success_message'] = "Show cancelled successfully.";
    header("Location: add_show.php");
    exit();
}else{
    $_SESSION['error_message'] = "Unable to cancel show.";
    header("Location: add_show.php");
    exit();
}

?>