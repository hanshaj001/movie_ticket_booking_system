<?php
session_start();

// Security Access and Session Tracking
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'CUSTOMER') {
    header("Location: ../login.php");
    exit();
}

// Customer Name
$customer_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Customer';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Panel - MTBS</title>

    <link rel="stylesheet" href="../Assets/css/Customer/navbar.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header class="navbar">

    <div class="logo">
        MTBS
    </div>

    <ul class="nav-links">

        <li class="<?= $current_page == 'home.php' ? 'active' : '' ?>">
            <a href="home.php">
                <i class="fa-solid fa-house"></i>
                Home
            </a>
        </li>

        <li class="<?= $current_page == 'about.php' ? 'active' : '' ?>">
            <a href="about.php">
                <i class="fa-solid fa-circle-info"></i>
                About
            </a>
        </li>

        <li class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
            <a href="contact.php">
                <i class="fa-solid fa-phone"></i>
                Contact
            </a>
        </li>

        <li class="<?= $current_page == 'booking_history.php' ? 'active' : '' ?>">
            <a href="booking_history.php">
                <i class="fa-solid fa-ticket"></i>
                Booking History
            </a>
        </li>

    </ul>

    <div class="right">

        <div class="customer">
            <i class="fa-solid fa-circle-user"></i>
            <span><?= htmlspecialchars($customer_name); ?></span>
        </div>

        <a href="../logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</header>

</body>
</html>