<?php

require_once '../Includes/db_conn.php';

$id=$_GET['id'];

mysqli_query(
$conn,
"UPDATE movies
SET status='CANCELLED'
WHERE movie_id=$id"
);

header("Location:add_movies.php");

?>