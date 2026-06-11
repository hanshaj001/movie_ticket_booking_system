<?php
require_once 'db_connect.php';

$id=$_GET['id'];

$result=mysqli_query(
$conn,
"SELECT * FROM movies WHERE movie_id=$id"
);

$row=mysqli_fetch_assoc($result);
?>
<form action="update_movie.php"
method="POST">

<input type="hidden"
name="id"
value="<?php echo $row['movie_id'];?>">

<input type="text"
name="title"
value="<?php echo $row['title'];?>">

<textarea
name="description"><?php echo $row['description'];?>
</textarea>

<input type="number"
name="duration"
value="<?php echo $row['duration_minutes'];?>">

<select name="format">

<option <?php if($row['movie_format']=="2D") echo "selected"; ?>>
2D
</option>

<option <?php if($row['movie_format']=="3D") echo "selected"; ?>>
3D
</option>

</select>

<button class="btn">
Update Movie
</button>

</form>