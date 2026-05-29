# Login Module – Movie Ticket Booking System

## login.php

```php
<?php
session_start();
include("db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Empty field validation
    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {

        // Check user in database
        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {

            $row = mysqli_fetch_assoc($result);

            // Verify password
            if (password_verify($password, $row['password'])) {

                // Session create
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Role based redirect
                if ($row['role'] == "ADMIN") {
                    header("Location: admin/dashboard.php");
                    exit();
                } else if ($row['role'] == "CUSTOMER") {
                    header("Location: customer/dashboard.php");
                    exit();
                }

            } else {
                $error = "Wrong password!";
            }

        } else {
            $error = "Invalid email!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login System</title>

    <link rel="stylesheet" href="style.css">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="login-container">

    <div class="login-card">

        <h2>Movie Ticket Booking</h2>

        <form method="POST" onsubmit="return validateForm()">

            <div class="input-group">
                <input type="text" name="email" id="email" placeholder="Email or Username">
            </div>

            <div class="input-group password-box">
                <input type="password" name="password" id="password" placeholder="Password">

                <span onclick="togglePassword()" class="show-btn">
                    Show
                </span>
            </div>

            <button type="submit">Login</button>

        </form>

        <p class="error"><?php echo $error; ?></p>

    </div>

</div>

<script>

function validateForm() {

    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();

    if (email == "" || password == "") {
        alert("Please fill all fields");
        return false;
    }

    return true;
}

// Show hide password
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
```

---

# style.css

```css
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, sans-serif;
}

body{
    background:#0f172a;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-container{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.login-card{
    background:white;
    width:380px;
    padding:35px;
    border-radius:12px;
    box-shadow:0px 5px 20px rgba(0,0,0,0.3);
}

.login-card h2{
    text-align:center;
    margin-bottom:25px;
    color:#0f172a;
}

.input-group{
    margin-bottom:20px;
}

.input-group input{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
    outline:none;
    transition:0.3s;
}

.input-group input:focus{
    border-color:#2563eb;
    box-shadow:0px 0px 5px rgba(37,99,235,0.5);
}

.password-box{
    position:relative;
}

.show-btn{
    position:absolute;
    right:15px;
    top:12px;
    cursor:pointer;
    color:#2563eb;
    font-size:14px;
}

button{
    width:100%;
    padding:12px;
    border:none;
    background:#2563eb;
    color:white;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#1d4ed8;
}

.error{
    color:red;
    margin-top:15px;
    text-align:center;
}

@media(max-width:500px){

    .login-card{
        width:100%;
    }

}
```

---

# db.php

```php
<?php

$conn = mysqli_connect("localhost", "root", "", "movie_ticket");

if (!$conn) {
    die("Connection Failed");
}

?>
```

---

# logout.php

```php
<?php
session_start();

session_destroy();

header("Location: login.php");
exit();
?>
```

---

# Session Protection Example

## admin/dashboard.php

```php
<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "ADMIN") {
    header("Location: ../login.php");
    exit();
}
?>
```

---

## customer/dashboard.php

```php
<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "CUSTOMER") {
    header("Location: ../login.php");
    exit();
}
?>
```

---

# MySQL Table

```sql
CREATE TABLE users (

    id INT PRIMARY KEY AUTO_INCREMENT,

    username VARCHAR(100),

    email VARCHAR(100),

    password VARCHAR(255),

    role VARCHAR(20)

);
```

---

# Insert Admin Example

```php
<?php

$password = password_hash("admin123", PASSWORD_DEFAULT);

echo $password;

?>
```

Then insert generated password into database.

Example:

```sql
INSERT INTO users(username,email,password,role)

VALUES(
'Admin',
'admin@gmail.com',
'PASTE_HASH_PASSWORD_HERE',
'ADMIN'
);
```

---

g