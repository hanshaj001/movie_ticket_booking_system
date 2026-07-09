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
    
    // Ensure the booking belongs to the logged-in user
    $check_query = "SELECT booking_status FROM bookings WHERE booking_id = $booking_id AND user_id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $booking = mysqli_fetch_assoc($check_result);
        
        // Only cancel if it's currently CONFIRMED
        if ($booking['booking_status'] === 'CONFIRMED') {
            $update_query = "
                UPDATE bookings 
                SET booking_status = 'CANCELLED', cancellation_time = NOW() 
                WHERE booking_id = $booking_id AND user_id = $user_id
            ";
            
            if (mysqli_query($conn, $update_query)) {
                // Redirect back with success message (optional: append ?status=success)
                header("Location: booking_history.php?cancel=success");
                exit();
            } else {
                // Query failed
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
