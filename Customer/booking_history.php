<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: customerlogin.php");
    exit();
}

require_once '../Includes/db_conn.php';
$user_id = intval($_SESSION['user_id']);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$order_by = "b.booking_time DESC";
if ($sort === 'oldest') {
    $order_by = "b.booking_time ASC";
}

$query = "
    SELECT b.booking_id, b.booking_status, b.booking_time, s.show_date, s.show_time, 
           m.title, m.movie_format, m.poster_url, m.language
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE b.user_id = $user_id
    ORDER BY $order_by
";

$result = mysqli_query($conn, $query);
$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $booking_id = $row['booking_id'];
    
    $seats_query = "
        SELECT s.seat_number
        FROM booking_details bd
        JOIN show_seats ss ON bd.show_seat_id = ss.show_seat_id
        JOIN seats s ON ss.seat_id = s.seat_id
        WHERE bd.booking_id = $booking_id
        ORDER BY s.seat_number
    ";
    $seats_result = mysqli_query($conn, $seats_query);
    $seats = [];
    while ($seat = mysqli_fetch_assoc($seats_result)) {
        $seats[] = $seat['seat_number'];
    }
    $row['seats_list'] = implode(', ', $seats);
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg-light: #f8f9fa;
            --text-main: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --table-header: #343a40;
            
            --success-bg: #e6f4ea;
            --success-text: #137333;
            
            --danger-bg: #fce8e6;
            --danger-text: #c5221f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
        }

        .history-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .history-header {
            margin-bottom: 24px;
        }

        .history-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .history-header p {
            font-size: 15px;
            color: var(--text-muted);
        }

        .history-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            padding: 24px;
        }

        .history-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .sort-select {
            padding: 8px 32px 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--text-main);
            background-color: #fff;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            outline: none;
        }

        .history-table-wrapper {
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .history-table th {
            background-color: var(--table-header);
            color: #ffffff;
            text-align: left;
            padding: 16px;
            font-weight: 500;
            font-size: 14px;
        }

        .history-table th:first-child {
            border-top-left-radius: 8px;
        }

        .history-table th:last-child {
            border-top-right-radius: 8px;
        }

        .history-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .movie-cell {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .movie-poster {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .movie-info h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .movie-info p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .seats-cell {
            font-size: 14px;
            color: var(--text-main);
            max-width: 200px;
            line-height: 1.5;
        }

        .date-cell .date {
            display: block;
            font-size: 14px;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .date-cell .time {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-confirmed {
            background-color: var(--success-bg);
            color: var(--success-text);
        }

        .status-failed {
            background-color: var(--danger-bg);
            color: var(--danger-text);
        }

        .status-icon {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include_once 'navbar.php'; ?>
    
    <div class="history-container">
        <div class="history-header">
            <h1>Booking History</h1>
            <p>View all your past bookings and their status.</p>
        </div>
        
        <div class="history-card">
            <div class="history-actions">
                <form action="" method="GET">
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </form>
            </div>
            
            <div class="history-table-wrapper">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Movie Name</th>
                            <th>Seats</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <?php
                                    // Make sure language exists, or set a default if column missing
                                    $language = isset($booking['language']) && $booking['language'] ? $booking['language'] : 'English';
                                ?>
                                <tr>
                                    <td>
                                        <div class="movie-cell">
                                            <?php if (!empty($booking['poster_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="movie-poster">
                                            <?php else: ?>
                                                <div class="movie-poster" style="background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-film" style="color: #adb5bd; font-size: 24px;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="movie-info">
                                                <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                                <p><?php echo htmlspecialchars($language); ?> | <?php echo htmlspecialchars($booking['movie_format']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seats-cell">
                                            <?php echo htmlspecialchars($booking['seats_list']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span class="date"><?php echo date('d M Y', strtotime($booking['show_date'])); ?></span>
                                            <span class="time"><?php echo date('h:i A', strtotime($booking['show_time'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($booking['booking_status'] === 'CONFIRMED'): ?>
                                            <div class="status-badge status-confirmed">
                                                <i class="fas fa-check-circle status-icon"></i>
                                                Confirmed
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge status-failed">
                                                <i class="fas fa-times-circle status-icon"></i>
                                                Failed
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    You have no past bookings.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php if (file_exists(__DIR__ . '/footer.php')) { include_once 'footer.php'; } ?>
</body>
</html>
<?php mysqli_close($conn); ?>
