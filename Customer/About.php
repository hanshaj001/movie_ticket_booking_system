<?php
//session_start();

/* Customer Authentication */
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'CUSTOMER') {
//     header("Location: login.php");
//     exit();
// }
// ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us | Movie Ticket Booking System</title>

<link rel="stylesheet" href="../Assets/css/Customer/about.css">
<link rel="stylesheet" href="../Assets/css/Customer/navbar.css">
<link rel="stylesheet" href="../Assets/css/Customer/footer.css">


<!-- Font Awesome -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>

<!-- Navbar -->
<?php
 include('../Customer/components/navbar.php'); 
 ?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="hero-content">
        <h1>About Our Cinema</h1>
        <p>
            Welcome to Movie Ticket Booking System (MTBS),
            your convenient platform for discovering movies,
            checking real-time seat availability, and booking tickets online.
        </p>
    </div>
</section>

<!-- About Cinema -->
<section class="section">
    <div class="container">
        <h2>About Our Cinema</h2>
        <p>
            Our cinema provides an enjoyable movie experience with modern
            facilities, multiple screens, comfortable seating arrangements,
            and the latest movie releases. We aim to deliver entertainment
            in a convenient and customer-friendly environment.
        </p>
    </div>
</section>

<!-- Mission -->
<section class="section bg-light">
    <div class="container">
        <h2>Our Mission</h2>
        <p>
            To provide a fast, reliable, and secure movie ticket booking
            experience while ensuring customer satisfaction through modern
            technology and excellent cinema facilities.
        </p>
    </div>
</section>

<!-- Features -->
<section class="section">
    <div class="container">
        <h2>System Features</h2>

        <div class="feature-grid">

            <div class="feature-card">
                <i class="fa-solid fa-ticket"></i>
                <h3>Online Movie Booking</h3>
                <p>Book movie tickets anytime from anywhere.</p>
            </div>

            <div class="feature-card">
                <i class="fa-solid fa-film"></i>
                <h3>Multiple Screens</h3>
                <p>Enjoy movies across different cinema screens.</p>
            </div>

            <div class="feature-card">
                <i class="fa-solid fa-couch"></i>
                <h3>Real-Time Seat Availability</h3>
                <p>View available seats instantly before booking.</p>
            </div>

            <div class="feature-card">
                <i class="fa-solid fa-lock"></i>
                <h3>Secure Booking Process</h3>
                <p>Safe booking system with authentication.</p>
            </div>

            <div class="feature-card">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h3>Booking History</h3>
                <p>Access previous bookings anytime.</p>
            </div>

            <div class="feature-card">
                <i class="fa-solid fa-ban"></i>
                <h3>Easy Cancellation</h3>
                <p>Cancel eligible bookings easily.</p>
            </div>

        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section bg-light">
    <div class="container">
        <h2>Why Choose Us</h2>

        <div class="why-grid">

            <div class="why-box">
                <i class="fa-solid fa-bolt"></i>
                <h4>Fast Booking</h4>
            </div>

            <div class="why-box">
                <i class="fa-solid fa-shield-halved"></i>
                <h4>Secure Authentication</h4>
            </div>

            <div class="why-box">
                <i class="fa-solid fa-mobile-screen"></i>
                <h4>Simple Interface</h4>
            </div>

            <div class="why-box">
                <i class="fa-solid fa-circle-check"></i>
                <h4>Instant Confirmation</h4>
            </div>

        </div>
    </div>
</section>

<!-- Facilities -->
<section class="section">
    <div class="container">

        <h2>Our Facilities</h2>

        <div class="facility-grid">

            <div class="facility-card">
                <i class="fa-solid fa-snowflake"></i>
                <h3>Air Conditioned Halls</h3>
            </div>

            <div class="facility-card">
                <i class="fa-solid fa-chair"></i>
                <h3>Comfortable Seats</h3>
            </div>

            <div class="facility-card">
                <i class="fa-solid fa-tv"></i>
                <h3>Multiple Movie Screens</h3>
            </div>

            <div class="facility-card">
                <i class="fa-solid fa-clapperboard"></i>
                <h3>Latest Movies</h3>
            </div>

        </div>

    </div>
</section>

<!-- Online Booking -->
<section class="section booking-info">
    <div class="container">

        <h2>Online Movie Booking</h2>

        <p>
            MTBS allows customers to browse movies, check show schedules,
            view real-time seat availability, select preferred seats,
            and receive instant booking confirmation through a simple
            and secure online booking process.
        </p>

    </div>
</section>

<!-- Footer -->
<?php include '../Customer/components/footer.php'; 

?>

<script src="../Assets/js/Customer/about.js"></script>

</body>
</html>