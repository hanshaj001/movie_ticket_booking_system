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


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//include 'config/db.php';

$selected_date = isset($_GET['date'])
    ? $_GET['date']
    : date('Y-m-d');

$sql = "
SELECT
    m.movie_id,
    m.title,
    m.genre,
    m.language,
    m.duration_minutes,
    m.movie_format,
    m.poster_url,
    MIN(s.show_time) AS earliest_show
FROM movies m
INNER JOIN shows s
    ON m.movie_id = s.movie_id
WHERE
    m.status='ACTIVE'
    AND s.show_status='ACTIVE'
    AND s.show_date = ?
    AND TIMESTAMP(s.show_date,s.show_time) > NOW()
GROUP BY m.movie_id
ORDER BY m.title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$selected_date);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Home</title>
<link rel="stylesheet" href="../Assets/Customer/homepage.css">


</head>
<body>

<section class="hero">

    <div class="hero-content">
        <h1>Welcome to Movie Ticket Booking System</h1>
        <p>Book Your Favorite Movies Anytime</p>

        <a href="#movies" class="book-btn">
            Book Now
        </a>
    </div>

</section>

<section class="date-filter">

<?php
for($i=0;$i<7;$i++) {

    $date = date('Y-m-d',strtotime("+$i day"));
    $label = date('d M',strtotime($date));

    if($i==0) $label='Today';
    if($i==1) $label='Tomorrow';

?>
<a
href="home.php?date=<?=$date?>"
class="<?=($selected_date==$date)?'active':'';?>">

<?=$label?>

</a>
<?php } ?>

</section>

<section class="movies-section" id="movies">

<h2>Currently Showing Movies</h2>

<div class="movie-grid">

<?php if($result->num_rows > 0){ ?>

<?php while($movie=$result->fetch_assoc()){ ?>

<div class="movie-card">

<img
src="uploads/<?=$movie['poster_url']?>"
alt="<?=$movie['title']?>">

<div class="movie-info">

<h3><?=$movie['title']?></h3>

<p><strong>Genre:</strong>
<?=$movie['genre']?></p>

<p><strong>Language:</strong>
<?=$movie['language']?></p>

<p><strong>Duration:</strong>
<?=$movie['duration_minutes']?> mins</p>

<p><strong>Format:</strong>
<?=$movie['movie_format']?></p>

<p><strong>First Show:</strong>
<?=date('h:i A',
strtotime($movie['earliest_show']))?>
</p>

<a
href="movie_details.php?movie_id=<?=$movie['movie_id']?>&date=<?=$selected_date?>"
class="details-btn">

View Details

</a>

</div>

</div>

<?php } ?>

<?php } else { ?>

<div class="no-movie">

No movies available for selected date.

</div>

<?php } ?>

</div>

</section>

</body>
</html>