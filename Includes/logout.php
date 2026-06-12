<?php
session_start();
session_destroy();
header("Location: ../Includes/login.php");
exit();
?>