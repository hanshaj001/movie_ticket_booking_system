<?php
// Initialize system secure authentication tracking session
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// if (!isset($_SESSION['user_id'])) {
//     header("Location: home.php");
//     exit();
// }

// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CUSTOMER') {
//     header("Location: home.php");
//     exit();
// }

require_once '../Includes/db_conn.php';

date_default_timezone_set('Asia/Kathmandu');
$current_datetime = date('Y-m-d H:i:s');

$user_id = 2; // Demo user

// Clean expired sessions
$cleanup = "UPDATE booking_sessions SET session_status = 'EXPIRED' WHERE session_status = 'ACTIVE' AND expiry_time < NOW()";
mysqli_query($conn, $cleanup);

$cleanup_locks = "DELETE sl FROM seat_locks sl JOIN booking_sessions bs ON sl.session_id = bs.session_id WHERE bs.session_status = 'EXPIRED'";
mysqli_query($conn, $cleanup_locks);

$update_seats = "UPDATE show_seats ss 
                 JOIN seat_locks sl ON ss.show_seat_id = sl.show_seat_id 
                 JOIN booking_sessions bs ON sl.session_id = bs.session_id 
                 SET ss.seat_status = 'AVAILABLE' 
                 WHERE bs.session_status = 'EXPIRED' AND ss.seat_status = 'LOCKED'";
mysqli_query($conn, $update_seats);

// Handle AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_seats' && isset($_GET['show_id'])) {
        $show_id = intval($_GET['show_id']);
        
        // Get current user's active session locked seats
        $my_locked_seats = [];
        $session_query = "SELECT * FROM booking_sessions WHERE user_id = $user_id AND show_id = $show_id AND session_status = 'ACTIVE' AND expiry_time > NOW()";
        $session_result = mysqli_query($conn, $session_query);
        if (mysqli_num_rows($session_result) > 0) {
            $session = mysqli_fetch_assoc($session_result);
            $locks_query = "SELECT show_seat_id FROM seat_locks WHERE session_id = {$session['session_id']}";
            $locks_result = mysqli_query($conn, $locks_query);
            while ($lock = mysqli_fetch_assoc($locks_result)) {
                $my_locked_seats[] = $lock['show_seat_id'];
            }
        }
        
        $query = "SELECT ss.*, s.seat_number, s.seat_type, s.row_group 
                 FROM show_seats ss 
                 JOIN seats s ON ss.seat_id = s.seat_id 
                 WHERE ss.show_id = $show_id 
                 ORDER BY s.row_group, s.seat_number";
        $result = mysqli_query($conn, $query);
        $seats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['is_locked_by_me'] = in_array($row['show_seat_id'], $my_locked_seats);
            $seats[] = $row;
        }
        echo json_encode(['seats' => $seats, 'my_locked_seats' => $my_locked_seats]);
        exit;
    }

    if ($action === 'toggle_seat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $show_seat_id = intval($_POST['show_seat_id']);
        $show_id = intval($_POST['show_id']);
        $is_selected = $_POST['selected'] === '1';

        // Get or create session
        $session_query = "SELECT * FROM booking_sessions WHERE user_id = $user_id AND show_id = $show_id AND session_status = 'ACTIVE' AND expiry_time > NOW()";
        $session_result = mysqli_query($conn, $session_query);
        $session = null;

        if (mysqli_num_rows($session_result) > 0) {
            $session = mysqli_fetch_assoc($session_result);
        } else {
            $expiry_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $insert_session = "INSERT INTO booking_sessions (user_id, show_id, expiry_time, session_status) VALUES ($user_id, $show_id, '$expiry_time', 'ACTIVE')";
            mysqli_query($conn, $insert_session);
            $session_id = mysqli_insert_id($conn); // Fixed! Use property, not method
            $session = ['session_id' => $session_id, 'expiry_time' => $expiry_time];
        }

        $session_id = $session['session_id'];

        if ($is_selected) {
            // Lock seat
            mysqli_begin_transaction($conn);
            try {
                $check = "SELECT seat_status FROM show_seats WHERE show_seat_id = $show_seat_id FOR UPDATE";
                $check_result = mysqli_query($conn, $check);
                $seat = mysqli_fetch_assoc($check_result);

                if (!$seat) {
                    throw new Exception("Seat not found");
                }
                if ($seat['seat_status'] !== 'AVAILABLE') {
                    throw new Exception("Seat is no longer available");
                }

                mysqli_query($conn, "UPDATE show_seats SET seat_status = 'LOCKED' WHERE show_seat_id = $show_seat_id");
                mysqli_query($conn, "INSERT INTO seat_locks (session_id, show_seat_id, expiry_time) VALUES ($session_id, $show_seat_id, '{$session['expiry_time']}')");

                mysqli_commit($conn);
                echo json_encode(['success' => true, 'expiry_time' => $session['expiry_time']]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            // Unlock seat
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "DELETE FROM seat_locks WHERE session_id = $session_id AND show_seat_id = $show_seat_id");
                mysqli_query($conn, "UPDATE show_seats SET seat_status = 'AVAILABLE' WHERE show_seat_id = $show_seat_id");
                mysqli_commit($conn);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
        exit;
    }

    if ($action === 'cancel_session' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $show_id = intval($_POST['show_id']);
        $session_query = "SELECT * FROM booking_sessions WHERE user_id = $user_id AND show_id = $show_id AND session_status = 'ACTIVE'";
        $session_result = mysqli_query($conn, $session_query);

        if (mysqli_num_rows($session_result) > 0) {
            $session = mysqli_fetch_assoc($session_result);
            $session_id = $session['session_id'];
            mysqli_query($conn, "UPDATE show_seats ss 
                                JOIN seat_locks sl ON ss.show_seat_id = sl.show_seat_id 
                                SET ss.seat_status = 'AVAILABLE' 
                                WHERE sl.session_id = $session_id");
            mysqli_query($conn, "DELETE FROM seat_locks WHERE session_id = $session_id");
            mysqli_query($conn, "UPDATE booking_sessions SET session_status = 'EXPIRED' WHERE session_id = $session_id");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'check_session' && isset($_GET['show_id'])) {
        $show_id = intval($_GET['show_id']);
        $session_query = "SELECT * FROM booking_sessions WHERE user_id = $user_id AND show_id = $show_id AND session_status = 'ACTIVE' AND expiry_time > NOW()";
        $session_result = mysqli_query($conn, $session_query);

        if (mysqli_num_rows($session_result) > 0) {
            $session = mysqli_fetch_assoc($session_result);
            $locks_query = "SELECT show_seat_id FROM seat_locks WHERE session_id = {$session['session_id']}";
            $locks_result = mysqli_query($conn, $locks_query);
            $locked_seats = [];
            while ($row = mysqli_fetch_assoc($locks_result)) {
                $locked_seats[] = $row['show_seat_id'];
            }
            echo json_encode([
                'has_session' => true,
                'expiry_time' => $session['expiry_time'],
                'locked_seats' => $locked_seats
            ]);
        } else {
            echo json_encode(['has_session' => false]);
        }
        exit;
    }
}

// Regular page load
if (!isset($_GET['show_id']) || !is_numeric($_GET['show_id'])) {
    header("Location: home.php");
    exit();
}

$show_id = intval($_GET['show_id']);

$show_query = "SELECT s.*, scr.screen_name, m.title, m.poster_url, m.movie_format, m.genre, m.duration_minutes, m.language 
              FROM shows s 
              JOIN screens scr ON s.screen_id = scr.screen_id 
              JOIN movies m ON s.movie_id = m.movie_id 
              WHERE s.show_id = $show_id AND s.show_status = 'ACTIVE' AND m.status = 'ACTIVE'";
$show_result = mysqli_query($conn, $show_query);

if (mysqli_num_rows($show_result) === 0) {
    header("Location: home.php");
    exit();
}

$show = mysqli_fetch_assoc($show_result);

$show_datetime = $show['show_date'] . ' ' . $show['show_time'];
if (strtotime($show_datetime) < strtotime($current_datetime)) {
    header("Location: home.php?error=show_started");
    exit();
}

// Get my locked seats
$my_locked_seats = [];
$session_query = "SELECT * FROM booking_sessions WHERE user_id = $user_id AND show_id = $show_id AND session_status = 'ACTIVE' AND expiry_time > NOW()";
$session_result = mysqli_query($conn, $session_query);
if (mysqli_num_rows($session_result) > 0) {
    $session = mysqli_fetch_assoc($session_result);
    $locks_query = "SELECT show_seat_id FROM seat_locks WHERE session_id = {$session['session_id']}";
    $locks_result = mysqli_query($conn, $locks_query);
    while ($lock = mysqli_fetch_assoc($locks_result)) {
        $my_locked_seats[] = $lock['show_seat_id'];
    }
}

// Get seats
$seats_query = "SELECT ss.*, s.seat_number, s.seat_type, s.row_group 
               FROM show_seats ss 
               JOIN seats s ON ss.seat_id = s.seat_id 
               WHERE ss.show_id = $show_id 
               ORDER BY s.row_group, s.seat_number";
$seats_result = mysqli_query($conn, $seats_query);

$seats_by_group = [];
while ($seat = mysqli_fetch_assoc($seats_result)) {
    $group = $seat['row_group'];
    if (!isset($seats_by_group[$group])) {
        $seats_by_group[$group] = [];
    }
    $seat['is_locked_by_me'] = in_array($seat['show_seat_id'], $my_locked_seats);
    $seats_by_group[$group][] = $seat;
}

// Handle booking
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $selected_seats = isset($_POST['selected_seats']) ? json_decode($_POST['selected_seats'], true) : [];

    if (empty($selected_seats)) {
        $message = "Please select at least one seat!";
        $message_type = 'error';
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Verify session
            $session_query = "SELECT * FROM booking_sessions WHERE user_id = $user_id AND show_id = $show_id AND session_status = 'ACTIVE' AND expiry_time > NOW()";
            $session_result = mysqli_query($conn, $session_query);

            if (mysqli_num_rows($session_result) === 0) {
                throw new Exception("Booking session expired! Please select seats again.");
            }

            $session = mysqli_fetch_assoc($session_result);
            $session_id = $session['session_id'];

            // Verify all seats
            foreach ($selected_seats as $seat_id) {
                $check_lock = "SELECT * FROM seat_locks WHERE session_id = $session_id AND show_seat_id = $seat_id";
                $lock_result = mysqli_query($conn, $check_lock);
                if (mysqli_num_rows($lock_result) === 0) {
                    throw new Exception("One or more seats are no longer locked! Please try again.");
                }

                $check_seat = "SELECT seat_status FROM show_seats WHERE show_seat_id = $seat_id";
                $seat_result = mysqli_query($conn, $check_seat);
                $seat = mysqli_fetch_assoc($seat_result);
                if ($seat['seat_status'] !== 'LOCKED') {
                    throw new Exception("One or more seats are no longer available!");
                }
            }

            // Create booking
            $total_seats = count($selected_seats);
            $total_amount = $total_seats * $show['ticket_price'];
            $insert_booking = "INSERT INTO bookings (user_id, show_id, total_seats, total_amount, booking_status) VALUES ($user_id, $show_id, $total_seats, $total_amount, 'CONFIRMED')";
            mysqli_query($conn, $insert_booking);
            $booking_id = mysqli_insert_id($conn);

            // Insert details and update seats
            foreach ($selected_seats as $seat_id) {
                mysqli_query($conn, "INSERT INTO booking_details (booking_id, show_seat_id, ticket_price) VALUES ($booking_id, $seat_id, {$show['ticket_price']})");
                mysqli_query($conn, "UPDATE show_seats SET seat_status = 'SOLD' WHERE show_seat_id = $seat_id");
            }

            // Cleanup
            mysqli_query($conn, "DELETE FROM seat_locks WHERE session_id = $session_id");
            mysqli_query($conn, "UPDATE booking_sessions SET session_status = 'COMPLETED' WHERE session_id = $session_id");

            mysqli_commit($conn);
            header("Location: booking_success.php?booking_id=$booking_id");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - <?php echo htmlspecialchars($show['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../Assets/Customer/seat_selection.css">
</head>
<body class="seat-selection-body">
    <?php include_once 'navbar.php'; ?>

    <main class="seat-selection-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav">
            <a href="home.php" class="bc-link"><i class="fa-solid fa-house"></i> Home</a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <a href="movie_details.php?movie_id=<?php echo $show['movie_id']; ?>" class="bc-link"><?php echo htmlspecialchars($show['title']); ?></a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="bc-current">Select Seats</span>
        </nav>

        <!-- Movie Info -->
        <section class="movie-info-card">
            <div class="movie-info-content">
                <?php if (!empty($show['poster_url'])): ?>
                    <div class="poster-wrapper">
                        <img src="<?php echo htmlspecialchars($show['poster_url']); ?>" 
                             alt="<?php echo htmlspecialchars($show['title']); ?>" 
                             class="movie-poster">
                    </div>
                <?php endif; ?>
                <div class="info-wrapper">
                    <h1 class="movie-title"><?php echo htmlspecialchars($show['title']); ?></h1>
                    <div class="show-details">
                        <div class="detail-item">
                            <i class="fa-solid fa-layer-group"></i>
                            <span><?php echo htmlspecialchars($show['genre']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-language"></i>
                            <span><?php echo htmlspecialchars($show['language']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-clock"></i>
                            <span><?php echo intval($show['duration_minutes']); ?> mins</span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-tv"></i>
                            <span><?php echo htmlspecialchars($show['screen_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-calendar"></i>
                            <span><?php echo date('l, d F Y', strtotime($show['show_date'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span><?php echo date('h:i A', strtotime($show['show_time'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-film"></i>
                            <span><?php echo htmlspecialchars($show['movie_format']); ?></span>
                        </div>
                        <div class="detail-item price">
                            <i class="fa-solid fa-ticket"></i>
                            <span>Rs. <?php echo number_format($show['ticket_price'], 2); ?> per seat</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Timer & Notice (New Position!) -->
        <div class="timer-notice-wrapper">
            <div class="timer-card" id="timerCard" style="display: none;">
                <div class="timer-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="timer-content">
                    <span class="timer-label">Time Remaining</span>
                    <span class="timer-value" id="timerValue">05:00</span>
                </div>
            </div>
            <div class="notice-card">
                <i class="fa-solid fa-circle-info notice-icon"></i>
                <div class="notice-text">
                    <strong>Important:</strong> Seats remain reserved for only <strong>5 minutes</strong>. Please complete your booking before the timer expires.<br>
                    Tickets can only be canceled before <strong>30 minutes</strong> of showtime. Once booked, seats cannot be changed.
                </div>
            </div>
        </div>

        <!-- Seat Selection -->
        <section class="seat-selection-card">
            <div class="section-header">
                <h2 class="section-title"><i class="fa-solid fa-couch"></i> Select Your Seats</h2>
                <a href="movie_details.php?movie_id=<?php echo $show['movie_id']; ?>" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back To Movie
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php if ($message_type === 'error'): ?>
                        <i class="fa-solid fa-circle-exclamation"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-check"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="screen-wrapper">
                <div class="screen">
                    <span>SCREEN</span>
                </div>
            </div>

            <form method="POST" id="bookingForm">
                <input type="hidden" id="selectedSeatsInput" name="selected_seats" value="">
                <div class="seats-wrapper" id="seatsWrapper">
                    <?php foreach ($seats_by_group as $group => $seats): ?>
                        <div class="row-group">
                            <h3 class="group-title"><?php echo htmlspecialchars($group); ?></h3>
                            <div class="seats-row">
                                <?php foreach ($seats as $seat): 
                                    $seat_class = strtolower($seat['seat_type']);
                                    $is_disabled = false;
                                    if ($seat['is_locked_by_me']) {
                                        // My selected seat: show as selected
                                        $seat_class .= ' selected';
                                    } else if ($seat['seat_status'] === 'SOLD') {
                                        $seat_class .= ' sold';
                                        $is_disabled = true;
                                    } else if ($seat['seat_status'] === 'LOCKED') {
                                        // Locked by someone else
                                        $seat_class .= ' locked';
                                        $is_disabled = true;
                                    } else {
                                        $seat_class .= ' available';
                                    }
                                ?>
                                    <div class="seat-item">
                                        <input type="checkbox" 
                                               id="seat-<?php echo $seat['show_seat_id']; ?>" 
                                               class="seat-checkbox" 
                                               value="<?php echo $seat['show_seat_id']; ?>"
                                               data-seat-number="<?php echo htmlspecialchars($seat['seat_number']); ?>"
                                               data-seat-type="<?php echo htmlspecialchars($seat['seat_type']); ?>"
                                               data-price="<?php echo $show['ticket_price']; ?>"
                                               data-status="<?php echo htmlspecialchars($seat['seat_status']); ?>"
                                               data-is-locked-by-me="<?php echo $seat['is_locked_by_me'] ? 'true' : 'false'; ?>"
                                               <?php echo $is_disabled ? 'disabled' : ''; ?>
                                               <?php echo $seat['is_locked_by_me'] ? 'checked' : ''; ?>>
                                        <label for="seat-<?php echo $seat['show_seat_id']; ?>" 
                                               class="seat-label <?php echo $seat_class; ?>"
                                               data-show-seat-id="<?php echo $seat['show_seat_id']; ?>">
                                            <span class="seat-number"><?php echo htmlspecialchars($seat['seat_number']); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="legend-wrapper">
                    <div class="legend-item">
                        <div class="legend-seat available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat selected"></div>
                        <span>Selected</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat locked"></div>
                        <span>Locked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat sold"></div>
                        <span>Sold</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat vip available"></div>
                        <span>VIP</span>
                    </div>
                </div>
            </form>
        </section>

        <section class="summary-card">
            <h3 class="summary-title"><i class="fa-solid fa-receipt"></i> Booking Summary</h3>
            <div class="summary-content">
                <div class="summary-row">
                    <span class="summary-label">Selected Seats:</span>
                    <span class="summary-value" id="selectedSeats">None</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Seats:</span>
                    <span class="summary-value" id="seatCount">0</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Price Per Seat:</span>
                    <span class="summary-value" id="pricePerSeat">Rs. <?php echo number_format($show['ticket_price'], 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span class="summary-label">Total Amount:</span>
                    <span class="summary-value" id="totalAmount">Rs. 0.00</span>
                </div>
            </div>
            <div class="summary-actions">
                <button type="button" class="btn-cancel" id="cancelBtn">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
                <button type="submit" 
                        form="bookingForm" 
                        name="confirm_booking" 
                        class="btn-confirm-booking" 
                        id="confirmBtn"
                        disabled>
                    <i class="fa-solid fa-check-circle"></i> Confirm Booking
                </button>
            </div>
        </section>
    </main>

    <!-- Session Started Popup -->
    <div id="sessionPopup" class="session-popup">
        <i class="fas fa-check-circle"></i>
        <span>Booking session started! You have 5 minutes to complete your booking.</span>
    </div>

    <?php include_once 'footer.php'; ?>

    <script>
        const showId = <?php echo $show_id; ?>;
        const ticketPrice = <?php echo $show['ticket_price']; ?>;
    </script>
    <script src="../Assets/js/seat_selection.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
