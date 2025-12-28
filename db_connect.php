<?php
/**
 * Database Connection File
 * ලක්way Delivery System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset to utf8mb4 for proper character support (Sinhala, emojis, etc.)
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Colombo');

// Optional: Enable error reporting for development (disable in production)
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>