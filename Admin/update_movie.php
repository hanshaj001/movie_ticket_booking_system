<?php

require_once '../Includes/db_conn.php';
include '../Includes/navbar.php';

$id=$_POST['id'];
$title=$_POST['title'];
$description=$_POST['description'];
$duration=$_POST['duration'];
$format=$_POST['format'];

mysqli_query(
$conn,
"UPDATE movies
SET
title='$title',
description='$description',
duration_minutes='$duration',
movie_format='$format'
WHERE movie_id=$id"
);

header("Location:add_movie.php");

?>