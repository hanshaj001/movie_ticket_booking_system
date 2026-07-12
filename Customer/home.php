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

/* Date Validation */
$selected_date = date('Y-m-d');

if(isset($_GET['date']))
{
    $input_date = $_GET['date'];

    if(
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_date)
        &&
        strtotime($input_date)
    )
    {
        $selected_date = $input_date;
    }
}

/* Movies Query */
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
GROUP BY
    m.movie_id,
    m.title,
    m.genre,
    m.language,
    m.duration_minutes,
    m.movie_format,
    m.poster_url
ORDER BY m.title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$selected_date);
$stmt->execute();
$result = $stmt->get_result();

/* Heading */
if($selected_date == date('Y-m-d'))
{
    $date_heading = "Showing Movies for Today";
}
else
{
    $date_heading =
    "Showing Movies for "
    . date('d F Y',strtotime($selected_date));
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Home</title>

<link rel="stylesheet"
href="../Assets/Customer/homepage.css">

</head>

<body>

<!-- HERO SECTION -->

<section class="hero">

<div class="hero-content">

<h1>
Welcome to Movie Ticket Booking System
</h1>

<p>
Book Your Favorite Movies Anytime
</p>

<a href="#movies" class="book-btn">
Book Now
</a>

</div>

</section>

<!-- DATE FILTER -->

<section class="date-filter">

<?php
for($i=0;$i<7;$i++)
{
    $date =
    date(
        'Y-m-d',
        strtotime("+$i day")
    );

    $label =
    date(
        'd M',
        strtotime($date)
    );

    if($i==0)
    {
        $label='Today';
    }

    if($i==1)
    {
        $label='Tomorrow';
    }
?>

<a
href="homepage.php?date=<?=$date?>"
class="<?=($selected_date==$date)?'active':'';?>">

<?=$label?>

</a>

<?php } ?>

</section>

<!-- MOVIES -->

<section
class="movies-section"
id="movies">

<h2><?=$date_heading?></h2>

<div class="movie-grid">

<?php if($result->num_rows > 0){ ?>

<?php while($movie=$result->fetch_assoc()){ ?>

<?php

$image =
!empty($movie['poster_url'])
?
"../uploads/".$movie['poster_url']
:
"../uploads/default_movie.jpg";

?>

<a
class="movie-card-link"
href="movie_details.php?movie_id=<?=$movie['movie_id']?>&date=<?=$selected_date?>">

<div class="movie-card">

<img
src="<?=$image?>"
alt="<?=$movie['title']?>"
onerror="
this.src='../uploads/default_movie.jpg';
">

<div class="movie-info">

<h3>
<?=$movie['title']?>
</h3>

<p>
<strong>Genre:</strong>
<?=$movie['genre']?>
</p>

<p>
<strong>Language:</strong>
<?=$movie['language']?>
</p>

<p>
<strong>Duration:</strong>
<?=$movie['duration_minutes']?> mins
</p>

<p>
<strong>Format:</strong>
<?=$movie['movie_format']?>
</p>

<p>
<strong>First Show:</strong>
<?=date(
'h:i A',
strtotime($movie['earliest_show'])
);?>
</p>

<span class="details-btn">
View Details
</span>

</div>

</div>

</a>

<?php } ?>

<?php } else { ?>

<div class="empty-state">

<img
src="../uploads/default_movie.jpg"
alt="No Movies">

<h3>
No Movies Available
</h3>

<p>
There are currently no movies
scheduled for the selected date.
</p>

</div>

<?php } ?>

</div>

</section>

</body>
</html>