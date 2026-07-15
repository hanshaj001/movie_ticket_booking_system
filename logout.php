<?php
session_start();

// Include DB connection (needed for seat‑lock cleanup for customers)
require_once 'Includes/db_conn.php';

/** Helper to redirect safely, clearing any output buffer */
function safe_redirect(string $url): void {
    if (ob_get_contents()) {
        ob_end_clean();
    }
    header('Location: ' . $url);
    exit();
}

/** Finalize logout: clear session, set no‑cache headers, then redirect */
function finalize_logout(string $redirectUrl): void {
    // Clear all session data
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 4200,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();

    // Prevent the browser from caching protected pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

    safe_redirect($redirectUrl);
}

// -----------------------------------------------------------------
// Determine the user's role. If not set, treat as a guest.
// -----------------------------------------------------------------
$role = $_SESSION['role'] ?? null;

// -----------------------------------------------------------------
// Admin logout – send them back to the admin login page.
// -----------------------------------------------------------------
if ($role === 'ADMIN') {
    finalize_logout('login.php');
}

// -----------------------------------------------------------------
// Any non‑customer (including guests or an expired/invalid session)
// should be sent to the public home page.
// -----------------------------------------------------------------
if ($role !== 'CUSTOMER') {
    finalize_logout('Customer/home.php');
}

// -----------------------------------------------------------------
// At this point we have a logged‑in CUSTOMER.
// -----------------------------------------------------------------
$user_id = intval($_SESSION['user_id'] ?? 0);

// ---------------------------------------------------------------
// Release any temporary seat locks for active booking sessions.
// ---------------------------------------------------------------
$active_sql = "SELECT session_id FROM booking_sessions WHERE user_id = ? AND session_status = 'ACTIVE'";
if ($stmt = mysqli_prepare($conn, $active_sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $session_ids = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $session_ids[] = $row['session_id'];
    }
    mysqli_stmt_close($stmt);

    if (!empty($session_ids)) {
        // Build placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($session_ids), '?'));

        // 1. Delete seat locks
        $del_sql = "DELETE FROM seat_locks WHERE session_id IN ($placeholders)";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, str_repeat('i', count($session_ids)), ...$session_ids);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);

        // 2. Mark the seats as AVAILABLE again
        $upd_sql = "UPDATE show_seats ss JOIN seat_locks sl ON ss.show_seat_id = sl.show_seat_id SET ss.seat_status = 'AVAILABLE' WHERE sl.session_id IN ($placeholders)";
        $upd_stmt = mysqli_prepare($conn, $upd_sql);
        mysqli_stmt_bind_param($upd_stmt, str_repeat('i', count($session_ids)), ...$session_ids);
        mysqli_stmt_execute($upd_stmt);
        mysqli_stmt_close($upd_stmt);

        // 3. Expire the booking sessions themselves
        $exp_sql = "UPDATE booking_sessions SET session_status = 'EXPIRED' WHERE session_id IN ($placeholders)";
        $exp_stmt = mysqli_prepare($conn, $exp_sql);
        mysqli_stmt_bind_param($exp_stmt, str_repeat('i', count($session_ids)), ...$session_ids);
        mysqli_stmt_execute($exp_stmt);
        mysqli_stmt_close($exp_stmt);
    }
}

// -----------------------------------------------------------------
// Log the customer out and send them to the public home page.
// -----------------------------------------------------------------
finalize_logout('Customer/home.php');
?>
