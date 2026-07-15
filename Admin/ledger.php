<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

// Filters
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : '';
$show_id = isset($_GET['show_id']) ? (int)$_GET['show_id'] : '';
$transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sort = isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'asc' : 'desc';

// Pagination
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($movie_id) {
    $where_clauses[] = "l.movie_id = ?";
    $params[] = $movie_id;
    $types .= "i";
}
if ($show_id) {
    $where_clauses[] = "l.show_id = ?";
    $params[] = $show_id;
    $types .= "i";
}
if ($transaction_type == 'BOOKING' || $transaction_type == 'CANCELLATION') {
    $where_clauses[] = "l.transaction_type = ?";
    $params[] = $transaction_type;
    $types .= "s";
}
if ($date_from) {
    $where_clauses[] = "DATE(l.transaction_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to) {
    $where_clauses[] = "DATE(l.transaction_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Count Query
$count_query = "SELECT COUNT(*) as total FROM ledger l WHERE $where_sql";
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main Query without subquery
$order_sql = $sort == 'asc' ? 'l.transaction_date ASC, l.ledger_id ASC' : 'l.transaction_date DESC, l.ledger_id DESC';

$query = "
    SELECT l.*, 
           b.booking_id as bk_display_id,
           m.title as movie_name,
           s.show_date, s.show_time, 
           sc.screen_name,
           u.full_name as customer_name
    FROM ledger l
    JOIN bookings b ON l.booking_id = b.booking_id
    JOIN movies m ON l.movie_id = m.movie_id
    JOIN shows s ON l.show_id = s.show_id
    JOIN screens sc ON s.screen_id = sc.screen_id
    JOIN users u ON b.user_id = u.user_id
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
$ledger_records = $stmt->get_result();

$records = [];
while ($row = $ledger_records->fetch_assoc()) {
    $records[] = $row;
}

// Calculate running balance precisely in PHP
if (count($records) > 0) {
    $first_row = $records[0];
    
    // Get the absolute balance up to the first displayed row
    $stmt_bal = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM ledger WHERE transaction_date < ? OR (transaction_date = ? AND ledger_id <= ?)");
    $stmt_bal->bind_param("ssi", $first_row['transaction_date'], $first_row['transaction_date'], $first_row['ledger_id']);
    $stmt_bal->execute();
    $current_balance = $stmt_bal->get_result()->fetch_assoc()['total'];

    foreach ($records as $index => &$row) {
        if ($sort == 'asc' && $index > 0) {
            $current_balance += $row['amount'];
        }
        
        $row['running_balance'] = $current_balance;
        
        if ($sort == 'desc') {
            $current_balance -= $row['amount'];
        }
    }
}

// Fetch movies for filter dropdown
$movies = $conn->query("SELECT movie_id, title FROM movies ORDER BY title ASC");
// Fetch shows for filter dropdown (can be refined via JS based on selected movie)
$shows = $conn->query("
    SELECT s.show_id, m.title, s.show_date, s.show_time 
    FROM shows s 
    JOIN movies m ON s.movie_id = m.movie_id 
    ORDER BY s.show_date DESC, s.show_time DESC
");

// Build query string for pagination links to preserve filters
$qs_array = $_GET;
unset($qs_array['page']);
$qs = http_build_query($qs_array);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ledger - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/ledger.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h1>Financial Ledger</h1>
                    <p>Track all financial transactions, bookings, and cancellations.</p>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="filter-card">
            <form method="GET" action="ledger.php" class="filter-form">
                <div class="form-group">
                    <label>Movie</label>
                    <select name="movie_id" id="filter_movie">
                        <option value="">All Movies</option>
                        <?php while($m = $movies->fetch_assoc()): ?>
                            <option value="<?= $m['movie_id'] ?>" <?= $movie_id == $m['movie_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Show</label>
                    <select name="show_id" id="filter_show">
                        <option value="">All Shows</option>
                        <?php while($sh = $shows->fetch_assoc()): ?>
                            <option value="<?= $sh['show_id'] ?>" <?= $show_id == $sh['show_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sh['title']) ?> - <?= date('d M', strtotime($sh['show_date'])) ?> <?= date('h:i A', strtotime($sh['show_time'])) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="transaction_type">
                        <option value="">All Types</option>
                        <option value="BOOKING" <?= $transaction_type == 'BOOKING' ? 'selected' : '' ?>>Booking</option>
                        <option value="CANCELLATION" <?= $transaction_type == 'CANCELLATION' ? 'selected' : '' ?>>Cancellation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="form-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="desc" <?= $sort == 'desc' ? 'selected' : '' ?>>Latest First</option>
                        <option value="asc" <?= $sort == 'asc' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-btn"><i class="fas fa-filter"></i> Filter</button>
                    <a href="ledger.php" class="reset-btn">Clear</a>
                </div>
            </form>
        </div>

        <!-- Ledger Table -->
        <div class="table-container" style="margin-top: 20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>ID</th>
                        <th>Movie</th>
                        <th>Show Info</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td class="nowrap"><?= date("Y-m-d H:i", strtotime($row['transaction_date'])) ?></td>
                                <td>BK<?= str_pad($row['bk_display_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><strong><?= htmlspecialchars($row['movie_name']) ?></strong></td>
                                <td class="meta-info">
                                    <?= date("d M Y", strtotime($row['show_date'])) ?> <?= date("H:i", strtotime($row['show_time'])) ?> (<?= htmlspecialchars($row['screen_name']) ?>)
                                </td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td>
                                    <span class="type-badge <?= strtolower($row['transaction_type']) ?>">
                                        <?= htmlspecialchars($row['transaction_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['amount'] >= 0): ?>
                                        <span class="amt-positive">+ Rs. <?= number_format($row['amount'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="amt-negative">- Rs. <?= number_format(abs($row['amount']), 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="balance-cell">
                                    Rs. <?= number_format($row['running_balance'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">No ledger transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="ledger.php?page=<?= $i; ?>&<?= $qs ?>" 
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
