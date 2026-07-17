<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

// Pagination & Filtering
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "m.title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($status === 'ACTIVE' || $status === 'INACTIVE') {
    $where_clauses[] = "m.status = ?";
    $params[] = $status;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM movies m WHERE $where_sql";
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Sorting
$order_sql = "m.created_at DESC"; // default newest
if ($sort === 'oldest') {
    $order_sql = "m.created_at ASC";
} elseif ($sort === 'highest_earnings') {
    $order_sql = "(SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movie_id = m.movie_id) DESC";
} elseif ($sort === 'lowest_earnings') {
    $order_sql = "(SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movie_id = m.movie_id) ASC";
} elseif ($sort === 'alphabetical') {
    $order_sql = "m.title ASC";
}

// Fetch movies with earnings statistics
$query = "
    SELECT 
        m.movie_id, m.title, m.status, m.poster_url,
        (SELECT COUNT(*) FROM shows WHERE movie_id = m.movie_id) as total_shows,
        (SELECT COUNT(*) FROM bookings b JOIN shows s ON b.show_id = s.show_id WHERE s.movie_id = m.movie_id AND b.booking_status = 'CONFIRMED') as total_bookings,
        (SELECT COALESCE(SUM(total_seats), 0) FROM bookings b JOIN shows s ON b.show_id = s.show_id WHERE s.movie_id = m.movie_id AND b.booking_status = 'CONFIRMED') as total_seats_sold,
        (SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movie_id = m.movie_id) as total_earnings
    FROM movies m
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $bind_params = $params;
    $bind_params[] = $offset;
    $bind_params[] = $records_per_page;
    $bind_types = $types . "ii";
    $stmt->bind_param($bind_types, ...$bind_params);
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$movies = $stmt->get_result();

// Build query string for pagination links
$qs_array = $_GET;
unset($qs_array['page']);
$qs = http_build_query($qs_array);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Earnings Summary - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/earnings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .filter-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            margin-bottom: 25px;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        .form-group select, .form-group input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .form-group select:focus, .form-group input:focus {
            border-color: #ff4d2d;
        }
        .form-actions {
            display: flex;
            gap: 10px;
        }
        .submit-btn, .reset-btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            display: inline-block;
        }
        .submit-btn {
            background: #ff4d2d;
            color: white;
        }
        .submit-btn:hover {
            background: #e63e1c;
        }
        .reset-btn {
            background: #f5f5f5;
            color: #555;
            border: 1px solid #ddd;
        }
        .reset-btn:hover {
            background: #ebebeb;
        }
    </style>
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

        <!-- Filter Form -->
        <div class="filter-card">
            <form method="GET" action="earnings.php" class="filter-form">
                <div class="form-group">
                    <label>Search Movie Title</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title...">
                </div>
                <div class="form-group">
                    <label>Movie Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="ACTIVE" <?= $status == 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                        <option value="INACTIVE" <?= $status == 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="highest_earnings" <?= $sort == 'highest_earnings' ? 'selected' : '' ?>>Highest Earnings</option>
                        <option value="lowest_earnings" <?= $sort == 'lowest_earnings' ? 'selected' : '' ?>>Lowest Earnings</option>
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Date Added (Newest)</option>
                        <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Date Added (Oldest)</option>
                        <option value="alphabetical" <?= $sort == 'alphabetical' ? 'selected' : '' ?>>Alphabetical</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-btn"><i class="fas fa-filter"></i> Filter</button>
                    <a href="earnings.php" class="reset-btn">Clear</a>
                </div>
            </form>
        </div>

        <div class="earnings-grid">
            <?php if ($movies->num_rows > 0): ?>
                <?php while ($movie = $movies->fetch_assoc()): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <?php
                            $poster_path = "../Assets/uploads/movie_posters/" . $movie['poster_url'];
                            if (!empty($movie['poster_url']) && file_exists($poster_path)): 
                            ?>
                                <img src="<?= htmlspecialchars("../Assets/uploads/movie_posters/" . $movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
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
                <a href="earnings.php?page=<?= $i; ?>&<?= $qs ?>" 
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
