<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "movie_ticket_booking_system"
);

if(!$conn)
{
    die("Database Connection Failed");
}

?>


<?php

require_once '../Includes/db_conn.php';

if(!isset($_GET['id']))
{
    die("Invalid Request");
}

$booking_id = $_GET['id'];

$booking = mysqli_query($conn,"
SELECT *
FROM bookings
WHERE booking_id='$booking_id'
");

$data = mysqli_fetch_assoc($booking);

if(!$data)
{
    die("Booking Not Found");
}

/* Update Booking Status */

mysqli_query($conn,"
UPDATE bookings
SET booking_status='CANCELLED'
WHERE booking_id='$booking_id'
");

/* Release Seats */

mysqli_query($conn,"
UPDATE show_seats ss
JOIN booking_details bd
ON ss.show_seat_id=bd.show_seat_id
SET ss.booking_status='AVAILABLE'
WHERE bd.booking_id='$booking_id'
");

header("Location: booking_cancel_success.php?id=".$booking_id);
exit();

?>