<?php
session_start();
require_once "../Includes/db_conn.php";
include "components/sidebar.php";

/* ==========================================
   DEFAULT ADMIN NAME
========================================== */
$adminName = $_SESSION['full_name'] ?? 'Admin';

/* ==========================================
   1. TOTAL ACTIVE MOVIES
========================================== */
$query = "SELECT COUNT(*) AS total_movies FROM movies WHERE status='ACTIVE'";
$result = mysqli_query($conn, $query);
$totalMovies = mysqli_fetch_assoc($result)['total_movies'] ?? 0;

/* ==========================================
   2. TOTAL ACTIVE SHOWS
========================================== */
$query = "SELECT COUNT(*) AS total_shows FROM shows WHERE show_status='ACTIVE'";
$result = mysqli_query($conn, $query);
$totalShows = mysqli_fetch_assoc($result)['total_shows'] ?? 0;

/* ==========================================
   3. TODAY'S BOOKINGS
========================================== */
$query = "SELECT COUNT(*) AS todays_bookings FROM bookings WHERE DATE(booking_time) = CURDATE()";
$result = mysqli_query($conn, $query);
$todaysBookings = mysqli_fetch_assoc($result)['todays_bookings'] ?? 0;

/* ==========================================
   4. TODAY REVENUE
========================================== */
$query = "
SELECT COALESCE(SUM(total_amount),0) AS revenue 
FROM bookings 
WHERE booking_status='CONFIRMED' 
AND DATE(booking_time)=CURDATE()
";
$result = mysqli_query($conn, $query);
$totalRevenue = mysqli_fetch_assoc($result)['revenue'] ?? 0;

/* ==========================================
   5. SOLD SEATS TODAY
========================================== */
$query = "
SELECT COUNT(*) AS sold_seats
FROM show_seats ss
INNER JOIN shows s ON ss.show_id = s.show_id
WHERE ss.seat_status='SOLD'
AND s.show_date = CURDATE()
AND s.show_status='ACTIVE'
";
$result = mysqli_query($conn, $query);
$soldSeats = mysqli_fetch_assoc($result)['sold_seats'] ?? 0;

/* ==========================================
   6. AVAILABLE SEATS TODAY
========================================== */
$query = "
SELECT COUNT(*) AS available_seats
FROM show_seats ss
INNER JOIN shows s ON ss.show_id = s.show_id
WHERE ss.seat_status='AVAILABLE'
AND s.show_date = CURDATE()
AND s.show_status='ACTIVE'
";
$result = mysqli_query($conn, $query);
$availableSeats = mysqli_fetch_assoc($result)['available_seats'] ?? 0;

/* ==========================================
   7. CANCELLED BOOKINGS (Total)
========================================== */
$query = "SELECT COUNT(*) AS cancelled_bookings FROM bookings WHERE booking_status='CANCELLED'";
$result = mysqli_query($conn, $query);
$cancelledBookings = mysqli_fetch_assoc($result)['cancelled_bookings'] ?? 0;

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
INNER JOIN shows s ON m.movie_id=s.movie_id
INNER JOIN screens sc ON s.screen_id=sc.screen_id
WHERE m.status='ACTIVE'
AND s.show_status='ACTIVE'
AND s.show_date = CURDATE()
ORDER BY s.show_time ASC
";
$runningShows = mysqli_query($conn, $query);

/* ==========================================
   RECENT BOOKINGS
========================================== */
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
INNER JOIN users u ON b.user_id = u.user_id
INNER JOIN shows s ON b.show_id = s.show_id
INNER JOIN movies m ON s.movie_id = m.movie_id
ORDER BY b.booking_time DESC
LIMIT 5
";
$recentBookings = mysqli_query($conn, $recentBookingsQuery);

/* ==========================================
   CHART 1: BOOKING TREND (LAST 7 DAYS)
========================================== */
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days[$date] = [
        'label' => date('d M', strtotime($date)),
        'seats' => 0
    ];
}

$trend_query = "
    SELECT DATE(b.booking_time) as booking_date, COUNT(*) as seats_count
    FROM booking_details bd
    JOIN bookings b ON bd.booking_id = b.booking_id
    WHERE b.booking_status IN ('CONFIRMED', 'PARTIALLY_CANCELLED')
      AND bd.seat_status = 'CONFIRMED'
      AND b.booking_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(b.booking_time)
";
$trend_result = mysqli_query($conn, $trend_query);
while ($row = @mysqli_fetch_assoc($trend_result)) {
    $b_date = $row['booking_date'];
    if (isset($days[$b_date])) {
        $days[$b_date]['seats'] = (int)$row['seats_count'];
    }
}

$trend_labels = [];
$trend_data = [];
foreach ($days as $day) {
    $trend_labels[] = $day['label'];
    $trend_data[] = $day['seats'];
}

/* ==========================================
   CHART 2: TOP 5 MOVIES BY REVENUE
========================================== */
$movie_revenue_query = "
    SELECT m.title, COALESCE(SUM(l.amount), 0) as net_revenue
    FROM ledger l
    JOIN movies m ON l.movie_id = m.movie_id
    GROUP BY l.movie_id
    ORDER BY net_revenue DESC
    LIMIT 5
";
$movie_revenue_result = mysqli_query($conn, $movie_revenue_query);
$top_movies = [];
$top_revenues = [];
while ($row = @mysqli_fetch_assoc($movie_revenue_result)) {
    $top_movies[] = $row['title'];
    $top_revenues[] = (float)$row['net_revenue'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../Assets/css/Admin/admin_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../Assets/css/Admin/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-chart-line"></i>
            <div>
                <h1>Admin Dashboard</h1>
                <p>Monitor cinema activities and performance.</p>
            </div>
        </div>
        <div class="admin-info">
            Welcome, <strong><?= htmlspecialchars($adminName) ?></strong>
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

        <!-- Today's Bookings -->
        <div class="stat-card">
            <div class="stat-icon booking-icon">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="stat-content">
                <h2><?= $todaysBookings ?></h2>
                <p>Today's Bookings</p>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="stat-card">
            <div class="stat-icon revenue-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <h2>Rs. <?= number_format($totalRevenue, 2) ?></h2>
                <p>Today's Revenue</p>
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

        <!-- Cancelled Bookings -->
        <div class="stat-card">
            <div class="stat-icon cancelled-icon" style="background: #ef4444;">
                <i class="fas fa-ban"></i>
            </div>
            <div class="stat-content">
                <h2><?= $cancelledBookings ?></h2>
                <p>Cancelled Bookings</p>
            </div>
        </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="charts-grid">
        <!-- Booking Trend Chart Card -->
        <div class="chart-card">
            <h3>Booking Trend (Last 7 Days)</h3>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
            <!-- Chart Index / Legend -->
            <div class="chart-legend" style="display: flex; gap: 15px; justify-content: center; margin-top: 15px; font-size: 0.85rem; color: #555;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #ff4d2d; border-radius: 50%;"></span>
                    <strong>Seats Booked</strong> (Volume of confirmed tickets)
                </div>
            </div>
        </div>

        <!-- Revenue Chart Card -->
        <div class="chart-card">
            <h3>Top 5 Movies by Revenue</h3>
            <div class="chart-container">
                <?php if (empty($top_movies)): ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #777; font-weight: 500;">
                        No revenue data available.
                    </div>
                <?php else: ?>
                    <canvas id="revenueChart"></canvas>
                <?php endif; ?>
            </div>
            <!-- Chart Index / Legend -->
            <?php if (!empty($top_movies)): ?>
                <div class="chart-legend" style="display: flex; flex-wrap: wrap; gap: 10px 15px; justify-content: center; margin-top: 15px; font-size: 0.85rem; color: #555;">
                    <div id="revenue-legend-container" style="display: flex; flex-wrap: wrap; gap: 10px 15px; justify-content: center; width: 100%;"></div>
                </div>
            <?php endif; ?>
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
                        <?php 
                        $poster_url = $show['poster_url'];
                        if (!empty($poster_url) && strpos($poster_url, 'http') !== 0 && strpos($poster_url, '../Assets/uploads/') === false) {
                            $poster_url = '../Assets/uploads/movie_posters/' . ltrim($poster_url, '/');
                        }
                        ?>
                        <?php if(!empty($poster_url) && file_exists($poster_url)): ?>
                            <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($show['title']) ?>">
                        <?php else: ?>
                            <div class="movie-poster-fallback" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: #ff4d2d; color: #ffffff; font-size: 2.5rem; gap: 10px; border-radius: 8px;">
                                <i class="fa-solid fa-clapperboard"></i>
                                <span style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">No Poster</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-info">
                        <h3><?= htmlspecialchars($show['title']) ?></h3>
                        <p><i class="fas fa-tv"></i> <?= htmlspecialchars($show['screen_name']) ?></p>
                        <p><i class="fas fa-clock"></i> <?= date("h:i A", strtotime($show['show_time'])) ?></p>
                        <span class="movie-format"><?= htmlspecialchars($show['movie_format']) ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-film"></i>
                <h3>No Shows Scheduled Today</h3>
                <p>There are no active movie shows scheduled today.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- RECENT BOOKINGS -->
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
                    <?php $sn = 1; while($booking = mysqli_fetch_assoc($recentBookings)): ?>
                        <tr>
                            <td>#<?= $sn++; ?></td>
                            <td><?= htmlspecialchars($booking['full_name']); ?></td>
                            <td><?= htmlspecialchars($booking['title']); ?></td>
                            <td><?= $booking['total_seats']; ?></td>
                            <td>Rs. <?= number_format($booking['total_amount'], 2); ?></td>
                            <td><?= date("d M Y h:i A", strtotime($booking['booking_time'])); ?></td>
                            <td>
                                <?php $status_cls = 'status-' . strtolower($booking['booking_status']); ?>
                                <span class="status-badge <?= $status_cls; ?>">
                                    <?= str_replace('_', ' ', $booking['booking_status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No bookings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Initialize Charts -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Booking Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendLabels = <?php echo json_encode($trend_labels); ?>;
    const trendData = <?php echo json_encode($trend_data); ?>;

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Seats Booked',
                data: trendData,
                borderColor: '#ff4d2d',
                backgroundColor: 'rgba(255, 77, 45, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ff4d2d',
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1200,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw + ' Seats';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 2. Top 5 Movies Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const topMovies = <?php echo json_encode($top_movies); ?>;
        const topRevenues = <?php echo json_encode($top_revenues); ?>;
        const colors = ['#ff4d2d', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b'];

        // Build Legend Index dynamically
        const legendContainer = document.getElementById('revenue-legend-container');
        if (legendContainer) {
            topMovies.forEach((movie, index) => {
                const color = colors[index % colors.length];
                const item = document.createElement('div');
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.gap = '6px';
                item.innerHTML = `<span style="display:inline-block; width:12px; height:12px; background:${color}; border-radius:3px;"></span> <strong>${movie}</strong>`;
                legendContainer.appendChild(item);
            });
        }

        new Chart(revenueCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: topMovies,
                datasets: [{
                    label: 'Revenue',
                    data: topRevenues,
                    backgroundColor: colors.slice(0, topMovies.length),
                    borderRadius: 8,
                    barThickness: 20
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rs. ' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rs. ' + value;
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>

</body>
</html>