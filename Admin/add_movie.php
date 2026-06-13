<?php 
require_once '../Includes/db_conn.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>Add Movie</title>
<link rel="stylesheet" href="add_movie.css">
</head>
<body>

<div class="container">

<div class="card">

<h2>Add Movie</h2>

<form action="add_movie_process.php"
method="POST"
enctype="multipart/form-data">

<input type="text"
name="title"
placeholder="Movie Title"
required>

<textarea
name="description"
placeholder="Movie Description"
required></textarea>

<input type="number"
name="duration"
placeholder="Duration (Minutes)"
required>

<select name="format" required>
<option value="">Movie Format</option>
<option value="2D">2D</option>
<option value="3D">3D</option>
</select>

<input type="date"
name="release_date">

<label>Poster</label>
<input type="file"
name="poster"
required>

<div class="buttons">

<button class="btn">
Add Movie
</button>

<button type="reset"
class="btn-reset">
Reset
</button>

</div>

</form>

</div>

</div>

</body>
</html>
<?php
require_once '../Includes/db_conn.php';

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