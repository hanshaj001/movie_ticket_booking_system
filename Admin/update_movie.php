<?php
session_start();
require_once '../Includes/db_conn.php';

// Allow only POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: add_movie.php");
    exit();
}

$id = intval($_POST['id']);
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$duration = trim($_POST['duration']);
$format = trim($_POST['format']);
$language = trim($_POST['language']);
$release_date = trim($_POST['release_date']);
$genre = isset($_POST['genre']) ? (is_array($_POST['genre']) ? implode(', ', $_POST['genre']) : trim($_POST['genre'])) : '';

$errors = [];

// Title validation
if (empty($title)) {
    $errors['title'] = "Movie title is required.";
} elseif (strlen($title) < 2) {
    $errors['title'] = "Movie title must contain at least 2 characters.";
} elseif (strlen($title) > 150) {
    $errors['title'] = "Movie title cannot exceed 150 characters.";
}

// Description validation
if (empty($description)) {
    $errors['description'] = "Description is required.";
} elseif (strlen($description) < 20) {
    $errors['description'] = "Description must contain at least 20 characters.";
}

// Duration validation
if (empty($duration)) {
    $errors['duration'] = "Duration is required.";
} elseif (!filter_var($duration, FILTER_VALIDATE_INT)) {
    $errors['duration'] = "Duration must be numeric.";
} elseif ($duration < 30) {
    $errors['duration'] = "Movie duration must be at least 30 minutes.";
} elseif ($duration > 500) {
    $errors['duration'] = "Invalid movie duration.";
}

// Genre validation
if (empty($genre)) {
    $errors['genre'] = "Genre is required.";
}

// Language validation
if (empty($language)) {
    $errors['language'] = "Language is required.";
}

// Format validation
if ($format != '2D' && $format != '3D') {
    $errors['movie_format'] = "Please select a valid movie format.";
}

// Release date validation
if (!empty($release_date)) {
    if (strtotime($release_date) > strtotime('+10 years')) {
        $errors['release_date'] = "Invalid release date.";
    }
}

// Duplicate title prevention check (excluding current movie ID)
if (empty($errors['title'])) {
    $checkMovie = $conn->prepare("SELECT movie_id FROM movies WHERE title = ? AND movie_id != ?");
    $checkMovie->bind_param("si", $title, $id);
    $checkMovie->execute();
    $result = $checkMovie->get_result();
    if ($result->num_rows > 0) {
        $errors['title'] = "Movie title already exists.";
    }
    $checkMovie->close();
}

// Get existing images from database to use as defaults or clean up on new upload
$stmt = $conn->prepare("SELECT poster_url, banner_url FROM movies WHERE movie_id = ?");
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

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

// Validate Poster image if uploaded
if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
    $file_type = mime_content_type($_FILES['poster']['tmp_name']);
    $file_size = $_FILES['poster']['size'];

    if (!in_array($file_type, $allowed_types)) {
        $errors['poster'] = "Only JPG, PNG and WEBP images are allowed for the poster.";
    }
    if ($file_size > 3145728) {
        $errors['poster'] = "Poster size must not exceed 3MB.";
    }
}

// Validate Banner image if uploaded
if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
    $file_type = mime_content_type($_FILES['banner']['tmp_name']);
    $file_size = $_FILES['banner']['size'];

    if (!in_array($file_type, $allowed_types)) {
        $errors['banner'] = "Only JPG, PNG and WEBP images are allowed for the banner.";
    }
    if ($file_size > 3145728) {
        $errors['banner'] = "Banner size must not exceed 3MB.";
    }
}

// If there are errors, redirect back to edit_movie.php with errors and form data
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header("Location: edit_movie.php?id=" . $id);
    exit();
}

// Process poster upload if valid
if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
    if (!empty($poster_name) && file_exists("../Assets/uploads/movie_posters/" . $poster_name)) {
        unlink("../Assets/uploads/movie_posters/" . $poster_name);
    }
    $extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
    $poster_name = time() . "_poster_" . uniqid() . "." . $extension;
    move_uploaded_file($_FILES['poster']['tmp_name'], "../Assets/uploads/movie_posters/" . $poster_name);
}

// Process banner upload if valid
if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
    if (!empty($banner_name) && file_exists("../Assets/uploads/movie_banners/" . $banner_name)) {
        unlink("../Assets/uploads/movie_banners/" . $banner_name);
    }
    $extension = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
    $banner_name = time() . "_banner_" . uniqid() . "." . $extension;
    move_uploaded_file($_FILES['banner']['tmp_name'], "../Assets/uploads/movie_banners/" . $banner_name);
}

// Update movie
$stmt = $conn->prepare("UPDATE `movies` SET `title`=?, `description`=?, `duration_minutes`=?, `language`=?, `release_date`=?, `movie_format`=?, `poster_url`=?, `banner_url`=? WHERE `movie_id`=?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$duration_int = intval($duration);
$stmt->bind_param(
    "ssisssssi",
    $title,
    $description,
    $duration_int,
    $language,
    $release_date,
    $format,
    $poster_name,
    $banner_name,
    $id
);

if ($stmt->execute()) {
    // Sync genres in movie_genres bridge table
    $del_stmt = $conn->prepare("DELETE FROM movie_genres WHERE movie_id = ?");
    $del_stmt->bind_param("i", $id);
    $del_stmt->execute();
    $del_stmt->close();
    
    if (isset($_POST['genre']) && is_array($_POST['genre'])) {
        foreach ($_POST['genre'] as $genre_name) {
            $genre_stmt = $conn->prepare("SELECT genre_id FROM genres WHERE genre_name = ?");
            $genre_stmt->bind_param("s", $genre_name);
            $genre_stmt->execute();
            $genre_res = $genre_stmt->get_result();
            if ($genre_row = $genre_res->fetch_assoc()) {
                $genre_id = $genre_row['genre_id'];
                $bridge_stmt = $conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                $bridge_stmt->bind_param("ii", $id, $genre_id);
                $bridge_stmt->execute();
                $bridge_stmt->close();
            }
            $genre_stmt->close();
        }
    }

    $_SESSION['success_message'] = "Movie updated successfully.";
    header("Location: add_movie.php");
    exit();
} else {
    echo "Failed to update movie.";
}

$stmt->close();
$conn->close();
?>