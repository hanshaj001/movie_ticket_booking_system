<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "movie_ticket_booking_system"
);

if(!$conn)
{
    die("Database Connection Failed");
}

?>


<?php
session_start();
require_once 'Includes/db_conn.php';

// Validate selected date parameter
$selected_date = date('Y-m-d');
if (isset($_GET['date'])) {
    $input_date = trim($_GET['date']);
    $d = DateTime::createFromFormat('Y-m-d', $input_date);
    if ($d && $d->format('Y-m-d') === $input_date) {
        $selected_date = $input_date;
    }
}

// Generate Date Filter (Total 7 dates)
$filter_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date_val = date('Y-m-d', strtotime("+$i day"));
    if ($i === 0) {
        $label = 'Today';
    } elseif ($i === 1) {
        $label = 'Tomm';
    } else {
        $label = date('d M', strtotime($date_val));
    }
    
    $filter_dates[] = [
        'date' => $date_val,
        'label' => $label
    ];
}

// Fetch Carousel Movies
$carousel_movies = [];
$carousel_sql = "SELECT DISTINCT m.movie_id, m.title, m.banner_url, m.poster_url, m.description, m.movie_format, m.language, m.duration_minutes 
                 FROM movies m
                 JOIN shows s ON m.movie_id = s.movie_id
                 WHERE m.status = 'ACTIVE' 
                   AND s.show_status = 'ACTIVE'
                   AND (s.show_date > CURDATE() OR (s.show_date = CURDATE() AND s.show_time > CURTIME()))
                 ORDER BY m.movie_id DESC LIMIT 5";
$carousel_res = mysqli_query($conn, $carousel_sql);
if ($carousel_res && mysqli_num_rows($carousel_res) > 0) {
    while ($row = mysqli_fetch_assoc($carousel_res)) {
        $banner_path = '';
        if (!empty($row['banner_url'])) {
            $banner_file = basename($row['banner_url']);
            $bp = 'Assets/uploads/movie_banners/' . $banner_file;
            if (file_exists($bp)) $banner_path = $bp;
        }
        if (empty($banner_path) && !empty($row['poster_url'])) {
            $poster_file = basename($row['poster_url']);
            $pp = 'Assets/uploads/movie_posters/' . $poster_file;
            if (file_exists($pp)) $banner_path = $pp;
        }
        if (!empty($banner_path)) {
            $row['banner_url'] = $banner_path;
            $carousel_movies[] = $row;
            break; // We only need 1 banner for the static hero section
        }
    }
}

// Fallback banner
if (empty($carousel_movies)) {
    $carousel_movies[] = [
        'movie_id' => 1,
        'title' => "Experience the Magic of Cinema",
        'description' => "Browse the latest movies and reserve your favorite seats.",
        'banner_url' => "",
        'movie_format' => "2D",
        'language' => "English",
        'duration_minutes' => 120
    ];
}

// Fetch Currently Showing Movies
$movies_sql = "
    SELECT 
        m.movie_id, 
        m.title, 
        m.duration_minutes, 
        m.language, 
        m.movie_format, 
        m.poster_url,
        GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR ', ') as genres,
        (SELECT MIN(show_time) FROM shows s2 WHERE s2.movie_id = m.movie_id AND s2.show_date = ? AND s2.show_status = 'ACTIVE' AND (s2.show_date > CURDATE() OR (s2.show_date = CURDATE() AND s2.show_time > CURTIME()))) as earliest_show
    FROM movies m
    JOIN shows s ON m.movie_id = s.movie_id
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    WHERE m.status = 'ACTIVE' 
      AND s.show_status = 'ACTIVE'
      AND s.show_date = ?
      AND (s.show_date > CURDATE() OR (s.show_date = CURDATE() AND s.show_time > CURTIME()))
    GROUP BY m.movie_id
    ORDER BY m.movie_id ASC
";

$stmt = $conn->prepare($movies_sql);
$stmt->bind_param("ss", $selected_date, $selected_date);
$stmt->execute();
$movies_result = $stmt->get_result();

$now_showing = [];
while ($row = $movies_result->fetch_assoc()) {
    $now_showing[] = $row;
}

// Fetch Upcoming Movies
$upcoming_sql = "
    SELECT 
        m.movie_id, 
        m.title, 
        m.duration_minutes, 
        m.language, 
        m.movie_format, 
        m.poster_url,
        m.release_date,
        GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR ', ') as genres
    FROM movies m
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    WHERE m.status = 'ACTIVE'
      AND m.movie_id NOT IN (
          SELECT DISTINCT movie_id FROM shows 
          WHERE show_status = 'ACTIVE'
            AND (show_date > CURDATE() OR (show_date = CURDATE() AND show_time > CURTIME()))
      )
    GROUP BY m.movie_id
    ORDER BY m.release_date ASC
";
$up_stmt = $conn->prepare($upcoming_sql);
$up_stmt->execute();
$up_res = $up_stmt->get_result();
$upcoming_movies = [];
while ($row = $up_res->fetch_assoc()) {
    $upcoming_movies[] = $row;
}

// Safe SVG Placeholder to prevent onerror loop
$svg_placeholder = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22300%22%20height%3D%22450%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ff4d2d%22%2F%3E%3Ctext%20fill%3D%22%23ffffff%22%20font-family%3D%22sans-serif%22%20font-size%3D%2224%22%20dy%3D%2210.5%22%20font-weight%3D%22bold%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%3ENo%20Poster%3C%2Ftext%3E%3C%2Fsvg%3E";

function renderCard($movie, $selected_date, $isUpcoming = false, $svg_placeholder) {
    $details_url = "Customer/movie_details.php?movie_id=" . urlencode($movie['movie_id']) . "&date=" . urlencode($selected_date);
    
    $poster = $movie['poster_url'] ?? '';
    $poster_file = basename($poster);
    $poster_path = 'Assets/uploads/movie_posters/' . $poster_file;
    if (empty($poster) || !file_exists($poster_path)) {
        $poster_img_src = $svg_placeholder;
    } else {
        $poster_img_src = $poster_path;
    }
    
    $title = $movie['title'] ?? 'Unknown Title';
    $duration_minutes = $movie['duration_minutes'] ?? '120';
    $rating_badge = (($movie['movie_id'] ?? 1) % 2 === 0) ? 'PG' : 'A';
    
    ?>
    <div class="ta-movie-item">
        <div class="ta-showing-img-wrapper">
            <div class="ta-imageWrapper">
                <img src="<?= htmlspecialchars($poster_img_src) ?>" alt="<?= htmlspecialchars($title) ?>" onerror="this.onerror=null; this.src='<?= $svg_placeholder ?>';">
                <span class="ta-movie-grade"><?= htmlspecialchars($rating_badge) ?></span>
                <?php if ($isUpcoming): ?>
                    <div class="movie-tag"><span>Advance Purchase</span></div>
                <?php endif; ?>
            </div>
            <ul class="ta-overView">
                <li class="ta-hview"><a href="<?= htmlspecialchars($details_url) ?>"><i class="fa-solid fa-play"></i> Play Trailer</a></li>
                <li class="ta-hdetail"><a href="<?= htmlspecialchars($details_url) ?>" class="movie-card-btn"><i class="fa-solid fa-ticket"></i> Buy Ticket</a></li>
            </ul>
        </div>
        <div class="ta-show-info">
            <div class="ta-movie-title">
                <a href="<?= htmlspecialchars($details_url) ?>"><?= htmlspecialchars($title) ?></a>
            </div>
            <?php if ($isUpcoming && !empty($movie['release_date'])): ?>
                <!-- Releasing on date removed as per requirements -->
            <?php endif; ?>
            <div class="ta-like-duration-wrapper">
                <div class="ta-show-duration"><?= floor($duration_minutes / 60) ?>h <?= $duration_minutes % 60 ?>m</div>
            </div>
            
            <?php if (!$isUpcoming && isset($movie['earliest_show'])): ?>
                <div class="ta-fShow-time">
                    Earliest: <?= date('h:i A', strtotime($movie['earliest_show'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Movie Ticket Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Assets/css/index.css">
</head>
<body>

    <?php include 'Customer/components/navbar.php'; ?>

    <!-- Hero Banner (Carousel emulation) -->
    <header class="ta-hBanner">
        <?php $hero = $carousel_movies[0]; ?>
        <div class="ta-hBanner-wrapper" style="background-image: url('<?= htmlspecialchars(!empty($hero['banner_url']) ? $hero['banner_url'] : $svg_placeholder) ?>');">
            <div class="ta-fBanner-item-wrapper">
                
                <div class="ta-overlay"></div>
                
                <ul class="ta-bbody">
                    <li class="ta-bname">
                        <a href="#currently-showing"><?= htmlspecialchars($hero['title']) ?></a>
                    </li>
                    <li>
                        <ul class="ta-bbody-f">
                            <li class="ta-hdetail">
                                <a href="#currently-showing" class="movie-card-btn"><i class="fa-solid fa-ticket"></i> Buy Tickets</a>
                            </li>
                        </ul>
                    </li>
                </ul>

            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="ta-container-template">
        
        <!-- Now Showing -->
        <section id="currently-showing" class="nowShowing ta-sectionWrapper">
            <div class="ta-tMovie-wrapper container">
                <div class="ta-tMovie-header">
                    <div class="ta-sectionTitle color-primary">Now Showing</div>
                    
                    <div class="ta-showDaysWrapper">
                        <?php foreach ($filter_dates as $d): ?>
                            <div class="ta-showDays <?= ($selected_date === $d['date']) ? 'active' : '' ?>">
                                <a href="index.php?date=<?= urlencode($d['date']) ?>#currently-showing"><?= htmlspecialchars($d['label']) ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ta-tMovie-list">
                    <div class="ta-movieWrapper">
                        <?php if (count($now_showing) > 0): ?>
                            <?php foreach ($now_showing as $movie): ?>
                                <div class="ta-movies-showing">
                                    <?php renderCard($movie, $selected_date, false, $svg_placeholder); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No movies are available for the selected date. Please choose another date.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Upcoming Movies -->
        <section id="upcoming-movies" class="ta-sectionWrapper ta-sectionWrapper-type-1">
            <div class="ta-tMovie-wrapper container">
                <div class="ta-tMovie-header">
                    <div class="ta-sectionTitle color-primary">Upcoming</div>
                </div>
                
                <div class="ta-tMovie-list">
                    <div class="ta-movieWrapper">
                        <?php if (count($upcoming_movies) > 0): ?>
                            <?php foreach ($upcoming_movies as $movie): ?>
                                <div class="ta-movies-showing">
                                    <?php renderCard($movie, $selected_date, true, $svg_placeholder); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No upcoming releases currently listed.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php include 'Customer/components/footer.php'; ?>

</body>
</html>
