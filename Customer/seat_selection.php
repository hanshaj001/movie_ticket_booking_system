<?php
// Securely initialize or resume the session context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce authentication context boundaries
// if (!isset($_SESSION['user_id'])) {
//     header("Location: home.php");
//     exit();
// }

// Enforce role-based structural access boundaries
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CUSTOMER') {
//     header("Location: home.php");
//     exit();
// }

require_once '../Includes/db_conn.php';


// Configure contextual operational time boundaries
date_default_timezone_set('Asia/Kathmandu');
$current_datetime = date('Y-m-d H:i:s');
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Secure CSRF Token Layer
if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['_csrf_token'];

// Automated garbage collection: Clean expired sessions using correct local PHP timezone variable
mysqli_query($conn, "
    UPDATE show_seats ss
    INNER JOIN seat_locks sl ON ss.show_seat_id = sl.show_seat_id
    INNER JOIN booking_sessions bs ON sl.session_id = bs.session_id
    SET ss.seat_status = 'AVAILABLE'
    WHERE bs.expiry_time <= '$current_datetime' AND bs.session_status = 'ACTIVE'
");

mysqli_query($conn, "
    DELETE sl FROM seat_locks sl
    INNER JOIN booking_sessions bs ON sl.session_id = bs.session_id
    WHERE bs.expiry_time <= '$current_datetime' AND bs.session_status = 'ACTIVE'
");

mysqli_query($conn, "
    UPDATE booking_sessions
    SET session_status='EXPIRED'
    WHERE expiry_time <= '$current_datetime' AND session_status='ACTIVE'
");

// Handle AJAX Endpoints
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // Action: Get Seats (Periodic background poller endpoint)
    if ($action === 'get_seats' && isset($_GET['show_id'])) {
        $show_id = intval($_GET['show_id']);
        $my_locked_seats = [];
        
        if ($user_id === 0) {
            // User not logged in, just return seats without locked_by_me data
        } else {
            $session_stmt = mysqli_prepare($conn, "SELECT session_id FROM booking_sessions WHERE user_id = ? AND show_id = ? AND session_status = 'ACTIVE' AND expiry_time > ?");
        mysqli_stmt_bind_param($session_stmt, "iis", $user_id, $show_id, $current_datetime);
        mysqli_stmt_execute($session_stmt);
        $session_res = mysqli_stmt_get_result($session_stmt);
        
        if ($session_row = mysqli_fetch_assoc($session_res)) {
            $lock_stmt = mysqli_prepare($conn, "SELECT show_seat_id FROM seat_locks WHERE session_id = ?");
            mysqli_stmt_bind_param($lock_stmt, "i", $session_row['session_id']);
            mysqli_stmt_execute($lock_stmt);
            $lock_res = mysqli_stmt_get_result($lock_stmt);
            while ($lock = mysqli_fetch_assoc($lock_res)) {
                $my_locked_seats[] = $lock['show_seat_id'];
            }
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

    // Action: Toggle Seat (Reserve / Unreserve)
    if ($action === 'toggle_seat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Fallback CSRF validation check accepting both standard form post payloads and HTTP Headers
        $headers = getallheaders();
        $received_token = $headers['X-CSRF-Token'] ?? ($_POST['csrf_token'] ?? '');
        
        if (empty($received_token) || !hash_equals($csrf_token, $received_token)) {
            echo json_encode(['error' => 'Invalid security token verification context.']);
            exit;
        }
        
        if ($user_id === 0) {
            echo json_encode(['error' => 'auth_required']);
            exit;
        }

        $show_seat_id = intval($_POST['show_seat_id']);
        $show_id = intval($_POST['show_id']);
        $is_selected = $_POST['selected'] === '1';

        mysqli_begin_transaction($conn);
        try {
            // Check session using write lock to prevent duplicates
            $session_stmt = mysqli_prepare($conn, "SELECT * FROM booking_sessions WHERE user_id = ? AND show_id = ? AND session_status = 'ACTIVE' AND expiry_time > ? FOR UPDATE");
            mysqli_stmt_bind_param($session_stmt, "iis", $user_id, $show_id, $current_datetime);
            mysqli_stmt_execute($session_stmt);
            $session_res = mysqli_stmt_get_result($session_stmt);
            
            if (mysqli_num_rows($session_res) > 0) {
                $session = mysqli_fetch_assoc($session_res);
            } else {
                $expiry_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                $ins_stmt = mysqli_prepare($conn, "INSERT INTO booking_sessions (user_id, show_id, expiry_time, session_status) VALUES (?, ?, ?, 'ACTIVE')");
                mysqli_stmt_bind_param($ins_stmt, "iis", $user_id, $show_id, $expiry_time);
                mysqli_stmt_execute($ins_stmt);
                $session = ['session_id' => mysqli_insert_id($conn), 'expiry_time' => $expiry_time];
            }

            $session_id = $session['session_id'];

            if ($is_selected) {
                $seat_stmt = mysqli_prepare($conn, "SELECT seat_status FROM show_seats WHERE show_seat_id = ? FOR UPDATE");
                mysqli_stmt_bind_param($seat_stmt, "i", $show_seat_id);
                mysqli_stmt_execute($seat_stmt);
                $seat_res = mysqli_stmt_get_result($seat_stmt);
                $seat = mysqli_fetch_assoc($seat_res);

                if (!$seat || $seat['seat_status'] !== 'AVAILABLE') {
                    throw new Exception("Seat is no longer available");
                }

                $up_stmt = mysqli_prepare($conn, "UPDATE show_seats SET seat_status = 'LOCKED' WHERE show_seat_id = ?");
                mysqli_stmt_bind_param($up_stmt, "i", $show_seat_id);
                mysqli_stmt_execute($up_stmt);

                $lock_ins = mysqli_prepare($conn, "INSERT INTO seat_locks (session_id, show_seat_id, expiry_time) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($lock_ins, "iis", $session_id, $show_seat_id, $session['expiry_time']);
                mysqli_stmt_execute($lock_ins);

                mysqli_commit($conn);
                echo json_encode(['success' => true, 'expiry_time' => $session['expiry_time']]);
            } else {
                $del_stmt = mysqli_prepare($conn, "DELETE FROM seat_locks WHERE session_id = ? AND show_seat_id = ?");
                mysqli_stmt_bind_param($del_stmt, "ii", $session_id, $show_seat_id);
                mysqli_stmt_execute($del_stmt);

                $up_stmt = mysqli_prepare($conn, "UPDATE show_seats SET seat_status = 'AVAILABLE' WHERE show_seat_id = ?");
                mysqli_stmt_bind_param($up_stmt, "i", $show_seat_id);
                mysqli_stmt_execute($up_stmt);

                mysqli_commit($conn);
                echo json_encode(['success' => true]);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Action: Cancel Session
    if ($action === 'cancel_session' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $headers = getallheaders();
        $received_token = $headers['X-CSRF-Token'] ?? ($_POST['csrf_token'] ?? '');
        
        if (empty($received_token) || !hash_equals($csrf_token, $received_token)) {
            echo json_encode(['error' => 'Security token verification context failed.']);
            exit;
        }
        
        if ($user_id === 0) {
            echo json_encode(['error' => 'auth_required']);
            exit;
        }

        $show_id = intval($_POST['show_id']);
        
        mysqli_begin_transaction($conn);
        try {
            $session_stmt = mysqli_prepare($conn, "SELECT session_id FROM booking_sessions WHERE user_id = ? AND show_id = ? AND session_status = 'ACTIVE'");
            mysqli_stmt_bind_param($session_stmt, "ii", $user_id, $show_id);
            mysqli_stmt_execute($session_stmt);
            $res = mysqli_stmt_get_result($session_stmt);

            if ($session = mysqli_fetch_assoc($res)) {
                $session_id = $session['session_id'];
                mysqli_query($conn, "UPDATE show_seats ss JOIN seat_locks sl ON ss.show_seat_id = sl.show_seat_id SET ss.seat_status = 'AVAILABLE' WHERE sl.session_id = $session_id");
                mysqli_query($conn, "DELETE FROM seat_locks WHERE session_id = $session_id");
                mysqli_query($conn, "UPDATE booking_sessions SET session_status = 'EXPIRED' WHERE session_id = $session_id");
            }
            mysqli_commit($conn);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['error' => 'Cancellation processing error.']);
        }
        exit;
    }
}

// Main View Logic Row Definitions
if (!isset($_GET['show_id']) || !is_numeric($_GET['show_id'])) {
    header("Location: ../index.php");
    exit();
}

$show_id = intval($_GET['show_id']);
$show_query = "SELECT s.*, scr.screen_name, m.title, m.poster_url, m.movie_format, m.duration_minutes, m.language,
                     GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', ') AS genre_names
              FROM shows s 
              JOIN screens scr ON s.screen_id = scr.screen_id 
              JOIN movies m ON s.movie_id = m.movie_id 
              LEFT JOIN movie_genres mg ON mg.movie_id = m.movie_id
              LEFT JOIN genres g ON g.genre_id = mg.genre_id
              WHERE s.show_id = $show_id AND s.show_status = 'ACTIVE' AND m.status = 'ACTIVE'
              GROUP BY s.show_id";
$show_result = mysqli_query($conn, $show_query);

if (mysqli_num_rows($show_result) === 0) {
    header("Location: ../index.php");
    exit();
}
$show = mysqli_fetch_assoc($show_result);
$show_genres = [];
if (!empty($show['genre_names'])) {
    $show_genres = array_map('trim', explode(',', $show['genre_names']));
}

if (strtotime($show['show_date'] . ' ' . $show['show_time']) < strtotime($current_datetime)) {
    header("Location: ../index.php?error=show_started");
    exit();
}

// Check for an existing active session to extract active expiry variables onto HTML load
$my_locked_seats = [];
$initial_expiry_time = null;

$session_stmt = mysqli_prepare($conn, "SELECT session_id, expiry_time FROM booking_sessions WHERE user_id = ? AND show_id = ? AND session_status = 'ACTIVE' AND expiry_time > ?");
mysqli_stmt_bind_param($session_stmt, "iis", $user_id, $show_id, $current_datetime);
mysqli_stmt_execute($session_stmt);
$session_res = mysqli_stmt_get_result($session_stmt);

if ($session_row = mysqli_fetch_assoc($session_res)) {
    $initial_expiry_time = $session_row['expiry_time'];
    $lock_stmt = mysqli_prepare($conn, "SELECT show_seat_id FROM seat_locks WHERE session_id = ?");
    mysqli_stmt_bind_param($lock_stmt, "i", $session_row['session_id']);
    mysqli_stmt_execute($lock_stmt);
    $lock_res = mysqli_stmt_get_result($lock_stmt);
    while ($lock = mysqli_fetch_assoc($lock_res)) {
        $my_locked_seats[] = $lock['show_seat_id'];
    }
}

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

// Handle Form Checkout Booking Postback
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $_POST['csrf_token'])) {
        $message = "Security authorization validation expired.";
        $message_type = 'error';
    } else {
        $selected_seats = isset($_POST['selected_seats']) ? json_decode($_POST['selected_seats'], true) : [];

        if (empty($selected_seats)) {
            $message = "Please select at least one seat!";
            $message_type = 'error';
        } else if (count($selected_seats) > 5) {
            $message = "You can only book up to 5 seats at once.";
            $message_type = 'error';
        } else {
            mysqli_begin_transaction($conn);
            try {
                $session_stmt = mysqli_prepare($conn, "SELECT * FROM booking_sessions WHERE user_id = ? AND show_id = ? AND session_status = 'ACTIVE' AND expiry_time > ? FOR UPDATE");
                mysqli_stmt_bind_param($session_stmt, "iis", $user_id, $show_id, $current_datetime);
                mysqli_stmt_execute($session_stmt);
                $session_res = mysqli_stmt_get_result($session_stmt);

                if (mysqli_num_rows($session_res) === 0) {
                    throw new Exception("Booking session expired! Please select seats again.");
                }
                $session = mysqli_fetch_assoc($session_res);
                $session_id = $session['session_id'];

                foreach ($selected_seats as $seat_id) {
                    $seat_id = intval($seat_id);
                    $check_lock = mysqli_query($conn, "SELECT * FROM seat_locks WHERE session_id = $session_id AND show_seat_id = $seat_id");
                    if (mysqli_num_rows($check_lock) === 0) {
                        throw new Exception("One or more seats are no longer locked! Please try again.");
                    }

                    $check_seat = mysqli_query($conn, "SELECT seat_status FROM show_seats WHERE show_seat_id = $seat_id FOR UPDATE");
                    $seat = mysqli_fetch_assoc($check_seat);
                    if ($seat['seat_status'] !== 'LOCKED') {
                        throw new Exception("One or more seats are no longer available!");
                    }
                }

                $total_seats = count($selected_seats);
                $total_amount = $total_seats * $show['ticket_price'];
                
                $ins_booking = mysqli_prepare($conn, "INSERT INTO bookings (user_id, show_id, total_seats, total_amount, booking_status) VALUES (?, ?, ?, ?, 'CONFIRMED')");
                mysqli_stmt_bind_param($ins_booking, "iiid", $user_id, $show_id, $total_seats, $total_amount);
                mysqli_stmt_execute($ins_booking);
                $booking_id = mysqli_insert_id($conn);

                foreach ($selected_seats as $seat_id) {
                    $seat_id = intval($seat_id);
                    mysqli_query($conn, "INSERT INTO booking_details (booking_id, show_seat_id, ticket_price) VALUES ($booking_id, $seat_id, {$show['ticket_price']})");
                    mysqli_query($conn, "UPDATE show_seats SET seat_status = 'SOLD' WHERE show_seat_id = $seat_id");
                }

                mysqli_query($conn, "DELETE FROM seat_locks WHERE session_id = $session_id");
                mysqli_query($conn, "UPDATE booking_sessions SET session_status = 'COMPLETED' WHERE session_id = $session_id");

                // Insert into Ledger
                $movie_id = intval($show['movie_id']);
                $ins_ledger = mysqli_prepare($conn, "INSERT INTO ledger (booking_id, movie_id, show_id, transaction_type, amount, remarks) VALUES (?, ?, ?, 'BOOKING', ?, 'Booking confirmed')");
                mysqli_stmt_bind_param($ins_ledger, "iiid", $booking_id, $movie_id, $show_id, $total_amount);
                mysqli_stmt_execute($ins_ledger);

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
    <link rel="stylesheet" href="../Assets/css/Customer/seat_selection.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../Assets/css/Customer/auth_modal.css?v=<?= time(); ?>">
</head>
<body class="seat-selection-body">
    <?php include_once 'components/navbar.php'; ?>

    <main class="seat-selection-container">
        <nav class="breadcrumb-nav">
            <a href="../index.php" class="bc-link"><i class="fa-solid fa-house"></i> Home</a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <a href="movie_details.php?movie_id=<?php echo $show['movie_id']; ?>" class="bc-link"><?php echo htmlspecialchars($show['title']); ?></a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="bc-current">Select Seats</span>
        </nav>

        <section class="movie-info-card">
            <div class="movie-info-content">
                <?php if (!empty($show['poster_url'])): ?>
                    <?php $show_poster_path = '../Assets/uploads/movie_posters/' . ltrim($show['poster_url'], '/'); ?>
                    <div class="poster-wrapper">
                        <img src="<?php echo htmlspecialchars($show_poster_path); ?>" alt="<?php echo htmlspecialchars($show['title']); ?>" class="movie-poster">
                    </div>
                <?php endif; ?>
                <div class="info-wrapper">
                    <h1 class="movie-title"><?php echo htmlspecialchars($show['title']); ?></h1>
                    <div class="show-details">
                        <div class="detail-item"><i class="fa-solid fa-layer-group"></i> <span><?php echo htmlspecialchars(!empty($show_genres) ? implode(' | ', $show_genres) : 'N/A'); ?></span></div>
                        <div class="detail-item"><i class="fa-solid fa-language"></i> <span><?php echo htmlspecialchars($show['language']); ?></span></div>
                        <div class="detail-item"><i class="fa-solid fa-clock"></i> <span><?php echo intval($show['duration_minutes']); ?> mins</span></div>
                        <div class="detail-item"><i class="fa-solid fa-tv"></i> <span><?php echo htmlspecialchars($show['screen_name']); ?></span></div>
                        <div class="detail-item"><i class="fa-solid fa-calendar"></i> <span><?php echo date('l, d F Y', strtotime($show['show_date'])); ?></span></div>
                        <div class="detail-item"><i class="fa-solid fa-clock-rotate-left"></i> <span><?php echo date('h:i A', strtotime($show['show_time'])); ?></span></div>
                        <div class="detail-item"><i class="fa-solid fa-film"></i> <span><?php echo htmlspecialchars($show['movie_format']); ?></span></div>
                        <div class="detail-item price"><i class="fa-solid fa-ticket"></i> <span>Rs. <?php echo number_format($show['ticket_price'], 2); ?> per seat</span></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="timer-notice-wrapper">
            <!-- PHP checks if session was already active to render timer display automatically -->
            <div class="timer-card" id="timerCard" style="<?php echo $initial_expiry_time ? 'display: flex;' : 'display: none;'; ?>">
                <div class="timer-icon"><i class="fa-solid fa-clock"></i></div>
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

        <section class="seat-selection-card">
            <div class="section-header">
                <h2 class="section-title"><i class="fa-solid fa-couch"></i> Select Your Seats</h2>
                <a href="movie_details.php?movie_id=<?php echo $show['movie_id']; ?>" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back To Movie</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fa-solid <?php echo ($message_type === 'error') ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="screen-wrapper">
                <div class="screen"><span>SCREEN</span></div>
            </div>

            <form method="POST" id="bookingForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
                                        $seat_class .= ' selected';
                                    } else if ($seat['seat_status'] === 'SOLD') {
                                        $seat_class .= ' sold';
                                        $is_disabled = true;
                                    } else if ($seat['seat_status'] === 'LOCKED') {
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
                                        <label for="seat-<?php echo $seat['show_seat_id']; ?>" class="seat-label <?php echo $seat_class; ?>" data-show-seat-id="<?php echo $seat['show_seat_id']; ?>">
                                            <span class="seat-number"><?php echo htmlspecialchars($seat['seat_number']); ?></span>
                                            <?php if (strtoupper($seat['seat_type']) === 'VIP'): ?>
                                                <span class="vip-label">VIP</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="legend-wrapper">
                    <div class="legend-item"><div class="legend-seat available"></div><span>Available</span></div>
                    <div class="legend-item"><div class="legend-seat selected"></div><span>Selected</span></div>
                    <div class="legend-item"><div class="legend-seat locked"></div><span>Locked</span></div>
                    <div class="legend-item"><div class="legend-seat sold"></div><span>Sold</span></div>
                </div>
            </form>
        </section>

        <section class="summary-card">
            <h3 class="summary-title"><i class="fa-solid fa-receipt"></i> Booking Summary</h3>
            <div class="summary-content">
                <div class="summary-row"><span class="summary-label">Selected Seats:</span><span class="summary-value" id="selectedSeats">None</span></div>
                <div class="summary-row"><span class="summary-label">Total Seats:</span><span class="summary-value" id="seatCount">0</span></div>
                <div class="summary-row"><span class="summary-label">Price Per Seat:</span><span class="summary-value" id="pricePerSeat">Rs. <?php echo number_format($show['ticket_price'], 2); ?></span></div>
                <div class="summary-row total"><span class="summary-label">Total Amount:</span><span class="summary-value" id="totalAmount">Rs. 0.00</span></div>
            </div>
            <div class="summary-actions">
                <button type="button" class="btn-cancel" id="cancelBtn"><i class="fa-solid fa-times"></i> Cancel</button>
                <button type="submit" form="bookingForm" name="confirm_booking" class="btn-confirm-booking" id="confirmBtn" disabled><i class="fa-solid fa-check-circle"></i> Confirm Booking</button>
            </div>
        </section>
    </main>

    <div id="sessionPopup" class="session-popup">
        <i class="fas fa-check-circle"></i>
        <span>Booking session started! You have 5 minutes to complete your booking.</span>
    </div>

    <!-- Exit Confirmation Modal -->
    <div id="exitConfirmModal" class="exit-confirm-modal" aria-hidden="true" style="display:none;">
        <div class="exit-confirm-card">
            <button class="exit-confirm-close" aria-label="Close">&times;</button>
            <div class="exit-confirm-icon">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <h3 class="exit-confirm-title">Exit Seat Selection?</h3>
            <p class="exit-confirm-desc">You have selected seats. If you exit now, your selected seats will be released and made available for other customers.</p>
            <div class="exit-confirm-actions">
                <button class="btn-exit-cancel" type="button">No, Keep Selecting</button>
                <button class="btn-exit-confirm" type="button">Yes, Exit</button>
            </div>
        </div>
    </div>

    <!-- Notification/Acknowledgement Modal -->
    <div id="notificationModal" class="exit-confirm-modal" aria-hidden="true" style="display:none;">
        <div class="exit-confirm-card">
            <button class="exit-confirm-close" id="notificationCloseBtn" aria-label="Close">&times;</button>
            <div class="exit-confirm-icon" id="notificationIcon">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <h3 class="exit-confirm-title" id="notificationTitle">Notification</h3>
            <p class="exit-confirm-desc" id="notificationDesc"></p>
            <div class="exit-confirm-actions">
                <button class="btn-exit-confirm" id="notificationOkBtn" type="button" style="width: 100%;">OK</button>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/components/auth_modal.php'; ?>

    <?php if (file_exists(__DIR__ . '/components/footer.php')) { include_once 'components/footer.php'; } ?>

    <script>
        // Global variables initialized cleanly via structural state rendering engines inside PHP
        const showId = <?php echo $show_id; ?>;
        const movieId = <?php echo intval($show['movie_id']); ?>;
        const ticketPrice = <?php echo $show['ticket_price']; ?>;
        const csrfToken = "<?php echo $csrf_token; ?>";
        const initialExpiryTime = <?php echo $initial_expiry_time ? '"'.date('Y/m/d H:i:s', strtotime($initial_expiry_time)).'"' : 'null'; ?>;
    </script>
    <script src="../Assets/js/Customer/auth_modal.js"></script>
    <script src="../Assets/js/Customer/seat_selection.js?v=<?= time(); ?>"></script>
    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const title = "<?php echo ($message_type === 'error') ? 'Booking Error' : 'Booking Message'; ?>";
                const type = "<?php echo ($message_type === 'error') ? 'error' : 'success'; ?>";
                const msg = <?php echo json_encode($message); ?>;
                if (typeof showNotificationModal === 'function') {
                    showNotificationModal(title, msg, type);
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
<?php mysqli_close($conn); ?>