<?php

require_once '../Includes/db_conn.php';

$id=$_POST['id'];
$title=$_POST['title'];
$description=$_POST['description'];
$duration=$_POST['duration'];
$format=$_POST['format'];
$language = $_POST['language'] ?? '';
$release_date = $_POST['release_date'] ?? '';

// Handle genre array
$genre = isset($_POST['genre']) ? (is_array($_POST['genre']) ? implode(', ', $_POST['genre']) : trim($_POST['genre'])) : '';

mysqli_query(
$conn,
"UPDATE movies
SET
title='$title',
description='$description',
duration_minutes='$duration',
movie_format='$format',
genre='$genre',
language='$language',
release_date='$release_date'
WHERE movie_id=$id"
);

header("Location:add_movie.php");

?>