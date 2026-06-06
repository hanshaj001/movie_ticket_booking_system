<?php

require_once '../Includes/db_conn.php';

/* ==============================
   GET SHOW ID
============================== */

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


/* ==============================
   MOVIES & SCREENS
============================== */

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


/* ==============================
   CHECK EXISTING BOOKINGS
============================== */

$booking_check = mysqli_query(
    $conn,
    "SELECT booking_id
     FROM bookings
     WHERE show_id='$show_id'
     AND booking_status='CONFIRMED'"
);

$has_bookings = mysqli_num_rows($booking_check) > 0;


/* ==============================
   DEFAULT VALUES
============================== */

$message = "";
$errors = [];

$movie_id = $show['movie_id'];
$screen_id = $show['screen_id'];
$show_date = $show['show_date'];
$show_time = $show['show_time'];
$ticket_price = $show['ticket_price'];


/* ==============================
   UPDATE SHOW
============================== */

if(isset($_POST['update_show']))
{

    $ticket_price = trim($_POST['ticket_price']);

    /*
      STRICT MODE
      IF BOOKINGS EXIST
      ONLY PRICE EDITABLE
    */

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


    /* ==========================
       MOVIE
    ========================== */

    if(empty($movie_id))
    {
        $errors['movie_id'] = "Please select movie.";
    }


    /* ==========================
       SCREEN
    ========================== */

    if(empty($screen_id))
    {
        $errors['screen_id'] = "Please select screen.";
    }


    /* ==========================
       DATE
    ========================== */

    $today = date("Y-m-d");

    if(empty($show_date))
    {
        $errors['show_date'] = "Please select date.";
    }
    elseif($show_date < $today)
    {
        $errors['show_date'] = "Past dates are not allowed.";
    }


    /* ==========================
       TIME
    ========================== */

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


    /* ==========================
       PRICE
    ========================== */

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


    /* ==========================
       OVERLAP VALIDATION
    ========================== */

    if(
        empty($errors)
        &&
        !$has_bookings
    )
    {

        $movie_query = mysqli_query(
            $conn,
            "SELECT duration_minutes
             FROM movies
             WHERE movie_id='$movie_id'"
        );

        $movie = mysqli_fetch_assoc($movie_query);

        $duration = $movie['duration_minutes'];

        $new_start = strtotime($show_date . ' ' . $show_time);

        $new_end = strtotime('+' . $duration . ' minutes',$new_start);

        $existing_shows = mysqli_query(
            $conn,
            "SELECT
                sh.show_id,
                sh.show_date,
                sh.show_time,
                m.duration_minutes

             FROM shows sh

             INNER JOIN movies m
             ON sh.movie_id = m.movie_id

             WHERE sh.screen_id='$screen_id'
             AND sh.show_date='$show_date'
             AND sh.show_status!='CANCELLED'
             AND sh.show_id != '$show_id'"
        );



        while(
            $existing =
            mysqli_fetch_assoc($existing_shows)
        )
        {

            $existing_start = strtotime(
                $existing['show_date']
                .' '.
                $existing['show_time']
            );

            $existing_end = strtotime(
                '+' .
                $existing['duration_minutes']
                .
                ' minutes',
                $existing_start
            );



            if(
                ($new_start < $existing_end)
                &&
                ($new_end > $existing_start)
            )
            {
                $errors['show_time']
                =
                "This show overlaps with another show on the selected screen.";

                break;
            }
        }
    }


    /* ==========================
       UPDATE
    ========================== */

    if(empty($errors))
    {

        $update = mysqli_query(
            $conn,
            "UPDATE shows
             SET
                movie_id='$movie_id',
                screen_id='$screen_id',
                show_date='$show_date',
                show_time='$show_time',
                ticket_price='$ticket_price'
             WHERE show_id='$show_id'"
        );

        if($update)
        {
            $message =
            "Show updated successfully.";

            $show = mysqli_fetch_assoc(
                mysqli_query(
                    $conn,
                    "SELECT *
                     FROM shows
                     WHERE show_id='$show_id'"
                )
            );
        }
        else
        {
            $message =
            "Failed to update show.";
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

    <link rel="stylesheet" href="../Assets/add_show.css">
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



            <form method="POST">

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