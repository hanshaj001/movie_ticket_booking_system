<?php

require_once '../Includes/db_conn.php';

// Allow only POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: add_movie.php");
    exit();
}

// Get form data
$id = intval($_POST['id']);
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$duration = intval($_POST['duration']);
$format = trim($_POST['format']);
$language = trim($_POST['language']);
$release_date = $_POST['release_date'];

$genre = isset($_POST['genre']) ? implode(', ', $_POST['genre']) : "";

// Get existing images
$stmt = $conn->prepare("SELECT poster_url,banner_url FROM movies WHERE movie_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Movie not found.");
}

$movie = $result->fetch_assoc();
$stmt->close();

$poster_name = $movie['poster_url'];
$banner_name = $movie['banner_url'];

// Allowed image types
$allowed_types = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/webp'
];

// Update poster
if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {

    $file_type = mime_content_type($_FILES['poster']['tmp_name']);

    if (in_array($file_type, $allowed_types)) {

        if (!empty($poster_name) && file_exists("../Assets/uploads/movie_posters/" . $poster_name)) {
            unlink("../Assets/uploads/movie_posters/" . $poster_name);
        }

        $extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));

        $poster_name = time() . "_poster_" . uniqid() . "." . $extension;

        move_uploaded_file(
            $_FILES['poster']['tmp_name'],
            "../Assets/uploads/movie_posters/" . $poster_name
        );
    }
}

// Update banner
if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {

    $file_type = mime_content_type($_FILES['banner']['tmp_name']);

    if (in_array($file_type, $allowed_types)) {

        if (!empty($banner_name) && file_exists("../Assets/uploads/movie_banners/" . $banner_name)) {
            unlink("../Assets/uploads/movie_banners/" . $banner_name);
        }

        $extension = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));

        $banner_name = time() . "_banner_" . uniqid() . "." . $extension;

        move_uploaded_file(
            $_FILES['banner']['tmp_name'],
            "../Assets/uploads/movie_banners/" . $banner_name
        );
    }
}

// Update movie
$stmt = $conn->prepare("UPDATE `movies` SET `title`=?, `description`=?, `duration_minutes`=?, `genre`=?, `language`=?, `release_date`=?, `movie_format`=?, `poster_url`=?, `banner_url`=? WHERE `movie_id`=?");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "ssissssssi",
    $title,
    $description,
    $duration,
    $genre,
    $language,
    $release_date,
    $format,
    $poster_name,
    $banner_name,
    $id
);

if ($stmt->execute()) {

    header("Location: add_movie.php");
    exit();

} else {

    echo "Failed to update movie.";

}

$stmt->close();
$conn->close();

?>