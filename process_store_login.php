<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'store_login') {
    
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($mobile) || empty($password)) {
        $_SESSION['message'] = 'Please enter both mobile number and password.';
        $_SESSION['message_type'] = 'error';
        header('Location: store_login.php');
        exit();
    }
    
    // Clean mobile number
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $_SESSION['message'] = 'Please enter a valid 10-digit mobile number.';
        $_SESSION['message_type'] = 'error';
        header('Location: store_login.php');
        exit();
    }
    
    try {
        // Query to find store by primary OR secondary mobile - NO USER JOIN
        $query = "SELECT * FROM stores 
                  WHERE (mobile_primary = ? OR mobile_secondary = ?)
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            $_SESSION['message'] = 'Database error: ' . htmlspecialchars($conn->error);
            $_SESSION['message_type'] = 'error';
            error_log("MySQLi Prepare Error: " . $conn->error);
            header('Location: store_login.php');
            exit();
        }
        
        $stmt->bind_param("ss", $mobile, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['message'] = 'No store found with this mobile number.';
            $_SESSION['message_type'] = 'error';
            $stmt->close();
            header('Location: store_login.php');
            exit();
        }
        
        $store = $result->fetch_assoc();
        $stmt->close();
        
        // Verify password - password is stored in stores table
        if (!password_verify($password, $store['password'])) {
            $_SESSION['message'] = 'Incorrect password. Please try again.';
            $_SESSION['message_type'] = 'error';
            header('Location: store_login.php');
            exit();
        }
        
        // Check store status (NEW SCHEMA)
        if ($store['status'] === 'pending') {
            $_SESSION['message'] = 'Your store is pending approval. Please wait for admin verification.';
            $_SESSION['message_type'] = 'warning';
            header('Location: store_login.php');
            exit();
        }
        
        if ($store['status'] === 'rejected') {
            $reason = $store['admin_notes'] ? ' Reason: ' . htmlspecialchars($store['admin_notes']) : '';
            $_SESSION['message'] = 'Your store registration was rejected.' . $reason;
            $_SESSION['message_type'] = 'error';
            header('Location: store_login.php');
            exit();
        }
        
        // Check if store is active
        if (!$store['is_active']) {
            $_SESSION['message'] = 'Your store account has been deactivated. Please contact support.';
            $_SESSION['message_type'] = 'error';
            header('Location: store_login.php');
            exit();
        }
        
        // Update last login timestamp
        $update_stmt = $conn->prepare("UPDATE stores SET last_login = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $store['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Login successful - Set session variables
        $_SESSION['store_id'] = $store['id'];
        $_SESSION['store_name'] = $store['store_name'];
        $_SESSION['store_type'] = $store['store_type'];
        $_SESSION['store_email'] = $store['email'];
        $_SESSION['store_mobile'] = $mobile;
        $_SESSION['store_address'] = $store['address'];
        $_SESSION['store_city'] = $store['city'];
        $_SESSION['store_postal_code'] = $store['postal_code'];
        $_SESSION['user_type'] = 'seller';
        $_SESSION['logged_in'] = true;
        $_SESSION['store_logged_in'] = true;
        $_SESSION['store_data'] = $store;
        
        // Handle "Remember Me" functionality
        if ($remember) {
            setcookie('store_remembered_mobile', $mobile, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        } else {
            if (isset($_COOKIE['store_remembered_mobile'])) {
                setcookie('store_remembered_mobile', '', time() - 3600, '/');
            }
        }
        
        $_SESSION['message'] = 'Welcome back, ' . htmlspecialchars($store['store_name']) . '!';
        $_SESSION['message_type'] = 'success';
        
        // Redirect to dashboard
        header('Location: store_dashboard.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = 'Login failed. Please try again.';
        $_SESSION['message_type'] = 'error';
        error_log("Store login exception: " . $e->getMessage());
        header('Location: store_login.php');
        exit();
    }
    
} else {
    // Invalid request method
    header('Location: store_login.php');
    exit();
}

$conn->close();
?>