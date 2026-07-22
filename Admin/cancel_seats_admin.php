<?php
/**
 * Admin Partial Seat Cancellation Endpoint
 * POST - AJAX JSON endpoint
 */
session_start();
header('Content-Type: application/json');
require_once '../Includes/db_conn.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$seat_ids = isset($_POST['seat_ids']) ? $_POST['seat_ids'] : [];

// Validate input
if ($booking_id <= 0 || empty($seat_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking or seats.']);
    exit;
}

// Sanitize seat_ids to integers
$seat_ids = array_map('intval', $seat_ids);
$seat_ids = array_filter($seat_ids, function($id) { return $id > 0; });

if (empty($seat_ids)) {
    echo json_encode(['success' => false, 'message' => 'No valid seats selected.']);
    exit;
}

// Verify booking exists
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_status, b.show_id, s.movie_id,
           s.show_date, s.show_time
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Check booking status
if (!in_array($booking['booking_status'], ['CONFIRMED', 'PARTIALLY_CANCELLED'])) {
    echo json_encode(['success' => false, 'message' => 'This booking is already fully cancelled.']);
    exit;
}

// Check if the show has already completed
$show_datetime = $booking['show_date'] . ' ' . $booking['show_time'];
$show_ts = strtotime($show_datetime);
if ($show_ts <= time()) {
    echo json_encode(['success' => false, 'message' => 'Cancellation failed. The show has already been completed.']);
    exit;
}

// Verify each seat belongs to this booking and is CONFIRMED
$placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
$types = str_repeat('i', count($seat_ids) + 1);
$params = array_merge([$booking_id], $seat_ids);

$stmt = $conn->prepare("
    SELECT bd.booking_detail_id, bd.show_seat_id, bd.ticket_price, bd.seat_status,
           se.seat_number
    FROM booking_details bd
    JOIN show_seats ss ON bd.show_seat_id = ss.show_seat_id
    JOIN seats se ON ss.seat_id = se.seat_id
    WHERE bd.booking_id = ? AND bd.show_seat_id IN ($placeholders)
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$seat_result = $stmt->get_result();

$valid_seats = [];
$seat_names = [];
while ($row = $seat_result->fetch_assoc()) {
    if ($row['seat_status'] !== 'CONFIRMED') {
        echo json_encode(['success' => false, 'message' => 'Seat ' . $row['seat_number'] . ' is already cancelled.']);
        $stmt->close();
        exit;
    }
    $valid_seats[] = $row;
    $seat_names[] = $row['seat_number'];
}
$stmt->close();

if (count($valid_seats) !== count($seat_ids)) {
    echo json_encode(['success' => false, 'message' => 'One or more seats do not belong to this booking.']);
    exit;
}

// Calculate cancellation amount
$cancel_amount = 0;
foreach ($valid_seats as $seat) {
    $cancel_amount += (float)$seat['ticket_price'];
}

// START TRANSACTION
$conn->begin_transaction();

try {
    // 1. Update booking_details: set seat_status = CANCELLED, cancellation_time = NOW()
    $stmt = $conn->prepare("
        UPDATE booking_details 
        SET seat_status = 'CANCELLED', cancellation_time = NOW()
        WHERE booking_id = ? AND show_seat_id = ?
    ");
    foreach ($valid_seats as $seat) {
        $stmt->bind_param("ii", $booking_id, $seat['show_seat_id']);
        $stmt->execute();
    }
    $stmt->close();

    // 2. Update show_seats: SOLD -> AVAILABLE
    $stmt = $conn->prepare("
        UPDATE show_seats 
        SET seat_status = 'AVAILABLE'
        WHERE show_seat_id = ?
    ");
    foreach ($valid_seats as $seat) {
        $stmt->bind_param("i", $seat['show_seat_id']);
        $stmt->execute();
    }
    $stmt->close();

    // 3. Count confirmed vs cancelled seats for this booking
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN seat_status = 'CONFIRMED' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN seat_status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_count,
            COUNT(*) as total_count
        FROM booking_details
        WHERE booking_id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $counts = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $confirmed = (int)$counts['confirmed_count'];
    $cancelled = (int)$counts['cancelled_count'];
    $total = (int)$counts['total_count'];

    // 4. Determine new booking status
    if ($confirmed === 0) {
        $new_status = 'CANCELLED';
    } elseif ($cancelled > 0 && $confirmed > 0) {
        $new_status = 'PARTIALLY_CANCELLED';
    } else {
        $new_status = 'CONFIRMED';
    }

    // 5. Update booking status
    if ($new_status === 'CANCELLED') {
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET booking_status = ?, cancellation_time = NOW()
            WHERE booking_id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET booking_status = ?
            WHERE booking_id = ?
        ");
    }
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    $stmt->close();

    // 6. Insert ledger entry
    $seats_str = implode(', ', $seat_names);
    if (count($seat_names) === 1 && $total === 1) {
        $remarks = 'Booking cancelled by Admin';
    } elseif ($confirmed === 0) {
        $remarks = "Full cancellation by Admin (Seats: $seats_str)";
    } else {
        $remarks = "Partial cancellation by Admin (Seats: $seats_str)";
    }
    $neg_amount = -abs($cancel_amount);

    $stmt = $conn->prepare("
        INSERT INTO ledger (booking_id, movie_id, show_id, transaction_type, amount, remarks)
        VALUES (?, ?, ?, 'CANCELLATION', ?, ?)
    ");
    $stmt->bind_param("iiids", $booking_id, $booking['movie_id'], $booking['show_id'], $neg_amount, $remarks);
    $stmt->execute();
    $stmt->close();

    // COMMIT
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Selected seats have been cancelled successfully by Admin.',
        'new_status' => $new_status
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
