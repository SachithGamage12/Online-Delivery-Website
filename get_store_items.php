<?php
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

// Get store ID from query parameter
if (!isset($_GET['store_id'])) {
    echo json_encode(['success' => false, 'message' => 'Store ID is required']);
    exit();
}

$store_id = intval($_GET['store_id']);

// Verify store exists and is active
$store_query = "SELECT id, store_name FROM stores WHERE id = ? AND status = 'approved' AND is_active = 1";
$stmt = $conn->prepare($store_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store_result = $stmt->get_result();

if ($store_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Store not found or inactive']);
    exit();
}

$store = $store_result->fetch_assoc();
$stmt->close();

// Fetch all available items for this store
$items_query = "SELECT 
                    id,
                    item_name,
                    item_price,
                    stock_count,
                    category,
                    description,
                    item_image,
                    is_available
                FROM items 
                WHERE store_id = ? AND is_available = 1
                ORDER BY category, item_name ASC";

$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Return items with store info
echo json_encode([
    'success' => true,
    'store' => $store,
    'items' => $items,
    'total_items' => count($items)
]);

$conn->close();
?>