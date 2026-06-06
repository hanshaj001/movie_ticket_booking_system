<?php

require_once '../Includes/db_conn.php';

if(isset($_GET['id']))
{
    $show_id = intval($_GET['id']);

    mysqli_query(
        $conn,
        "UPDATE shows
         SET show_status='CANCELLED'
         WHERE show_id='$show_id'"
    );
}

header("Location:add_show.php");
exit;