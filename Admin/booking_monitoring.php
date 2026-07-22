<?php
require_once '../Includes/db_conn.php'; 
include 'components/sidebar.php';

/* Authentication */
if(!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}

/* Search & Filters */
$search = $_GET['search'] ?? '';
$movie = $_GET['movie'] ?? '';
$date  = $_GET['date'] ?? '';

$where = " WHERE 1=1 ";

if($search != '') {
    $where .= " AND (
        b.booking_id LIKE '%$search%' OR
        u.full_name LIKE '%$search%' OR
        m.title LIKE '%$search%'
    )";
}

if($movie != '') {
    $where .= " AND m.movie_id='$movie'";
}

if($date != '') {
    $where .= " AND DATE(b.booking_time)='$date'";
}

/* Pagination */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // records per page
$offset = ($page - 1) * $limit;
$selected_date = $date;

/* Count Total Records */
$count_query = "
SELECT COUNT(DISTINCT b.booking_id) as total
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN shows sh ON b.show_id=sh.show_id
JOIN movies m ON sh.movie_id=m.movie_id
$where
";

$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

/* Booking Records (without joining details directly to avoid GROUP BY issues) */
$query = "
SELECT
    b.booking_id,
    u.full_name,
    m.title,
    CONCAT(sh.show_date,' ',sh.show_time) show_time,
    sh.show_date,
    sh.show_time AS raw_show_time,
    b.booking_status,
    b.booking_time
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN shows sh ON b.show_id=sh.show_id
JOIN movies m ON sh.movie_id=m.movie_id
$where
ORDER BY b.booking_time DESC
LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $query);
$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $booking_id = $row['booking_id'];
    
    // Fetch seat statuses and details for this booking
    $seats_query = "
        SELECT bd.show_seat_id, bd.seat_status, st.seat_number
        FROM booking_details bd
        JOIN show_seats ss ON bd.show_seat_id = ss.show_seat_id
        JOIN seats st ON ss.seat_id = st.seat_id
        WHERE bd.booking_id = $booking_id
        ORDER BY st.seat_number
    ";
    $seats_result = mysqli_query($conn, $seats_query);
    $seats = [];
    $confirmed_count = 0;
    while ($seat = mysqli_fetch_assoc($seats_result)) {
        $seats[] = $seat;
        if ($seat['seat_status'] === 'CONFIRMED') {
            $confirmed_count++;
        }
    }
    $row['seats'] = $seats;
    $row['confirmed_count'] = $confirmed_count;

    // Check if the show has already completed
    $show_datetime = $row['show_date'] . ' ' . $row['raw_show_time'];
    $show_ts = strtotime($show_datetime);
    $row['show_completed'] = ($show_ts <= time()) ? 1 : 0;

    $bookings[] = $row;
}

/* Movie Filter */
$movies = mysqli_query($conn,"
SELECT movie_id,title
FROM movies
WHERE status='ACTIVE'
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Monitoring</title>
    <link rel="stylesheet" href="../Assets/css/Admin/booking_monitoring.css?v=<?= time() ?>"/>
    <link rel="stylesheet" href="../Assets/css/Admin/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-ticket"></i>
            <div>
                <h1>Booking Monitoring</h1>
                <p>Track and manage customer reservations</p>
            </div>
        </div>
    </div>
    <form method="GET">
        <div class="filters">
            <input type="text" name="search" placeholder="Search booking..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="movie">
                <option value="">All movies</option>
                <?php mysqli_data_seek($movies, 0); ?>
                <?php while($m=mysqli_fetch_assoc($movies)){ ?>
                <option value="<?php echo $m['movie_id']; ?>" <?php if($movie==$m['movie_id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($m['title']); ?>
                </option>
                <?php } ?>
            </select>
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
            <button type="submit" class="search-btn">Search</button>
            <a href="booking_monitoring.php" class="reset-btn">Reset</a>
        </div>
    </form>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Movie</th>
                    <th>Show Time</th>
                    <th>Seats</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $row): ?>
                    <tr>
                        <td>BK<?php echo str_pad($row['booking_id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo $row['show_time']; ?></td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <?php foreach ($row['seats'] as $seat): ?>
                                    <?php if ($seat['seat_status'] === 'CONFIRMED'): ?>
                                        <span class="seat-status-tag tag-confirmed">
                                            ✓ <?php echo htmlspecialchars($seat['seat_number']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="seat-status-tag tag-cancelled" style="text-decoration: line-through; opacity: 0.7;">
                                            <?php echo htmlspecialchars($seat['seat_number']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $status = strtolower($row['booking_status']);
                            $display_status = str_replace('_', ' ', $row['booking_status']);
                            echo "<span class='badge $status'>$display_status</span>";
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button class="action-btn view-btn" onclick="viewBooking(<?php echo $row['booking_id']; ?>)"><i class="fa-solid fa-eye"></i> View</button>
                                <?php if ($row['confirmed_count'] > 0 && in_array($row['booking_status'], ['CONFIRMED', 'PARTIALLY_CANCELLED'])): ?>
                                    <?php if ($row['show_completed']): ?>
                                        <button class="action-btn cancel-btn" disabled style="opacity: 0.5; cursor: not-allowed;" title="Show has already completed"><i class="fa-solid fa-ban"></i> Cancel Seats</button>
                                    <?php else: ?>
                                        <button class="action-btn cancel-btn btn-cancel-seats-admin" data-booking-id="<?php echo $row['booking_id']; ?>"><i class="fa-solid fa-xmark"></i> Cancel Seats</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #888;">No bookings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?date=<?= $selected_date ?>&movie=<?= $movie ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="?date=<?= $selected_date ?>&movie=<?= $movie ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>" class="<?= ($page == $p) ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?date=<?= $selected_date ?>&movie=<?= $movie ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
        <?php endif; ?>
    </div>
</div>

<!-- Admin Cancellation Modal -->
<div class="modal-overlay" id="modalOverlay"></div>
<div class="cancel-modal" id="cancelModal">
    <div class="modal-header">
        <h2>Cancel Seats (Admin)</h2>
        <button class="modal-close" id="modalClose"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
        <form id="cancelForm">
            <input type="hidden" id="cancelBookingId" name="booking_id" value="">

            <div class="select-all-row">
                <input type="checkbox" id="selectAllSeats">
                <label for="selectAllSeats">Select All</label>
            </div>

            <!-- Seat groups per booking -->
            <?php foreach ($bookings as $row): ?>
                <?php if ($row['confirmed_count'] > 0 && in_array($row['booking_status'], ['CONFIRMED', 'PARTIALLY_CANCELLED'])): ?>
                    <div class="modal-seat-group" data-booking-id="<?php echo $row['booking_id']; ?>" style="display:none;">
                        <?php foreach ($row['seats'] as $seat): ?>
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

            <button type="submit" class="modal-submit-btn" id="cancelSubmitBtn" disabled>Cancel Selected Seats</button>
        </form>
    </div>
</div>

<script src="../Assets/js/Admin/booking_monitoring.js"></script>
<script>
function viewBooking(id) {
    window.location = 'booking_details.php?id=' + id;
}
</script>

</body>
</html>