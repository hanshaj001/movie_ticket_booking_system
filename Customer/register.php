<?php
session_start();
include '../Includes/db_conn.php';

// If already logged in, redirect to appropriate home.
if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
        header('Location: ../Admin/dashboard.php');
    } else {
      //  header('Location: ../Customer/home.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
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
                    header('refresh:1; url=../Includes/login.php');
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
            Already have an account? <a href="../Includes/login.php">Login</a>
        </p>
    </div>
</main>

<?php include_once 'components/footer.php'; ?>


</body>
</html>

