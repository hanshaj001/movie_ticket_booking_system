aryan/customer/register
﻿<?php
 HEAD
﻿<?php
session_start();
include '../Includes/db_conn.php';

$errors = [];
$success = "";
$full_name = '';
$email = '';
<?php
 main
session_start();
include '../Includes/db_conn.php';

// If already logged in, redirect to appropriate home.
 aryan/customer/register
if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
        header('Location: ../Admin/dashboard.php');
    } else {
      //  header('Location: ../Customer/home.php');
    }
    exit;
}
// if (isset($_SESSION['user_id'])) {
//     if (!empty($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
//         header('Location: ../Admin/dashboard.php');
//     } else {
//         header('Location: ../Customer/home.php');
//     }
//     exit;
// }
main

$error = '';
$success = '';
 main

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
 HEAD
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

    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if ($full_name === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password and Confirm Password do not match.';
    } else {
        // Prevent duplicates: email
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $error = 'Email already exists. Please use another email.';
        } else {
            // Prevent duplicates: phone
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE phone = ? LIMIT 1');
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $error = 'Phone number already exists. Please use another phone number.';
            } else {
                // Hash password securely
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Use transaction so user + role are always consistent
                $conn->begin_transaction();
                try {
                    // Create user
                    $stmt = $conn->prepare('INSERT INTO users (full_name, email, phone, password_hash) VALUES (?,?,?,?)');
                    $stmt->bind_param('ssss', $full_name, $email, $phone, $password_hash);
                    $stmt->execute();
                    $user_id = $conn->insert_id;

                    // Assign CUSTOMER role automatically
                    // Assumes roles.role_name holds values like ADMIN/CUSTOMER
                    $stmt = $conn->prepare('SELECT role_id FROM roles WHERE role_name = ? LIMIT 1');
                    $roleName = 'CUSTOMER';
                    $stmt->bind_param('s', $roleName);
                    $stmt->execute();
                    $roleRes = $stmt->get_result();
                    if ($roleRes->num_rows !== 1) {
                        throw new Exception('CUSTOMER role not found in roles table.');
                    }
                    $roleRow = $roleRes->fetch_assoc();
                    $role_id = (int)$roleRow['role_id'];

                    $stmt = $conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)');
                    $stmt->bind_param('ii', $user_id, $role_id);
                    $stmt->execute();

                    $conn->commit();
                    $success = 'Registration successful. Redirecting to Login...';

                    // Redirect to login
                    header('refresh:1; url=../login.php');
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
 main
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
 HEAD
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register - Movie Ticket System</title>
    <link rel="stylesheet" href="../Assets/Customer/register.css"/>
<?php /* guest register should not depend on admin sidebar css */ ?>

</head>
<body>

<?php
// Public navbar/footer (works for guests too)
?>
<?php include_once 'components/navbar.php'; ?>


<main class="register-container">
    <div class="register-box">
        <h2>Create Your Account</h2>

        <?php if ($error !== '') { ?>
            <div class="error" role="alert"><?= htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if ($success !== '') { ?>
            <div class="success" role="status"><?= htmlspecialchars($success); ?></div>
        <?php } ?>

        <form method="POST" action="" autocomplete="off">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Enter full name" required value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>"/>

            <label>Email</label>
            <input type="email" name="email" placeholder="Enter email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"/>

            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="Enter phone number" required value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"/>

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required/>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm password" required/>

            <button type="submit">Register</button>
        </form>

        <p class="footer-text">
            Already have an account? <a href="../login.php">Login</a>
        </p>
    </div>
</main>

<?php include_once 'components/footer.php'; ?>


</body>
</html>

main
