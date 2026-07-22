<?php
require_once '../Includes/db_conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    die("Unauthorized");
}

if(!isset($_GET['id']))
{
    die("Invalid Request");
}

$booking_id = intval($_GET['id']);

$booking = mysqli_query($conn,"
    SELECT b.*, s.movie_id, s.show_date, s.show_time
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    WHERE b.booking_id='$booking_id'
");

$data = mysqli_fetch_assoc($booking);

if(!$data)
{
    die("Booking Not Found");
}

if ($data['booking_status'] === 'CONFIRMED') {
    // Check if the show has already completed
    $show_datetime = $data['show_date'] . ' ' . $data['show_time'];
    $show_ts = strtotime($show_datetime);
    if ($show_ts <= time()) {
        die("Cancellation failed. The show has already been completed.");
    }

    mysqli_begin_transaction($conn);
    try {
        /* Update Booking Status */
        mysqli_query($conn,"
            UPDATE bookings
            SET booking_status='CANCELLED', cancellation_time=NOW()
            WHERE booking_id='$booking_id'
        ");

        /* Release Seats */
        mysqli_query($conn,"
            UPDATE show_seats ss
            JOIN booking_details bd ON ss.show_seat_id=bd.show_seat_id
            SET ss.seat_status='AVAILABLE'
            WHERE bd.booking_id='$booking_id'
        ");

        /* Cancel Seats in Booking Details */
        mysqli_query($conn,"
            UPDATE booking_details
            SET seat_status='CANCELLED', cancellation_time=NOW()
            WHERE booking_id='$booking_id'
        ");

        /* Insert into Ledger */
        $movie_id = $data['movie_id'];
        $show_id = $data['show_id'];
        $amount = -abs($data['total_amount']);
        $ins_ledger = mysqli_prepare($conn, "INSERT INTO ledger (booking_id, movie_id, show_id, transaction_type, amount, remarks) VALUES (?, ?, ?, 'CANCELLATION', ?, 'Booking cancelled by Admin')");
        mysqli_stmt_bind_param($ins_ledger, "iiid", $booking_id, $movie_id, $show_id, $amount);
        if (!mysqli_stmt_execute($ins_ledger)) {
            throw new Exception("Ledger insert failed");
        }

        mysqli_commit($conn);
        header("Location: booking_cancel_success.php?id=".$booking_id);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        die("Error cancelling booking.");
    }
} else {
    die("Booking is not in confirmed state.");
}
?>