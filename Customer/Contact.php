<?php
session_start();


/* Authentication Check */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validation

    if (empty($fullname)) {
        $errors[] = "Full Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid Email Address.";
    }

    if (empty($phone)) {
        $errors[] = "Phone Number is required.";
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $errors[] = "Phone Number must contain only numbers.";
    }

    if (empty($subject)) {
        $errors[] = "Subject is required.";
    }

    if (empty($message)) {
        $errors[] = "Message is required.";
    }

    if (empty($errors)) {
        $success = "Your inquiry has been submitted successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us | Movie Ticket Booking System</title>

<link rel="stylesheet" href="../Assets/Customer/contact.css">
</head>
<body>

<?php include '../Customer/components/navbar.php'; ?>

<section class="contact-header">
    <h1>Contact Us</h1>
    <p>We're here to help with bookings, schedules, and cinema services.</p>
</section>

<div class="contact-container">

    <!-- Contact Information -->
    <div class="contact-info">

        <h2>Cinema Information</h2>

        <div class="info-box">
            <h3>Cinema Name</h3>
            <p>MTBS Cinema</p>
        </div>

        <div class="info-box">
            <h3>Address</h3>
            <p>New Baneshwor, Kathmandu, Nepal</p>
        </div>

        <div class="info-box">
            <h3>Phone</h3>
            <p>+977 9800000000</p>
        </div>

        <div class="info-box">
            <h3>Email</h3>
            <p>support@mtbscinema.com</p>
        </div>

        <div class="info-box">
            <h3>Business Hours</h3>
            <p>Monday – Sunday</p>
            <p>9:00 AM – 10:00 PM</p>
        </div>

    </div>

    <!-- Contact Form -->
    <div class="contact-form">

        <h2>Send Inquiry</h2>

        <?php if(!empty($success)): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="contactForm">

            <input type="text"
                   name="fullname"
                   placeholder="Full Name"
                   required>

            <input type="email"
                   name="email"
                   placeholder="Email Address"
                   required>

            <input type="text"
                   name="phone"
                   id="phone"
                   placeholder="Phone Number"
                   required>

            <input type="text"
                   name="subject"
                   placeholder="Subject"
                   required>

            <textarea
                    name="message"
                    rows="6"
                    placeholder="Enter your message..."
                    required></textarea>

            <button type="submit">
                Send Message
            </button>

        </form>

    </div>

</div>

<!-- FAQ Section -->

<section class="faq-section">

    <h2>Frequently Asked Questions</h2>

    <div class="faq-item">
        <h3>How do I book tickets?</h3>
        <p>Select a movie, choose a showtime, select seats, and confirm booking.</p>
    </div>

    <div class="faq-item">
        <h3>Can I cancel my booking?</h3>
        <p>Bookings can be cancelled before the allowed cancellation period.</p>
    </div>

    <div class="faq-item">
        <h3>How long are seats reserved?</h3>
        <p>Seats are temporarily reserved during the booking session.</p>
    </div>

    <div class="faq-item">
        <h3>When does seat locking expire?</h3>
        <p>Seat locks expire automatically after 5 minutes if booking is not completed.</p>
    </div>

</section>

<!-- Optional Google Map -->

<section class="map-section">

    <h2>Our Location</h2>

    <!-- <iframe
        src="https://www.google.com/maps/embed?pb=!1m18..."
        allowfullscreen=""
        loading="lazy">
    </iframe> -->

</section>

<?php include 'footer.php'; ?>

<script src="../Assets/js/contact.js"></script>

</body>
</html>