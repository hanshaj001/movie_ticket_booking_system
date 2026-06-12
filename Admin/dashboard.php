<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "movie_ticket_booking_system"
);

if(!$conn)
{
    die("Database Connection Failed");
}

?>


<?php

session_start();
require_once "../Includes/db_conn.php";
include "../Includes/sidebar.php";

/* ==========================================
   DEFAULT ADMIN NAME
========================================== */

$adminName = $_SESSION['full_name'] ?? 'Admin';

/* ==========================================
   TOTAL ACTIVE MOVIES
========================================== */

$query = "
SELECT COUNT(*) AS total_movies
FROM movies
WHERE status='ACTIVE'
";

$result = mysqli_query($conn, $query);
$totalMovies = mysqli_fetch_assoc($result)['total_movies'] ?? 0;

/* ==========================================
   TOTAL ACTIVE SHOWS
========================================== */

$query = "
SELECT COUNT(*) AS total_shows
FROM shows
WHERE show_status='ACTIVE'
";

$result = mysqli_query($conn, $query);
$totalShows = mysqli_fetch_assoc($result)['total_shows'] ?? 0;

/* ==========================================
   TODAY'S SHOWS
========================================== */

$query = "
SELECT COUNT(*) AS todays_shows
FROM shows
WHERE show_date = CURDATE()
AND show_status='ACTIVE'
";

$result = mysqli_query($conn, $query);
$todaysShows = mysqli_fetch_assoc($result)['todays_shows'] ?? 0;

/* ==========================================
   AVAILABLE SEATS TODAY
========================================== */

$query = "
SELECT COUNT(*) AS available_seats
FROM show_seats ss
INNER JOIN shows s
ON ss.show_id = s.show_id
WHERE
ss.seat_status='AVAILABLE'
AND s.show_date = CURDATE()
AND s.show_status='ACTIVE'
";

$result = mysqli_query($conn, $query);
$availableSeats = mysqli_fetch_assoc($result)['available_seats'] ?? 0;

/* ==========================================
   SOLD SEATS TODAY
========================================== */

$query = "
SELECT COUNT(*) AS sold_seats
FROM show_seats ss
INNER JOIN shows s
ON ss.show_id = s.show_id
WHERE
ss.seat_status='SOLD'
AND s.show_date = CURDATE()
AND s.show_status='ACTIVE'
";

$result = mysqli_query($conn, $query);
$soldSeats = mysqli_fetch_assoc($result)['sold_seats'] ?? 0;

/* ==========================================
   TODAY REVENUE
========================================== */

$query = "
SELECT
COALESCE(SUM(total_amount),0) AS revenue
FROM bookings
WHERE booking_status='CONFIRMED'
AND DATE(booking_time)=CURDATE()
";

$result = mysqli_query($conn, $query);
$totalRevenue = mysqli_fetch_assoc($result)['revenue'] ?? 0;

/* ==========================================
   TODAY'S RUNNING SHOWS
========================================== */

$query = "
SELECT
m.title,
m.poster_url,
m.movie_format,
s.show_time,
sc.screen_name
FROM movies m
INNER JOIN shows s
ON m.movie_id=s.movie_id
INNER JOIN screens sc
ON s.screen_id=sc.screen_id
WHERE
m.status='ACTIVE'
AND s.show_status='ACTIVE'
AND s.show_date = CURDATE()
ORDER BY s.show_time ASC
";

$runningShows = mysqli_query($conn,$query);



$recentBookingsQuery = "
SELECT
    b.booking_id,
    u.full_name,
    m.title,
    b.total_seats,
    b.total_amount,
    b.booking_time,
    b.booking_status
FROM bookings b
INNER JOIN users u
    ON b.user_id = u.user_id
INNER JOIN shows s
    ON b.show_id = s.show_id
INNER JOIN movies m
    ON s.movie_id = m.movie_id
ORDER BY b.booking_time DESC
LIMIT 5
";

$recentBookings = mysqli_query(
    $conn,
    $recentBookingsQuery
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard</title>

<link rel="stylesheet" href="../Assets/admin_dashboard.css">
<link rel="stylesheet" href="../Assets/sidebar.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body>

<div class="dashboard-container">

    <!-- HEADER -->

    <div class="dashboard-header">

        <div>
            <h1>Admin Dashboard</h1>
            <p>Monitor cinema activities and performance.</p>
        </div>

        <div class="admin-info">
            Welcome,
            <strong><?= htmlspecialchars($adminName) ?></strong>
        </div>

    </div>

    <!-- DASHBOARD CARDS -->

    <div class="stats-grid">

        <!-- Movies -->

        <div class="stat-card">

            <div class="stat-icon movie-icon">
                <i class="fas fa-film"></i>
            </div>

            <div class="stat-content">
                <h2><?= $totalMovies ?></h2>
                <p>Total Movies</p>
            </div>

        </div>

        <!-- Shows -->

        <div class="stat-card">

            <div class="stat-icon show-icon">
                <i class="fas fa-video"></i>
            </div>

            <div class="stat-content">
                <h2><?= $totalShows ?></h2>
                <p>Total Shows</p>
            </div>

        </div>

        <!-- Today Shows -->

        <div class="stat-card">

            <div class="stat-icon booking-icon">
                <i class="fas fa-calendar-day"></i>
            </div>

            <div class="stat-content">
                <h2><?= $todaysShows ?></h2>
                <p>Today's Shows</p>
            </div>

        </div>

        <!-- Available Seats -->

        <div class="stat-card">

            <div class="stat-icon available-icon">
                <i class="fas fa-couch"></i>
            </div>

            <div class="stat-content">
                <h2><?= $availableSeats ?></h2>
                <p>Available Seats</p>
            </div>

        </div>

        <!-- Sold Seats -->

        <div class="stat-card">

            <div class="stat-icon sold-icon">
                <i class="fas fa-chair"></i>
            </div>

            <div class="stat-content">
                <h2><?= $soldSeats ?></h2>
                <p>Sold Seats</p>
            </div>

        </div>

        <!-- Revenue -->

        <div class="stat-card">

            <div class="stat-icon revenue-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>

            <div class="stat-content">
                <h2>Rs. <?= number_format($totalRevenue,2) ?></h2>
                <p>Today's Revenue</p>
            </div>

        </div>

    </div>

    <!-- RUNNING SHOWS -->

    <div class="section-header">
        <h2>Today's Running Shows</h2>
    </div>

    <div class="movies-grid">

        <?php if(mysqli_num_rows($runningShows) > 0): ?>

            <?php while($show = mysqli_fetch_assoc($runningShows)): ?>

                <div class="movie-card">

                    <div class="movie-poster">

                        <?php if(!empty($show['poster_url'])): ?>

                            <img
                            src="<?= htmlspecialchars($show['poster_url']) ?>"
                            alt="<?= htmlspecialchars($show['title']) ?>">

                        <?php else: ?>

                            <img
                            src="../Assets/images/default-movie.jpg"
                            alt="Movie Poster">

                        <?php endif; ?>

                    </div>

                    <div class="movie-info">

                        <h3>
                            <?= htmlspecialchars($show['title']) ?>
                        </h3>

                        <p>
                            <i class="fas fa-tv"></i>
                            <?= htmlspecialchars($show['screen_name']) ?>
                        </p>

                        <p>
                            <i class="fas fa-clock"></i>

                            <?= date(
                                "h:i A",
                                strtotime($show['show_time'])
                            ) ?>
                        </p>

                        <span class="movie-format">
                            <?= htmlspecialchars($show['movie_format']) ?>
                        </span>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-state">

                <i class="fas fa-film"></i>

                <h3>No Shows Scheduled Today</h3>

                <p>
                    There are no active movie shows scheduled today.
                </p>

            </div>

        <?php endif; ?>

    </div>

    <div class="section-header">
    <h2>Recent Bookings</h2>
</div>

<div class="booking-table-container">

    <table class="booking-table">

        <thead>

            <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Movie</th>
                <th>Seats</th>
                <th>Amount</th>
                <th>Time</th>
                <th>Status</th>
            </tr>

        </thead>

        <tbody>

            <?php if(mysqli_num_rows($recentBookings) > 0): ?>

                <?php while($booking = mysqli_fetch_assoc($recentBookings)): ?>

                    <tr>

                        <td>
                            #<?= $booking['booking_id']; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking['full_name']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking['title']); ?>
                        </td>

                        <td>
                            <?= $booking['total_seats']; ?>
                        </td>

                        <td>
                            Rs.
                            <?= number_format(
                                $booking['total_amount'],
                                2
                            ); ?>
                        </td>

                        <td>
                            <?= date(
                                "d M Y h:i A",
                                strtotime(
                                    $booking['booking_time']
                                )
                            ); ?>
                        </td>

                        <td>

                            <span class="status-badge">

                                <?= $booking['booking_status']; ?>

                            </span>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7">
                        No bookings found.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

</div>

</body>
</html>  