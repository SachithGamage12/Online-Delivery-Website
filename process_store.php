<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'register_store') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $uploadDir = __DIR__ . '/uploads/stores/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $response = ['success' => false, 'message' => ''];

    try {
        // Validate required fields
        $requiredFields = [
            'store_name', 'store_type', 'br_number', 'email', 
            'mobile_primary', 'password', 'confirm_password',
            'address', 'city', 'latitude', 'longitude',
            'opening_time', 'closing_time'
        ];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields: $field is missing");
            }
        }

        // Check if passwords match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match!");
        }

        // Check password strength
        if (strlen($_POST['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long!");
        }

        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format!");
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM stores WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered. Please use a different email or login.");
        }

        // Validate operating hours
        $open247 = isset($_POST['open_24_7']) && $_POST['open_24_7'] == '1';
        
        if (!$open247) {
            $openingTime = $_POST['opening_time'];
            $closingTime = $_POST['closing_time'];
            
            if ($openingTime >= $closingTime) {
                throw new Exception("Closing time must be after opening time!");
            }
        } else {
            // For 24/7 stores, set standard times
            $openingTime = '00:00:00';
            $closingTime = '23:59:00';
        }

        // Handle file uploads
        $allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $allowedDocTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

        // Store image upload
        if (!isset($_FILES['store_image']) || $_FILES['store_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Store image is required!");
        }

        $storeImage = $_FILES['store_image'];
        $storeImageExt = strtolower(pathinfo($storeImage['name'], PATHINFO_EXTENSION));
        $storeImageName = 'store_' . time() . '_' . uniqid() . '.' . $storeImageExt;
        $storeImagePath = $uploadDir . $storeImageName;

        if (!in_array($storeImage['type'], $allowedImageTypes)) {
            throw new Exception("Store image must be JPG, JPEG, PNG or GIF!");
        }

        if ($storeImage['size'] > 5 * 1024 * 1024) {
            throw new Exception("Store image must be less than 5MB!");
        }

        if (!move_uploaded_file($storeImage['tmp_name'], $storeImagePath)) {
            throw new Exception("Failed to upload store image!");
        }

        // BR certificate upload
        if (!isset($_FILES['br_image']) || $_FILES['br_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("BR certificate is required!");
        }

        $brImage = $_FILES['br_image'];
        $brImageExt = strtolower(pathinfo($brImage['name'], PATHINFO_EXTENSION));
        $brImageName = 'br_' . time() . '_' . uniqid() . '.' . $brImageExt;
        $brImagePath = $uploadDir . $brImageName;

        if (!in_array($brImage['type'], $allowedDocTypes)) {
            throw new Exception("BR certificate must be JPG, JPEG, PNG or PDF!");
        }

        if ($brImage['size'] > 5 * 1024 * 1024) {
            throw new Exception("BR certificate must be less than 5MB!");
        }

        if (!move_uploaded_file($brImage['tmp_name'], $brImagePath)) {
            throw new Exception("Failed to upload BR certificate!");
        }

        // Food certificate upload (optional)
        $foodCertPath = null;
        if (isset($_FILES['food_certificate']) && $_FILES['food_certificate']['error'] === UPLOAD_ERR_OK) {
            $foodCert = $_FILES['food_certificate'];
            $foodCertExt = strtolower(pathinfo($foodCert['name'], PATHINFO_EXTENSION));
            $foodCertName = 'food_cert_' . time() . '_' . uniqid() . '.' . $foodCertExt;
            $foodCertPath = $uploadDir . $foodCertName;

            if (!in_array($foodCert['type'], $allowedDocTypes)) {
                throw new Exception("Food certificate must be JPG, JPEG, PNG or PDF!");
            }

            if ($foodCert['size'] > 5 * 1024 * 1024) {
                throw new Exception("Food certificate must be less than 5MB!");
            }

            if (!move_uploaded_file($foodCert['tmp_name'], $foodCertPath)) {
                throw new Exception("Failed to upload food certificate!");
            }
        }

        // Hash password
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Prepare data for insertion
        $storeData = [
            'store_name' => trim($_POST['store_name']),
            'store_type' => $_POST['store_type'],
            'br_number' => trim($_POST['br_number']),
            'email' => trim($_POST['email']),
            'country_code' => $_POST['country_code'] ?? '+94',
            'mobile_primary' => trim($_POST['mobile_primary']),
            'mobile_secondary' => !empty($_POST['mobile_secondary']) ? trim($_POST['mobile_secondary']) : null,
            'address' => trim($_POST['address']),
            'city' => trim($_POST['city']),
            'postal_code' => !empty($_POST['postal_code']) ? trim($_POST['postal_code']) : null,
            'latitude' => $_POST['latitude'],
            'longitude' => $_POST['longitude'],
            'opening_time' => $openingTime,
            'closing_time' => $closingTime,
            'open_24_7' => $open247 ? 1 : 0,
            'password' => $hashedPassword,
            'store_image_path' => $storeImagePath,
            'br_image_path' => $brImagePath,
            'food_certificate_path' => $foodCertPath,
            'status' => 'pending'
        ];

        // Insert into database
        $columns = implode(', ', array_keys($storeData));
        $placeholders = ':' . implode(', :', array_keys($storeData));
        
        $sql = "INSERT INTO stores ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($storeData)) {
            $response['success'] = true;
            $response['message'] = "🎉 Store registration submitted successfully! Your application is under review. We'll contact you once approved.";
            
            // Optional: Send email notification
            // sendAdminNotification($storeData);
            
        } else {
            throw new Exception("Database error: Failed to save store information.");
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        
        // Clean up uploaded files if there was an error
        if (isset($storeImagePath) && file_exists($storeImagePath)) {
            unlink($storeImagePath);
        }
        if (isset($brImagePath) && file_exists($brImagePath)) {
            unlink($brImagePath);
        }
        if (isset($foodCertPath) && file_exists($foodCertPath)) {
            unlink($foodCertPath);
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not POST request, redirect to registration page
header('Location: store_register.php');
exit;