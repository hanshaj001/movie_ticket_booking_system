<?php
$id = $_GET['id'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Cancelled</title>
<link rel="stylesheet" href="../Assets/cancel_success.css">
</head>
<body>

<div class="box">

<div class="icon">
✓
</div>

<h2>
Booking Cancelled Successfully
</h2>

<p>
Booking ID #<?php echo $id; ?> has been cancelled.
Associated seats have been released.
</p>

<a href="booking_monitoring.php">
Return To Booking Monitoring
</a>

</div>

</body>
</html>