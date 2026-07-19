<?php
session_start();
require_once 'Includes/db_conn.php';
require_once 'Includes/mail_config.php';

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id, full_name, account_status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if ($user['account_status'] === 'BLOCKED') {
                $error = "Your account is blocked. You cannot reset your password.";
            } else {
                // Generate 6-digit OTP
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store OTP
                $ins_stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
                $ins_stmt->bind_param("sss", $email, $otp, $expires_at);
                
                if ($ins_stmt->execute()) {
                    // Send Email
                    $subject = "Your Password Reset OTP - MTBS Cinema";
                    $body = "Hello " . htmlspecialchars($user['full_name']) . ",<br><br>"
                          . "You requested a password reset. Your OTP is: <h2 style='color:#e74c3c;'>$otp</h2><br>"
                          . "This OTP will expire in 15 minutes.<br><br>"
                          . "If you did not request a password reset, please ignore this email.";
                          
                    $mailResult = sendMail($email, $subject, $body, true);
                    
                    if ($mailResult['success']) {
                        $_SESSION['reset_email'] = $email;
                        header("Location: verify_otp.php");
                        exit();
                    } else {
                        $error = "Failed to send OTP email. Please try again later.";
                    }
                } else {
                    $error = "System error occurred. Please try again.";
                }
            }
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MTBS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
    <link rel="stylesheet" href="Assets/css/login.css">
</head>
<body>
    <?php include 'Customer/components/navbar.php'; ?>

    <main class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2>Forgot Password</h2>
                <p style="margin-bottom: 20px; color: #555; text-align: center;">Enter your registered email address to receive an OTP.</p>
                
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" placeholder="Enter your registered email" required>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fa-solid fa-paper-plane"></i> Send OTP
                    </button>
                </form>

                <div class="auth-link">
                    <p>Remembered your password? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'Customer/components/footer.php'; ?>
</body>
</html>
