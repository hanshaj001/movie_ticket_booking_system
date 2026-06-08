<?php
session_start();
include "db_conn.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {

        $sql = "SELECT u.user_id, u.full_name, u.password_hash, r.role_name
                FROM users u
                JOIN user_roles ur ON u.user_id = ur.user_id
                JOIN roles r ON ur.role_id = r.role_id
                WHERE u.email = '$email'";

        $result = mysqli_query($conn, $sql);

        if ($row = mysqli_fetch_assoc($result)) {

            if (( $password) === $row['password_hash']) {

                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['full_name'];
                $_SESSION['role'] = $row['role_name'];

                if ($row['role_name'] == "ADMIN") {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: customer/dashboard.php");
                }
                exit();

            } else {
                $error = "Wrong password!";
            }

        } else {
            $error = "Invalid email or user not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../Assets/login.css">
</head>
<body>

<div class="container">

    <div class="card">
        <h2>Login</h2>

        <?php if ($error != "") { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form method="POST" onsubmit="return validateForm()">

            <input type="text" id="email" name="email" placeholder="Email or Username">

            <div class="password-box">
                <input type="password" id="password" name="password" placeholder="Password">
                <span onclick="togglePassword()">👁</span>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>

</div>

<script>
function validateForm() {
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    if (email == "" || password == "") {
        alert("All fields are required!");
        return false;
    }
    return true;
}

function togglePassword() {
    let pass = document.getElementById("password");

    if (pass.type === "password") {
        pass.type = "text";
    } else {
        pass.type = "password";
    }
}
</script>

</body>
</html>