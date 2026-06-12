<?php
require_once '../Includes/db_conn.php';


$message = "";
$errors = [];

$title = "";
$description = "";
$duration_minutes = "";
$genre = "";
$language = "";
$release_date = "";
$movie_format = "";

if (isset($_POST['add_movie'])) {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration_minutes = trim($_POST['duration_minutes']);
    $genre = trim($_POST['genre']);
    $language = trim($_POST['language']);
    $release_date = trim($_POST['release_date']);
    $movie_format = trim($_POST['movie_format']);

    // Validation

    if (empty($title)) {
        $errors['title'] = "Movie title is required.";
    }

    if (empty($description)) {
        $errors['description'] = "Description is required.";
    }

    if (empty($duration_minutes)) {
        $errors['duration_minutes'] = "Duration is required.";
    } elseif (!is_numeric($duration_minutes) || $duration_minutes <= 0) {
        $errors['duration_minutes'] = "Invalid duration.";
    }

    if (empty($genre)) {
        $errors['genre'] = "Genre is required.";
    }

    if (empty($language)) {
        $errors['language'] = "Language is required.";
    }

    if (empty($release_date)) {
        $errors['release_date'] = "Release date is required.";
    }

    if (empty($movie_format)) {
        $errors['movie_format'] = "Movie format is required.";
    }

    // Poster Upload
    $poster_url = "";

    if (!empty($_FILES['poster']['name'])) {

        $folder = "../Uploads/";

        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $filename = time() . "_" . $_FILES['poster']['name'];
        $target = $folder . $filename;

        move_uploaded_file($_FILES['poster']['tmp_name'], $target);

        $poster_url = $filename;
    }

    // Insert

    if (empty($errors)) {

        $query = "INSERT INTO movies
        (
            title,
            description,
            duration_minutes,
            genre,
            language,
            release_date,
            movie_format,
            poster_url,
            status
        )
        VALUES
        (
            '$title',
            '$description',
            '$duration_minutes',
            '$genre',
            '$language',
            '$release_date',
            '$movie_format',
            '$poster_url',
            'ACTIVE'
        )";

        if (mysqli_query($conn, $query)) {

            $message = "Movie added successfully.";

            $title = "";
            $description = "";
            $duration_minutes = "";
            $genre = "";
            $language = "";
            $release_date = "";
            $movie_format = "";

        } else {

            $message = "Failed to add movie.";
        }
    }
}

$result = mysqli_query(
    $conn,
    "SELECT * FROM movies ORDER BY created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Movie</title>
    <link rel="stylesheet" href="../Assets/add_movie.css">
</head>
<body>

<div class="container">

    <h2>Add Movie</h2>

    <?php if($message!=""): ?>
        <div class="message">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <input type="text"
               name="title"
               placeholder="Movie Title"
               value="<?= htmlspecialchars($title) ?>">
        <span><?= $errors['title'] ?? '' ?></span>

        <textarea name="description"
                  placeholder="Description"><?= htmlspecialchars($description) ?></textarea>
        <span><?= $errors['description'] ?? '' ?></span>

        <input type="number"
               name="duration_minutes"
               placeholder="Duration (Minutes)"
               value="<?= htmlspecialchars($duration_minutes) ?>">
        <span><?= $errors['duration_minutes'] ?? '' ?></span>

        <input type="text"
               name="genre"
               placeholder="Genre"
               value="<?= htmlspecialchars($genre) ?>">
        <span><?= $errors['genre'] ?? '' ?></span>

        <input type="text"
               name="language"
               placeholder="Language"
               value="<?= htmlspecialchars($language) ?>">
        <span><?= $errors['language'] ?? '' ?></span>

        <input type="date"
               name="release_date"
               value="<?= htmlspecialchars($release_date) ?>">
        <span><?= $errors['release_date'] ?? '' ?></span>

        <select name="movie_format">
            <option value="">Select Format</option>
            <option value="2D">2D</option>
            <option value="3D">3D</option>
            <option value="IMAX">IMAX</option>
        </select>
        <span><?= $errors['movie_format'] ?? '' ?></span>

        <label>Poster</label>
        <input type="file" name="poster">

        <button type="submit" name="add_movie">
            Add Movie
        </button>

    </form>

    <hr>

    <h2>Movie List</h2>

    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Poster</th>
            <th>Title</th>
            <th>Genre</th>
            <th>Duration</th>
            <th>Language</th>
            <th>Format</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($result)): ?>

        <tr>

            <td><?= $row['movie_id'] ?></td>

            <td>
                <img src="../Uploads/<?= $row['poster_url'] ?>"
                     width="80">
            </td>

            <td><?= htmlspecialchars($row['title']) ?></td>

            <td><?= htmlspecialchars($row['genre']) ?></td>

            <td><?= $row['duration_minutes'] ?> min</td>

            <td><?= htmlspecialchars($row['language']) ?></td>

            <td><?= $row['movie_format'] ?></td>

            <td><?= $row['status'] ?></td>

            <td>
                <a href="edit_movie.php?id=<?= $row['movie_id'] ?>">
                    Edit
                </a>
            </td>

        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>