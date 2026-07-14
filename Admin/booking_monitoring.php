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
//session_start();
 require_once '../Includes/db_conn.php'; 
include '../Includes/sidebar.php';

/* Authentication */

if(!isset($_SESSION['user_id']))
{
    header("Location:../Includes/login.php");
    exit();
}

/* Search & Filters */

$search = $_GET['search'] ?? '';
$movie = $_GET['movie'] ?? '';
$date  = $_GET['date'] ?? '';

$where = " WHERE 1=1 ";

if($search != '')
{
    $where .= " AND (
        b.booking_id LIKE '%$search%' OR
        u.full_name LIKE '%$search%' OR
        m.title LIKE '%$search%'
    )";
}

if($movie != '')
{
    $where .= " AND m.movie_id='$movie'";
}

if($date != '')
{
    $where .= " AND DATE(b.booking_time)='$date'";
}

/* Pagination */

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // records per page
$offset = ($page - 1) * $limit;


$selected_date = $date;

/* Count Total Records */

$count_query = "
SELECT COUNT(DISTINCT b.booking_id) as total
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN shows sh ON b.show_id=sh.show_id
JOIN movies m ON sh.movie_id=m.movie_id
$where
";

$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);

$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);


/* Booking Records */

$query = "
SELECT
    b.booking_id,
    u.full_name,
    m.title,
    CONCAT(sh.show_date,' ',sh.show_time) show_time,
    GROUP_CONCAT(st.seat_number) seats,
    b.booking_status,
    b.booking_time
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN shows sh ON b.show_id=sh.show_id
JOIN movies m ON sh.movie_id=m.movie_id
LEFT JOIN booking_details bd ON b.booking_id=bd.booking_id
LEFT JOIN show_seats ss ON bd.show_seat_id=ss.show_seat_id
LEFT JOIN seats st ON ss.seat_id=st.seat_id
$where
GROUP BY b.booking_id
ORDER BY b.booking_time DESC
LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn,$query);

/* Movie Filter */

$movies = mysqli_query($conn,"
SELECT movie_id,title
FROM movies
WHERE status='ACTIVE'
");





?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Monitoring</title>
<link rel="stylesheet" href="../Assets/booking_monitoring.css"/>
<link rel="stylesheet" href="../Assets/sidebar.css">
</head>

<body>

<div class="container">

<h1 class="heading">
🎟 Booking Monitoring
</h1>
<form method="GET">

<div class="filters">

<input
type="text"
name="search"
placeholder="Search booking..."
value="<?php echo $search; ?>"
>

<select
    name="movie"
    required
    oninvalid="this.setCustomValidity('Please Select Movie')"
    onchange="this.setCustomValidity('')"
>
    <option value="">
       All movies
    </option>

    <?php while($m=mysqli_fetch_assoc($movies)){ ?>

    <option
        value="<?php echo $m['movie_id']; ?>"
        <?php if($movie==$m['movie_id']) echo "selected"; ?>
    >
        <?php echo $m['title']; ?>
    </option>

    <?php } ?>

</select>

<input
type="date"
name="date"
value="<?php echo $date; ?>"
>

<button type="submit" class="search-btn">
    Search
</button>

<a href="booking_monitoring.php" class="reset-btn">
    Reset
</a>

</div>

</form>

<div class="table-box">

<table>

<thead>

<tr>
<th>ID</th>
<th>Customer</th>
<th>Movie</th>
<th>Show Time</th>
<th>Seats</th>
<th>Status</th>
<th>Booking Time</th>
<th>Actions</th>
</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td>
<?php echo $row['booking_id']; ?>
</td>

<td>
<?php echo $row['full_name']; ?>
</td>

<td>
<?php echo $row['title']; ?>
</td>

<td>
<?php echo $row['show_time']; ?>
</td>

<td>
<?php echo $row['seats']; ?>
</td>

<td>

<?php

$status = strtolower($row['booking_status']);

echo "
<span class='badge $status'>
{$row['booking_status']}
</span>";

?>

</td>

<td>
<?php echo $row['booking_time']; ?>
</td>

<td>

<button
class="action-btn view-btn"
onclick="viewBooking(<?php echo $row['booking_id']; ?>)"
>
View
</button>

<?php
if($row['booking_status']=="CONFIRMED")
{
?>

<button
class="action-btn cancel-btn"
onclick="cancelBooking(<?php echo $row['booking_id']; ?>)"
>
Cancel
</button>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<div class="pagination">

    <?php if ($page > 1): ?>
        <a href="?date=<?= $selected_date ?>&page=<?= $page - 1 ?>">
            Previous
        </a>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="?date=<?= $selected_date ?>&page=<?= $p ?>"
           class="<?= ($page == $p) ? 'active' : '' ?>">
            <?= $p ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
        <a href="?date=<?= $selected_date ?>&page=<?= $page + 1 ?>">
            Next
        </a>
    <?php endif; ?>

</div>

</div>

<script>

function viewBooking(id)
{
window.location=
'booking_details.php?id='+id;
}

function cancelBooking(id)
{
if(confirm("Cancel this booking?"))
{
window.location=
'cancel_booking.php?id='+id;
}
}

setInterval(function(){

console.log("Refreshing booking data...");

},30000);

</script>

</body>
</html>