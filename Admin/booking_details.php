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
include "components/sidebar.php";

if(!isset($_GET['id']))
{
    die("Invalid Booking");
}

$booking_id = $_GET['id'];

$query = "
SELECT
    b.booking_id,
    b.booking_status,
    b.booking_time,
    u.full_name,
    u.email,
    u.phone,
    m.title,
    sh.show_date,
    sh.show_time,
    GROUP_CONCAT(st.seat_number) seats
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN shows sh ON b.show_id=sh.show_id
JOIN movies m ON sh.movie_id=m.movie_id
LEFT JOIN booking_details bd ON b.booking_id=bd.booking_id
LEFT JOIN show_seats ss ON bd.show_seat_id=ss.show_seat_id
LEFT JOIN seats st ON ss.seat_id=st.seat_id
WHERE b.booking_id='$booking_id'
GROUP BY b.booking_id
";

$result = mysqli_query($conn,$query);
$booking = mysqli_fetch_assoc($result);

if(!$booking)
{
    die("Booking Not Found");
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Details</title>
<link rel="stylesheet" href="../Assets/css/Admin/booking_details.css">
<link rel="stylesheet" href="../Assets/css/Admin/sidebar.css">
</head>
<body>

<div class="container">

<h1>🎟 Booking Details</h1>

<div class="card">

<div class="row">
<label>Booking ID</label>
<span><?php echo $booking['booking_id']; ?></span>
</div>

<div class="row">
<label>Customer Name</label>
<span><?php echo $booking['full_name']; ?></span>
</div>

<div class="row">
<label>Email</label>
<span><?php echo $booking['email']; ?></span>
</div>

<div class="row">
<label>Phone</label>
<span><?php echo $booking['phone']; ?></span>
</div>

<div class="row">
<label>Movie</label>
<span><?php echo $booking['title']; ?></span>
</div>

<div class="row">
<label>Show Date</label>
<span><?php echo $booking['show_date']; ?></span>
</div>

<div class="row">
<label>Show Time</label>
<span><?php echo $booking['show_time']; ?></span>
</div>

<div class="row">
<label>Seats</label>
<span><?php echo $booking['seats']; ?></span>
</div>

<div class="row">
<label>Status</label>
<span class="status">
<?php echo $booking['booking_status']; ?>
</span>
</div>

<div class="row">
<label>Booking Time</label>
<span><?php echo $booking['booking_time']; ?></span>
</div>

<a href="booking_monitoring.php" class="back-btn">
Back
</a>

</div>

</div>

</body>
</html>