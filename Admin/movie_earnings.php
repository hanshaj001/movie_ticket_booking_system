<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

if (!$movie_id) {
    header("Location: earnings.php");
    exit();
}

// Fetch Movie Summary
$movie_query = "
    SELECT 
        m.title, m.status, m.poster_url,
        (SELECT COUNT(*) FROM shows WHERE movie_id = m.movie_id) as total_shows,
        (SELECT COUNT(*) FROM bookings b JOIN shows s ON b.show_id = s.show_id WHERE s.movie_id = m.movie_id AND b.booking_status = 'CONFIRMED') as total_bookings,
        (SELECT COALESCE(SUM(total_seats), 0) FROM bookings b JOIN shows s ON b.show_id = s.show_id WHERE s.movie_id = m.movie_id AND b.booking_status = 'CONFIRMED') as total_seats_sold,
        (SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movie_id = m.movie_id) as total_earnings
    FROM movies m
    WHERE m.movie_id = ?
";
$m_stmt = $conn->prepare($movie_query);
$m_stmt->bind_param("i", $movie_id);
$m_stmt->execute();
$movie_res = $m_stmt->get_result();

if ($movie_res->num_rows == 0) {
    header("Location: earnings.php");
    exit();
}
$movie = $movie_res->fetch_assoc();

// Fetch Show-wise Earnings
$shows_query = "
    SELECT 
        s.show_id, s.show_date, s.show_time, s.ticket_price, sc.screen_name,
        (SELECT COUNT(*) FROM bookings b WHERE b.show_id = s.show_id AND b.booking_status = 'CONFIRMED') as confirmed_bookings,
        (SELECT COALESCE(SUM(total_seats), 0) FROM bookings b WHERE b.show_id = s.show_id AND b.booking_status = 'CONFIRMED') as seats_sold,
        (SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE show_id = s.show_id) as show_earnings
    FROM shows s
    JOIN screens sc ON s.screen_id = sc.screen_id
    WHERE s.movie_id = ?
    ORDER BY s.show_date DESC, s.show_time DESC
";
$s_stmt = $conn->prepare($shows_query);
$s_stmt->bind_param("i", $movie_id);
$s_stmt->execute();
$shows = $s_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Movie Earnings - <?= htmlspecialchars($movie['title']) ?></title>
    <link rel="stylesheet" href="../Assets/css/Admin/movie_earnings.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header" style="display:flex; justify-content: space-between; align-items: flex-start;">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-film"></i>
                </div>
                <div>
                    <h1>Earnings Report</h1>
                    <p>Detailed performance for: <strong><?= htmlspecialchars($movie['title']) ?></strong></p>
                </div>
            </div>
            <a href="earnings.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Earnings</a>
        </div>

        <!-- Movie Summary Card -->
        <div class="summary-card">
            <div class="poster-container">
                <?php
                $poster_path = "../Assets/uploads/movie_posters/" . $movie['poster_url'];
                if (!empty($movie['poster_url']) && file_exists($poster_path)): 
                ?>
                    <img src="<?= htmlspecialchars("../Assets/uploads/movie_posters/" . $movie['poster_url']) ?>" alt="Poster">
                <?php else: ?>
                    <div class="poster-placeholder">
                        <i class="fas fa-film"></i>
                    </div>
                <?php endif; ?>
                <span class="movie-status <?= strtolower($movie['status']) ?>"><?= htmlspecialchars($movie['status']) ?></span>
            </div>
            <div class="summary-details">
                <h2><?= htmlspecialchars($movie['title']) ?></h2>
                <div class="stats-row">
                    <div class="stat-box">
                        <span class="label">Total Shows</span>
                        <span class="value"><?= $movie['total_shows'] ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="label">Confirmed Bookings</span>
                        <span class="value"><?= $movie['total_bookings'] ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="label">Seats Sold</span>
                        <span class="value"><?= $movie['total_seats_sold'] ?></span>
                    </div>
                    <div class="stat-box highlight">
                        <span class="label">Total Earnings</span>
                        <span class="value">Rs. <?= number_format($movie['total_earnings'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shows List -->
        <div class="shows-header">
            <h3><i class="fas fa-list"></i> Show-wise Earnings</h3>
        </div>

        <div class="shows-list">
            <?php if($shows->num_rows > 0): ?>
                <?php 
                $show_counter = 1;
                while($sh = $shows->fetch_assoc()): 
                ?>
                    <div class="show-card">
                        <div class="show-info">
                            <div class="show-date">
                                <span class="show-number">Show <?= $show_counter++ ?></span>
                                <i class="fas fa-calendar-day" style="margin-left: 8px;"></i> 
                                <?= date("d M Y", strtotime($sh['show_date'])) ?> | 
                                <?= date("h:i A", strtotime($sh['show_time'])) ?>
                            </div>
                            <div class="show-meta">
                                <span><i class="fas fa-tv"></i> <?= htmlspecialchars($sh['screen_name']) ?></span>
                                <span><i class="fas fa-ticket-alt"></i> Rs. <?= number_format($sh['ticket_price'], 2) ?></span>
                            </div>
                        </div>
                        <div class="show-stats">
                            <div class="s-stat">
                                <label>Confirmed Bookings</label>
                                <strong><?= $sh['confirmed_bookings'] ?></strong>
                            </div>
                            <div class="s-stat">
                                <label>Seats Sold</label>
                                <strong><?= $sh['seats_sold'] ?></strong>
                            </div>
                            <div class="s-stat highlight-earn">
                                <label>Total Earnings</label>
                                <strong>Rs. <?= number_format($sh['show_earnings'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No shows scheduled for this movie.</div>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>
