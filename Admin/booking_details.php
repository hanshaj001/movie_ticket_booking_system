<?php
require_once '../Includes/db_conn.php';
include "components/sidebar.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: booking_monitoring.php");
    exit();
}

$booking_id = intval($_GET['id']);

$query = "
SELECT
    b.booking_id,
    b.booking_status,
    b.booking_time,
    b.total_amount,
    b.total_seats,
    b.cancellation_time,
    u.full_name,
    u.email,
    u.phone,
    m.title AS movie_title,
    m.movie_format,
    m.language,
    m.poster_url,
    sh.show_date,
    sh.show_time,
    sc.screen_name
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN shows sh ON b.show_id = sh.show_id
JOIN movies m ON sh.movie_id = m.movie_id
JOIN screens sc ON sh.screen_id = sc.screen_id
WHERE b.booking_id = '$booking_id'
";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header("Location: booking_monitoring.php");
    exit();
}

// Fetch individual seat details
$seats_query = "
    SELECT bd.show_seat_id, bd.ticket_price, bd.seat_status, bd.cancellation_time,
           se.seat_number
    FROM booking_details bd
    JOIN show_seats ss ON bd.show_seat_id = ss.show_seat_id
    JOIN seats se ON ss.seat_id = se.seat_id
    WHERE bd.booking_id = $booking_id
    ORDER BY se.seat_number
";
$seats_result = mysqli_query($conn, $seats_query);
$seats = [];
$confirmed_count = 0;
$cancelled_count = 0;
while ($seat = mysqli_fetch_assoc($seats_result)) {
    $seats[] = $seat;
    if ($seat['seat_status'] === 'CONFIRMED') $confirmed_count++;
    if ($seat['seat_status'] === 'CANCELLED') $cancelled_count++;
}

// Check if show is completed
$show_datetime = $booking['show_date'] . ' ' . $booking['show_time'];
$show_ts = strtotime($show_datetime);
$show_completed = ($show_ts <= time()) ? true : false;

$status = $booking['booking_status'];
$status_lower = strtolower($status);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - #<?= $booking['booking_id'] ?></title>
    <link rel="stylesheet" href="../Assets/css/Admin/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', Arial, sans-serif; }
        body { background: #f5f5f7; color: #333; }

        .main-container { display: flex; width: 100%; min-height: 100vh; }
        .content-area {
            flex: 1; min-width: 0; padding: 30px; margin-left: 220px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: #fff; padding: 20px; border-radius: 14px;
            margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-title { display: flex; align-items: center; gap: 15px; }
        .title-icon {
            width: 55px; height: 55px; background: #ff6136; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white;
        }
        .page-title h1 { font-size: 22px; color: #333; margin-bottom: 3px; }
        .page-title p { color: #777; font-size: 13px; }

        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: #f3f4f6; color: #4b5563; text-decoration: none;
            padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 14px;
            border: 1px solid #d1d5db; transition: 0.3s;
        }
        .back-btn:hover { background: #e5e7eb; color: #1f2937; }

        /* Summary Cards */
        .summary-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 25px;
        }
        .summary-card {
            background: #fff; padding: 20px; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align: center;
        }
        .summary-card .label { font-size: 12px; color: #888; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .summary-card .value { font-size: 20px; font-weight: 700; color: #333; }
        .summary-card .value.amount { color: #ff4d2d; }

        /* Details Card */
        .details-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;
        }
        .detail-card {
            background: #fff; padding: 24px; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .detail-card h3 {
            font-size: 15px; font-weight: 700; color: #333; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;
        }
        .detail-card h3 i { color: #ff4d2d; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { font-weight: 600; color: #666; font-size: 13px; }
        .detail-row .value { font-weight: 600; color: #333; font-size: 13px; text-align: right; }

        /* Status Badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
        }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-partially_cancelled { background: #fef3c7; color: #b45309; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }

        /* Seats Section */
        .seats-card {
            background: #fff; padding: 24px; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 25px;
        }
        .seats-card h3 {
            font-size: 15px; font-weight: 700; color: #333; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;
        }
        .seats-card h3 i { color: #ff4d2d; }
        .seats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
        .seat-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; border-radius: 10px; border: 1px solid #f0f0f0;
        }
        .seat-item.confirmed { background: #f0fdf4; border-color: #bbf7d0; }
        .seat-item.cancelled { background: #fef2f2; border-color: #fecaca; opacity: 0.7; }
        .seat-name { font-weight: 700; font-size: 14px; }
        .seat-price { font-size: 12px; color: #666; }
        .seat-tag {
            font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px;
        }
        .seat-tag.tag-confirmed { background: #dcfce7; color: #15803d; }
        .seat-tag.tag-cancelled { background: #fee2e2; color: #dc2626; text-decoration: line-through; }

        /* Show Completed Banner */
        .show-completed-banner {
            background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px;
            padding: 14px 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;
            color: #92400e; font-weight: 600; font-size: 14px;
        }
        .show-completed-banner i { font-size: 18px; }

        @media (max-width: 992px) { .content-area { margin-left: 0; padding: 20px; } }
        @media (max-width: 768px) {
            .details-grid { grid-template-columns: 1fr; }
            .summary-row { grid-template-columns: 1fr 1fr; }
            .detail-row { flex-direction: column; gap: 4px; }
            .detail-row .value { text-align: left; }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="content-area">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon"><i class="fa-solid fa-receipt"></i></div>
                <div>
                    <h1>Booking #<?= str_pad($booking['booking_id'], 4, '0', STR_PAD_LEFT) ?></h1>
                    <p>Detailed booking information</p>
                </div>
            </div>
            <a href="booking_monitoring.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Monitoring
            </a>
        </div>

        <?php if ($show_completed): ?>
            <div class="show-completed-banner">
                <i class="fa-solid fa-circle-check"></i>
                This show was completed on <?= date('d M Y', strtotime($booking['show_date'])) ?> at <?= date('h:i A', strtotime($booking['show_time'])) ?>.
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="summary-row">
            <div class="summary-card">
                <div class="label">Total Seats</div>
                <div class="value"><?= $booking['total_seats'] ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Amount</div>
                <div class="value amount">Rs. <?= number_format($booking['total_amount'], 2) ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Confirmed</div>
                <div class="value" style="color: #15803d;"><?= $confirmed_count ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Cancelled</div>
                <div class="value" style="color: #dc2626;"><?= $cancelled_count ?></div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="details-grid">
            <!-- Customer Info -->
            <div class="detail-card">
                <h3><i class="fa-solid fa-user"></i> Customer Information</h3>
                <div class="detail-row">
                    <span class="label">Full Name</span>
                    <span class="value"><?= htmlspecialchars($booking['full_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Email</span>
                    <span class="value"><?= htmlspecialchars($booking['email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Phone</span>
                    <span class="value"><?= htmlspecialchars($booking['phone']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Booking Status</span>
                    <span class="value">
                        <span class="status-badge status-<?= $status_lower ?>">
                            <?= str_replace('_', ' ', $status) ?>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Show Info -->
            <div class="detail-card">
                <h3><i class="fa-solid fa-film"></i> Show Information</h3>
                <div class="detail-row">
                    <span class="label">Movie</span>
                    <span class="value"><?= htmlspecialchars($booking['movie_title']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Format</span>
                    <span class="value"><?= htmlspecialchars($booking['movie_format']) ?> | <?= htmlspecialchars($booking['language']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Screen</span>
                    <span class="value"><?= htmlspecialchars($booking['screen_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Show Date</span>
                    <span class="value"><?= date('d M Y', strtotime($booking['show_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Show Time</span>
                    <span class="value"><?= date('h:i A', strtotime($booking['show_time'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Booked On</span>
                    <span class="value"><?= date('d M Y, h:i A', strtotime($booking['booking_time'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Seats -->
        <div class="seats-card">
            <h3><i class="fa-solid fa-chair"></i> Seat Details</h3>
            <div class="seats-grid">
                <?php foreach ($seats as $seat): ?>
                    <div class="seat-item <?= strtolower($seat['seat_status']) ?>">
                        <div>
                            <div class="seat-name"><?= htmlspecialchars($seat['seat_number']) ?></div>
                            <div class="seat-price">Rs. <?= number_format($seat['ticket_price'], 2) ?></div>
                        </div>
                        <span class="seat-tag tag-<?= strtolower($seat['seat_status']) ?>">
                            <?= $seat['seat_status'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>