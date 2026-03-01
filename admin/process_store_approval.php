<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Database configuration

require_once '../config/database.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PHPMailer
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'sachithgamage2310@gmail.com');
define('SMTP_PASS', 'sgsydsmnjijpbunv');
define('FROM_EMAIL', 'sachithgamage2310@gmail.com');
define('FROM_NAME', 'ලක්way Delivery');

$action = $_GET['action'] ?? '';
$store_id = $_GET['id'] ?? 0;

if ($action === 'approve') {
    approveStore($store_id);
} elseif ($action === 'reject') {
    $reason = $_GET['reason'] ?? '';
    rejectStore($store_id, $reason);
} else {
    redirectWithMessage('Invalid action', 'error');
}

function approveStore($store_id) {
    global $conn;
    
    // Get store details
    $stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirectWithMessage('Store not found', 'error');
    }
    
    $store = $result->fetch_assoc();
    
    // Update store approval status
    $stmt = $conn->prepare("UPDATE stores SET status = 'approved', approved_date = NOW() WHERE id = ?");
    $stmt->bind_param("i", $store_id);
    
    if ($stmt->execute()) {
        // Send approval email
        sendApprovalEmail($store['email'], $store['store_name']);
        redirectWithMessage('Store approved successfully! Approval email sent.', 'success');
    } else {
        redirectWithMessage('Failed to approve store', 'error');
    }
}

function rejectStore($store_id, $reason) {
    global $conn;
    
    if (empty($reason)) {
        redirectWithMessage('Rejection reason is required', 'error');
    }
    
    // Get store details
    $stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirectWithMessage('Store not found', 'error');
    }
    
    $store = $result->fetch_assoc();
    
    // Update store rejection status
    $stmt = $conn->prepare("UPDATE stores SET status = 'rejected', admin_notes = ? WHERE id = ?");
    $stmt->bind_param("si", $reason, $store_id);
    
    if ($stmt->execute()) {
        // Send rejection email
        sendRejectionEmail($store['email'], $store['store_name'], $reason);
        redirectWithMessage('Store rejected. Rejection email sent.', 'success');
    } else {
        redirectWithMessage('Failed to reject store', 'error');
    }
}

function sendApprovalEmail($to, $store_name) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = '🎉 Store Approved - ලක්way Delivery';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;'>
                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px;'>
                    <h1 style='color: #26de81; text-align: center;'>🎉 Congratulations!</h1>
                    <h2 style='color: #333;'>Your Store Has Been Approved</h2>
                    <p style='color: #666; font-size: 16px;'>Dear {$store_name},</p>
                    <p style='color: #666; font-size: 16px;'>Great news! Your store registration has been approved by our admin team.</p>
                    <div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #26de81;'>
                        <p style='margin: 0; color: #155724; font-weight: bold;'>✓ Your store is now live on ලක්way Delivery!</p>
                    </div>
                    <p style='color: #666; font-size: 16px;'><strong>Next Steps:</strong></p>
                    <ol style='color: #666; font-size: 16px;'>
                        <li>Login to your store dashboard</li>
                        <li>Complete your store profile</li>
                        <li>Add your products/menu</li>
                        <li>Start accepting orders!</li>
                    </ol>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/luckway/store_login.php' style='display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Login to Dashboard</a>
                    </p>
                    <p style='color: #999; font-size: 12px; margin-top: 30px;'>Welcome to the ලක්way Delivery family! We're excited to have you on board.</p>
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

function sendRejectionEmail($to, $store_name, $reason) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = 'Store Registration Status - ලක්way Delivery';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;'>
                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px;'>
                    <h1 style='color: #667eea; text-align: center;'>🏪 ලක්way Delivery</h1>
                    <h2 style='color: #333;'>Store Registration Update</h2>
                    <p style='color: #666; font-size: 16px;'>Dear {$store_name},</p>
                    <p style='color: #666; font-size: 16px;'>Thank you for your interest in joining ලක්way Delivery.</p>
                    <div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffa502;'>
                        <p style='margin: 0; color: #856404;'><strong>Unfortunately, we cannot approve your store registration at this time.</strong></p>
                    </div>
                    <p style='color: #666; font-size: 16px;'><strong>Reason:</strong></p>
                    <p style='color: #666; font-size: 16px; background: #f8f9fa; padding: 15px; border-radius: 8px;'>{$reason}</p>
                    <p style='color: #666; font-size: 16px;'>If you believe this was a mistake or would like to reapply after addressing the issues mentioned, please contact our support team.</p>
                    <p style='color: #999; font-size: 12px; margin-top: 30px;'>Thank you for your understanding.</p>
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

function redirectWithMessage($message, $type) {
    $_SESSION['admin_message'] = $message;
    $_SESSION['admin_message_type'] = $type;
    header('Location: pending_stores.php');
    exit;
}

$conn->close();
?>