<?php
session_start();
session_destroy();
header("Location: ../Customer/customerlogin.php");
exit();
?>