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
require_once '../Includes/db_conn.php'; 

/* Authentication */

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

/* Dashboard Statistics */

$totalMovies = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM movies
        WHERE status='ACTIVE'
    ")
)['total'] ?? 0;

$totalShows = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM shows
        WHERE show_status='ACTIVE'
    ")
)['total'] ?? 0;

$todayBookings = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM bookings
        WHERE DATE(booking_time)=CURDATE()
        AND booking_status='CONFIRMED'
    ")
)['total'] ?? 0;

$availableSeats = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM show_seats
        WHERE seat_status='AVAILABLE'
    ")
)['total'] ?? 0;

$soldSeats = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM show_seats
        WHERE seat_status='SOLD'
    ")
)['total'] ?? 0;


/* Running Movies */

$movies = mysqli_query($conn,"
SELECT
    m.title,
    m.poster_url,
    m.movie_format,
    GROUP_CONCAT(
        TIME_FORMAT(s.show_time,'%h:%i %p')
        SEPARATOR ', '
    ) timings
FROM movies m
INNER JOIN shows s
ON m.movie_id = s.movie_id
WHERE m.status='ACTIVE'
AND s.show_status='ACTIVE'
GROUP BY
    m.movie_id,
    m.title,
    m.poster_url,
    m.movie_format
ORDER BY m.title
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Admin Dashboard</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#f4f6fb;
    padding:30px;
}

/* Title */

.page-title{
    font-size:34px;
    font-weight:700;
    color:#1e293b;
    margin-bottom:35px;
}

/* Statistics Cards */

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:45px;
}

.card{
    background:#ffffff;
    padding:25px;
    border-radius:18px;
    text-align:center;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
    transition:.3s;
}

.card:hover{
    transform:translateY(-8px);
}

.card i{
    width:70px;
    height:70px;
    line-height:70px;
    border-radius:50%;
    color:#fff;
    font-size:28px;
    margin-bottom:15px;
}

.movies-icon{
    background:#7c3aed;
}

.shows-icon{
    background:#10b981;
}

.booking-icon{
    background:#f97316;
}

.available-icon{
    background:#2563eb;
}

.sold-icon{
    background:#ef4444;
}

.card-info h2{
    font-size:38px;
    color:#111827;
    margin-bottom:8px;
}

.card-info p{
    color:#6b7280;
    font-size:15px;
}

/* Movies Section */

.section-title{
    font-size:28px;
    font-weight:700;
    color:#1e293b;
    margin-bottom:25px;
}

.movies{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:25px;
}

.movie-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
    transition:.3s;
}

.movie-card:hover{
    transform:translateY(-8px);
}

.movie-card img{
    width:100%;
    height:350px;
    object-fit:cover;
}

.movie-content{
    padding:20px;
}

.movie-content h3{
    color:#111827;
    margin-bottom:12px;
    font-size:22px;
}

.movie-content p{
    color:#64748b;
    margin-bottom:12px;
    line-height:1.6;
}

.format{
    display:inline-block;
    padding:8px 16px;
    border-radius:30px;
    background:#2563eb;
    color:#fff;
    font-size:12px;
    font-weight:600;
}

.empty{
    background:#fff;
    border-radius:15px;
    padding:40px;
    text-align:center;
    color:#64748b;
    font-size:18px;
}

/* Responsive */

@media(max-width:768px){

    body{
        padding:15px;
    }

    .page-title{
        font-size:28px;
    }

    .card-info h2{
        font-size:30px;
    }

    .movie-card img{
        height:280px;
    }
}




</style>
</head>

<body>

<h1 class="page-title">
    <i class="fa-solid fa-chart-line"></i>
    Admin Dashboard
</h1>

<!-- Statistics -->

<div class="stats">

    <div class="card">
        <i class="fa-solid fa-film movies-icon"></i>
        <div class="card-info">
            <h2><?= $totalMovies ?></h2>
            <p>Total Movies</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-video shows-icon"></i>
        <div class="card-info">
            <h2><?= $totalShows ?></h2>
            <p>Total Shows</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-ticket booking-icon"></i>
        <div class="card-info">
            <h2><?= $todayBookings ?></h2>
            <p>Today's Bookings</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-couch available-icon"></i>
        <div class="card-info">
            <h2><?= $availableSeats ?></h2>
            <p>Available Seats</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-chair sold-icon"></i>
        <div class="card-info">
            <h2><?= $soldSeats ?></h2>
            <p>Sold Seats</p>
        </div>
    </div>

</div>

<!-- Running Movies -->

<h2 class="section-title">
    <i class="fa-solid fa-clapperboard"></i>
    Running Movies
</h2>

<div class="movies">

<?php if(mysqli_num_rows($movies)>0){ ?>

<?php while($movie=mysqli_fetch_assoc($movies)){ ?>

<div class="movie-card">

    <img src="<?= $movie['poster_url']; ?>"
         alt="<?= $movie['title']; ?>">

    <div class="movie-content">

        <h3><?= $movie['title']; ?></h3>

        <p>
            <strong>Show Timings:</strong><br>
            <?= $movie['timings']; ?>
        </p>

        <span class="format">
            <?= $movie['movie_format']; ?>
        </span>

    </div>

</div>

<?php } ?>

<?php } else { ?>

<div class="empty">
    No Active Movies Available
</div>

<?php } ?>

</div>

<script>

setInterval(function(){
    location.reload();
},60000);

</script>

</body>
</html>