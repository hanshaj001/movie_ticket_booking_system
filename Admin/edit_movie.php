<?php
session_start();
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

if (!isset($_GET['id'])) {
    echo "<script>
            alert('Movie not found!');
            window.location='add_movie.php';
          </script>";
    exit();
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM movies WHERE movie_id='$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>
            alert('Movie not found!');
            window.location='add_movie.php';
          </script>";
    exit();
}

$row = mysqli_fetch_assoc($result);

$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors']);
unset($_SESSION['form_data']);

$title = $form_data['title'] ?? $row['title'];
$duration = $form_data['duration'] ?? $row['duration_minutes'];
$genre = $form_data['genre'] ?? (is_array($form_data['genre'] ?? null) ? implode(', ', $form_data['genre']) : ($row['genre'] ?? ''));
$language = $form_data['language'] ?? $row['language'];
$format = $form_data['format'] ?? $row['movie_format'];
$release_date = $form_data['release_date'] ?? $row['release_date'];
$description = $form_data['description'] ?? $row['description'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/add_movie.css">
    <style>
        .error-message {
            color: #ff4d2d;
            font-size: 13px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="content-area">

    <div class="page-header">
        <div class="page-title">
            <div class="title-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div>
                <h1>Edit Movie</h1>
                <p>Update movie information</p>
            </div>
        </div>
    </div>

    <div class="form-card">

        <form action="update_movie.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?php echo $row['movie_id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

            <div class="form-grid">

                <!-- Movie Title -->
                <div class="form-group">
                    <label>Movie Title</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="<?php echo htmlspecialchars($title); ?>"
                        required>
                    <?php if (!empty($errors['title'])): ?>
                        <span class="error-message"><?php echo $errors['title']; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Duration -->
                <div class="form-group">
                    <label>Duration (Minutes)</label>
                    <input
                        type="number"
                        name="duration"
                        class="form-control"
                        value="<?php echo htmlspecialchars($duration); ?>"
                        required>
                    <?php if (!empty($errors['duration'])): ?>
                        <span class="error-message"><?php echo $errors['duration']; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Genre -->
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
                        
                        $selected_genres = is_array($form_data['genre'] ?? null) ? $form_data['genre'] : array_map('trim', explode(',', $genre));
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
                    <?php if (!empty($errors['genre'])): ?>
                        <span class="error-message"><?php echo $errors['genre']; ?></span>
                    <?php endif; ?>
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

                <!-- Language -->
                <div class="form-group">
                    <label>Language</label>
                    <input
                        type="text"
                        name="language"
                        class="form-control"
                        value="<?php echo htmlspecialchars($language); ?>"
                        required>
                    <?php if (!empty($errors['language'])): ?>
                        <span class="error-message"><?php echo $errors['language']; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Movie Format -->
                <div class="form-group">
                    <label>Movie Format</label>
                    <select name="format" class="form-control" required>
                        <option value="2D" <?php if($format == "2D") echo "selected"; ?>>2D</option>
                        <option value="3D" <?php if($format == "3D") echo "selected"; ?>>3D</option>
                    </select>
                    <?php if (!empty($errors['movie_format'])): ?>
                        <span class="error-message"><?php echo $errors['movie_format']; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Release Date -->
                <div class="form-group">
                    <label>Release Date</label>
                    <input
                        type="date"
                        name="release_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($release_date); ?>"
                        required>
                    <?php if (!empty($errors['release_date'])): ?>
                        <span class="error-message"><?php echo $errors['release_date']; ?></span>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Poster -->
            <div class="form-group full-width">
                <label>Movie Poster</label>
                <input
                    type="file"
                    name="poster"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp">
                <?php if (!empty($errors['poster'])): ?>
                    <span class="error-message"><?php echo $errors['poster']; ?></span>
                <?php endif; ?>

                <?php if (!empty($row['poster_url'])) { ?>
                    <br>
                    <?php
                    $poster_path = "../Assets/uploads/movie_posters/" . $row['poster_url'];
                    if (file_exists($poster_path)):
                    ?>
                        <img src="<?= htmlspecialchars($poster_path) ?>"
                             width="120"
                             alt="Movie Poster">
                    <?php else: ?>
                        <div class="poster-placeholder" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; width: 120px; background: #f8f9fc; color: #9494a8; gap: 8px;">
                            <i class="fas fa-film" style="font-size: 2rem;"></i>
                            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: center;">No Image</span>
                        </div>
                    <?php endif; ?>
                <?php } ?>
            </div>

            <!-- Banner -->
            <div class="form-group full-width">
                <label>Hero Banner (16:9)</label>
                <input
                    type="file"
                    name="banner"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp">
                <?php if (!empty($errors['banner'])): ?>
                    <span class="error-message"><?php echo $errors['banner']; ?></span>
                <?php endif; ?>

                <?php if (!empty($row['banner_url'])) { ?>
                    <br>
                    <img src="../Assets/uploads/movie_banners/<?php echo htmlspecialchars($row['banner_url']); ?>"
                         width="300"
                         style="border-radius:8px;"
                         alt="Hero Banner">
                <?php } ?>
            </div>

            <!-- Description -->
            <div class="form-group full-width">
                <label>Description</label>
                <textarea
                    name="description"
                    class="form-control"
                    rows="6"
                    required><?php echo htmlspecialchars($description); ?></textarea>
                <?php if (!empty($errors['description'])): ?>
                    <span class="error-message"><?php echo $errors['description']; ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn" style="width: 100%; margin-top: 20px;">
                Update Movie
            </button>

        </form>

    </div>
    </div>
</div>
</body>
</html>
