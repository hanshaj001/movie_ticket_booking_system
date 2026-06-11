<?php

require_once 'db_connect.php';

$id=$_GET['id'];

mysqli_query(
$conn,
"UPDATE movies
SET status='CANCELLED'
WHERE movie_id=$id"
);

header("Location:manage_movies.php");

?>