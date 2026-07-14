<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

$count_res = $conn->query("SELECT COUNT(*) as total FROM movies");
$total_records = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch movies with earnings statistics
$query = "
    SELECT 
        m.movie_id, m.title, m.status, m.poster_url,
        (SELECT COUNT(*) FROM shows WHERE movie_id = m.movie_id) as total_shows,
        (SELECT COUNT(*) FROM bookings b JOIN shows s ON b.show_id = s.show_id WHERE s.movie_id = m.movie_id AND b.booking_status = 'CONFIRMED') as total_bookings,
        (SELECT COALESCE(SUM(total_seats), 0) FROM bookings b JOIN shows s ON b.show_id = s.show_id WHERE s.movie_id = m.movie_id AND b.booking_status = 'CONFIRMED') as total_seats_sold,
        (SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movie_id = m.movie_id) as total_earnings
    FROM movies m
    ORDER BY m.created_at DESC
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$movies = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Earnings Summary - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/earnings.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h1>Movie Earnings Summary</h1>
                    <p>Overview of revenue generated across all movies</p>
                </div>
            </div>
        </div>

        <div class="earnings-grid">
            <?php if ($movies->num_rows > 0): ?>
                <?php while ($movie = $movies->fetch_assoc()): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <?php
                            $poster_path = "../" . $movie['poster_url'];
                            if (!empty($movie['poster_url']) && file_exists($poster_path)): 
                            ?>
                                <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                            <?php else: ?>
                                <div class="poster-placeholder">
                                    <i class="fas fa-film"></i>
                                </div>
                            <?php endif; ?>
                            <span class="movie-status <?= strtolower($movie['status']) ?>"><?= htmlspecialchars($movie['status']) ?></span>
                        </div>
                        <div class="movie-details">
                            <h3><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="label">Shows</span>
                                    <span class="value"><?= $movie['total_shows'] ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label">Bookings</span>
                                    <span class="value"><?= $movie['total_bookings'] ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label">Seats Sold</span>
                                    <span class="value"><?= $movie['total_seats_sold'] ?></span>
                                </div>
                            </div>
                            <div class="earnings-box">
                                <span class="label">Total Earnings</span>
                                <span class="amount">Rs. <?= number_format($movie['total_earnings'], 2) ?></span>
                            </div>
                            <a href="movie_earnings.php?movie_id=<?= $movie['movie_id'] ?>" class="view-btn">
                                <i class="fas fa-eye"></i> View Earnings
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No movies available.</div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="earnings.php?page=<?= $i; ?>" 
                   style="padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s;
                   <?= $i == $page ? 'background: #ff4d2d; color: white;' : 'background: white; color: #555; border: 1px solid #ddd;'; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
