<?php

require_once '../Includes/db_conn.php';

// checking if theere is id in url
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: add_show.php?msg=Invalid show ID&type=danger");
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
    header("Location: add_show.php?msg=Show not found&type=danger");
    exit();
}

$show = mysqli_fetch_assoc($result);

// cheking if aleady cancelled 
if ($show['show_status'] == 'CANCELLED') {

    header("Location: add_show.php?msg=Show is already cancelled&type=warning");
    exit();
}

// checking if already completed
if ($show['show_status'] == 'COMPLETED') {

    header("Location: add_show.php?msg=Completed shows cannot be cancelled&type=warning");
    exit();
}

// checking if show has already started
$showDateTime = strtotime(
    $show['show_date'] . ' ' . $show['show_time']
);

if (time() >= $showDateTime) {

    header("Location: add_show.php?msg=Show has already started&type=warning");
    exit();
}

//checkin if there exits booking for that particular shoeo
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

    header("Location: add_show.php?msg=Cannot cancel. Customers have already booked tickets.&type=danger");
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

    header("Location:add_show.php?msg=Show cancelled successfully&type=success");
    exit();

}else{

    header("Location:add_show.php?msg=Unable to cancel show&type=danger");
    exit();

}

?>