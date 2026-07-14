<?php
// Public footer shared by register/login/movies pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<footer class="public-footer">
  <div>© <?= date('Y'); ?> Movie Ticket Booking System</div>
</footer>

