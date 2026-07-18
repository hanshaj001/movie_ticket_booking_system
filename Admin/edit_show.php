<?php

require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// get show id from query parameter

if(!isset($_GET['id']))
{
    header("Location: add_show.php");
    exit;
}

$show_id = (int)$_GET['id'];

//    GET SHOW DETAILS
$show_query = mysqli_query($conn,"SELECT *FROM shows WHERE show_id='$show_id'");

if(mysqli_num_rows($show_query) == 0)
{
    header("Location: add_show.php");
    exit;
}

$show = mysqli_fetch_assoc($show_query);


//   GET ACTIVE MOVIES & SCREENS

$movies = mysqli_query(
    $conn,
    "SELECT *
     FROM movies
     WHERE status='ACTIVE'
     ORDER BY title"
);

$screens = mysqli_query(
    $conn,
    "SELECT *
     FROM screens
     WHERE screen_status='ACTIVE'
     ORDER BY screen_name"
);


// CHECK IF BOOKINGS EXIST FOR THIS SHOW

$booking_check = mysqli_query(
    $conn,
    "SELECT booking_id
     FROM bookings
     WHERE show_id='$show_id'
     AND booking_status='CONFIRMED'"
);

$has_bookings = mysqli_num_rows($booking_check) > 0;


// INITIALIZE VARIABLES

$message = "";
$errors = [];

$movie_id = $show['movie_id'];
$screen_id = $show['screen_id'];
$show_date = $show['show_date'];
$show_time = $show['show_time'];
$ticket_price = $show['ticket_price'];


// update show

if(isset($_POST['update_show']))
{
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }

    $ticket_price = trim($_POST['ticket_price']);

   // if bookings exist, use existing values for movie, screen, date, time

    if($has_bookings)
    {
        $movie_id = $show['movie_id'];
        $screen_id = $show['screen_id'];
        $show_date = $show['show_date'];
        $show_time = $show['show_time'];
    }
    else
    {
        $movie_id = trim($_POST['movie_id']);
        $screen_id = trim($_POST['screen_id']);
        $show_date = trim($_POST['show_date']);
        $show_time = trim($_POST['show_time']);
    }


    // movie validation

    if(empty($movie_id))
    {
        $errors['movie_id'] = "Please select movie.";
    }


// screen validation

    if(empty($screen_id))
    {
        $errors['screen_id'] = "Please select screen.";
    }
    else
    {
        $seat_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM seats WHERE screen_id = ?");
        $seat_check_stmt->bind_param("i", $screen_id);
        $seat_check_stmt->execute();
        $seat_count_res = $seat_check_stmt->get_result()->fetch_assoc();
        $seat_count = $seat_count_res['count'] ?? 0;
        $seat_check_stmt->close();

        if ($seat_count === 0) {
            $errors['screen_id'] = "The selected screen has no seats configured. Please add seats to this screen first.";
        }
    }


    // date validation

    $today = date("Y-m-d");

    if(empty($show_date))
    {
        $errors['show_date'] = "Please select date.";
    }
    elseif($show_date < $today)
    {
        $errors['show_date'] = "Past dates are not allowed.";
    }


    // time validation
    if(empty($show_time))
    {
        $errors['show_time'] = "Please select time.";
    }

    if(
        empty($errors['show_date'])
        &&
        $show_date == date("Y-m-d")
    )
    {
        $current_time = date("H:i");

        if($show_time <= $current_time)
        {
            $errors['show_time']
            = "Time must be after current time.";
        }
    }


    // price validation

    if(empty($ticket_price))
    {
        $errors['ticket_price']
        = "Please enter ticket price.";
    }
    elseif(!is_numeric($ticket_price))
    {
        $errors['ticket_price']
        = "Invalid ticket price.";
    }
    elseif($ticket_price <= 0)
    {
        $errors['ticket_price']
        = "Price must be greater than zero.";
    }


   // overlapping show validation

    // overlapping show validation
    if (empty($errors) && !$has_bookings) {
        $movie_stmt = $conn->prepare("SELECT duration_minutes FROM movies WHERE movie_id = ?");
        $movie_stmt->bind_param("i", $movie_id);
        $movie_stmt->execute();
        $movie_res = $movie_stmt->get_result()->fetch_assoc();
        $duration = $movie_res['duration_minutes'] ?? 0;
        $movie_stmt->close();

        $new_start = strtotime($show_date . ' ' . $show_time);
        $new_end = strtotime('+' . $duration . ' minutes', $new_start);

        $existing_stmt = $conn->prepare("
            SELECT sh.show_id, sh.show_date, sh.show_time, m.duration_minutes
            FROM shows sh
            INNER JOIN movies m ON sh.movie_id = m.movie_id
            WHERE sh.screen_id = ? AND sh.show_date = ? AND sh.show_status != 'CANCELLED' AND sh.show_id != ?
        ");
        $existing_stmt->bind_param("isi", $screen_id, $show_date, $show_id);
        $existing_stmt->execute();
        $existing_shows = $existing_stmt->get_result();

        while ($existing = $existing_shows->fetch_assoc()) {
            $existing_start = strtotime($existing['show_date'] . ' ' . $existing['show_time']);
            $existing_end = strtotime('+' . $existing['duration_minutes'] . ' minutes', $existing_start);

            if (($new_start < $existing_end) && ($new_end > $existing_start)) {
                $errors['show_time'] = "This show overlaps with another show on the selected screen.";
                break;
            }
        }
        $existing_stmt->close();
    }

    // if no errors, update show
    if (empty($errors)) {
        $update_stmt = $conn->prepare("
            UPDATE shows
            SET movie_id = ?, screen_id = ?, show_date = ?, show_time = ?, ticket_price = ?
            WHERE show_id = ?
        ");
        $update_stmt->bind_param("iissdi", $movie_id, $screen_id, $show_date, $show_time, $ticket_price, $show_id);

        if ($update_stmt->execute()) {
            $update_stmt->close();
            $_SESSION['success_message'] = "Show updated successfully.";
            header("Location: add_show.php");
            exit();
        } else {
            $update_stmt->close();
            $message = "Failed to update show.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Show</title>

    <link rel="stylesheet" href="../Assets/css/Admin/add_show.css">
</head>

<body>

<div class="main-container">

    <div class="content-area">

        <div class="page-header">

            <div>
                <h1>Edit Show</h1>
                <p>Update movie schedule details</p>
            </div>

        </div>



        <div class="form-card">

            <?php if(!empty($message)): ?>

                <div class="message">
                    <?= $message ?>
                </div>

            <?php endif; ?>



            <?php if($has_bookings): ?>

                <div class="message">

                    Bookings already exist for this show.
                    Only ticket price can be modified.

                </div>

            <?php endif; ?>

            <form method="POST" id="editShowForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="form-grid">


                    <!-- MOVIE -->

                    <div class="form-group">

                        <label>Select Movie</label>

                        <select
                            name="movie_id"
                            <?= $has_bookings ? 'disabled' : '' ?>
                        >

                            <option value="">
                                Choose Movie
                            </option>

                            <?php while($movie = mysqli_fetch_assoc($movies)): ?>

                                <option
                                    value="<?= $movie['movie_id'] ?>"
                                    <?= ($movie_id == $movie['movie_id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($movie['title']) ?>
                                </option>

                            <?php endwhile; ?>

                        </select>

                        <span class="error">
                            <?= $errors['movie_id'] ?? '' ?>
                        </span>

                    </div>



                    <!-- SCREEN -->

                    <div class="form-group">

                        <label>Select Screen</label>

                        <select
                            name="screen_id"
                            <?= $has_bookings ? 'disabled' : '' ?>
                        >

                            <option value="">
                                Choose Screen
                            </option>

                            <?php while($screen = mysqli_fetch_assoc($screens)): ?>

                                <option
                                    value="<?= $screen['screen_id'] ?>"
                                    <?= ($screen_id == $screen['screen_id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($screen['screen_name']) ?>
                                </option>

                            <?php endwhile; ?>

                        </select>

                        <span class="error">
                            <?= $errors['screen_id'] ?? '' ?>
                        </span>

                    </div>



                    <!-- SHOW DATE -->

                    <div class="form-group">

                        <label>Show Date</label>

                        <input
                            type="date"
                            name="show_date"
                            value="<?= htmlspecialchars($show_date) ?>"
                            <?= $has_bookings ? 'readonly' : '' ?>
                        >

                        <span class="error">
                            <?= $errors['show_date'] ?? '' ?>
                        </span>

                    </div>



                    <!-- SHOW TIME -->

                    <div class="form-group">

                        <label>Show Time</label>

                        <input
                            type="time"
                            name="show_time"
                            value="<?= htmlspecialchars($show_time) ?>"
                            <?= $has_bookings ? 'readonly' : '' ?>
                        >

                        <span class="error">
                            <?= $errors['show_time'] ?? '' ?>
                        </span>

                    </div>



                    <!-- PRICE -->

                    <div class="form-group full-width">

                        <label>Ticket Price</label>

                        <input
                            type="number"
                            step="0.01"
                            min="1"
                            name="ticket_price"
                            value="<?= htmlspecialchars($ticket_price) ?>"
                            placeholder="Enter ticket price"
                        >

                        <span class="error">
                            <?= $errors['ticket_price'] ?? '' ?>
                        </span>

                    </div>

                </div>



                <button
                    type="submit"
                    name="update_show"
                    class="submit-btn"
                >
                    Update Show
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>