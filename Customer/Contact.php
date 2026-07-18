<?php
session_start();
require_once '../Includes/db_conn.php';
require_once '../Includes/mail_config.php';

$success = "";
$errors = [];

// Initialize variables for pre-filling
$fullname = "";
$email = "";
$phone = "";
$subject = "";
$message = "";

// Pre-fill logic if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $user_query = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $fullname = $row['full_name'];
        $email = $row['email'];
        $phone = $row['phone'];
    }
    $stmt->close();
}

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
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_id_val = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

        // Insert into database
        $insert_query = "INSERT INTO contact_messages (user_id, full_name, email, phone, subject, message, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issssss", $user_id_val, $fullname, $email, $phone, $subject, $message, $ip_address);
        
        if ($stmt->execute()) {
            
            // Send email to Cinema
            $cinema_email = "mtbs.hansh@gmail.com"; 
            $cinema_subject = "New Contact Inquiry: " . $subject;
            $cinema_body = "
                <h3>New Contact Inquiry</h3>
                <p><strong>Customer Name:</strong> " . htmlspecialchars($fullname) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                <p><strong>Submitted Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            ";
            
            // Send email notification to Admin
            sendMail($cinema_email, $cinema_subject, $cinema_body);

            // Send confirmation email to Customer
            $customer_subject = "MTBS Cinema - We received your inquiry";
            $customer_body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <h3>Hello " . htmlspecialchars($fullname) . ",</h3>
                    <p>Thank you for contacting MTBS Cinema.</p>
                    <p>Your inquiry regarding <strong>\"" . htmlspecialchars($subject) . "\"</strong> has been received successfully.</p>
                    <p>Our support team will review your message and contact you shortly.</p>
                    <br>
                    <p>Regards,<br><strong>MTBS Cinema Support Team</strong></p>
                </div>
            ";
            
            // Try sending email
            $mailResult = sendMail($email, $customer_subject, $customer_body);
            
            $success = "Your inquiry has been submitted successfully.";
            
            // Clear form data after successful submission
            if (!isset($_SESSION['user_id'])) {
                $fullname = $email = $phone = "";
            }
            $subject = $message = "";
            
        } else {
            $errors[] = "Failed to submit inquiry. Please try again later.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us | Movie Ticket Booking System</title>

<link rel="stylesheet" href="../Assets/css/Customer/contact.css">
<link rel="icon" type="image/jpeg" href="../favicon.jpeg">
</head>
<body>

<?php include 'components/navbar.php'; ?>

<div class="contact-container">

    <div class="contact-page-header">
        <nav class="breadcrumb-nav">
            <a href="../index.php" class="bc-link"><i class="fa-solid fa-house"></i> Home</a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="bc-current">Contact Us</span>
        </nav>
        <h1 class="page-title">Contact Us</h1>
        <p class="page-subtitle">We're here to help with bookings, schedules, and cinema services.</p>
    </div>

    <div class="contact-grid">
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
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success) ?>, 'success'));</script>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode(implode(' ', $errors)) ?>, 'error'));</script>
        <?php endif; ?>

        <form method="POST" id="contactForm" data-loader-msg="Sending message. Please wait...">

            <input type="text"
                   name="fullname"
                   placeholder="Full Name"
                   value="<?php echo htmlspecialchars($fullname); ?>"
                   required>

            <input type="email"
                   name="email"
                   placeholder="Email Address"
                   value="<?php echo htmlspecialchars($email); ?>"
                   required>

            <input type="text"
                   name="phone"
                   id="phone"
                   placeholder="Phone Number"
                   value="<?php echo htmlspecialchars($phone); ?>"
                   required>

            <input type="text"
                   name="subject"
                   placeholder="Subject"
                   value="<?php echo htmlspecialchars($subject); ?>"
                   required>

            <textarea
                    name="message"
                    rows="6"
                    placeholder="Enter your message..."
                    required><?php echo htmlspecialchars($message); ?></textarea>

            <button type="submit">
                Send Message
            </button>

        </form>

    </div>

    </div>

</div>

<!-- FAQ Section -->

<section class="faq-section">

    <h2>Frequently Asked Questions</h2>

    <details class="faq-item">
        <summary>How do I book tickets?</summary>
        <p>Select a movie, choose a showtime, select seats, and confirm booking.</p>
    </details>

    <details class="faq-item">
        <summary>Can I cancel my booking?</summary>
        <p>Bookings can be cancelled before the allowed cancellation period.</p>
    </details>

    <details class="faq-item">
        <summary>How long are seats reserved?</summary>
        <p>Seats are temporarily reserved during the booking session.</p>
    </details>

    <details class="faq-item">
        <summary>When does seat locking expire?</summary>
        <p>Seat locks expire automatically after 5 minutes if booking is not completed.</p>
    </details>

</section>

<!-- Optional Google Map -->

<section class="location-section">

    <h2>Our Location</h2>

    <div class="locations">

        <div class="location-box">
            <i class="fa-solid fa-location-dot"></i>
            <h3>New Baneshwor</h3>
        </div>

    </div>

</section>

<?php include 'components/footer.php'; ?>

<script src="../Assets/js/contact.js"></script>

</body>
</html>