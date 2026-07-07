<?php
// Initialize system secure authentication tracking session
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // 1. Access Control Validation: Verify user login status
// if (!isset($_SESSION['user_id'])) {
//     header("Location: home.php");
//     exit();
// }

require_once '../Includes/db_conn.php';

// Check if booking_id is provided
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: home.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);

// Get booking details
$booking_query = "SELECT b.*, s.show_date, s.show_time, scr.screen_name, m.title, m.poster_url, m.movie_format, m.movie_id
                  FROM bookings b
                  JOIN shows s ON b.show_id = s.show_id
                  JOIN screens scr ON s.screen_id = scr.screen_id
                  JOIN movies m ON s.movie_id = m.movie_id
                  WHERE b.booking_id = $booking_id";
$booking_result = mysqli_query($conn, $booking_query);

if (mysqli_num_rows($booking_result) === 0) {
    header("Location: home.php");
    exit();
}

$booking = mysqli_fetch_assoc($booking_result);

// Get booked seats
$seats_query = "SELECT ss.show_seat_id, s.seat_number, s.seat_type, bd.ticket_price
                FROM booking_details bd
                JOIN show_seats ss ON bd.show_seat_id = ss.show_seat_id
                JOIN seats s ON ss.seat_id = s.seat_id
                WHERE bd.booking_id = $booking_id
                ORDER BY s.seat_number";
$seats_result = mysqli_query($conn, $seats_query);

$booked_seats = [];
while ($seat = mysqli_fetch_assoc($seats_result)) {
    $booked_seats[] = $seat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>
    <?php include_once 'navbar.php'; ?>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Booking Successful!</h1>
            <p class="success-subtitle">Your tickets have been booked successfully. Enjoy the movie!</p>
        </div>

        <div class="booking-details-card">
            <h2 class="section-title">
                <i class="fas fa-ticket-alt"></i>
                Booking Details
            </h2>
            
            <div class="movie-info">
                <?php if (!empty($booking['poster_url'])): ?>
                    <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" 
                         alt="<?php echo htmlspecialchars($booking['title']); ?>" 
                         class="poster">
                <?php endif; ?>
                <div class="movie-text">
                    <h3 class="movie-title"><?php echo htmlspecialchars($booking['title']); ?></h3>
                    <div class="detail-row">
                        <i class="fas fa-tv"></i>
                        <span><?php echo htmlspecialchars($booking['screen_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, d F Y', strtotime($booking['show_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('h:i A', strtotime($booking['show_time'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-film"></i>
                        <span><?php echo htmlspecialchars($booking['movie_format']); ?></span>
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-hashtag"></i>
                        <span>Booking ID: #<?php echo $booking['booking_id']; ?></span>
                    </div>
                </div>
            </div>

            <h2 class="section-title">
                <i class="fas fa-couch"></i>
                Your Seats
            </h2>
            <div class="seats-grid">
                <?php foreach ($booked_seats as $seat): ?>
                    <div class="seat-badge">
                        <i class="fas fa-chair"></i>
                        <?php echo htmlspecialchars($seat['seat_number']); ?> (<?php echo htmlspecialchars($seat['seat_type']); ?>)
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-section">
                <span class="total-label">Total Amount</span>
                <span class="total-amount">Rs. <?php echo number_format($booking['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="btn-container">
            <a href="home.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Back to Home
            </a>
            <a href="movie_details.php?movie_id=<?php echo $booking['movie_id']; ?>" class="btn btn-primary">
                <i class="fas fa-film"></i>
                Book Another Ticket
            </a>
        </div>
    </div>
    <?php if (file_exists(__DIR__ . '/footer.php')) { include_once 'footer.php'; } ?>
</body>
</html>
<?php mysqli_close($conn); ?>
