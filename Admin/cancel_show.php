<?php

require_once '../Includes/db_conn.php';

$id=$_GET['id'];

mysqli_query(
$conn,
"UPDATE shows
SET show_status='CANCELLED'
WHERE show_id=$id"
);

header("Location:add_show.php");

?>