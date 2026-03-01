<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Redirect to admin login
header('Location: admin_login.php');
exit;
?>