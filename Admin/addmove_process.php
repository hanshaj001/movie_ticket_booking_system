<?php
require_once 'db_connect.php';

$title=$_POST['title'];
$description=$_POST['description'];
$duration=$_POST['duration'];
$format=$_POST['format'];
$release_date=$_POST['release_date'];

$imageName=time().$_FILES['poster']['name'];

move_uploaded_file(
$_FILES['poster']['tmp_name'],
"uploads/".$imageName
);

$sql="
INSERT INTO movies
(
title,
description,
duration_minutes,
movie_format,
poster_url,
release_date
)
VALUES
(
'$title',
'$description',
'$duration',
'$format',
'$imageName',
'$release_date'
)
";

mysqli_query($conn,$sql);

header("Location:manage_movies.php");
?>