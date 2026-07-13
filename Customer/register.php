<?php
session_start();
include '../Includes/db_conn.php';

$errors = [];
$success = "";
$full_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($confirm_password === '') {
        $errors[] = 'Confirm password is required.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $errors[] = 'Email address is already registered.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param('sss', $full_name, $email, $password_hash);

            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;
                $role_sql = "SELECT role_id FROM roles WHERE role_name = 'CUSTOMER' LIMIT 1";
                $role_result = $conn->query($role_sql);

                if ($role_result && $role_result->num_rows === 1) {
                    $role_row = $role_result->fetch_assoc();
                    $role_id = $role_row['role_id'];
                    $role_stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    $role_stmt->bind_param('ii', $user_id, $role_id);
                    $role_stmt->execute();
                }

                $success = 'Registration completed successfully. You may now login.';
                $full_name = $email = '';
            } else {
                $errors[] = 'Registration failed. Please try again later.';
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
    <title>Customer Register - Movie Ticket Booking System</title>

    <link rel="stylesheet" href="../Assets/Customer/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-film"></i>
            MTBS
        </div>

        <nav class="navbar">
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main class="register-section">
        <div class="register-container">
            <div class="register-card">
                <h2>Customer Registration</h2>

                <?php if (!empty($errors)) : ?>
                    <div class="error-message">
                        <ul>
                            <?php foreach ($errors as $error) : ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success !== '') : ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" onsubmit="return validateRegisterForm();">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group password-container">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <span class="toggle-password" onclick="togglePassword('password', this)"><i class="fa-solid fa-eye"></i></span>
                        <p class="form-note">Minimum 8 characters.</p>
                    </div>

                    <div class="form-group password-container">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <span class="toggle-password" onclick="togglePassword('confirm_password', this)"><i class="fa-solid fa-eye"></i></span>
                    </div>

                    <button type="submit" class="register-btn">Create Account</button>
                </form>

                <div class="login-link">
                    <p>Already have an account? <a href="customerlogin.php">Login here</a></p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2026 Movie Ticket Booking System. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        function validateRegisterForm() {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!fullName) {
                alert('Please enter your full name.');
                return false;
            }

            if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }

            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                return false;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                return false;
            }

            return true;
        }

        function togglePassword(fieldId, toggle) {
            const input = document.getElementById(fieldId);
            const icon = toggle.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
