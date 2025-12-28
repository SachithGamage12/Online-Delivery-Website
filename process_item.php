<?php
session_start();
header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if store is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller' || !isset($_SESSION['store_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$store_id = $_SESSION['store_id'];

// Handle DELETE action
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['item_id'])) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit();
    }
    
    $item_id = intval($_POST['item_id']);
    
    // Verify item belongs to this store
    $verify_query = "SELECT item_image FROM items WHERE id = ? AND store_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $item_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found or unauthorized']);
        exit();
    }
    
    $item = $result->fetch_assoc();
    $stmt->close();
    
    // Delete the item
    $delete_query = "DELETE FROM items WHERE id = ? AND store_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $item_id, $store_id);
    
    if ($stmt->execute()) {
        // Delete image file if exists
        if (!empty($item['item_image']) && file_exists($item['item_image'])) {
            unlink($item['item_image']);
        }
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
    }
    
    $stmt->close();
    exit();
}

// Handle ADD/EDIT item
$item_name = trim($_POST['item_name'] ?? '');
$item_price = floatval($_POST['item_price'] ?? 0);
$stock_count = intval($_POST['stock_count'] ?? 0);
$category = trim($_POST['category'] ?? 'other');
$description = trim($_POST['description'] ?? '');
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;

// Validation
if (empty($item_name)) {
    echo json_encode(['success' => false, 'message' => 'Item name is required']);
    exit();
}

if ($item_price < 0) {
    echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
    exit();
}

if ($stock_count < 0) {
    echo json_encode(['success' => false, 'message' => 'Stock count cannot be negative']);
    exit();
}

// Handle image upload
$image_path = null;
$upload_dir = 'uploads/items/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['item_image']['tmp_name'];
    $file_name = $_FILES['item_image']['name'];
    $file_size = $_FILES['item_image']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed']);
        exit();
    }
    
    // Validate file size (5MB max)
    if ($file_size > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        exit();
    }
    
    // Generate unique filename
    $unique_filename = 'item_' . $store_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $image_path = $upload_dir . $unique_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file_tmp, $image_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit();
    }
}

// UPDATE existing item
if ($item_id) {
    // Verify item belongs to this store
    $verify_query = "SELECT item_image FROM items WHERE id = ? AND store_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $item_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found or unauthorized']);
        exit();
    }
    
    $existing_item = $result->fetch_assoc();
    $stmt->close();
    
    // If new image uploaded, delete old image
    if ($image_path && !empty($existing_item['item_image']) && file_exists($existing_item['item_image'])) {
        unlink($existing_item['item_image']);
    }
    
    // If no new image, keep the old one
    if (!$image_path) {
        $image_path = $existing_item['item_image'];
    }
    
    // Update query
    $update_query = "UPDATE items SET 
                     item_name = ?, 
                     item_price = ?, 
                     stock_count = ?, 
                     category = ?, 
                     description = ?, 
                     item_image = ?,
                     updated_at = NOW()
                     WHERE id = ? AND store_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdisssii", $item_name, $item_price, $stock_count, $category, $description, $image_path, $item_id, $store_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update item: ' . $stmt->error]);
    }
    
    $stmt->close();
}
// INSERT new item
else {
    if (!$image_path) {
        echo json_encode(['success' => false, 'message' => 'Item image is required for new items']);
        exit();
    }
    
    $insert_query = "INSERT INTO items (store_id, item_name, item_price, stock_count, category, description, item_image, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isdisss", $store_id, $item_name, $item_price, $stock_count, $category, $description, $image_path);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item added successfully', 'item_id' => $conn->insert_id]);
    } else {
        // If insert fails, delete uploaded image
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        echo json_encode(['success' => false, 'message' => 'Failed to add item: ' . $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>