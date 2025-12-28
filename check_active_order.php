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

echo json_encode([
    'success' => true,
    'has_active_order' => $has_active_order,
    'active_count' => $active_count
]);
?>