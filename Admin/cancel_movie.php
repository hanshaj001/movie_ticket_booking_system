<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die("CSRF Token Validation Failed.");
}

require_once '../Includes/db_conn.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid movie ID.";
    header("Location: add_movie.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch movie details
$stmt = $conn->prepare("SELECT title, status FROM movies WHERE movie_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "Movie not found.";
    header("Location: add_movie.php");
    exit();
}

$movie = $result->fetch_assoc();
$movie_title = $movie['title'];
$stmt->close();

// Check if already inactive
if ($movie['status'] === 'INACTIVE') {
    $_SESSION['error_message'] = "\"$movie_title\" is already inactive.";
    header("Location: add_movie.php");
    exit();
}

// Check for active shows linked to this movie
$show_check = $conn->prepare("SELECT COUNT(*) as count FROM shows WHERE movie_id = ? AND show_status = 'ACTIVE'");
$show_check->bind_param("i", $id);
$show_check->execute();
$active_shows = $show_check->get_result()->fetch_assoc()['count'];
$show_check->close();

if ($active_shows > 0) {
    $_SESSION['error_message'] = "Cannot cancel \"$movie_title\" because it has $active_shows active show(s) scheduled. Please cancel those shows first.";
    header("Location: add_movie.php");
    exit();
}

// Soft delete — change status to INACTIVE
$update = $conn->prepare("UPDATE movies SET status = 'INACTIVE' WHERE movie_id = ?");
$update->bind_param("i", $id);

if ($update->execute()) {
    $_SESSION['success_message'] = "\"$movie_title\" has been cancelled and set to inactive.";
} else {
    $_SESSION['error_message'] = "Failed to cancel movie. Please try again.";
}
$update->close();

header("Location: add_movie.php");
exit();

?>