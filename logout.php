<?php
// logout.php
require_once 'config/config.php';
require_once 'includes/auth.php';

logout();
header("Location: " . BASE_URL . "/login.php");
exit;
?>


