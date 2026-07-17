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

/* Pagination setup */
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10; // 10 rows per page
$offset = ($page - 1) * $limit;

/* Count total bookings for user */
$count_query = "SELECT COUNT(*) as total FROM bookings WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

/* Fetch paginated bookings */
$query = "
    SELECT b.booking_id, b.booking_status, b.booking_time, b.total_seats, b.total_amount,
           s.show_date, s.show_time, s.show_id, s.movie_id,
           m.title, m.movie_format, m.poster_url, m.language
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE b.user_id = $user_id
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $query);
$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $booking_id = $row['booking_id'];

    // Fetch individual seat details with seat_status
    $seats_query = "
        SELECT bd.show_seat_id, bd.ticket_price, bd.seat_status,
               se.seat_number
        FROM booking_details bd
        JOIN show_seats ss ON bd.show_seat_id = ss.show_seat_id
        JOIN seats se ON ss.seat_id = se.seat_id
        WHERE bd.booking_id = $booking_id
        ORDER BY se.seat_number
    ";
    $seats_result = mysqli_query($conn, $seats_query);
    $seats = [];
    while ($seat = mysqli_fetch_assoc($seats_result)) {
        $seats[] = $seat;
    }
    $row['seats'] = $seats;

    // Build flat seat list for display
    $seat_numbers = array_map(function($s) { return $s['seat_number']; }, $seats);
    $row['seats_list'] = implode(', ', $seat_numbers);

    // Calculate if cancellation is allowed (> 30 min before show)
    $show_datetime = $row['show_date'] . ' ' . $row['show_time'];
    $show_ts = strtotime($show_datetime);
    $remaining_minutes = ($show_ts - time()) / 60;
    $row['can_cancel'] = ($remaining_minutes > 30) ? 1 : 0;

    // Count confirmed seats
    $confirmed_count = 0;
    foreach ($seats as $s) {
        if ($s['seat_status'] === 'CONFIRMED') $confirmed_count++;
    }
    $row['confirmed_count'] = $confirmed_count;

    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Movie Ticket Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../Assets/css/Customer/booking_history.css?v=<?= time() ?>">
</head>
<body>
    <?php include_once 'components/navbar.php'; ?>

    <div class="history-container">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav">
            <a href="home.php" class="bc-link"><i class="fa-solid fa-house"></i> Home</a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="bc-current">Booking History</span>
        </nav>

        <div class="history-header">
            <h1>Booking History</h1>
            <div class="history-actions">
                <form action="" method="GET">
                    <?php if (isset($_GET['page'])): ?>
                        <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']); ?>">
                    <?php endif; ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Important Information -->
        <div class="important-info-box">
            <h3><i class="fa-solid fa-circle-exclamation"></i> Important Information</h3>
            <ul>
                <li><i class="fa-solid fa-clock"></i> Cancellations can only be made up to 30 minutes before the scheduled show time.</li>
                <li><i class="fa-solid fa-ticket"></i> Please present your Booking ID at the counter to receive your physical tickets.</li>
                <li><i class="fa-solid fa-ban"></i> Outside food and beverages are strictly prohibited inside the cinema hall.</li>
            </ul>
        </div>

        <div class="history-card">
            <div class="history-table-wrapper">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Movie Name</th>
                            <th>Seats</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <?php
                                    $language = isset($booking['language']) && $booking['language'] ? $booking['language'] : 'English';
                                    $status = $booking['booking_status'];
                                    
                                    // Handle correct poster URL prefixing
                                    $poster_url = $booking['poster_url'];
                                    if (!empty($poster_url) && strpos($poster_url, 'http') !== 0 && strpos($poster_url, '../Assets/uploads/') === false) {
                                        $poster_url = '../Assets/uploads/movie_posters/' . ltrim($poster_url, '/');
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="movie-cell">
                                            <?php if (!empty($poster_url) && file_exists($poster_url)): ?>
                                                <img src="<?php echo htmlspecialchars($poster_url); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="movie-poster">
                                            <?php else: ?>
                                                <div class="movie-poster-fallback">
                                                    <i class="fas fa-film"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="movie-info">
                                                <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                                <span><?php echo htmlspecialchars($language); ?> | <?php echo htmlspecialchars($booking['movie_format']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seats-cell">
                                            <?php foreach ($booking['seats'] as $seat): ?>
                                                <?php if ($seat['seat_status'] === 'CONFIRMED'): ?>
                                                    <span class="seat-tag confirmed">
                                                        <i class="fa-solid fa-check"></i>
                                                        <?php echo htmlspecialchars($seat['seat_number']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="seat-tag cancelled">
                                                        <?php echo htmlspecialchars($seat['seat_number']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span class="date"><?php echo date('d M Y', strtotime($booking['show_date'])); ?></span>
                                            <span class="time"><?php echo date('h:i A', strtotime($booking['show_time'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($status === 'CONFIRMED'): ?>
                                            <div class="status-badge status-confirmed">
                                                <i class="fas fa-check-circle status-icon"></i>
                                                Confirmed
                                            </div>
                                        <?php elseif ($status === 'PARTIALLY_CANCELLED'): ?>
                                            <div class="status-badge status-partially-cancelled">
                                                <i class="fas fa-exclamation-circle status-icon"></i>
                                                Partially Cancelled
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge status-cancelled">
                                                <i class="fas fa-times-circle status-icon"></i>
                                                Cancelled
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['confirmed_count'] > 0 && in_array($status, ['CONFIRMED', 'PARTIALLY_CANCELLED'])): ?>
                                            <button class="btn-cancel-seats"
                                                    data-booking-id="<?php echo $booking['booking_id']; ?>"
                                                    data-can-cancel="<?php echo $booking['can_cancel']; ?>">
                                                <i class="fa-solid fa-xmark"></i>
                                                Cancel Seats
                                            </button>
                                        <?php elseif ($status === 'CANCELLED'): ?>
                                            <span style="color: var(--text-muted); font-size: 0.85rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-ticket"></i></div>
                                        <h3>No Bookings Yet</h3>
                                        <p>Your booking history will appear here once you make a reservation.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Links -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?sort=<?php echo $sort; ?>&page=<?php echo max(1, $page - 1); ?>" class="<?php echo ($page <= 1) ? 'disabled' : ''; ?>">Previous</a>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <a href="?sort=<?php echo $sort; ?>&page=<?php echo $p; ?>" class="<?php echo ($page == $p) ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
                <a href="?sort=<?php echo $sort; ?>&page=<?php echo min($total_pages, $page + 1); ?>" class="<?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">Next</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancel Seats Modal -->
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="cancel-modal" id="cancelModal">
        <div class="modal-header">
            <h2>Select Seats to Cancel</h2>
            <button class="modal-close" id="modalClose"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="cancelForm">
                <input type="hidden" id="cancelBookingId" name="booking_id" value="">

                <div id="cancelTimeWarning" class="cancel-time-warning">
                    <i class="fa-solid fa-clock"></i>
                    Cancellation is allowed only more than 30 minutes before the scheduled show.
                </div>

                <div class="select-all-row">
                    <input type="checkbox" id="selectAllSeats">
                    <label for="selectAllSeats">Select All</label>
                </div>

                <!-- Seat groups per booking (only one visible at a time) -->
                <?php foreach ($bookings as $booking): ?>
                    <?php if ($booking['confirmed_count'] > 0 && in_array($booking['booking_status'], ['CONFIRMED', 'PARTIALLY_CANCELLED'])): ?>
                        <div class="modal-seat-group" data-booking-id="<?php echo $booking['booking_id']; ?>" style="display:none;">
                            <?php foreach ($booking['seats'] as $seat): ?>
                                <?php if ($seat['seat_status'] === 'CONFIRMED'): ?>
                                    <div class="modal-seat-row">
                                        <label class="seat-label">
                                            <input type="checkbox" class="seat-checkbox"
                                                   name="seat_ids[]"
                                                   value="<?php echo $seat['show_seat_id']; ?>"
                                                   data-seat-name="<?php echo htmlspecialchars($seat['seat_number']); ?>">
                                            <span class="seat-name"><?php echo htmlspecialchars($seat['seat_number']); ?></span>
                                        </label>
                                        <span class="seat-status-tag tag-confirmed">Confirmed</span>
                                    </div>
                                <?php else: ?>
                                    <div class="modal-seat-row disabled-seat">
                                        <label class="seat-label">
                                            <input type="checkbox" class="seat-checkbox" disabled>
                                            <span class="seat-name" style="text-decoration: line-through; opacity: 0.5;"><?php echo htmlspecialchars($seat['seat_number']); ?></span>
                                        </label>
                                        <span class="seat-status-tag tag-cancelled">Cancelled</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <button type="submit" class="modal-submit-btn btn-disabled" id="cancelSubmitBtn" disabled>Cancel Selected Seats</button>
            </form>
        </div>
    </div>

    <?php if (file_exists(__DIR__ . '/components/footer.php')) { include_once 'components/footer.php'; } ?>

    <script src="../Assets/js/Customer/booking_history.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
