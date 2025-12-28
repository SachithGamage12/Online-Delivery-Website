<?php
session_start();
header('Content-Type: application/json');

// Database connection
$conn = new mysqli('localhost', 'root', 'Sun123flower@', 'lakway_delivery');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$store_id = $_GET['store_id'] ?? 0;

// Fetch pending orders
$orders_query = "SELECT o.*, u.email as customer_email, u.mobile as customer_mobile 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 WHERE o.store_id = ? AND o.status = 'pending' 
                 ORDER BY o.created_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch order items
$order_items = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    $items_query = "SELECT * FROM order_items WHERE order_id IN ($placeholders)";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $stmt->execute();
    $items_result = $stmt->get_result();
    while ($item = $items_result->fetch_assoc()) {
        $order_items[$item['order_id']][] = $item;
    }
    $stmt->close();
}

// Add items to orders
foreach ($orders as &$order) {
    $order['items'] = $order_items[$order['id']] ?? [];
}

echo json_encode(['success' => true, 'orders' => $orders]);
$conn->close();
?>