<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration = trim($_POST['duration']);
    $genre = isset($_POST['genre']) ? (is_array($_POST['genre']) ? implode(', ', $_POST['genre']) : trim($_POST['genre'])) : '';
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
    $banner_name = "";
            
    // Poster validation
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = mime_content_type($_FILES['poster']['tmp_name']);
        $file_size = $_FILES['poster']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors['poster'] = "Only JPG, PNG and WEBP images are allowed for the poster.";
        }
        if ($file_size > 3145728) {
            $errors['poster'] = "Poster size must not exceed 3MB.";
        }
    } else {
        $errors['poster'] = "Movie poster is required.";
    }

    // Banner validation
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = mime_content_type($_FILES['banner']['tmp_name']);
        $file_size = $_FILES['banner']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors['banner'] = "Only JPG, PNG and WEBP images are allowed.";
        }
        if ($file_size > 3145728) {
            $errors['banner'] = "Banner size must not exceed 3MB.";
        }
    } else {
        $errors['banner'] = "Movie banner is required.";
    }

    // Process image move and insert into database
    if (empty($errors)) {
        $poster_extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
        $banner_extension = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $poster_name = time() . "_poster_" . uniqid() . "." . $poster_extension;
        $banner_name = time() . "_banner_" . uniqid() . "." . $banner_extension;
        $poster_path = "../Assets/uploads/movie_posters/" . $poster_name;
        $banner_path = "../Assets/uploads/movie_banners/" . $banner_name;
        
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $poster_path) &&
            move_uploaded_file($_FILES['banner']['tmp_name'], $banner_path)) {
            
            $stmt = $conn->prepare("INSERT INTO movies(title,description,duration_minutes,language,release_date,movie_format,poster_url,banner_url,status) VALUES(?,?,?,?,?,?,?,?,'ACTIVE')");
            $stmt->bind_param(
                "ssisssss",
                $title,
                $description,
                $duration,
                $language,
                $release_date,
                $movie_format,
                $poster_name,
                $banner_name
            );
            
            if ($stmt->execute()) {
                $new_movie_id = $stmt->insert_id;
                
                // Insert selected genres into movie_genres bridge table
                if (isset($_POST['genre']) && is_array($_POST['genre'])) {
                    foreach ($_POST['genre'] as $genre_name) {
                        $genre_stmt = $conn->prepare("SELECT genre_id FROM genres WHERE genre_name = ?");
                        $genre_stmt->bind_param("s", $genre_name);
                        $genre_stmt->execute();
                        $genre_res = $genre_stmt->get_result();
                        if ($genre_row = $genre_res->fetch_assoc()) {
                            $genre_id = $genre_row['genre_id'];
                            $bridge_stmt = $conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                            $bridge_stmt->bind_param("ii", $new_movie_id, $genre_id);
                            $bridge_stmt->execute();
                            $bridge_stmt->close();
                        }
                        $genre_stmt->close();
                    }
                }

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
    <link rel="stylesheet" href="../Assets/css/Admin/add_movie.css">
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
    <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success_message']) ?>, 'success'));</script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])) : ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error_message']) ?>, 'error'));</script>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($errors['general'])) : ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($errors['general']) ?>, 'error'));</script>
<?php endif; ?>

<div class="form-card">
    <form method="POST" enctype="multipart/form-data" data-loader-msg="Adding movie and uploading files. Please wait...">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
                <div style="display: flex; gap: 15px; flex-wrap: wrap; padding: 10px 0;">
                    <?php
                    $genres_query = $conn->query("SELECT genre_name FROM genres ORDER BY genre_name ASC");
                    $available_genres = [];
                    if($genres_query) {
                        while($g_row = $genres_query->fetch_assoc()) {
                            $available_genres[] = $g_row['genre_name'];
                        }
                    }
                    
                    if(empty($available_genres)) {
                        echo "<span style='color:#777; font-size:14px;'>No genres available. Please add them in Manage Genres.</span>";
                    }
                    $selected_genres = array_map('trim', explode(',', $genre));
                    foreach ($available_genres as $g) {
                        $checked = in_array($g, $selected_genres) ? 'checked' : '';
                        echo "<label style='font-weight: normal; display: flex; align-items: center; gap: 5px;'>
                                <input type='checkbox' name='genre[]' value='$g' class='genre-checkbox' $checked> $g
                              </label>";
                    }
                    ?>
                </div>
                <div style="margin-top: 10px; font-style: italic; color: #666;">
                    Selected: <span id="selected-genres-display">None</span>
                </div>
                <span class="error"><?= $errors['genre'] ?? ''; ?></span>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const checkboxes = document.querySelectorAll('.genre-checkbox');
                    const display = document.getElementById('selected-genres-display');

                    function updateDisplay() {
                        const selected = Array.from(checkboxes)
                            .filter(cb => cb.checked)
                            .map(cb => cb.value);
                        display.textContent = selected.length > 0 ? selected.join(', ') : 'None';
                    }

                    checkboxes.forEach(cb => cb.addEventListener('change', updateDisplay));
                    updateDisplay();
                });
            </script>

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
                <label>Hero Banner (16:9)</label>
                <input type="file"
                    name="banner"
                    accept=".jpg,.jpeg,.png,.webp">
                <small>
                    Recommended size: 1920 × 1080 or any 16:9 image.
                </small>
                <span class="error"><?= $errors['banner'] ?? ''; ?></span>
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

<?php 
$movies_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $movies_per_page;

$count_movies_res = $conn->query("SELECT COUNT(*) as total FROM movies");
$total_movies = $count_movies_res->fetch_assoc()['total'];
$total_movie_pages = ceil($total_movies / $movies_per_page);

$movieQuery = $conn->query("
    SELECT m.*, GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR ', ') as genre 
    FROM movies m 
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id 
    LEFT JOIN genres g ON mg.genre_id = g.genre_id 
    GROUP BY m.movie_id 
    ORDER BY m.created_at DESC 
    LIMIT $offset, $movies_per_page
"); 
?>

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
                <?php
                $poster_path = "../Assets/uploads/movie_posters/" . $movie['poster_url'];
                if (!empty($movie['poster_url']) && file_exists($poster_path)): 
                ?>
                    <img src="<?= htmlspecialchars($poster_path) ?>" alt="<?= htmlspecialchars($movie['title']); ?>">
                <?php else: ?>
                    <div class="poster-placeholder" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: #f8f9fc; color: #9494a8; gap: 8px;">
                        <i class="fas fa-film" style="font-size: 2rem;"></i>
                        <span style="font-size: 12px; font-weight: 600; text-transform: uppercase;">No Image Available</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="movie-content">
                <div class="movie-top">
                    <h3><?= htmlspecialchars($movie['title']); ?></h3>
                    <span class="status <?= strtolower($movie['status']) ?>"><?= htmlspecialchars($movie['status']); ?></span>
                </div>

                <div class="movie-meta">
                    <span><i class="fas fa-theater-masks"></i> &nbsp; <?= htmlspecialchars($movie['genre'] ?? ''); ?></span>
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
                    <a href="edit_movie.php?id=<?= $movie['movie_id']; ?>" class="edit-btn">Edit</a>
                    <?php if ($movie['status'] === 'ACTIVE'): ?>
                        <a href="cancel_movie.php?id=<?= $movie['movie_id']; ?>&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>" class="cancel-btn" onclick="return confirm('Cancel this movie? It will be set to inactive.');">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php else : ?>
    <div class="no-data">No movies available.</div>
<?php endif; ?>
</div>

<?php if ($total_movie_pages > 1): ?>
<div class="pagination-container" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
    <?php for ($i = 1; $i <= $total_movie_pages; $i++): ?>
        <a href="add_movie.php?page=<?= $i; ?>" 
           style="padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s;
           <?= $i == $current_page ? 'background: #ff4d2d; color: white;' : 'background: white; color: #555; border: 1px solid #ddd;'; ?>">
            <?= $i; ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

    </div>
</div>
</body>
</html>