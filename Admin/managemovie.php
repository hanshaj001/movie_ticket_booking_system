<?php
require_once 'db_connect.php';

$result=mysqli_query(
$conn,
"SELECT * FROM movies ORDER BY created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Movies</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="table-card">

<h2>Manage Movies</h2>

<a href="add_movie.php" class="add-btn">
+ Add Movie
</a>

<table>

<tr>
<th>Poster</th>
<th>Movie Title</th>
<th>Duration</th>
<th>Format</th>
<th>Status</th>
<th>Actions</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td>
<img class="poster"
src="uploads/<?php echo $row['poster_url']; ?>">
</td>

<td>
<?php echo $row['title']; ?>
</td>

<td>
<?php echo $row['duration_minutes']; ?> Min
</td>

<td>
<?php echo $row['movie_format']; ?>
</td>

<td>
<?php echo $row['status']; ?>
</td>

<td>

<a class="view"
href="view_movie.php?id=<?php echo $row['movie_id']; ?>">
View
</a>

<a class="edit"
href="edit_movie.php?id=<?php echo $row['movie_id']; ?>">
Edit
</a>

<a class="cancel"
onclick="return confirm('Cancel this movie?')"
href="cancel_movie.php?id=<?php echo $row['movie_id']; ?>">
Cancel
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>