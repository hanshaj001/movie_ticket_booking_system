<?php
session_start();
require_once 'Includes/db_conn.php';

$error = "";

// Ensure user has verified OTP
if (!isset($_SESSION['reset_email_verified']) || $_SESSION['reset_email_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the users table
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Delete all OTPs for this user to prevent reuse
            $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = (SELECT user_id FROM users WHERE email = ?)");
            $del_stmt->bind_param("s", $email);
            $del_stmt->execute();
            
            // Clean up session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_email_verified']);
            
            // Redirect to login page with success param
            header("Location: login.php?reset_success=1");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - MTBS</title>
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
                <h2>Set New Password</h2>
                <p style="margin-bottom: 20px; color: #555; text-align: center;">Enter your new password below.</p>
                
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-box">
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-box">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include 'Customer/components/footer.php'; ?>
</body>
</html>
