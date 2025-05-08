<?php
// config/config.php
define('SITE_NAME', 'Virtual Classroom');
define('BASE_URL', 'http://localhost/virtual-classroom');
define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/virtual-classroom/uploads/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Error settings (change for production)
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

