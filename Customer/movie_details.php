<?php
// Initialize system secure authentication tracking session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Access and Session Tracking
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'CUSTOMER') {
    header("Location: ../login.php");
    exit();
}

// 1. Access Control Validation: Verify user login status
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Access Control Validation: Check customer role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CUSTOMER') {
    header("Location: home.php");
    exit();
}

require_once '../Includes/db_conn.php';

date_default_timezone_set('Asia/Kathmandu');
$current_date_marker = date('Y-m-d');
$current_datetime_string = date('Y-m-d H:i:s');

if (!isset($_GET['movie_id']) || !is_numeric($_GET['movie_id'])) {
    header("Location: home.php");
    exit();
}

$movie_id = intval($_GET['movie_id']);
if ($movie_id <= 0) {
    header("Location: home.php");
    exit();
}

$selected_date = $current_date_marker;
if (isset($_GET['date'])) {
    $url_date = trim($_GET['date']);
    $date_regex_pattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_regex_pattern, $url_date)) {
        header("Location: home.php");
        exit();
    }
    // Date boundary check: reject any date older than today
    if ($url_date < $current_date_marker) {
        header("Location: movie_details.php?movie_id=$movie_id");
        exit();
    }
    $selected_date = mysqli_real_escape_string($conn, $url_date);
}

$movie_query_string = "SELECT * FROM movies WHERE movie_id = $movie_id AND status = 'ACTIVE' LIMIT 1";
$movie_query_result = mysqli_query($conn, $movie_query_string);

if (!$movie_query_result || mysqli_num_rows($movie_query_result) === 0) {
    header("Location: home.php");
    exit();
}

$movie_record = mysqli_fetch_assoc($movie_query_result);

$seven_days_schedule_matrix = array();
for ($day_offset = 0; $day_offset < 7; $day_offset++) {
    $calculated_timestamp = strtotime("+$day_offset days");
    $loop_date_string = date('Y-m-d', $calculated_timestamp);
    $loop_day_name = date('l', $calculated_timestamp);
    $loop_day_number = date('j', $calculated_timestamp);

    $check_shows_query = "SELECT COUNT(*) AS active_shows_count FROM shows
                          WHERE movie_id = $movie_id
                          AND show_date = '$loop_date_string'
                          AND show_status = 'ACTIVE'
                          AND CONCAT(show_date, ' ', show_time) >= '$current_datetime_string'";
    $check_shows_result = mysqli_query($conn, $check_shows_query);

    $date_has_available_shows = false;
    if ($check_shows_result) {
        $check_shows_row = mysqli_fetch_assoc($check_shows_result);
        if (intval($check_shows_row['active_shows_count']) > 0) {
            $date_has_available_shows = true;
        }
    }

    $seven_days_schedule_matrix[] = array(
        'date_string'  => $loop_date_string,
        'day_name'     => $loop_day_name,
        'day_number'   => $loop_day_number,
        'is_selectable'=> $date_has_available_shows
    );
}

$shows_list_query = "SELECT s.*, scr.screen_name, scr.total_seats as screen_total_seats,
                     (SELECT COUNT(*) FROM show_seats WHERE show_id = s.show_id) as capacity_total,
                     (SELECT COUNT(*) FROM show_seats WHERE show_id = s.show_id AND seat_status = 'AVAILABLE') as capacity_available
                     FROM shows s
                     INNER JOIN screens scr ON s.screen_id = scr.screen_id
                     WHERE s.movie_id = $movie_id
                     AND s.show_date = '$selected_date'
                     AND s.show_status = 'ACTIVE'
                     AND CONCAT(s.show_date, ' ', s.show_time) >= '$current_datetime_string'
                     ORDER BY s.show_time ASC";
$shows_list_result = mysqli_query($conn, $shows_list_query);

$similar_movies_query = "SELECT * FROM movies WHERE status = 'ACTIVE' AND movie_id != $movie_id LIMIT 4";
$similar_movies_result = mysqli_query($conn, $similar_movies_query);

// Collect unique formats for filter buttons
$formats_available = array();
if ($shows_list_result && mysqli_num_rows($shows_list_result) > 0) {
    while ($row = mysqli_fetch_assoc($shows_list_result)) {
        $fmt = $movie_record['movie_format'];
        if (!in_array($fmt, $formats_available)) {
            $formats_available[] = $fmt;
        }
    }
    mysqli_data_seek($shows_list_result, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie_record['title']); ?> - Movie Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../Assets/css/Customer/movie_details.css">
</head>
<body class="movie-details-body">

    <?php include_once 'navbar.php'; ?>

    <main class="movie-details-container">

        <!-- BREADCRUMB NAVIGATION -->
        <nav class="breadcrumb-nav">
            <a href="home.php" class="bc-link"><i class="fa-solid fa-house"></i> Home</a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="bc-current"><?php echo htmlspecialchars($movie_record['title']); ?></span>
        </nav>

        <!-- HERO BANNER -->
        <section class="movie-hero-banner">
            <div class="hero-content-wrapper">
                <!-- Poster -->
                <div class="hero-poster-column">
                    <div class="poster-card">
                        <?php if (!empty($movie_record['poster_url'])): ?>
                            <img src="<?php echo htmlspecialchars($movie_record['poster_url']); ?>"
                                 alt="<?php echo htmlspecialchars($movie_record['title']); ?> Poster"
                                 class="hero-poster-img"
                                 onerror="showFallback(this)">
                        <?php endif; ?>
                        <div class="no-image-fallback" style="<?php echo empty($movie_record['poster_url']) ? 'display:flex;' : 'display:none;'; ?>">
                            <i class="fa-solid fa-film fallback-icon"></i>
                            <span>No Image Available</span>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="hero-info-column">
                    <h1 class="hero-movie-title"><?php echo htmlspecialchars($movie_record['title']); ?></h1>

                    <div class="hero-meta-badges">
                        <span class="badge badge-format"><i class="fa-solid fa-film"></i> <?php echo htmlspecialchars($movie_record['movie_format']); ?></span>
                        <span class="badge badge-lang"><i class="fa-solid fa-language"></i> <?php echo htmlspecialchars($movie_record['language']); ?></span>
                        <span class="badge badge-duration"><i class="fa-regular fa-clock"></i> <?php echo intval($movie_record['duration_minutes']); ?> Mins</span>
                        <?php if (!empty($movie_record['age_rating'])): ?>
                            <span class="badge badge-rating"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($movie_record['age_rating']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="hero-meta-list">
                        <div class="meta-item">
                            <span class="meta-label">Genre:</span>
                            <span class="meta-value"><?php echo htmlspecialchars(implode(' | ', explode('|', $movie_record['genre'] ?? ''))); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Release Date:</span>
                            <span class="meta-value"><?php echo htmlspecialchars(date('d F Y', strtotime($movie_record['release_date']))); ?></span>
                        </div>
                    </div>

                    <div class="hero-synopsis-brief">
                        <p><?php echo htmlspecialchars($movie_record['description']); ?></p>
                    </div>

                    <div class="hero-cta-row">
                        <button id="bookTicketsButton" class="btn-primary-cta">
                            <i class="fa-solid fa-ticket"></i> Book Tickets
                        </button>
                        <!-- SHARE BUTTON -->
                        <button id="shareBtn" class="btn-share-cta" title="Share this movie">
                            <i class="fa-solid fa-share-nodes"></i> Share
                        </button>
                        <span id="shareMsg" class="share-msg" style="display:none;">
                            <i class="fa-solid fa-check"></i> Link copied!
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <div class="details-layout-grid">

            <!-- MAIN COLUMN -->
            <div class="layout-main-column">

                <!-- DATE SELECTION (sticky) -->
                <section id="dateSelectionSection" class="section-card date-selection-card sticky-dates">
                    <h3 class="section-title"><i class="fa-solid fa-calendar-days"></i> Select Screening Date</h3>
                    <div class="date-tabs-scroll-container">
                        <div class="date-tabs-flex">
                            <?php foreach ($seven_days_schedule_matrix as $tab_item): ?>
                                <?php
                                $is_active_tab = ($selected_date === $tab_item['date_string']);
                                $active_class  = $is_active_tab ? 'selected-date-tab' : '';
                                ?>
                                <?php if ($tab_item['is_selectable']): ?>
                                    <a href="movie_details.php?movie_id=<?php echo $movie_id; ?>&date=<?php echo $tab_item['date_string']; ?>#dateSelectionSection"
                                       class="date-tab-link <?php echo $active_class; ?>">
                                        <span class="tab-day-name"><?php echo htmlspecialchars(substr($tab_item['day_name'], 0, 3)); ?></span>
                                        <span class="tab-day-num"><?php echo htmlspecialchars($tab_item['day_number']); ?></span>
                                    </a>
                                <?php else: ?>
                                    <button class="date-tab-disabled" disabled>
                                        <span class="tab-day-name"><?php echo htmlspecialchars(substr($tab_item['day_name'], 0, 3)); ?></span>
                                        <span class="tab-day-num"><?php echo htmlspecialchars($tab_item['day_number']); ?></span>
                                        <span class="no-shows-label">No Shows</span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- SHOW TIMES -->
                <section id="showtimesSection" class="section-card showtimes-list-card">
                    <div class="section-header-row">
                        <h3 class="section-title"><i class="fa-solid fa-clock"></i> Show Times</h3>
                        <span class="selected-date-indicator">
                            <i class="fa-regular fa-calendar"></i>
                            <strong><?php echo htmlspecialchars(date('l, d F Y', strtotime($selected_date))); ?></strong>
                        </span>
                    </div>

                    <!-- FORMAT FILTER BUTTONS -->
                    <?php if ($shows_list_result && mysqli_num_rows($shows_list_result) > 0): ?>
                    <div class="format-filter-bar">
                        <span class="filter-label"><i class="fa-solid fa-filter"></i> Filter:</span>
                        <button class="format-filter-btn active-filter" data-format="all" onclick="filterShows('all', this)">All</button>
                        <button class="format-filter-btn" data-format="2D" onclick="filterShows('2D', this)">2D</button>
                        <button class="format-filter-btn" data-format="3D" onclick="filterShows('3D', this)">3D</button>
                    </div>
                    <?php endif; ?>

                    <div class="showtime-cards-stack">
                        <?php if ($shows_list_result && mysqli_num_rows($shows_list_result) > 0): ?>
                            <?php while ($show_row = mysqli_fetch_assoc($shows_list_result)): ?>
                                <?php
                                $total_seats_capacity  = intval($show_row['capacity_total']);
                                $available_seats_count = intval($show_row['capacity_available']);

                                if ($total_seats_capacity === 0) {
                                    $total_seats_capacity  = intval($show_row['screen_total_seats']) > 0 ? intval($show_row['screen_total_seats']) : 60;
                                    $available_seats_count = $total_seats_capacity;
                                }

                                $booked_seats  = $total_seats_capacity - $available_seats_count;
                                $fill_pct      = $total_seats_capacity > 0 ? round(($booked_seats / $total_seats_capacity) * 100) : 0;

                                $is_sold_out     = ($available_seats_count === 0);
                                $is_fast_filling = ($available_seats_count > 0 && $available_seats_count <= 12);

                                $status_class = 'status-available';
                                if ($is_sold_out)     $status_class = 'status-soldout';
                                elseif ($is_fast_filling) $status_class = 'status-fastfilling';

                                // Countdown & booking cutoff warning
                                $show_datetime_str  = $show_row['show_date'] . ' ' . $show_row['show_time'];
                                $show_dt            = new DateTime($show_datetime_str);
                                $now_dt             = new DateTime($current_datetime_string);
                                $diff               = $now_dt->diff($show_dt);
                                $diff_minutes       = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
                                $is_cutoff_warning  = ($diff_minutes <= 30 && $diff_minutes > 0);

                                if ($diff->days === 0 && $diff->h === 0) {
                                    $countdown_label = $diff->i . 'm';
                                } elseif ($diff->days === 0) {
                                    $countdown_label = $diff->h . 'h ' . $diff->i . 'm';
                                } else {
                                    $countdown_label = $diff->days . 'd ' . $diff->h . 'h';
                                }
                                ?>
                                <div class="showtime-row-card <?php echo $is_sold_out ? 'card-soldout' : ''; ?>"
                                     data-format="<?php echo htmlspecialchars($movie_record['movie_format']); ?>">

                                        <div class="card-body-row">
                                        <!-- Left: Screen & Time -->
                                        <div class="card-left-info">
                                            <div class="info-box">
                                                <i class="fa-solid fa-tv"></i>
                                                <span><?php echo htmlspecialchars($show_row['screen_name']); ?></span>
                                            </div>
                                            <div class="info-box">
                                                <i class="fa-regular fa-clock"></i>
                                                <span><?php echo htmlspecialchars(date('h:i A', strtotime($show_row['show_time']))); ?></span>
                                            </div>
                                        </div>

                                        <!-- Mid: Price, Seats, Status -->
                                        <div class="card-mid-price-seats">
                                            <div class="info-box price-box">
                                                <i class="fa-solid fa-indian-rupee-sign"></i>
                                                <span><?php echo htmlspecialchars(number_format($show_row['ticket_price'], 2)); ?></span>
                                            </div>

                                            <!-- Seat Info Box -->
                                            <div class="info-box seats-box">
                                                <i class="fa-solid fa-chair"></i>
                                                <span><?php echo $available_seats_count; ?> / <?php echo $total_seats_capacity; ?></span>
                                            </div>

                                            <div class="info-box status-box <?php echo $status_class; ?>">
                                                <i class="fa-solid fa-circle status-dot-icon"></i>
                                                <span>
                                                    <?php if ($is_sold_out): ?>
                                                        Sold Out
                                                    <?php elseif ($is_fast_filling): ?>
                                                        Fast Filling (<?php echo $available_seats_count; ?> left)
                                                    <?php else: ?>
                                                        Available
                                                    <?php endif; ?>
                                                </span>
                                            </div>

                                            <div class="info-box format-box">
                                                <i class="fa-solid fa-film"></i>
                                                <span><?php echo htmlspecialchars($movie_record['movie_format']); ?></span>
                                            </div>
                                        </div>

                                        <!-- Right: Action -->
                                        <div class="card-right-action">
                                            <?php if ($is_sold_out): ?>
                                                <button class="btn-seats-disabled" disabled>
                                                    <i class="fa-solid fa-xmark"></i> Sold Out
                                                </button>
                                            <?php else: ?>
                                                <a href="seat_selection.php?show_id=<?php echo intval($show_row['show_id']); ?>"
                                                   class="btn-seats-select seat-booking-link"
                                                   data-show-id="<?php echo intval($show_row['show_id']); ?>">
                                                    <i class="fa-solid fa-couch"></i> Select Seats
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-showtimes-placeholder">
                                <i class="fa-solid fa-calendar-xmark empty-icon"></i>
                                <h4>No Shows Available</h4>
                                <p>No active showtimes for this date. Please choose another date above.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- LEGEND -->
                    <div class="showtimes-legend-footer">
                        <span class="legend-title"><i class="fa-solid fa-circle-info"></i> Legend:</span>
                        <div class="legend-items-wrapper">
                            <span class="legend-item"><i class="fa-solid fa-circle" style="color:#14a44d;font-size:10px;"></i> Available</span>
                            <span class="legend-item"><i class="fa-solid fa-circle" style="color:#f59f00;font-size:10px;"></i> Fast Filling</span>
                            <span class="legend-item"><i class="fa-solid fa-circle" style="color:#ff4d2d;font-size:10px;"></i> Sold Out</span>
                            <span class="legend-item"><i class="fa-solid fa-circle" style="color:#888;font-size:10px;"></i> Unavailable</span>
                        </div>
                    </div>
                </section>

                <!-- ABOUT THE MOVIE -->
                <section class="section-card about-movie-card">
                    <h3 class="section-title"><i class="fa-solid fa-circle-info"></i> About The Movie</h3>
                    <p class="about-description-text"><?php echo nl2br(htmlspecialchars($movie_record['description'])); ?></p>
                </section>

            </div>

            <!-- SIDE COLUMN -->
            <div class="layout-side-column">

                <section class="section-card info-card">
                    <h3 class="section-title"><i class="fa-solid fa-circle-exclamation"></i> Important Information</h3>
                    <ul class="info-list">
                        <li><i class="fa-solid fa-clock info-bullet"></i><span>Please arrive 15 minutes before show time.</span></li>
                        <li><i class="fa-solid fa-ban info-bullet"></i><span>Outside food is not allowed.</span></li>
                        <li><i class="fa-solid fa-ticket info-bullet"></i><span>Booking can only be cancelled before 30 minutes of show time.</span></li>
                        <li><i class="fa-solid fa-lock info-bullet"></i><span>Seats remain locked for 5 minutes during booking.</span></li>
                    </ul>
                </section>

            </div>

        </div>

        <!-- SIMILAR MOVIES -->
        <?php if ($similar_movies_result && mysqli_num_rows($similar_movies_result) > 0): ?>
            <section class="similar-movies-section section-card">
                <h3 class="section-title"><i class="fa-solid fa-film"></i> You May Also Like</h3>
                <div class="similar-movies-grid">
                    <?php while ($similar_movie = mysqli_fetch_assoc($similar_movies_result)): ?>
                        <a href="movie_details.php?movie_id=<?php echo intval($similar_movie['movie_id']); ?>" class="similar-movie-card">
                            <div class="similar-poster-wrapper">
                                <?php if (!empty($similar_movie['poster_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($similar_movie['poster_url']); ?>"
                                         alt="<?php echo htmlspecialchars($similar_movie['title']); ?>"
                                         class="similar-poster-img"
                                         onerror="showFallback(this)">
                                <?php endif; ?>
                                <div class="no-image-fallback" style="<?php echo empty($similar_movie['poster_url']) ? 'display:flex;' : 'display:none;'; ?>">
                                    <i class="fa-solid fa-film fallback-icon"></i>
                                </div>
                                <span class="similar-badge-format">
                                    <i class="fa-solid fa-film"></i> <?php echo htmlspecialchars($similar_movie['movie_format']); ?>
                                </span>
                            </div>
                            <div class="similar-info">
                                <h4 class="similar-title"><?php echo htmlspecialchars($similar_movie['title']); ?></h4>
                                <span class="similar-genre"><?php echo htmlspecialchars($similar_movie['genre']); ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- BACK LINK -->
        <div class="back-home-wrapper">
            <a href="home.php" class="btn-back-home">
                <i class="fa-solid fa-arrow-left"></i> Back To Home
            </a>
        </div>

    </main>

    <?php if (file_exists(__DIR__ . '/footer.php')) { include_once 'footer.php'; } ?>

    <script src="../Assets/js/Customer/movie_details.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>