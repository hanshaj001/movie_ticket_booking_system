<?php
session_start();
require_once 'Includes/db_conn.php';

$error = "";
$otp_input = "";

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_input = trim($_POST['otp']);

    if (empty($otp_input)) {
        $error = "Please enter the 6-digit OTP.";
    } else {
        // Verify OTP from database
        $stmt = $conn->prepare("SELECT pr.reset_id, pr.expires_at, pr.is_used FROM password_resets pr JOIN users u ON pr.user_id = u.user_id WHERE u.email = ? AND pr.otp_code = ? AND pr.is_used = 0 ORDER BY pr.created_at DESC LIMIT 1");
        $stmt->bind_param("ss", $email, $otp_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $reset_record = $result->fetch_assoc();
            
            // Check expiry
            if (strtotime($reset_record['expires_at']) > time()) {
                // Success - Valid OTP
                $_SESSION['reset_email_verified'] = true;
                header("Location: reset_password.php");
                exit();
            } else {
                $error = "This OTP has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid OTP. Please check your email and try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - MTBS</title>
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
                <h2>Verify OTP</h2>
                <p style="margin-bottom: 20px; color: #555; text-align: center;">We've sent a 6-digit OTP to <b><?= htmlspecialchars($email) ?></b>.</p>
                
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="otp">Enter OTP</label>
                        <input type="text" id="otp" name="otp" value="<?= htmlspecialchars($otp_input); ?>" placeholder="Enter 6-digit OTP" maxlength="6" pattern="\d{6}" required>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fa-solid fa-check-circle"></i> Verify OTP
                    </button>
                </form>

                <div class="auth-link">
                    <p>Didn't receive the email? <a href="forgot_password.php">Try again</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'Customer/components/footer.php'; ?>
</body>
</html>
