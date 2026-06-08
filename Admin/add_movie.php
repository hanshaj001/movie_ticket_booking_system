<?php require_once 'db_connect.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>Add Movie</title>
<link rel="stylesheet" href="style.css">
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