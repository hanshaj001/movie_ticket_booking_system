<?php
require_once '../Includes/db_conn.php'; 
include '../Includes/sidebar.php';

$page = $_GET['page'] ?? 1;
$page = max(1, $page);
$limit = 10;
$offset = ($page - 1) * $limit;
$selected_date = $_GET['date'] ?? 'ALL';
// Auto-update past shows
mysqli_query($conn, "
    UPDATE shows sh
    INNER JOIN movies m ON sh.movie_id = m.movie_id
    SET sh.show_status='COMPLETED'
    WHERE sh.show_status='ACTIVE'
    AND NOW() > TIMESTAMPADD(MINUTE, m.duration_minutes, CONCAT(sh.show_date,' ',sh.show_time))
");

$movies = mysqli_query($conn, "SELECT * FROM movies WHERE status='ACTIVE'");
$screens = mysqli_query($conn, "SELECT * FROM screens WHERE screen_status='ACTIVE'");

$message = "";
$errors = [];
$movie_id = $screen_id = $show_date = $show_time = $ticket_price = "";


// show addition logic
if (isset($_POST['add_show'])) {

    $movie_id = trim($_POST['movie_id']);
    $screen_id = trim($_POST['screen_id']);
    $show_date = trim($_POST['show_date']);
    $show_time = trim($_POST['show_time']);
    $ticket_price = trim($_POST['ticket_price']);

    $today = date("Y-m-d");
    $max_date = date("Y-m-d", strtotime("+7 days"));

    // movie validation

    if (empty($movie_id)) {
        $errors['movie_id'] = "Please select movie.";
    }

    // screen validation
    if (empty($screen_id)) {
        $errors['screen_id'] = "Please select screen.";
    }

    // date validation

    if (empty($show_date)) {
        $errors['show_date'] = "Please select show date.";
    } elseif ($show_date < $today) {
        $errors['show_date'] = "Past date not allowed.";
    } elseif ($show_date > $max_date) {
        $errors['show_date'] = "Only next 7 days allowed.";
    }

    // time validation
    if (empty($show_time)) {
        $errors['show_time'] = "Please select show time.";
    } elseif (
        $show_date == date("Y-m-d")
        &&
        strtotime($show_time) <= strtotime(date("H:i"))
    ) {
        $errors['show_time'] = "Past time not allowed for today.";
    }

    // price validation

    if (empty($ticket_price)) {
        $errors['ticket_price'] = "Please enter ticket price.";
    } elseif (!is_numeric($ticket_price)) {
        $errors['ticket_price'] = "Invalid ticket price.";
    } elseif ($ticket_price <= 0) {
        $errors['ticket_price'] = "Price must be greater than 0.";
    }

    // overlap validation

    if (empty($errors)) {
        $movie_query = mysqli_query(
            $conn,
            "SELECT duration_minutes
             FROM movies
             WHERE movie_id='$movie_id'"
        );

        $movie_data = mysqli_fetch_assoc($movie_query);

        $duration = $movie_data['duration_minutes'];

        $new_start = strtotime($show_date . ' ' . $show_time);

        $new_end = $new_start + ($duration * 60);

        $existing_shows = mysqli_query(
            $conn,
            "
            SELECT
                sh.show_time,
                m.duration_minutes
            FROM shows sh
            INNER JOIN movies m
                ON sh.movie_id=m.movie_id
            WHERE
                sh.screen_id='$screen_id'
                AND sh.show_date='$show_date'
                AND sh.show_status='ACTIVE'
            "
        );

        while (
            $existing = mysqli_fetch_assoc($existing_shows)
        ) {
            $existing_start = strtotime($show_date . ' ' . $existing['show_time']);

            $existing_end = $existing_start + ($existing['duration_minutes'] * 60);

            if (
                $new_start < $existing_end
                &&
                $new_end > $existing_start
            ) {
                $errors['show_time'] = "Show overlaps another show on this screen.";
                break;
            }
        }
    }

        // insert show if no errors

    if (empty($errors)) {
        $insert_show = mysqli_query(
            $conn,
            "INSERT INTO shows
            (
                movie_id,
                screen_id,
                show_date,
                show_time,
                ticket_price,
                show_status
            )
            VALUES
            (
                '$movie_id',
                '$screen_id',
                '$show_date',
                '$show_time',
                '$ticket_price',
                'ACTIVE'
            )"
        );

        if ($insert_show) {
            $show_id = mysqli_insert_id($conn);

            // create show_seats entries for this show based on screen seats

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
    <title>Manage Movie Shows</title>
    <link rel="stylesheet" href="../Assets/add_show.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="content-area">
            
            <div class="page-header">
                <div class="page-title">
                    <div class="title-icon"><i class="fa-regular fa-circle-play"></i></div>
                    <div>
                        <h1>Add Show</h1>
                        <p>Create movie schedules</p>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <?php if ($message != ""): ?>
                    <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error-msg' ?>">
                        <?= $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Movie</label>
                            <select name="movie_id">
                                <option value="">Choose Movie</option>
                                <?php while ($movie = mysqli_fetch_assoc($movies)): ?>
                                    <option value="<?= $movie['movie_id']; ?>" <?= $movie_id == $movie['movie_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <span class="error"><?= $errors['movie_id'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Select Screen</label>
                            <select name="screen_id">
                                <option value="">Choose Screen</option>
                                <?php while ($screen = mysqli_fetch_assoc($screens)): ?>
                                    <option value="<?= $screen['screen_id']; ?>" <?= $screen_id == $screen['screen_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($screen['screen_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <span class="error"><?= $errors['screen_id'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Show Date</label>
                            <input type="date" name="show_date" value="<?= htmlspecialchars($show_date); ?>">
                            <span class="error"><?= $errors['show_date'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Show Time</label>
                            <input type="time" name="show_time" value="<?= htmlspecialchars($show_time); ?>">
                            <span class="error"><?= $errors['show_time'] ?? ''; ?></span>
                        </div>

                        <div class="form-group full-width">
                            <label>Ticket Price</label>
                            <input type="number" step="0.01" name="ticket_price" placeholder="Enter ticket price" value="<?= htmlspecialchars($ticket_price); ?>">
                            <span class="error"><?= $errors['ticket_price'] ?? ''; ?></span>
                        </div>
                    </div>
                    <button type="submit" name="add_show" class="submit-btn">Add Show</button>
                </form>
            </div>

            <?php
            $where_clause = ($selected_date == 'ALL') ? "" : "WHERE sh.show_date='$selected_date'";
            $count_query = mysqli_query($conn, "SELECT COUNT(*) total FROM shows sh $where_clause");
            $total_rows = mysqli_fetch_assoc($count_query)['total'];
            $total_pages = ceil($total_rows / $limit);

            $query = "SELECT sh.*, m.title, m.duration_minutes, sc.screen_name,
                        TIMESTAMPADD(MINUTE, m.duration_minutes, CONCAT(sh.show_date, ' ', sh.show_time)) AS end_time
                      FROM shows sh
                      INNER JOIN movies m ON sh.movie_id = m.movie_id
                      INNER JOIN screens sc ON sh.screen_id = sc.screen_id
                      $where_clause
                      ORDER BY sh.created_at DESC LIMIT $offset, $limit";
            $result = mysqli_query($conn, $query);
            ?>

            <div class="show-list-header">
                <div class="show-list-title">
                    <i class="fa-solid fa-film"></i>
                    <div>
                        <h2>Show List</h2>
                        <p>View and manage scheduled movie shows</p>
                    </div>
                </div>
            </div>

                <div class="show-filter-bar">
                    <a href="?date=ALL&page=1#show-list" class="date-tab <?= $selected_date == 'ALL' ? 'active-date' : '' ?>">
                        All
                    </a>

                    <?php
                    for ($i = 0; $i < 7; $i++) {
                        $date = date("Y-m-d", strtotime("+$i day"));
                        $label = date("d M", strtotime($date));

                        if ($i == 0) $label = "Today";
                        if ($i == 1) $label = "Tomorrow";
                    ?>
                        <a href="?date=<?= $date ?>&page=1#show-list" class="date-tab <?= $selected_date == $date ? 'active-date' : '' ?>">
                            <?= $label ?>
                        </a>
                    <?php
                    }
                    ?>
                </div>

            <div id="show-list" class="show-table-card">
                <table class="show-table">
                    <thead>
                        <tr>
                            <th>S.N</th>
                            <th>Movie</th>
                            <th>Screen</th>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Price</th>
                            <th>Added On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $i = $offset + 1;
                    if (mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $status = $row['show_status'];
                            $status_low = strtolower($status);
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                            <td><?= htmlspecialchars($row['screen_name']) ?></td>
                            <td><?= $row['show_date'] ?></td>
                            <td><?= date("h:i A", strtotime($row['show_time'])) ?></td>
                            <td><?= date("h:i A", strtotime($row['end_time'])) ?></td>
                            <td>Rs. <?= $row['ticket_price'] ?></td>
                            <td><?= date("d M Y h:i A", strtotime($row['created_at'])) ?></td>
                            <td><span class="status <?= $status_low ?>"><?= ucfirst($status_low) ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($status == 'ACTIVE'): ?>
                                        <a href="edit_show.php?id=<?= $row['show_id'] ?>" class="edit-btn">Edit</a>
                                        <a href="cancel_show.php?id=<?= $row['show_id'] ?>" class="cancel-btn" onclick="return confirm('Cancel this show?')">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="10" class="no-data">No shows available</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?date=<?= $selected_date ?>&page=<?= $page - 1 ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <a href="?date=<?= $selected_date ?>&page=<?= $p ?>" class="<?= $page == $p ? 'active-page' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?date=<?= $selected_date ?>&page=<?= $page + 1 ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>

        </div> </div> </body>
</html>