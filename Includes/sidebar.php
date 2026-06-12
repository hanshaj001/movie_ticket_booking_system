<?php

session_start();

if(
    !isset($_SESSION['user_id'])
    || $_SESSION['role'] != 'ADMIN'
){
    header("Location: ../login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title> </title>
  <link rel="stylesheet" href="../Assets/sidebar.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
  
<body>
<body>

<!-- Mobile Overlay -->
<div class="overlay" id="overlay"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">

    <div class="logo">
         MTBS Admin
    </div>

    <ul>

        <li class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                📊 Dashboard
            </a>
        </li>

        <li class="<?= basename($_SERVER['PHP_SELF'])=='manage_movies.php' ? 'active' : '' ?>">
            <a href="manage_movies.php">
                🎥 Manage Movies
            </a>
        </li>

        <li class="<?= basename($_SERVER['PHP_SELF'])=='add_show.php' ? 'active' : '' ?>">
            <a href="add_show.php">
                🎭 Add Show
            </a>
        </li>

        <li class="<?= basename($_SERVER['PHP_SELF'])=='seat_monitoring.php' ? 'active' : '' ?>">
            <a href="seat_monitoring.php">
                💺 Seat Monitoring
            </a>
        </li>

        <li class="<?= basename($_SERVER['PHP_SELF'])=='booking_monitoring.php' ? 'active' : '' ?>">
            <a href="booking_monitoring.php">
                📑 Booking Monitoring
            </a>
        </li>

        <li class="logout-menu">
            <a href="../logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</nav>

<!-- MAIN -->
<div class="main">

    <!-- NAVBAR -->
    <header class="navbar">

<<<<<<< HEAD
        <div class="left">

            <button
                class="toggle-btn"
                id="toggleBtn"
                aria-label="Open Navigation">
                ☰
            </button>

            <h2>Admin Dashboard</h2>

        </div>

        <div class="right">

            <div id="datetime"></div>

            <div class="admin">
                👤 <?= $_SESSION['full_name']; ?>
            </div>

            <a href="../logout.php" class="logout-btn">
                Logout
            </a>

        </div>

    </header>

    <div class="content">

        <!-- Page Content -->

=======
    <div class="right">
      <span id="datetime"></span>
      <div class="admin">
        <i class="fa fa-user-circle"></i>
        Admin
      </div>
>>>>>>> main
    </div>

</div>

</body>

<script>

const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const overlay = document.getElementById("overlay");

toggleBtn.addEventListener("click", () => {

    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");

});

overlay.addEventListener("click", () => {

    sidebar.classList.remove("active");
    overlay.classList.remove("active");

});

document.querySelectorAll(".sidebar a").forEach(link => {

    link.addEventListener("click", () => {

        if(window.innerWidth <= 768){

            sidebar.classList.remove("active");
            overlay.classList.remove("active");

        }

    });

});

/* Professional Date Time */

function updateDateTime(){

    const now = new Date();

    const options = {
        day:'2-digit',
        month:'long',
        year:'numeric'
    };

    const date = now.toLocaleDateString('en-GB', options);

    const time = now.toLocaleTimeString();

    document.getElementById("datetime").innerHTML =
        date + " | " + time;
}

setInterval(updateDateTime,1000);

updateDateTime();

</script>

</body>
</html>
