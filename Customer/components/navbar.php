<?php
// Customer/components/navbar.php

// Helper to determine active page
$current_page = basename($_SERVER['PHP_SELF']);

// Check login status
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'CUSTOMER';
$customer_name = $is_logged_in ? $_SESSION['full_name'] : '';

// Resolve base URL for links depending on where the navbar is included
// If we're in login.php (root), we need to adjust paths differently than if we're in Customer/home.php
// Let's assume a generic approach: if $current_page is login.php, base is '', else base is '../'
$in_customer_dir = (dirname($_SERVER['PHP_SELF']) !== '/' && strpos(dirname($_SERVER['PHP_SELF']), 'Customer') !== false);
$base_path = $in_customer_dir ? '../' : '';
$customer_path = $in_customer_dir ? '' : 'Customer/';
?>

<link rel="stylesheet" href="<?= $base_path ?>Assets/css/Customer/navbar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<nav class="navbar">
    <a href="<?= $customer_path ?>home.php" class="nav-brand">MTBS</a>
    
    <div class="menu-toggle" id="mobile-menu">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <ul class="nav-links" id="nav-links">
        <li><a href="<?= $customer_path ?>home.php" class="<?= $current_page == 'home.php' ? 'active' : '' ?>">Home</a></li>
        <li><a href="<?= $customer_path ?>About.php" class="<?= $current_page == 'About.php' ? 'active' : '' ?>">About</a></li>
        <li><a href="<?= $customer_path ?>Contact.php" class="<?= $current_page == 'Contact.php' ? 'active' : '' ?>">Contact</a></li>
        
        <?php if ($is_logged_in): ?>
            <li><a href="<?= $customer_path ?>booking_history.php" class="<?= $current_page == 'booking_history.php' ? 'active' : '' ?>">Booking History</a></li>
            <li>
            <a href="<?php echo $base_path; ?>logout.php" class="direct-logout-btn-rect">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>
            <li class="user-dropdown">
                <span class="user-name">
                    <i class="fa-regular fa-user-circle"></i> <?= htmlspecialchars($customer_name) ?>
                </span>
            </li>
        <?php else: ?>
            <?php
            // Determine redirect URL for navbar buttons
            $redirect_qs = '';
            if ($current_page !== 'login.php' && $current_page !== 'register.php') {
                $redirect_qs = '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
            } else {
                if (!empty($_SESSION['pending_redirect'])) {
                    $redirect_qs = '?redirect=' . urlencode($_SESSION['pending_redirect']);
                } elseif (!empty($_GET['redirect'])) {
                    $redirect_qs = '?redirect=' . urlencode($_GET['redirect']);
                }
            }
            ?>
            <li class="nav-auth">
                <a href="<?= $base_path ?>login.php<?= $redirect_qs ?>" class="login-btn <?= $current_page == 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="<?= $customer_path ?>register.php<?= $redirect_qs ?>" class="register-btn">Register</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
