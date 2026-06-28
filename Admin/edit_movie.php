<?php

require_once '../Includes/db_conn.php';
include '../Includes/sidebar.php';

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
    <link rel="stylesheet" href="../Assets/add_movie.css">
</head>
<body>

<div class="main-content">


    <div class="page-header">
        <div class="header-icon">
            <i class="fas fa-edit"></i>
        </div>

        <div>
            <h2>Edit Movie</h2>
            <p>Update movie information</p>
        </div>
    </div>

    <div class="form-container">

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

                    <input
                        type="text"
                        name="genre"
                        class="form-control"
                        value="<?php echo htmlspecialchars($row['genre']); ?>">
                </div>

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
            <div class="form-group">

                <label>Movie Poster</label>

                <input
                    type="file"
                    name="poster"
                    class="form-control">

                <?php
                if(!empty($row['poster']))
                {
                    echo "<br><img src='../uploads/".$row['poster']."' width='120'>";
                }
                ?>

            </div>

            <!-- Description -->
            <div class="form-group">

                <label>Description</label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="6"><?php echo htmlspecialchars($row['description']); ?></textarea>

            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Movie
            </button>

        </form>

    </div>

</div>
</body>