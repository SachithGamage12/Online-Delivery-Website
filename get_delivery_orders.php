<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check if delivery person is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'delivery' || !isset($_SESSION['delivery_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Fetch ready orders that are not assigned to any delivery person
$ready_orders_query = "SELECT o.*, s.store_name, s.address as store_address, s.mobile_primary as store_mobile,
                              s.latitude as store_lat, s.longitude as store_lng,
                              u.email as customer_email, u.mobile as customer_mobile
                       FROM orders o 
                       JOIN stores s ON o.store_id = s.id 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.status = 'ready_for_delivery' 
                       AND (o.delivery_person_id IS NULL OR o.delivery_person_id = 0)
                       ORDER BY o.created_at ASC";
$result = $conn->query($ready_orders_query);
$ready_orders = [];

if ($result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
        // Fetch order items for each order
        $items_query = "SELECT * FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("i", $order['id']);
        $stmt->execute();
        $items_result = $stmt->get_result();
        $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $ready_orders[] = $order;
    }
}

echo json_encode([
    'success' => true,
    'ready_orders' => $ready_orders
]);
?>