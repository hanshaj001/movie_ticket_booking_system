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

if (isset($_GET['booking_id']) && is_numeric($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    
    // Ensure the booking belongs to the logged-in user and fetch details
    $check_query = "
        SELECT b.booking_status, b.show_id, b.total_amount, s.movie_id
        FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        WHERE b.booking_id = $booking_id AND b.user_id = $user_id
    ";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $booking = mysqli_fetch_assoc($check_result);
        
        // Only cancel if it's currently CONFIRMED
        if ($booking['booking_status'] === 'CONFIRMED') {
            mysqli_begin_transaction($conn);
            try {
                $update_query = "
                    UPDATE bookings 
                    SET booking_status = 'CANCELLED', cancellation_time = NOW() 
                    WHERE booking_id = $booking_id AND user_id = $user_id
                ";
                mysqli_query($conn, $update_query);
                
                // Release Seats
                mysqli_query($conn,"
                    UPDATE show_seats ss
                    JOIN booking_details bd ON ss.show_seat_id=bd.show_seat_id
                    SET ss.seat_status='AVAILABLE'
                    WHERE bd.booking_id='$booking_id'
                ");

                // Cancel Seats in Booking Details
                mysqli_query($conn,"
                    UPDATE booking_details
                    SET seat_status='CANCELLED', cancellation_time=NOW()
                    WHERE booking_id='$booking_id'
                ");

                // Insert into Ledger
                $movie_id = $booking['movie_id'];
                $show_id = $booking['show_id'];
                $amount = -abs($booking['total_amount']); // Ensure negative
                
                $ins_ledger = mysqli_prepare($conn, "INSERT INTO ledger (booking_id, movie_id, show_id, transaction_type, amount, remarks) VALUES (?, ?, ?, 'CANCELLATION', ?, 'Booking cancelled')");
                mysqli_stmt_bind_param($ins_ledger, "iiid", $booking_id, $movie_id, $show_id, $amount);
                if (!mysqli_stmt_execute($ins_ledger)) {
                    throw new Exception("Ledger insert failed");
                }
                
                mysqli_commit($conn);
                header("Location: booking_history.php?cancel=success");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                header("Location: booking_history.php?cancel=error");
                exit();
            }
        } else {
            // Already cancelled or different status
            header("Location: booking_history.php?cancel=already_cancelled");
            exit();
        }
    } else {
        // Booking not found or does not belong to user
        header("Location: booking_history.php?cancel=not_found");
        exit();
    }
} else {
    // No booking ID provided
    header("Location: booking_history.php");
    exit();
}
?>
