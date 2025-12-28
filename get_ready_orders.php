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

// Fetch ready for delivery orders for this store
$ready_orders_query = "SELECT o.*, u.email as customer_email, u.mobile as customer_mobile,
                              dp.username as delivery_person_name, dp.vehicle_type, dp.vehicle_number
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 LEFT JOIN delivery_persons dp ON o.delivery_person_id = dp.id
                 WHERE o.store_id = ? AND o.status IN ('ready_for_delivery', 'out_for_delivery') 
                 ORDER BY o.created_at DESC";
$stmt = $conn->prepare($ready_orders_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$ready_orders_result = $stmt->get_result();
$ready_orders = $ready_orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch order items for ready orders
$ready_order_items = [];
if (!empty($ready_orders)) {
    $order_ids = array_column($ready_orders, 'id');
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    $items_query = "SELECT * FROM order_items WHERE order_id IN ($placeholders)";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $stmt->execute();
    $items_result = $stmt->get_result();
    while ($item = $items_result->fetch_assoc()) {
        $ready_order_items[$item['order_id']][] = $item;
    }
    $stmt->close();
}

// Add items to orders
foreach ($ready_orders as &$order) {
    $order['items'] = $ready_order_items[$order['id']] ?? [];
}

echo json_encode(['success' => true, 'orders' => $ready_orders]);
$conn->close();
?>