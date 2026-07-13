<link rel="stylesheet" href="../Assets/css/Customer/footer.css">
<footer class="footer">
    <div class="footer-container">

        <!-- Project Information -->
        <div class="footer-brand">
            <h3>Movie Ticket Booking System</h3>
            <p>Your convenient platform for online movie ticket booking.</p>
        </div>

        <!-- Footer Navigation -->
        <div class="footer-links">
            <a href="home.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="booking_history.php">My Bookings</a>
        </div>

        <!-- Copyright -->
        <div class="footer-copy">
            <p>
                &copy; <?php echo date("Y"); ?>
                Movie Ticket Booking System. All Rights Reserved.
            </p>
        </div>

    </div>
</footer>

<script>
    // Highlight active footer link
    const currentPage = window.location.pathname.split("/").pop();

    document.querySelectorAll(".footer-links a").forEach(link => {
        const linkPage = link.getAttribute("href");

        if (currentPage === linkPage) {
            link.classList.add("active-link");
        }
    });
</script>