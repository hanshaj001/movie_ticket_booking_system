<?php

require_once '../Includes/db_conn.php';

$movies = mysqli_query(
    $conn,
    "SELECT * FROM movies WHERE status='ACTIVE'"
);

$screens = mysqli_query(
    $conn,
    "SELECT * FROM screens WHERE screen_status='ACTIVE'"
);

/* ================================
   VARIABLES
================================ */

$message = "";
$errors = [];

$movie_id = "";
$screen_id = "";
$show_date = "";
$show_time = "";
$ticket_price = "";

/* ================================
   FORM SUBMIT
================================ */

if (isset($_POST['add_show'])) {

    $movie_id = trim($_POST['movie_id']);
    $screen_id = trim($_POST['screen_id']);
    $show_date = trim($_POST['show_date']);
    $show_time = trim($_POST['show_time']);
    $ticket_price = trim($_POST['ticket_price']);

    $today = date("Y-m-d");
    $max_date = date("Y-m-d", strtotime("+7 days"));

    /* ================================
       MOVIE VALIDATION
    ================================ */

    if (empty($movie_id)) {
        $errors['movie_id'] = "Please select movie.";
    }

    /* ================================
       SCREEN VALIDATION
    ================================ */

    if (empty($screen_id)) {
        $errors['screen_id'] = "Please select screen.";
    }

    /* ================================
       DATE VALIDATION
    ================================ */

    if (empty($show_date)) {
        $errors['show_date'] = "Please select show date.";
    } elseif ($show_date < $today) {
        $errors['show_date'] = "Past date not allowed.";
    } elseif ($show_date > $max_date) {
        $errors['show_date'] = "Only next 7 days allowed.";
    }

    /* ================================
       TIME VALIDATION
    ================================ */

    if (empty($show_time)) {
        $errors['show_time'] = "Please select show time.";
    }

    /* ================================
       PRICE VALIDATION
    ================================ */

    if (empty($ticket_price)) {
        $errors['ticket_price'] = "Please enter ticket price.";
    } elseif (!is_numeric($ticket_price)) {
        $errors['ticket_price'] = "Invalid ticket price.";
    } elseif ($ticket_price <= 0) {
        $errors['ticket_price'] = "Price must be greater than 0.";
    }

    /* ================================
       DUPLICATE SHOW CHECK
    ================================ */

    if (empty($errors)) {
        $check_query = mysqli_query(
            $conn,
            "SELECT *
             FROM shows
             WHERE screen_id='$screen_id'
             AND show_date='$show_date'
             AND show_time='$show_time'"
        );

        if (mysqli_num_rows($check_query) > 0) {
            $errors['show_time'] = "Another show already exists at this time.";
        }
    }

    /* ================================
       INSERT SHOW
    ================================ */

    if (empty($errors)) {
        $insert_show = mysqli_query(
            $conn,
            "INSERT INTO shows
            (
                movie_id,
                screen_id,
                show_date,
                show_time,
                ticket_price
            )
            VALUES
            (
                '$movie_id',
                '$screen_id',
                '$show_date',
                '$show_time',
                '$ticket_price'
            )"
        );

        if ($insert_show) {
            $show_id = mysqli_insert_id($conn);

            /* ================================
               GENERATE SHOW SEATS
            ================================ */

            $seat_query = mysqli_query(
                $conn,
                "SELECT *
                 FROM seats
                 WHERE screen_id='$screen_id'"
            );

            while ($seat = mysqli_fetch_assoc($seat_query)) {
                $seat_id = $seat['seat_id'];
                mysqli_query(
                    $conn,
                    "INSERT INTO show_seats
                    (
                        show_id,
                        seat_id,
                        seat_status
                    )
                    VALUES
                    (
                        '$show_id',
                        '$seat_id',
                        'AVAILABLE'
                    )"
                );
            }

            $message = "Show added successfully.";

            /* RESET INPUTS */
            $movie_id = "";
            $screen_id = "";
            $show_date = "";
            $show_time = "";
            $ticket_price = "";
        } else {
            $message = "Failed to add show.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Show</title>
    <link rel="stylesheet" href="../Assets/add_show.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h1>Add Show</h1>
                    <p>Create movie schedules</p>
                </div>
            </div>

            <div class="form-card">
                <?php if ($message != "") { ?>
                    <div class="message">
                        <?php echo $message; ?>
                    </div>
                <?php } ?>

                <form method="POST">
                    <div class="form-grid">

                        <div class="form-group">
                            <label>Select Movie</label>
                            <select name="movie_id">
                                <option value="">Choose Movie</option>
                                <?php while ($movie = mysqli_fetch_assoc($movies)) { ?>
                                    <option value="<?php echo $movie['movie_id']; ?>" <?php if ($movie_id == $movie['movie_id']) { echo "selected"; } ?>>
                                        <?php echo $movie['title']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="error">
                                <?php echo $errors['movie_id'] ?? ''; ?>
                            </span>
                        </div>

                        <div class="form-group">
                            <label>Select Screen</label>
                            <select name="screen_id">
                                <option value="">Choose Screen</option>
                                <?php while ($screen = mysqli_fetch_assoc($screens)) { ?>
                                    <option value="<?php echo $screen['screen_id']; ?>" <?php if ($screen_id == $screen['screen_id']) { echo "selected"; } ?>>
                                        <?php echo $screen['screen_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="error">
                                <?php echo $errors['screen_id'] ?? ''; ?>
                            </span>
                        </div>

                        <div class="form-group">
                            <label>Show Date</label>
                            <input type="date" name="show_date" value="<?php echo htmlspecialchars($show_date); ?>">
                            <span class="error">
                                <?php echo $errors['show_date'] ?? ''; ?>
                            </span>
                        </div>

                        <div class="form-group">
                            <label>Show Time</label>
                            <input type="time" name="show_time" value="<?php echo htmlspecialchars($show_time); ?>">
                            <span class="error">
                                <?php echo $errors['show_time'] ?? ''; ?>
                            </span>
                        </div>

                        <div class="form-group full-width">
                            <label>Ticket Price</label>
                            <input type="number" step="0.01" name="ticket_price" placeholder="Enter ticket price" value="<?php echo htmlspecialchars($ticket_price); ?>">
                            <span class="error">
                                <?php echo $errors['ticket_price'] ?? ''; ?>
                            </span>
                        </div>

                    </div>

                    <button type="submit" name="add_show" class="submit-btn">
                        Add Show
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>