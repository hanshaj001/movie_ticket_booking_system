<?php
session_start();

// Security Access and Session Tracking
 if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
     header("Location: ../Includes/login.php");
    exit();
}

// Fallback to match login session configurations securely
$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin System Panel - MTBS</title>
    <link rel="stylesheet" href="../Assets/sidebar.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="overlay" id="overlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="logo">
        MTBS Admin
    </div>

    <ul>
        <li class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li class="<?= $current_page == 'add_movie.php' ? 'active' : '' ?>">
            <a href="add_movie.php">
                <i class="fa-solid fa-film"></i> Add Movie
            </a>
        </li>
        <li class="<?= $current_page == 'add_show.php' ? 'active' : '' ?>">
            <a href="add_show.php">
                <i class="fa-solid fa-calendar-plus"></i> Add Show
            </a>
        </li>

        <li class="<?= $current_page == 'booking_monitoring.php' ? 'active' : '' ?>">
            <a href="booking_monitoring.php">
                <i class="fa-solid fa-ticket"></i> Booking Monitoring
            </a>
        </li>
        <li class="logout-menu">
            <a href="../Includes/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<div class="main">

    <header class="navbar">
        <div class="left">
            <button class="toggle-btn" id="toggleBtn" aria-label="Open Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div class="right">
            <span id="datetime"></span>
            
            <div class="admin">
                <i class="fa-solid fa-circle-user"></i> <span><?= htmlspecialchars($admin_name); ?></span>
            </div>
            
            <a href="../Includes/logout.php" class="logout-btn">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </header>
    
</div>

<script>
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const overlay = document.getElementById("overlay");

// Off-canvas mobile navigation drawer toggle routines
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
});

overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
});

// Auto drop sidebar active drawers during screen size shifts or selections
document.querySelectorAll(".sidebar a").forEach(link => {
    link.addEventListener("click", () => {
        if(window.innerWidth <= 768){
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        }
    });
});

// Precision Live Time Clock Routine Updating Instantly Every 1000ms
function updateDateTime(){
    const now = new Date();
    const options = { day:'2-digit', month:'long', year:'numeric' };
    const date = now.toLocaleDateString('en-GB', options);
    const time = now.toLocaleTimeString();
    
    const dateTimeElement = document.getElementById("datetime");
    if(dateTimeElement) {
        dateTimeElement.innerHTML = `<i class="fa-regular fa-clock"></i> ${date} | ${time}`;
    }
}
setInterval(updateDateTime, 1000);
updateDateTime();
</script>
</body>
</html>