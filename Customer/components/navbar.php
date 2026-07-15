<?php
// Public navbar for guests/customers (no role-gated access control)
// Replace any previous customer-only navbar to ensure Register is accessible.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer_name = isset($_SESSION['name']) ? $_SESSION['name'] : null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="navbar">
    <div class="logo">MTBS</div>

    <ul class="nav-links">
        <li class="<?= $current_page == 'home.php' ? 'active' : '' ?>">
            <a href="home.php"><i class="fa-solid fa-house"></i> Home</a>
        </li>
        <li class="<?= $current_page == 'about.php' ? 'active' : '' ?>">
            <a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a>
        </li>
        <li class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
            <a href="contact.php"><i class="fa-solid fa-phone"></i> Contact</a>
        </li>
        <li class="<?= $current_page == 'booking_history.php' ? 'active' : '' ?>">
            <a href="booking_history.php"><i class="fa-solid fa-ticket"></i> Booking History</a>
        </li>
    </ul>

    <div class="right">
        <?php if ($customer_name): ?>
            <div class="customer"><i class="fa-solid fa-circle-user"></i><span><?= htmlspecialchars($customer_name); ?></span></div>
            <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="../Includes/login.php" class="logout-btn"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
        <?php endif; ?>
    </div>
</header>

