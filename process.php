<?php
// process.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PHPMailer configuration - Direct file includes
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration - Update with your SMTP details
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'sachithgamage2310@gmail.com'); // Your Gmail
define('SMTP_PASS', 'sgsydsmnjijpbunv'); // Gmail App Password
define('FROM_EMAIL', 'your-email@gmail.com');
define('FROM_NAME', 'ලක්way Delivery');

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'send_reset_code':
        handleSendResetCode();
        break;
    case 'verify_reset_code':
        handleVerifyResetCode();
        break;
    case 'reset_password':
        handleResetPassword();
        break;
    default:
        redirectWithMessage('Invalid action', 'error');
}

// User Registration
function handleRegister() {
    global $conn;
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $mobile = $_POST['mobile'];
    $country_code = $_POST['country_code'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('Invalid email format', 'error');
    }
    
    if ($password !== $confirm_password) {
        redirectWithMessage('Passwords do not match', 'error');
    }
    
    if (strlen($password) < 8) {
        redirectWithMessage('Password must be at least 8 characters', 'error');
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        redirectWithMessage('Email already registered', 'error');
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (email, mobile, country_code, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $email, $mobile, $country_code, $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['email'] = $email;
        redirectWithMessage('Registration successful! Welcome to ලක්way Delivery', 'success');
    } else {
        redirectWithMessage('Registration failed. Please try again', 'error');
    }
}

// User Login
function handleLogin() {
    global $conn;
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Fetch user
    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirectWithMessage('Invalid email or password', 'error');
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        redirectWithMessage('Invalid email or password', 'error');
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    
    // Remember me cookie (30 days)
    if ($remember) {
        setcookie('remembered_email', $email, time() + (30 * 24 * 60 * 60), '/');
    } else {
        setcookie('remembered_email', '', time() - 3600, '/');
    }
    
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
}

// Send Reset Code
function handleSendResetCode() {
    global $conn;
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit;
    }
    
    // Generate 6-digit code
    $code = sprintf("%06d", mt_rand(1, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store code in database
    $stmt = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE code = ?, expires_at = ?");
    $stmt->bind_param("sssss", $email, $code, $expires, $code, $expires);
    $stmt->execute();
    
    // Send email
    if (sendResetEmail($email, $code)) {
        echo json_encode(['success' => true, 'message' => 'Verification code sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
    exit;
}

// Verify Reset Code
function handleVerifyResetCode() {
    global $conn;
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $code = $_POST['code'];
    
    $stmt = $conn->prepare("SELECT code, expires_at FROM password_resets WHERE email = ? AND code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        exit;
    }
    
    $reset = $result->fetch_assoc();
    
    if (strtotime($reset['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Code has expired']);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Code verified']);
    exit;
}

// Reset Password
function handleResetPassword() {
    global $conn;
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $code = $_POST['code'];
    $new_password = $_POST['new_password'];
    
    // Verify code again
    $stmt = $conn->prepare("SELECT expires_at FROM password_resets WHERE email = ? AND code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirectWithMessage('Invalid verification code', 'error');
    }
    
    $reset = $result->fetch_assoc();
    
    if (strtotime($reset['expires_at']) < time()) {
        redirectWithMessage('Code has expired', 'error');
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // Delete reset code
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        redirectWithMessage('Password reset successful! Please login', 'success');
    } else {
        redirectWithMessage('Failed to reset password', 'error');
    }
}

// Send Reset Email using PHPMailer
function sendResetEmail($to, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - ලක්way Delivery';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;'>
                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px;'>
                    <h1 style='color: #667eea; text-align: center;'>🚚 ලක්way Delivery</h1>
                    <h2 style='color: #333;'>Password Reset Request</h2>
                    <p style='color: #666; font-size: 16px;'>You requested to reset your password. Use the code below:</p>
                    <div style='background: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                        <h1 style='color: #667eea; font-size: 36px; margin: 0; letter-spacing: 5px;'>{$code}</h1>
                    </div>
                    <p style='color: #666;'>This code will expire in 15 minutes.</p>
                    <p style='color: #999; font-size: 12px; margin-top: 30px;'>If you didn't request this, please ignore this email.</p>
                </div>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Helper function to redirect with message
function redirectWithMessage($message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: index.php');
    exit;
}

$conn->close();
?>