<?php
session_start();
require_once '../Includes/db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us | Movie Ticket Booking System</title>
  <link rel="stylesheet" href="../Assets/css/Customer/about.css" />
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="about-body">

<?php include 'components/navbar.php'; ?>

<main class="about-main">
  <div class="about-container">
    <!-- Breadcrumbs Navigation -->
    <nav class="breadcrumb-nav">
        <a href="home.php" class="bc-link"><i class="fa-solid fa-house"></i> Home</a>
        <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <span class="bc-current">About Us</span>
    </nav>

    <!-- Intro & Mission -->
    <section class="intro-mission-grid">
      <div class="about-card intro-card">
        <h2 class="card-title"><i class="fa-solid fa-film"></i> About Our Cinema</h2>
        <p class="lead-text">Welcome to Movie Ticket Booking System (MTBS), a convenient platform for discovering movies, checking real‑time seat availability, and booking tickets online.</p>
        <p class="body-text">Our cinema offers modern facilities, multiple screens, comfortable seating, and the latest releases. We aim to deliver entertainment in a customer‑friendly environment.</p>
        <p class="body-text"><strong>Location:</strong> Baneshwor</p>
      </div>
      <div class="about-card mission-card">
        <h2 class="card-title"><i class="fa-solid fa-bullseye"></i> Our Mission</h2>
        <p class="lead-text">Empowering movie lovers with seamless access to premium cinema entertainment.</p>
        <p class="body-text">Provide a fast, reliable, and secure booking experience while ensuring satisfaction through modern technology and excellent facilities.</p>
        <p class="body-text"><strong>Location:</strong> Baneshwor</p>
      </div>
    </section>

    <!-- System Features (4‑column boxes) -->
    <section class="about-section">
      <h2 class="section-heading">System Features</h2>
      <div class="feature-list">
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-ticket"></i></div>
          <h3>Online Booking</h3>
          <p>Book tickets anytime, anywhere.</p>
        </div>
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-video"></i></div>
          <h3>Multiple Screens</h3>
          <p>Enjoy movies across different halls.</p>
        </div>
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-couch"></i></div>
          <h3>Real‑Time Seats</h3>
          <p>View seat availability instantly.</p>
        </div>
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-lock"></i></div>
          <h3>Secure Booking</h3>
          <p>Robust authentication and data protection.</p>
        </div>
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <h3>Booking History</h3>
          <p>Access past bookings at a glance.</p>
        </div>
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-ban"></i></div>
          <h3>Easy Cancellation</h3>
          <p>Cancel eligible bookings effortlessly.</p>
        </div>
      </div>
    </section>

    <!-- Why Choose Us (4‑column boxes) -->
    <section class="about-section">
      <h2 class="section-heading">Why Choose Us</h2>
      <div class="why-list">
        <div class="why-card">
          <div class="icon-wrapper"><i class="fa-solid fa-bolt"></i></div>
          <h3>Fast Booking</h3>
          <p>Reserve seats in under a minute.</p>
        </div>
        <div class="why-card">
          <div class="icon-wrapper"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>Secure Login</h3>
          <p>Strong user authentication and privacy.</p>
        </div>
        <div class="why-card">
          <div class="icon-wrapper"><i class="fa-solid fa-mobile-screen"></i></div>
          <h3>Responsive UI</h3>
          <p>Optimized for desktop and mobile devices.</p>
        </div>
        <div class="why-card">
          <div class="icon-wrapper"><i class="fa-solid fa-circle-check"></i></div>
          <h3>Instant Ticket</h3>
          <p>Real‑time confirmation with seat details.</p>
        </div>
      </div>
    </section>

    <!-- Facilities (4‑column boxes) -->
    <section class="about-section">
      <h2 class="section-heading">Our Facilities</h2>
      <div class="facility-list">
        <div class="facility-card">
          <div class="icon-wrapper"><i class="fa-solid fa-snowflake"></i></div>
          <h3>Air‑Conditioned Halls</h3>
          <p>Comfortable climate‑controlled screens.</p>
        </div>
        <div class="facility-card">
          <div class="icon-wrapper"><i class="fa-solid fa-chair"></i></div>
          <h3>Premium Seats</h3>
          <p>Luxury reclining leather chairs.</p>
        </div>
        <div class="facility-card">
          <div class="icon-wrapper"><i class="fa-solid fa-tv"></i></div>
          <h3>HD Projection</h3>
          <p>High‑definition screens with Dolby sound.</p>
        </div>
        <div class="facility-card">
          <div class="icon-wrapper"><i class="fa-solid fa-clapperboard"></i></div>
          <h3>Latest Releases</h3>
          <p>Fresh movies updated daily.</p>
        </div>
      </div>
    </section>

  </div>
</main>

<?php include 'components/footer.php'; ?>

<script src="../Assets/js/Customer/about.js"></script>
</body>
</html>
