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
    <style>
        :root {
            --accent: #ff4d2d;
            --accent-hover: #e63e1f;
            --accent-light: #fff4f1;
            --text-primary: #1a1a2e;
            --text-secondary: #4a4a6a;
            --text-muted: #9494a8;
            --border: #e8e8f0;
            --card-shadow: 0 10px 40px rgba(0,0,0,0.08);
            --card-shadow-hover: 0 15px 50px rgba(0,0,0,0.12);
            --radius: 24px;
            --radius-sm: 16px;
            --gradient-primary: linear-gradient(135deg, #ff4d2d 0%, #ff6b4a 100%);
            --bg-light: #f8f9fc;
            --success: #14a44d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .success-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            padding: 40px;
            text-align: center;
            margin-bottom: 24px;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #14a44d 0%, #22c55e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 24px rgba(20,164,77,0.3);
            animation: bounceIn 0.6s ease;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .success-icon i {
            font-size: 48px;
            color: white;
        }

        .success-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--text-primary) 0%, #3a3a5a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .success-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }

        .booking-details-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            padding: 32px;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent);
        }

        .movie-info {
            display: flex;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--border);
        }

        .poster {
            width: 120px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .movie-text {
            flex: 1;
            text-align: left;
        }

        .movie-title {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .detail-row i {
            color: var(--accent);
            width: 18px;
        }

        .seats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .seat-badge {
            background: var(--accent-light);
            color: var(--accent);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .seat-badge i {
            font-size: 16px;
        }

        .total-section {
            background: linear-gradient(135deg, #fff4f1 0%, #ffe8e3 100%);
            padding: 20px;
            border-radius: var(--radius-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .total-label {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .total-amount {
            font-size: 28px;
            font-weight: 900;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-container {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            border: none;
            box-shadow: 0 6px 20px rgba(255,77,45,0.35);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,77,45,0.45);
        }

        .btn-secondary {
            background: white;
            color: var(--text-secondary);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            color: var(--accent);
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        @media (max-width: 600px) {
            .success-title {
                font-size: 26px;
            }
            
            .movie-info {
                flex-direction: column;
                text-align: center;
            }
            
            .movie-text {
                text-align: center;
            }
            
            .detail-row {
                justify-content: center;
            }
        }
    </style>
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
    <?php include_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
