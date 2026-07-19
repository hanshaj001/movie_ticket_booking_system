
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

// Fetch Premium Showcase Movies
$carousel_movies = [];
$carousel_sql = "
    SELECT m.movie_id, m.title, m.banner_url, m.poster_url, m.description, 
           m.movie_format, m.language, m.duration_minutes,
           GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', ') AS genres
    FROM movies m
    JOIN shows s ON m.movie_id = s.movie_id
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    WHERE m.status = 'ACTIVE' 
      AND s.show_status = 'ACTIVE'
      AND (s.show_date > CURDATE() OR (s.show_date = CURDATE() AND s.show_time > CURTIME()))
    GROUP BY m.movie_id
    ORDER BY m.movie_id DESC 
    LIMIT 5
";
$carousel_res = mysqli_query($conn, $carousel_sql);
if ($carousel_res && mysqli_num_rows($carousel_res) > 0) {
    while ($row = mysqli_fetch_assoc($carousel_res)) {
        $banner_path = '';
        if (!empty($row['banner_url'])) {
            $bp = 'Assets/uploads/movie_banners/' . basename($row['banner_url']);
            if (file_exists(__DIR__ . '/' . $bp)) $banner_path = $bp;
        }
        if (empty($banner_path) && !empty($row['poster_url'])) {
            $pp = 'Assets/uploads/movie_posters/' . basename($row['poster_url']);
            if (file_exists(__DIR__ . '/' . $pp)) $banner_path = $pp;
        }
        if (!empty($banner_path)) {
            $row['banner_url'] = $banner_path;
            $carousel_movies[] = $row;
        }
    }
}

// Fallback logic
if (empty($carousel_movies)) {
    $fallback_sql = "
        SELECT m.movie_id, m.title, m.banner_url, m.poster_url, m.description, 
               m.movie_format, m.language, m.duration_minutes,
               GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', ') AS genres
        FROM movies m
        LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        WHERE m.status = 'ACTIVE' 
        GROUP BY m.movie_id
        ORDER BY m.movie_id DESC LIMIT 5
    ";
    $fallback_res = mysqli_query($conn, $fallback_sql);
    if ($fallback_res && mysqli_num_rows($fallback_res) > 0) {
        while ($row = mysqli_fetch_assoc($fallback_res)) {
            $banner_path = '';
            if (!empty($row['banner_url'])) {
                $bp = 'Assets/uploads/movie_banners/' . basename($row['banner_url']);
                if (file_exists(__DIR__ . '/' . $bp)) $banner_path = $bp;
            }
            if (empty($banner_path) && !empty($row['poster_url'])) {
                $pp = 'Assets/uploads/movie_posters/' . basename($row['poster_url']);
                if (file_exists(__DIR__ . '/' . $pp)) $banner_path = $pp;
            }
            if (!empty($banner_path)) {
                $row['banner_url'] = $banner_path;
                $carousel_movies[] = $row;
            }
        }
    }
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
    if (empty($poster) || !file_exists(__DIR__ . '/' . $poster_path)) {
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
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
    <link rel="stylesheet" href="Assets/css/index.css">
</head>
<body>

    <?php include 'Customer/components/navbar.php'; ?>

    <!-- Premium Hero Showcase -->
    <?php if(!empty($carousel_movies)): ?>
    <section class="premium-hero-showcase">
        <div class="showcase-slider">
            <?php foreach($carousel_movies as $index => $hero): ?>
            <div class="showcase-slide <?= $index === 0 ? 'active' : '' ?>">
                <!-- Background Image -->
                <div class="showcase-bg">
                    <img src="<?= htmlspecialchars(!empty($hero['banner_url']) ? $hero['banner_url'] : $svg_placeholder) ?>" alt="<?= htmlspecialchars($hero['title']) ?>">
                    <div class="showcase-vignette"></div>
                </div>

                <!-- Content Overlay -->
                <div class="showcase-content container">
                    <div class="showcase-info-glass">
                        <div class="showcase-badges">
                            <?php if(!empty($hero['movie_format'])): ?>
                                <span class="s-badge format-badge"><?= htmlspecialchars($hero['movie_format']) ?></span>
                            <?php endif; ?>
                            <?php if(!empty($hero['language'])): ?>
                                <span class="s-badge lang-badge"><?= htmlspecialchars($hero['language']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="showcase-title"><?= htmlspecialchars($hero['title']) ?></h1>
                        
                        <div class="showcase-meta">
                            <?php if(!empty($hero['genres'])): ?>
                                <span class="meta-item"><i class="fa-solid fa-film"></i> <?= htmlspecialchars($hero['genres']) ?></span>
                            <?php endif; ?>
                            <?php if(!empty($hero['duration_minutes'])): ?>
                                <span class="meta-item"><i class="fa-solid fa-clock"></i> <?= $hero['duration_minutes'] ?> min</span>
                            <?php endif; ?>
                        </div>

                        <div class="showcase-actions">
                            <a href="Customer/movie_details.php?movie_id=<?= $hero['movie_id'] ?>" class="btn-showcase-primary">
                                <i class="fa-solid fa-ticket"></i> Book Now
                            </a>
                            <a href="Customer/movie_details.php?movie_id=<?= $hero['movie_id'] ?>" class="btn-showcase-secondary">
                                <i class="fa-solid fa-circle-info"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(count($carousel_movies) > 1): ?>
        <!-- Custom Controls -->
        <div class="showcase-controls container">
            <div class="showcase-nav">
                <button id="showcase-prev" class="s-nav-btn"><i class="fa-solid fa-arrow-left-long"></i></button>
                <div class="showcase-pagination">
                    <?php foreach($carousel_movies as $index => $hero): ?>
                        <span class="s-dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></span>
                    <?php endforeach; ?>
                </div>
                <button id="showcase-next" class="s-nav-btn"><i class="fa-solid fa-arrow-right-long"></i></button>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>


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
    <script src="Assets/js/index.js"></script>
</body>
</html>
