<?php
// Customer/components/footer.php

$in_customer_dir = (dirname($_SERVER['PHP_SELF']) !== '/' && strpos(dirname($_SERVER['PHP_SELF']), 'Customer') !== false);
$base_path = $in_customer_dir ? '../' : '';
$customer_path = $in_customer_dir ? '' : 'Customer/';
?>

<link rel="stylesheet" href="<?= $base_path ?>Assets/css/Customer/footer.css?v=<?= time() ?>">

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>Movie Ticket Booking System</h3>
            <p>Experience the best of cinema with our easy-to-use movie ticket booking platform. Find movies, select your seats, and enjoy the show!</p>
            <div class="footer-social">
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-twitter"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
        
        <div class="footer-col">
            <h3>Quick Links</h3>
            <ul class="footer-links">
                <li><a href="<?= $customer_path ?>home.php">Home</a></li>
                <li><a href="<?= $customer_path ?>About.php">About Us</a></li>
                <li><a href="<?= $customer_path ?>Contact.php">Contact Us</a></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        &copy; <?= date("Y") ?> Movie Ticket Booking System. All rights reserved.
    </div>
</footer>
