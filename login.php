<?php
session_start();
include 'Includes/db_conn.php';

// Capture redirect param into session on initial page load (GET request)
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $_SESSION['pending_redirect'] = $_GET['redirect'];
}

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'CUSTOMER') {
        // Honor pending redirect if user is already logged in
        if (!empty($_SESSION['pending_redirect'])) {
            $redirect_url = $_SESSION['pending_redirect'];
            unset($_SESSION['pending_redirect']);
            header("Location: $redirect_url");
            exit();
        }
        header("Location: index.php");
        exit();
    } elseif ($_SESSION['role'] === 'ADMIN') {
        header("Location: Admin/dashboard.php");
        exit();
    }
}

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
            // Set session variables after successful authentication
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];

            // Default role-based redirects
            if ($_SESSION['role'] === 'ADMIN') {
                header("Location: Admin/dashboard.php");
                exit();
            } elseif ($_SESSION['role'] === 'CUSTOMER') {
                // Honor pending redirect for customers (e.g., returning to seat selection)
                if (!empty($_SESSION['pending_redirect'])) {
                    $redirect_url = $_SESSION['pending_redirect'];
                    unset($_SESSION['pending_redirect']);
                    // Safety: only allow relative paths (no scheme/host)
                    $parsed = parse_url($redirect_url);
                    if (!isset($parsed['scheme']) && !isset($parsed['host'])) {
                        header("Location: $redirect_url");
                        exit();
                    }
                }
                header("Location: index.php");
                exit();
            } else {
                $error = "Access Denied: Unrecognized organizational permission assignment.";
            }

        } else {
            $error = "Invalid email or password"; // Generic error message
        }
    } else {
        $error = "Invalid email or password"; // Generic error message
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Movie Ticket Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Assets/css/login.css">
</head>
<body>

    <?php include 'Customer/components/navbar.php'; ?>

    <main class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2>Welcome Back</h2>

                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger">
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
                        <div class="password-box">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fa-solid fa-eye" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </button>
                </form>

                <div class="auth-link">
                    <?php
                        $register_url = 'Customer/register.php';
                        if (!empty($_SESSION['pending_redirect'])) {
                            $register_url .= '?redirect=' . urlencode($_SESSION['pending_redirect']);
                        }
                    ?>
                    <p>Don't have an account? <a href="<?= htmlspecialchars($register_url); ?>">Register</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'Customer/components/footer.php'; ?>

    <script>
    // Asynchronous UI Password Visibility Toggle Engine
    const passwordInput = document.getElementById("password");
    const toggleIcon = document.getElementById("togglePassword");

    if(toggleIcon) {
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
    }
    </script>
</body>
</html>