<?php
session_start();
include '../Includes/db_conn.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT
                u.user_id,
                u.full_name,
                u.email,
                u.password_hash,
                r.role_name
            FROM users u
            JOIN user_roles ur
                ON u.user_id = ur.user_id
            JOIN roles r
                ON ur.role_id = r.role_id
            WHERE u.email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows == 1){

        $user = $result->fetch_assoc();

        // Plain password check
        if(
            $password === $user['password_hash']
            &&
            strtoupper($user['role_name']) == 'CUSTOMER'
        ){

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['role'] = 'CUSTOMER';
            $_SESSION['login_time'] = time();

            header("Location: home.php");
            exit();

        }else{

            $error = "Invalid email or password";
        }

    }else{

        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Movie Ticket Booking System</title>

    <link rel="stylesheet" href="../Assets/Customer/customerlogin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <!-- Header -->
    <header class="header">

        <div class="logo">
            <i class="fa-solid fa-film"></i>
            MTBS
        </div>

        <nav class="navbar">
            <ul>
                <li>
                    <a href="../login.php">
                        Home
                    </a>
                </li>

                <li>
                    <a href="#">
                        About
                    </a>
                </li>

                <li>
                    <a href="#">
                        Contact
                    </a>
                </li>
            </ul>
        </nav>

    </header>

    <!-- Login Section -->
    <main class="login-section">

        <div class="login-container">

            <div class="login-card">

                <h2>
                    Customer Login
                </h2>

                <?php if (!empty($error)) : ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required>

                    </div>

                   <div class="form-group">

    <label for="password">
        Password
    </label>

    <div class="password-box">

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required>

        <i class="fa-solid fa-eye" id="togglePassword"></i>

    </div>

</div>

                    <button type="submit" class="login-btn">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Login
                    </button>

                </form>

                <div class="register-link">

                    <p>
                        Don't have an account?
                        <a href="register.php">
                            Register Here
                        </a>
                    </p>

                </div>

            </div>

        </div>
        <script>
const password = document.getElementById("password");
const toggle = document.getElementById("togglePassword");

toggle.addEventListener("click", function(){

    if(password.type === "password"){
        password.type = "text";
        this.classList.remove("fa-eye");
        this.classList.add("fa-eye-slash");
    }
    else{
        password.type = "password";
        this.classList.remove("fa-eye-slash");
        this.classList.add("fa-eye");
    }

});
</script>

    </main>

    <!-- Footer -->
    <footer class="footer">

        <div class="footer-content">

            <p>
                &copy; 2026 Movie Ticket Booking System.
                All Rights Reserved.
            </p>

        </div>

    </footer>

</body>
</html>
