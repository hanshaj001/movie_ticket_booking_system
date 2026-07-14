<?php
session_start();
include 'Includes/db_conn.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fully matching your database schema schema join architecture
    $sql = "SELECT 
                u.user_id, 
                u.full_name, 
                u.email, 
                u.password_hash, 
                u.account_status, 
                r.role_name
            FROM users u
            JOIN user_roles ur ON u.user_id = ur.user_id
            JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ? 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Enforcing Business Rule BR-02: Prevent blocked profiles from booking
        if (strtoupper($user['account_status']) === 'BLOCKED') {
            $error = "Your account has been suspended. Please contact management.";
        } 
        // Secure verify update mapping standard password_verify validation check
        elseif (password_verify($password, $user['password_hash'])) {
            
            // Set dynamic global session profile variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['role'] = strtoupper($user['role_name']); // Normalizes text string casing
            $_SESSION['login_time'] = time();

            // Optional: Update last login timestamp field if tracked
            $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_login->bind_param("i", $user['user_id']);
            $update_login->execute();

            // Dynamic RBAC Role Authorization Router Redirects
            if ($_SESSION['role'] === 'ADMIN') {
                header("Location: Admin/dashboard.php");
                exit();
            } elseif ($_SESSION['role'] === 'CUSTOMER') {
                header("Location: Customer/home.php");
                exit();
            } else {
                // Fail-safe protection fallback route
                $error = "Access Denied: Unrecognized organizational permission assignment.";
            }

        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Gateway - Movie Ticket Booking System</title>
    <link rel="stylesheet" href="Assets/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <main class="login-section">
        <div class="login-container">
            <div class="login-card">
                <h2>Cinema Gateway Portal</h2>

                <?php if (!empty($error)) : ?>
                    <div class="error-message" style="background-color: #e74c3c; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; text-align: center;">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-box" style="position: relative; display: flex; align-items: center;">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required style="width: 100%; padding-right: 40px;">
                            <i class="fa-solid fa-eye" id="togglePassword" style="position: absolute; right: 15px; cursor: pointer; color: #aaa;"></i>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </button>
                </form>

                <div class="register-link">
                    <p>New to the theater? <a href="Customer/register.php">Register Account Here</a></p>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Asynchronous UI Password Visibility Toggle Engine
    const passwordInput = document.getElementById("password");
    const toggleIcon = document.getElementById("togglePassword");

    toggleIcon.addEventListener("click", function() {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            this.classList.remove("fa-eye");
            this.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            this.classList.remove("fa-eye-slash");
            this.classList.add("fa-eye");
        }
    });
    </script>
</body>
</html>