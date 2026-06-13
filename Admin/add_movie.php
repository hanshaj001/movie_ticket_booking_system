<?php
include '../Includes/sidebar.php';
require_once '../includes/db_conn.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Includes/login.php");
    exit();
}

// Initialize variables
$title = "";
$description = "";
$duration = "";
$genre = "";
$language = "";
$movie_format = "";
$release_date = "";
$errors = [];
$success = "";

// Form submission validation & handling
if (isset($_POST['add_movie'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration = trim($_POST['duration']);
    $genre = trim($_POST['genre']);
    $language = trim($_POST['language']);
    $movie_format = trim($_POST['movie_format']);
    $release_date = trim($_POST['release_date']);

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
    if ($movie_format != '2D' && $movie_format != '3D') {
        $errors['movie_format'] = "Please select a valid movie format.";
    }

    // Release date validation
    if (!empty($release_date)) {
        if (strtotime($release_date) > strtotime('+10 years')) {
            $errors['release_date'] = "Invalid release date.";
        }
    }

    // Duplicate records prevention check
    if (empty($errors['title'])) {
        $checkMovie = $conn->prepare("SELECT movie_id FROM movies WHERE title = ?");
        $checkMovie->bind_param("s", $title);
        $checkMovie->execute();
        $result = $checkMovie->get_result();

        if ($result->num_rows > 0) {
            $errors['title'] = "Movie title already exists.";
        }
    }

    // File binary upload validation
    $poster_name = "";
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = mime_content_type($_FILES['poster']['tmp_name']);
        $file_size = $_FILES['poster']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors['poster'] = "Only JPG, PNG and WEBP images are allowed.";
        }
        if ($file_size > 2097152) {
            $errors['poster'] = "Poster size must not exceed 2MB.";
        }
    } else {
        $errors['poster'] = "Movie poster is required.";
    }

    // Process image move and insert into database
    if (empty($errors)) {
        $extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
        $poster_name = time() . "_" . uniqid() . "." . $extension;
        $upload_path = "../uploads/movie_posters/" . $poster_name;

        if (move_uploaded_file($_FILES['poster']['tmp_name'], $upload_path)) {
            $stmt = $conn->prepare("INSERT INTO movies (title, description, duration_minutes, genre, language, release_date, movie_format, poster_url, status) VALUES (?,?,?,?,?,?,?,?,'ACTIVE')");
            $stmt->bind_param("ssisssss", $title, $description, $duration, $genre, $language, $release_date, $movie_format, $poster_name);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Movie added successfully.";
                header("Location: add_movie.php");
                exit();
            } else {
                $errors['general'] = "Failed to add movie.";
            }
        } else {
            $errors['poster'] = "Failed to save uploaded image file.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Movie - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/add_movie.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
    <div class="page-title">
        <div class="title-icon">
            <i class="fas fa-film"></i>
        </div>
        <div>
            <h1>Add Movie</h1>
            <p>Create and manage movie records</p>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])) : ?>
    <div class="message">
        <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message']; ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($errors['general'])) : ?>
    <div class="message error-msg">
        <i class="fas fa-exclamation-circle"></i> <?= $errors['general']; ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>Movie Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($title); ?>" placeholder="Enter movie title">
                <span class="error"><?= $errors['title'] ?? ''; ?></span>
            </div>

            <div class="form-group">
                <label>Duration (Minutes)</label>
                <input type="number" name="duration" value="<?= htmlspecialchars($duration); ?>" placeholder="120">
                <span class="error"><?= $errors['duration'] ?? ''; ?></span>
            </div>

            <div class="form-group">
                <label>Genre</label>
                <input type="text" name="genre" value="<?= htmlspecialchars($genre); ?>" placeholder="Action, Drama, Comedy">
                <span class="error"><?= $errors['genre'] ?? ''; ?></span>
            </div>

            <div class="form-group">
                <label>Language</label>
                <input type="text" name="language" value="<?= htmlspecialchars($language); ?>" placeholder="English">
                <span class="error"><?= $errors['language'] ?? ''; ?></span>
            </div>

            <div class="form-group">
                <label>Movie Format</label>
                <select name="movie_format">
                    <option value="">Select Format</option>
                    <option value="2D" <?= ($movie_format == '2D') ? 'selected' : ''; ?>>2D</option>
                    <option value="3D" <?= ($movie_format == '3D') ? 'selected' : ''; ?>>3D</option>
                </select>
                <span class="error"><?= $errors['movie_format'] ?? ''; ?></span>
            </div>

            <div class="form-group">
                <label>Release Date</label>
                <input type="date" name="release_date" value="<?= htmlspecialchars($release_date); ?>">
                <span class="error"><?= $errors['release_date'] ?? ''; ?></span>
            </div>

            <div class="form-group full-width">
                <label>Movie Poster</label>
                <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">
                <span class="error"><?= $errors['poster'] ?? ''; ?></span>
            </div>

            <div class="form-group full-width">
                <label>Description</label>
                <textarea name="description" rows="6" placeholder="Enter movie description..."><?= htmlspecialchars($description); ?></textarea>
                <span class="error"><?= $errors['description'] ?? ''; ?></span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="add_movie" class="submit-btn">Add Movie</button>
            <button type="reset" class="reset-btn">Reset</button>
        </div>
    </form>
</div>

<?php $movieQuery = $conn->query("SELECT * FROM movies ORDER BY created_at DESC"); ?>

<div class="show-list-header">
    <div class="show-list-title">
        <i class="fas fa-video"></i>
        <div>
            <h2>Movie List</h2>
            <p>Recently added movies</p>
        </div>
    </div>
</div>

<div class="movie-grid">
<?php if ($movieQuery->num_rows > 0) : ?>
    <?php while ($movie = $movieQuery->fetch_assoc()) : ?>
        <div class="movie-card">
            <div class="movie-poster">
                <img src="../uploads/movie_posters/<?= htmlspecialchars($movie['poster_url']); ?>" alt="<?= htmlspecialchars($movie['title']); ?>">
            </div>

            <div class="movie-content">
                <div class="movie-top">
                    <h3><?= htmlspecialchars($movie['title']); ?></h3>
                    <span class="status active"><?= htmlspecialchars($movie['status']); ?></span>
                </div>

                <div class="movie-meta">
                    <span><i class="fas fa-theater-masks"></i> &nbsp; <?= htmlspecialchars($movie['genre']); ?></span>
                    <span><i class="fas fa-globe"></i> &nbsp; <?= htmlspecialchars($movie['language']); ?></span>
                </div>

                <div class="movie-meta">
                    <span><i class="fas fa-clock"></i> &nbsp; <?= $movie['duration_minutes']; ?> Min</span>
                    <span><i class="fas fa-clapperboard"></i> &nbsp; <?= $movie['movie_format']; ?></span>
                </div>

                <div class="movie-meta">
                    <span><i class="fas fa-calendar-alt"></i> &nbsp; <?= date("d M Y", strtotime($movie['release_date'])); ?></span>
                </div>

                <p class="movie-description">
                    <?= substr(htmlspecialchars($movie['description']), 0, 140); ?>...
                </p>

                <div class="action-buttons">
                    <a href="view_movie.php?id=<?= $movie['movie_id']; ?>" class="view-btn">View</a>
                    <a href="edit_movie.php?id=<?= $movie['movie_id']; ?>" class="edit-btn">Edit</a>
                    <a href="delete_movie.php?id=<?= $movie['movie_id']; ?>" class="cancel-btn" onclick="return confirm('Delete this movie?');">Delete</a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php else : ?>
    <div class="no-data">No movies available.</div>
<?php endif; ?>
</div>

    </div>
</div>
</body>
</html>