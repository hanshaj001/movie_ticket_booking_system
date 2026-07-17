<?php
session_start();
include '../Includes/db_conn.php';

// Capture redirect param into session on page load
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
        header("Location: ../index.php");
        exit();
    } elseif ($_SESSION['role'] === 'ADMIN') {
        header("Location: ../Admin/dashboard.php");
        exit();
    }
}

$errors = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'confirm_password' => '',
    'general' => ''
];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validation checks
    if (empty($full_name)) {
        $errors['full_name'] = "Full Name is required.";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }
    
    if (empty($phone)) {
        $errors['phone'] = "Phone Number is required.";
    } elseif (!preg_match('/^9[0-9]{9}$/', $phone)) {
        $errors['phone'] = "Phone number must be a 10-digit number starting with 9.";
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }
    
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Confirm Password is required.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Check if there are no validation errors before checking DB duplicates
    $has_errors = false;
    foreach ($errors as $k => $v) {
        if (!empty($v)) {
            $has_errors = true;
            break;
        }
    }

    if (!$has_errors) {
        // Check for duplicate email address
        $check_email_sql = "SELECT email FROM users WHERE email = ? LIMIT 1";
        $check_email_stmt = $conn->prepare($check_email_sql);
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();

        if ($check_email_result->num_rows > 0) {
            $errors['email'] = "Email address is already registered.";
            $has_errors = true;
        }

        // Check for duplicate phone number
        $check_phone_sql = "SELECT phone FROM users WHERE phone = ? LIMIT 1";
        $check_phone_stmt = $conn->prepare($check_phone_sql);
        $check_phone_stmt->bind_param("s", $phone);
        $check_phone_stmt->execute();
        $check_phone_result = $check_phone_stmt->get_result();

        if ($check_phone_result->num_rows > 0) {
            $errors['phone'] = "Phone number is already registered.";
            $has_errors = true;
        }

        if (!$has_errors) {
            // Proceed with registration
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $account_status = 'ACTIVE';

            $conn->begin_transaction();

            try {
                // Insert into users
                $insert_user = "INSERT INTO users (full_name, email, phone, password_hash, account_status) VALUES (?, ?, ?, ?, ?)";
                $stmt_user = $conn->prepare($insert_user);
                $stmt_user->bind_param("sssss", $full_name, $email, $phone, $password_hash, $account_status);
                $stmt_user->execute();
                $user_id = $conn->insert_id;

                // Dynamically look up CUSTOMER role_id
                $role_id_query = "SELECT role_id FROM roles WHERE role_name = 'CUSTOMER' LIMIT 1";
                $role_result = $conn->query($role_id_query);
                if ($role_result && $role_result->num_rows > 0) {
                    $role_row = $role_result->fetch_assoc();
                    $role_id = $role_row['role_id'];
                } else {
                    $role_id = 2; // Fallback default
                }

                // Insert into user_roles
                $insert_role = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
                $stmt_role = $conn->prepare($insert_role);
                $stmt_role->bind_param("ii", $user_id, $role_id);
                $stmt_role->execute();

                $conn->commit();
                
                $success = "Registration successful. Redirecting to login...";
            } catch (Exception $e) {
                $conn->rollback();
                $errors['general'] = "Registration failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Movie Ticket Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../Assets/css/login.css">
</head>
<body>

    <?php include 'components/navbar.php'; ?>

    <main class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2>Create Account</h2>

                <?php if (!empty($errors['general'])) : ?>
                    <div class="alert alert-danger">
                        <div><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($errors['general']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)) : ?>
                    <div class="alert alert-success">
                        <div><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success); ?></div>
                    </div>
    <?php
        // Preserve redirect after registration — pass it to login.php via query param
        // so login.php stores it into session
        if (!empty($_SESSION['pending_redirect'])) {
            $encoded = urlencode($_SESSION['pending_redirect']);
            echo "<script>setTimeout(function(){ window.location.href = '../login.php?redirect={$encoded}'; }, 2000);</script>";
        } else {
            echo "<script>setTimeout(function(){ window.location.href = '../login.php'; }, 2000);</script>";
        }
?>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" required>
                        <?php if (!empty($errors['full_name'])) : ?>
                            <span class="error-feedback"><?= htmlspecialchars($errors['full_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        <?php if (!empty($errors['email'])) : ?>
                            <span class="error-feedback"><?= htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter your phone number" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" required>
                        <?php if (!empty($errors['phone'])) : ?>
                            <span class="error-feedback"><?= htmlspecialchars($errors['phone']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-box">
                            <input type="password" id="password" name="password" placeholder="Create a password" required>
                            <i class="fa-solid fa-eye" id="togglePassword1"></i>
                        </div>
                        <?php if (!empty($errors['password'])) : ?>
                            <span class="error-feedback"><?= htmlspecialchars($errors['password']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-box">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <i class="fa-solid fa-eye" id="togglePassword2"></i>
                        </div>
                        <?php if (!empty($errors['confirm_password'])) : ?>
                            <span class="error-feedback"><?= htmlspecialchars($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </button>
                </form>

                <div class="auth-link">
                    <?php
                        $login_url = '../login.php';
                        if (!empty($_SESSION['pending_redirect'])) {
                            $login_url .= '?redirect=' . urlencode($_SESSION['pending_redirect']);
                        }
                    ?>
                    <p>Already have an account? <a href="<?= htmlspecialchars($login_url); ?>">Login</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if(icon && input) {
            icon.addEventListener("click", function() {
                if (input.type === "password") {
                    input.type = "text";
                    this.classList.remove("fa-eye");
                    this.classList.add("fa-eye-slash");
                } else {
                    input.type = "password";
                    this.classList.remove("fa-eye-slash");
                    this.classList.add("fa-eye");
                }
            });
        }
    }
    
    togglePass('password', 'togglePassword1');
    togglePass('confirm_password', 'togglePassword2');
    </script>
</body>
</html>
