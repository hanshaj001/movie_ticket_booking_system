<?php
session_start();
<<<<<<< HEAD
=======
include '../Includes/db_conn.php';
>>>>>>> main

// Security Access and Session Tracking
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
//     header("Location: ../login.php");
//     exit();
// }

<<<<<<< HEAD
// Fallback to match login session configurations securely
$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);
=======
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user
    $sql = "SELECT u.user_id, u.full_name, u.email, u.password_hash, r.role_name
            FROM users u
            JOIN user_roles ur ON u.user_id = ur.user_id
            JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // ⚠️ Your DB uses plain password (admin123), so direct compare
        // Later you should upgrade to password_hash()
        if ($password === $user['password_hash']) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];

            // Role-based redirection
            if ($user['role_name'] == "ADMIN") {
                header("Location: ../Admin/dash.php");
            } else {
                header("Location: ../Customer/home.php");
            }
            exit();

        } else {
            $error = "Invalid password!";
        }

    } else {
        $error = "User not found!";
    }
}
>>>>>>> main
?>
<!DOCTYPE html>
<html lang="en">
<head>
<<<<<<< HEAD
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin System Panel - MTBS</title>
    <link rel="stylesheet" href="../Assets/sidebar.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
=======
    <title>Login - Movie Ticket System</title>
    <link rel="stylesheet" href="../Assets/login.css">
>>>>>>> main
</head>

<body>

<<<<<<< HEAD
<div class="overlay" id="overlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="logo">
        MTBS Admin
=======
<div class="login-container">

    <div class="login-box">
        <h2>Movie Ticket Login</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?= $error; ?></div>
        <?php } ?>

        <form method="POST" action="">
            
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter email" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <button type="submit">Login</button>
        </form>

        <p class="footer-text">
            Admin / Customer Login
        </p>
>>>>>>> main
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
            <a href="../logout.php">
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
            <h2>Admin Dashboard</h2>
        </div>

        <div class="right">
            <span id="datetime"></span>
            
            <div class="admin">
                <i class="fa-solid fa-circle-user"></i> <span><?= htmlspecialchars($admin_name); ?></span>
            </div>
            
            <a href="../logout.php" class="logout-btn">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </header>

    <main class="content">
        </main>
</div>

<<<<<<< HEAD
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
=======
>>>>>>> main
</body>
</html>