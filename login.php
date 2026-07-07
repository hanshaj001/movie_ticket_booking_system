<?php
session_start();
include 'Includes/db_conn.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user
    $sql = "SELECT u.user_id, u.full_name, u.email, u.password_hash, r.role_name
            FROM users u
            JOIN user_roles ur ON u.user_id = ur.user_id
            JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // ⚠️ Your DB uses plain password (admin123), so direct compare
        // Later you should upgrade to password_hash()
        if ($password === $user['password_hash']) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];

            // Role-based redirection
            if ($user['role_name'] == "ADMIN") {
                header("Location: Admin/dashboard.php");
            } else {
                header("Location: Customer/home.php");
            }
            exit();

       } else {
    $error = "Invalid email or password!";
}

} else {
    $error = "Invalid email or password!";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - Movie Ticket System</title>
    <link rel="stylesheet" href="Assets/login.css">
</head>

<body>

<div class="login-container">

    <div class="login-box">
        <h2>Movie Ticket Login</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?= $error; ?></div>
        <?php } ?>

        <form method="POST" action="">
            
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter email" required>

            <label>Password</label>

<div class="password-container">
    <input type="password"
           id="password"
           name="password"
           placeholder="Enter password"
           required>

    <span class="toggle-password" onclick="togglePassword()">
        👁
    </span>
</div>

            <button type="submit">Login</button>
        </form>

        <p class="footer-text">
            Admin / Customer Login
        </p>
    </div>

</div>
<script>
function togglePassword() {
    const password = document.getElementById("password");

    if (password.type === "password") {
        password.type = "text";
    } else {
        password.type = "password";
    }
}
</script>

</body>
</html>