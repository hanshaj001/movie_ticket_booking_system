<?php

require_once '../Includes/db_conn.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: add_movie.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch poster and banner filenames so we can delete the files
$result = mysqli_query($conn, "SELECT poster_url, banner_url FROM movies WHERE movie_id=$id");

if ($result && mysqli_num_rows($result) > 0) {
    $movie = mysqli_fetch_assoc($result);

    // Delete poster file if it exists
    $poster_path = "../Assets/uploads/movie_posters/" . $movie['poster_url'];
    if (!empty($movie['poster_url']) && file_exists($poster_path)) {
        unlink($poster_path);
    }

    // Delete banner file if it exists
    $banner_path = "../Assets/uploads/movie_banners/" . $movie['banner_url'];
    if (!empty($movie['banner_url']) && file_exists($banner_path)) {
        unlink($banner_path);
    }

    // Delete the movie record from the database
    mysqli_query($conn, "DELETE FROM movies WHERE movie_id=$id");
}

header("Location: add_movie.php");
exit();

?>