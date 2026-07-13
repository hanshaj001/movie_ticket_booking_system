<?php
session_start();
session_destroy();
header("Location: customerlogin.php");
exit();
?>