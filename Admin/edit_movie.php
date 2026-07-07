<?php

require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

if (!isset($_GET['id'])) {
    echo "<script>
            alert('Movie not found!');
            window.location='add_movies.php';
          </script>";
    exit();
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM movies WHERE movie_id='$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>
            alert('Movie not found!');
            window.location='manage_movies.php';
          </script>";
    exit();
}

$row = mysqli_fetch_assoc($result);
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

            <input type="hidden" name="id"
                value="<?php echo $row['movie_id']; ?>">

            <div class="form-grid">

                <!-- Movie Title -->
                <div class="form-group">
                    <label>Movie Title</label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="<?php echo htmlspecialchars($row['title']); ?>"
                        required>
                </div>

                <!-- Duration -->
                <div class="form-group">
                    <label>Duration (Minutes)</label>

                    <input
                        type="number"
                        name="duration"
                        class="form-control"
                        value="<?php echo $row['duration_minutes']; ?>"
                        required>
                </div>

                <!-- Genre -->
                <div class="form-group">
                    <label>Genre</label>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap; padding: 10px 0;">
                        <?php
                        $available_genres = [
                            'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 
                            'Family', 'Fantasy', 'History', 'Horror', 'Music', 'Mystery', 'Romance', 
                            'Sci-Fi', 'Superhero', 'Thriller', 'War', 'Western'
                        ];
                        $selected_genres = array_map('trim', explode(',', $row['genre']));
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
                        value="<?php echo htmlspecialchars($row['language']); ?>">
                </div>

                <!-- Movie Format -->
                <div class="form-group">
                    <label>Movie Format</label>

                    <select name="format" class="form-control">

                        <option value="2D"
                        <?php if($row['movie_format']=="2D") echo "selected"; ?>>
                            2D
                        </option>

                        <option value="3D"
                        <?php if($row['movie_format']=="3D") echo "selected"; ?>>
                            3D
                        </option>

                    </select>
                </div>

                <!-- Release Date -->
                <div class="form-group">
                    <label>Release Date</label>

                    <input
                        type="date"
                        name="release_date"
                        class="form-control"
                        value="<?php echo $row['release_date']; ?>">
                </div>

            </div>

            <!-- Poster -->
            <div class="form-group full-width">

                <label>Movie Poster</label>

                <input
                    type="file"
                    name="poster"
                    class="form-control">

                <?php
                if(!empty($row['poster']))
                {
                    echo "<br><img src='../Assets/uploads/".$row['poster']."' width='120'>";
                }
                ?>

            </div>

            <!-- Description -->
            <div class="form-group full-width">

                <label>Description</label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="6"><?php echo htmlspecialchars($row['description']); ?></textarea>

            </div>

            <button type="submit" class="submit-btn" style="width: 100%; margin-top: 20px;">
                Update Movie
            </button>

        </form>


    </div>
    </div>
</div>
</body>
