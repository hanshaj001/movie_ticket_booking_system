<?php
if (session_status() === PHP_SESSION_NONE) {
    
}

include '../Includes/sidebar.php';
require_once '../Includes/db_conn.php';

// Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Includes/login.php");
    exit();
}

// Check Movie ID
if (!isset($_GET['id'])) {
    echo "<script>
            alert('Movie not found!');
            window.location='manage_movies.php';
          </script>";
    exit();
}

$id = intval($_GET['id']);
$errors = [];

// Fetch Movie
$stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            alert('Movie not found!');
            window.location='manage_movies.php';
          </script>";
    exit();
}

$row = $result->fetch_assoc();

$title          = $row['title'];
$description    = $row['description'];
$duration        = $row['duration_minutes'];
$genre          = $row['genre'];
$language        = $row['language'];
$movie_format    = $row['movie_format'];
$release_date    = $row['release_date'];

$existing_poster = !empty($row['poster_url'])
    ? $row['poster_url']
    : (!empty($row['poster']) ? $row['poster'] : '');

if(isset($_POST['update_movie'])){

    $title          = trim($_POST['title']);
    $description    = trim($_POST['description']);
    $duration = trim($_POST['duration']);
    $genre = isset($_POST['genre']) && is_array($_POST['genre']) ? implode(', ', $_POST['genre']) : '';
    $language       = trim($_POST['language']);
    $movie_format   = trim($_POST['movie_format']);
    $release_date   = trim($_POST['release_date']);

    // Movie Title
    if(empty($title)){
        $errors['title']="Movie title is required.";
    }
    elseif(strlen($title)<2){
        $errors['title']="Movie title must contain at least 2 characters.";
    }
    elseif(strlen($title)>150){
        $errors['title']="Movie title cannot exceed 150 characters.";
    }

    // Description
    if(empty($description)){
        $errors['description']="Description is required.";
    }
    elseif(strlen($description)<20){
        $errors['description']="Description must contain at least 20 characters.";
    }
    elseif(strlen($description)>1000){
        $errors['description']="Description cannot exceed 1000 characters.";
    }

    // Duration
    if(empty($duration)){
        $errors['duration']="Duration is required.";
    }
    elseif(!filter_var($duration,FILTER_VALIDATE_INT)){
        $errors['duration']="Duration must be numeric.";
    }
    elseif($duration<30 || $duration>500){
        $errors['duration']="Duration must be between 30 and 500 minutes.";
    }

    // Genre
    if(empty($genre)){
        $errors['genre']="Genre is required.";
    }

    // Language
    if(empty($language)){
        $errors['language']="Language is required.";
    }
    elseif(!preg_match("/^[A-Za-z\s]+$/",$language)){
        $errors['language']="Language may contain only letters.";
    }

    // Movie Format
    if(empty($movie_format)){
        $errors['movie_format']="Movie format is required.";
    }
    elseif(!in_array($movie_format,['2D','3D'])){
        $errors['movie_format']="Invalid movie format.";
    }

    // Release Date
    if(empty($release_date)){
        $errors['release_date']="Release date is required.";
    }
    elseif(!strtotime($release_date)){
        $errors['release_date']="Invalid release date.";
    }

    // Duplicate Movie Title
    if(empty($errors['title'])){

        $check=$conn->prepare("
            SELECT movie_id
            FROM movies
            WHERE title=?
            AND movie_id!=?
        ");

        $check->bind_param("si",$title,$id);
        $check->execute();

        if($check->get_result()->num_rows>0){
            $errors['title']="Movie title already exists.";
        }
    }

    // Poster Validation
    $poster_name=$existing_poster;
    $updatePoster=false;

    if(isset($_FILES['poster']) && $_FILES['poster']['error']==0){

        $allowed=[
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp'
        ];

        $type=mime_content_type($_FILES['poster']['tmp_name']);
        $size=$_FILES['poster']['size'];

        if(!in_array($type,$allowed)){
            $errors['poster']="Only JPG, JPEG, PNG and WEBP images are allowed.";
        }
        elseif($size>2*1024*1024){
            $errors['poster']="Poster size must not exceed 2 MB.";
        }
        else{
            $updatePoster=true;
        }
    }
        // Upload Poster
    if(empty($errors)){

        if($updatePoster){

            $extension=strtolower(pathinfo($_FILES['poster']['name'],PATHINFO_EXTENSION));

            $poster_name=time()."_".uniqid().".".$extension;

            $upload_dir="../uploads/movie_posters/";

            if(!is_dir($upload_dir)){
                mkdir($upload_dir,0777,true);
            }

            $upload_path=$upload_dir.$poster_name;

            if(move_uploaded_file($_FILES['poster']['tmp_name'],$upload_path)){

                if(!empty($existing_poster)){

                    $oldPoster="../uploads/movie_posters/".$existing_poster;

                    if(file_exists($oldPoster)){
                        unlink($oldPoster);
                    }

                }

            }else{

                $errors['poster']="Unable to upload poster.";

            }

        }

    }

    // Update Movie
    if(empty($errors)){

        $stmt=$conn->prepare("
            UPDATE movies
            SET
                title=?,
                description=?,
                duration_minutes=?,
                genre=?,
                language=?,
                release_date=?,
                movie_format=?,
                poster_url=?
            WHERE movie_id=?
        ");

        $stmt->bind_param(
            "ssisssssi",
            $title,
            $description,
            $duration,
            $genre,
            $language,
            $release_date,
            $movie_format,
            $poster_name,
            $id
        );

        if($stmt->execute()){

            $_SESSION['success_message']="Movie updated successfully.";

            header("Location: edit_movie.php?id=".$id);

            exit();

        }else{

            $errors['general']="Failed to update movie.";

        }

    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Movie</title>

<link rel="stylesheet" href="../Assets/add_movie.css">

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

<?php if(isset($_SESSION['success_message'])): ?>

<div class="message">

<?= $_SESSION['success_message']; ?>

</div>

<?php unset($_SESSION['success_message']); ?>

<?php endif; ?>

<?php if(isset($errors['general'])): ?>

<div class="message error-msg">

<?= $errors['general']; ?>

</div>

<?php endif; ?>

<div class="form-card">

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $id; ?>">

<div class="form-grid">
    <div class="form-group">
    <label>Movie Title</label>
    <input
        type="text"
        name="title"
        value="<?= htmlspecialchars($title); ?>"
        placeholder="Enter movie title">

    <span class="error">
        <?= $errors['title'] ?? ''; ?>
    </span>
</div>

<div class="form-group">
    <label>Duration (Minutes)</label>

    <input
        type="number"
        name="duration"
        value="<?= htmlspecialchars($duration); ?>"
        placeholder="120">

    <span class="error">
        <?= $errors['duration'] ?? ''; ?>
    </span>
</div>

                    <div class="form-group">
                        <label>Genre</label>
                        <div class="checkbox-group" id="genre-checkboxes" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
                            <?php
                            $available_genres = ['Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Family', 'Fantasy', 'History', 'Horror', 'Music', 'Mystery', 'Romance', 'Science Fiction', 'Thriller', 'War', 'Western'];
                            $selected_genres = $genre ? array_map('trim', explode(',', $genre)) : [];
                            foreach ($available_genres as $g) {
                                $checked = in_array($g, $selected_genres) ? 'checked' : '';
                                echo "<label style='display:inline-flex; align-items:center; gap:5px; font-weight:normal; cursor:pointer;'><input type='checkbox' name='genre[]' value='$g' $checked onchange='updateSelectedGenres()'> $g</label>";
                            }
                            ?>
                        </div>
                        <div id="selected-genres-display" style="margin-top: 8px; font-weight: 500; color: #4caf50;">
                            <?= $genre ? "Selected: " . htmlspecialchars($genre) : '' ?>
                        </div>
                        <span class="error"><?= $errors['genre'] ?? ''; ?></span>
                    </div>

<div class="form-group">
    <label>Language</label>

    <input
        type="text"
        name="language"
        value="<?= htmlspecialchars($language); ?>"
        placeholder="English">

    <span class="error">
        <?= $errors['language'] ?? ''; ?>
    </span>
</div>

<div class="form-group">
    <label>Movie Format</label>

    <select name="movie_format">

        <option value="">Select Format</option>

        <option value="2D"
            <?= ($movie_format=="2D") ? "selected" : ""; ?>>
            2D
        </option>

        <option value="3D"
            <?= ($movie_format=="3D") ? "selected" : ""; ?>>
            3D
        </option>

    </select>

    <span class="error">
        <?= $errors['movie_format'] ?? ''; ?>
    </span>
</div>

<div class="form-group">
    <label>Release Date</label>

    <input
        type="date"
        name="release_date"
        value="<?= htmlspecialchars($release_date); ?>">

    <span class="error">
        <?= $errors['release_date'] ?? ''; ?>
    </span>
</div>

<div class="form-group full-width">

    <label>
        Movie Poster
        (Leave empty to keep existing)
    </label>

    <input
        type="file"
        name="poster"
        accept=".jpg,.jpeg,.png,.webp">

    <span class="error">
        <?= $errors['poster'] ?? ''; ?>
    </span>

    <?php if(!empty($existing_poster)): ?>

        <div style="margin-top:15px;">

            <img
                src="../uploads/movie_posters/<?= htmlspecialchars($existing_poster); ?>"
                width="130"
                style="border-radius:8px;">

        </div>

    <?php endif; ?>

</div>

<div class="form-group full-width">

    <label>Description</label>

    <textarea
        name="description"
        rows="6"
        placeholder="Movie Description"><?= htmlspecialchars($description); ?></textarea>

    <span class="error">
        <?= $errors['description'] ?? ''; ?>
    </span>

</div>

</div>

<div class="form-actions">

<button
    type="submit"
    name="update_movie"
    class="submit-btn">

    Update Movie

</button>

<a
    href="manage_movies.php"
    class="reset-btn"
    style="text-decoration:none;
           display:inline-flex;
           align-items:center;
           justify-content:center;">

    Cancel

</a>

</div>

</form>

</div>

</div>

</div>

<script>
function updateSelectedGenres() {
    const checkboxes = document.querySelectorAll('input[name="genre[]"]:checked');
    const selected = Array.from(checkboxes).map(cb => cb.value);
    const display = document.getElementById('selected-genres-display');
    
    if (selected.length > 0) {
        display.textContent = 'Selected: ' + selected.join(', ');
    } else {
        display.textContent = '';
    }
}
</script>
</body>

</html>