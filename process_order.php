<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', 'Sun123flower@', 'lakway_delivery');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    // Validate required POST data
    $required_fields = ['user_id', 'cart_items', 'subtotal', 'delivery_charge', 'total_amount', 'delivery_address', 'delivery_distance', 'user_lat', 'user_lng'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Get POST data with validation
    $user_id = intval($_POST['user_id']);
    $cart_items = json_decode($_POST['cart_items'], true);
    $subtotal = floatval($_POST['subtotal']);
    $delivery_charge = floatval($_POST['delivery_charge']);
    $total_amount = floatval($_POST['total_amount']);
    $delivery_address = $conn->real_escape_string($_POST['delivery_address']);
    $delivery_distance = floatval($_POST['delivery_distance']);
    $user_lat = floatval($_POST['user_lat']);
    $user_lng = floatval($_POST['user_lng']);

    // Validate cart items
    if (!is_array($cart_items) || empty($cart_items)) {
        throw new Exception("Cart is empty or invalid");
    }

    // Get store ID
    $store_result = $conn->query("SELECT id FROM stores WHERE status='approved' LIMIT 1");
    if (!$store_result || $store_result->num_rows === 0) {
        throw new Exception("No approved store found");
    }
    $store = $store_result->fetch_assoc();
    $store_id = $store['id'];

    // Start transaction
    $conn->begin_transaction();

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, store_id, subtotal, delivery_charge, total_amount, delivery_address, delivery_distance, user_lat, user_lng, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    
    if (!$stmt) {
        throw new Exception("Prepare failed for orders: " . $conn->error);
    }
    
    $stmt->bind_param("iidddsddd", $user_id, $store_id, $subtotal, $delivery_charge, $total_amount, $delivery_address, $delivery_distance, $user_lat, $user_lng);
    
    if (!$stmt->execute()) {
        throw new Exception("Order insertion failed: " . $stmt->error);
    }
    
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, item_price, quantity, total_price) VALUES (?, ?, ?, ?, ?)");
    
    if (!$item_stmt) {
        throw new Exception("Prepare failed for order_items: " . $conn->error);
    }
    
    foreach ($cart_items as $item) {
        if (!isset($item['name'], $item['price'], $item['quantity'])) {
            throw new Exception("Invalid item data in cart");
        }
        
        $item_total = floatval($item['price']) * intval($item['quantity']);
        $item_stmt->bind_param("isdid", $order_id, $item['name'], $item['price'], $item['quantity'], $item_total);
        
        if (!$item_stmt->execute()) {
            throw new Exception("Order item insertion failed: " . $item_stmt->error);
        }
    }
    
    $item_stmt->close();

    // Clear cart from session
    unset($_SESSION['checkout_cart']);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'order_id' => $order_id,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    
    error_log("Order processing error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error placing order: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>