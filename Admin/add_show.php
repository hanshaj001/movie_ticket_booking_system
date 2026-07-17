<?php
require_once '../Includes/db_conn.php'; 
include 'components/sidebar.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
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

$notify_message = "";
$notify_type = "";
$errors = [];
$movie_id = $screen_id = $show_date = $show_time = $ticket_price = "";

// Capture notification queries coming from external action scripts (Edit, Delete, Cancel parameters)
if (isset($_GET['msg'])) {
    $notify_message = $_GET['msg'];
    $notify_type = $_GET['type'] ?? 'success';
}

// Show addition logic
if (isset($_POST['add_show'])) {

    $movie_id = trim($_POST['movie_id']);
    $screen_id = trim($_POST['screen_id']);
    $show_date = trim($_POST['show_date']);
    $show_time = trim($_POST['show_time']);
    $ticket_price = trim($_POST['ticket_price']);

    $today = date("Y-m-d");
    $max_date = date("Y-m-d", strtotime("+7 days"));

    if (empty($movie_id)) {
        $errors['movie_id'] = "Please select movie.";
    }

    if (empty($screen_id)) {
        $errors['screen_id'] = "Please select screen.";
    }

    if (empty($show_date)) {
        $errors['show_date'] = "Please select show date.";
    } elseif ($show_date < $today) {
        $errors['show_date'] = "Past date not allowed.";
    } elseif ($show_date > $max_date) {
        $errors['show_date'] = "Only next 7 days allowed.";
    }

    if (empty($show_time)) {
        $errors['show_time'] = "Please select show time.";
    } elseif ($show_date == date("Y-m-d")) {
        $selected_show_datetime = strtotime($show_date . ' ' . $show_time);
        $minimum_allowed_datetime = strtotime('+1 hour');

        if ($selected_show_datetime < $minimum_allowed_datetime) {
            $errors['show_time'] = "Show time must be at least 1 hour after the current time.";
        }
    }

    if (empty($ticket_price)) {
        $errors['ticket_price'] = "Please enter ticket price.";
    } elseif (!is_numeric($ticket_price)) {
        $errors['ticket_price'] = "Invalid ticket price.";
    } elseif ($ticket_price <= 0) {
        $errors['ticket_price'] = "Price must be greater than 0.";
    }

    // Overlap validation
    if (empty($errors)) {
        $movie_stmt = $conn->prepare("SELECT duration_minutes FROM movies WHERE movie_id = ?");
        $movie_stmt->bind_param("i", $movie_id);
        $movie_stmt->execute();
        $movie_data = $movie_stmt->get_result()->fetch_assoc();
        $duration = $movie_data['duration_minutes'] ?? 0;
        $movie_stmt->close();

        $new_start = strtotime($show_date . ' ' . $show_time);
        $new_end = $new_start + ($duration * 60);

        $existing_stmt = $conn->prepare("
            SELECT sh.show_time, m.duration_minutes 
            FROM shows sh 
            INNER JOIN movies m ON sh.movie_id = m.movie_id 
            WHERE sh.screen_id = ? AND sh.show_date = ? AND sh.show_status = 'ACTIVE'
        ");
        $existing_stmt->bind_param("is", $screen_id, $show_date);
        $existing_stmt->execute();
        $existing_shows = $existing_stmt->get_result();

        while ($existing = $existing_shows->fetch_assoc()) {
            $existing_start = strtotime($show_date . ' ' . $existing['show_time']);
            $existing_end = $existing_start + ($existing['duration_minutes'] * 60);

            if ($new_start < $existing_end && $new_end > $existing_start) {
                $errors['show_time'] = "Show overlaps another show on this screen.";
                break;
            }
        }
        $existing_stmt->close();
    }

    // Insert data if clean
    if (empty($errors)) {
        $insert_stmt = $conn->prepare("
            INSERT INTO shows (movie_id, screen_id, show_date, show_time, ticket_price, show_status) 
            VALUES (?, ?, ?, ?, ?, 'ACTIVE')
        ");
        $insert_stmt->bind_param("iissd", $movie_id, $screen_id, $show_date, $show_time, $ticket_price);

        if ($insert_stmt->execute()) {
            $show_id = $insert_stmt->insert_id;
            $insert_stmt->close();

            // Populate show_seats entries based on layout templates
            $seat_stmt = $conn->prepare("SELECT seat_id FROM seats WHERE screen_id = ?");
            $seat_stmt->bind_param("i", $screen_id);
            $seat_stmt->execute();
            $seats_res = $seat_stmt->get_result();
            
            $ins_seat_stmt = $conn->prepare("INSERT INTO show_seats (show_id, seat_id, seat_status) VALUES (?, ?, 'AVAILABLE')");
            while ($seat = $seats_res->fetch_assoc()) {
                $seat_id = $seat['seat_id'];
                $ins_seat_stmt->bind_param("ii", $show_id, $seat_id);
                $ins_seat_stmt->execute();
            }
            $ins_seat_stmt->close();
            $seat_stmt->close();

            $notify_message = "Show added successfully.";
            $notify_type = "success";

            // Reset inputs cleanly
            $movie_id = $screen_id = $show_date = $show_time = $ticket_price = "";
        } else {
            $insert_stmt->close();
            $notify_message = "Failed to add show.";
            $notify_type = "error";
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
    <link rel="stylesheet" href="../Assets/css/Admin/add_show.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Floating Toast Notification CSS Configuration */
        .toast-box {
            position: fixed;
            top: 25px;
            right: -450px;
            background-color: #ffffff;
            padding: 16px 22px;
            border-radius: 6px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 14px;
            z-index: 10000;
            transition: right 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
            opacity: 0;
        }
        .toast-box.active {
            right: 25px;
            opacity: 1;
        }
        .toast-box.success { border-left: 6px solid #2ecc71; color: #27ae60; }
        .toast-box.error { border-left: 6px solid #e74c3c; color: #c0392b; }
        .toast-box .toast-icon { font-size: 22px; }
        .toast-box .toast-msg-text { font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 14px; }

        /* Mobile Responsive View Styling Sheets */
        @media screen and (max-width: 768px) {
            .main-container { padding: 8px; }
            .form-grid { grid-template-columns: 1fr !important; gap: 10px; }
            .show-table-card { overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
            .show-table th, .show-table td { padding: 10px 8px; font-size: 12px; }
            .show-filter-bar { display: flex; overflow-x: auto; padding-bottom: 6px; gap: 5px; }
            .date-tab { padding: 6px 10px; font-size: 11px; flex-shrink: 0; }
            .action-buttons { flex-direction: column; gap: 4px; }
            .edit-btn, .cancel-btn { padding: 4px 6px; font-size: 11px; width: 100%; text-align: center; }
            .toast-box { width: 90%; max-width: 320px; top: 15px; }
            .toast-box.active { right: 5%; }
        }
    </style>
</head>
<body>
    <div id="popupToast" class="toast-box">
        <div id="popupIcon" class="toast-icon"></div>
        <div id="popupText" class="toast-msg-text"></div>
    </div>

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
                <form method="POST" id="addShowForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Movie</label>
                            <select name="movie_id" id="movie_id">
                                <option value="">Choose Movie</option>
                                <?php 
                                mysqli_data_seek($movies, 0);
                                while ($movie = mysqli_fetch_assoc($movies)): 
                                ?>
                                    <option value="<?= $movie['movie_id']; ?>" <?= $movie_id == $movie['movie_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <span class="error"><?= $errors['movie_id'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Select Screen</label>
                            <select name="screen_id" id="screen_id">
                                <option value="">Choose Screen</option>
                                <?php 
                                mysqli_data_seek($screens, 0);
                                while ($screen = mysqli_fetch_assoc($screens)): 
                                ?>
                                    <option value="<?= $screen['screen_id']; ?>" <?= $screen_id == $screen['screen_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($screen['screen_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <span class="error"><?= $errors['screen_id'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Show Date</label>
                            <input type="date" name="show_date" id="show_date" value="<?= htmlspecialchars($show_date); ?>">
                            <span class="error"><?= $errors['show_date'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Show Time</label>
                            <input type="time" name="show_time" id="show_time" value="<?= htmlspecialchars($show_time); ?>">
                            <span class="error"><?= $errors['show_time'] ?? ''; ?></span>
                        </div>

                        <div class="form-group full-width">
                            <label>Ticket Price</label>
                            <input type="number" step="0.01" name="ticket_price" id="ticket_price" placeholder="Enter ticket price" value="<?= htmlspecialchars($ticket_price); ?>">
                            <span class="error"><?= $errors['ticket_price'] ?? ''; ?></span>
                        </div>
                    </div>
                    <button type="submit" name="add_show" class="submit-btn">Add Show</button>
                </form>
            </div>

            <?php
            $where_clause = ($selected_date == 'ALL') ? "" : "WHERE sh.show_date='" . mysqli_real_escape_string($conn, $selected_date) . "'";
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
                <a href="?date=ALL&page=1" class="date-tab <?= $selected_date == 'ALL' ? 'active-date' : '' ?>">All</a>
                <?php
                for ($i = 0; $i < 7; $i++) {
                    $date = date("Y-m-d", strtotime("+$i day"));
                    $label = date("d M", strtotime($date));
                    if ($i == 0) $label = "Today";
                    if ($i == 1) $label = "Tomorrow";
                ?>
                    <a href="?date=<?= $date ?>&page=1" class="date-tab <?= $selected_date == $date ? 'active-date' : '' ?>">
                        <?= $label ?>
                    </a>
                <?php } ?>
            </div>

            <div class="show-table-card">
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

        </div> 
    </div>

    <script>
    // Unified function to throw clean toast notification alerts (disappears after 5 seconds)
    function showNotification(message, type = 'success') {
        const toast = document.getElementById("popupToast");
        const icon = document.getElementById("popupIcon");
        const text = document.getElementById("popupText");

        toast.className = "toast-box";
        
        if (type === 'error') {
            toast.classList.add("error");
            icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
        } else {
            toast.classList.add("success");
            icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
        }

        text.textContent = message;
        toast.classList.add("active");

        setTimeout(() => {
            toast.classList.remove("active");
        }, 5000);
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Trigger popup message if parsed from PHP database transaction execution
        <?php if (!empty($notify_message)): ?>
            showNotification("<?= addslashes($notify_message) ?>", "<?= $notify_type ?>");
        <?php endif; ?>

        // URL interceptor for incoming external redirections (Edit/Cancel success parameters)
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const mType = urlParams.get('type');

        if (msg) {
            // Trigger custom notification box safely
            showNotification(msg, mType || 'success');
            
            // Clean up the URL state parameters instantly to control F5/refresh popup bugs
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
        }

        // JavaScript Client Form Validation 
        const form = document.getElementById("addShowForm");
        form.addEventListener("submit", function(event) {
            let isValid = true;
            
            // Wipe older errors
            document.querySelectorAll(".error").forEach(el => el.textContent = "");

            const movie = document.getElementById("movie_id").value;
            const screen = document.getElementById("screen_id").value;
            const sDate = document.getElementById("show_date").value;
            const sTime = document.getElementById("show_time").value;
            const price = document.getElementById("ticket_price").value;

            if (!movie) {
                document.getElementById("movie_id").nextElementSibling.textContent = "Please select movie.";
                isValid = false;
            }
            if (!screen) {
                document.getElementById("screen_id").nextElementSibling.textContent = "Please select screen.";
                isValid = false;
            }
            if (!sDate) {
                document.getElementById("show_date").nextElementSibling.textContent = "Please select show date.";
                isValid = false;
            }
            if (!sTime) {
                document.getElementById("show_time").nextElementSibling.textContent = "Please select show time.";
                isValid = false;
            }
            if (sDate && sTime) {
                const selectedDateTime = new Date(`${sDate}T${sTime}`);
                const minimumAllowedTime = new Date(Date.now() + 60 * 60 * 1000);

                if (selectedDateTime < minimumAllowedTime) {
                    document.getElementById("show_time").nextElementSibling.textContent = "Show time must be at least 1 hour after the current time.";
                    isValid = false;
                }
            }
            if (!price || isNaN(price) || parseFloat(price) <= 0) {
                document.getElementById("ticket_price").nextElementSibling.textContent = "Price must be greater than 0.";
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault(); 
              }
        });
    });
    </script>
</body>
</html>