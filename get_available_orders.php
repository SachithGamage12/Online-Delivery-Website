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

$delivery_id = $_SESSION['delivery_id'];

// Check if delivery person has any active orders
$active_order_check = "SELECT COUNT(*) as active_count FROM orders WHERE delivery_person_id = ? AND status IN ('ready_for_delivery', 'accepted', 'out_for_delivery')";
$stmt = $conn->prepare($active_order_check);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$active_result = $stmt->get_result();
$active_count = $active_result->fetch_assoc()['active_count'];
$stmt->close();

$has_active_order = $active_count > 0;

// Only fetch available orders if no active orders
if (!$has_active_order) {
    // Fetch available orders (orders ready for delivery but not assigned to any delivery person)
    $available_orders_query = "SELECT o.*, s.store_name, s.address as store_address, s.mobile_primary as store_mobile,
                               s.latitude as store_lat, s.longitude as store_lng,
                               u.email as customer_email, u.mobile as customer_mobile
                        FROM orders o 
                        JOIN stores s ON o.store_id = s.id 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.delivery_person_id IS NULL AND o.status = 'ready_for_delivery'
                        ORDER BY o.created_at ASC";

    $stmt = $conn->prepare($available_orders_query);
    $stmt->execute();
    $available_orders_result = $stmt->get_result();
    $available_orders = $available_orders_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch order items for available orders
    $available_order_items = [];
    if (!empty($available_orders)) {
        $order_ids = array_column($available_orders, 'id');
        $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
        $items_query = "SELECT * FROM order_items WHERE order_id IN ($placeholders)";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
        $stmt->execute();
        $items_result = $stmt->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $available_order_items[$item['order_id']][] = $item;
        }
        $stmt->close();
    }

    // Prepare response
    $available_orders_js = [];
    foreach ($available_orders as $order) {
        $order_data = [
            'id' => $order['id'],
            'store_name' => $order['store_name'],
            'store_address' => $order['store_address'],
            'store_mobile' => $order['store_mobile'],
            'customer_email' => $order['customer_email'],
            'delivery_address' => $order['delivery_address'],
            'delivery_distance' => $order['delivery_distance'],
            'total_amount' => $order['total_amount'],
            'delivery_charge' => $order['delivery_charge'],
            'items' => $available_order_items[$order['id']] ?? []
        ];
        $available_orders_js[] = $order_data;
    }

    echo json_encode([
        'success' => true,
        'available_orders' => $available_orders_js,
        'has_active_order' => $has_active_order
    ]);
} else {
    echo json_encode([
        'success' => true,
        'available_orders' => [],
        'has_active_order' => $has_active_order
    ]);
}
?>