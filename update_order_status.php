<?php
session_start();
header('Content-Type: application/json');

// === DATABASE CONNECTION ===
$conn = new mysqli('localhost', 'root', 'Sun123flower@', 'lakway_delivery');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// === INPUT DATA ===
$order_id   = $_POST['order_id'] ?? 0;
$status     = $_POST['status'] ?? '';
$store_id   = $_POST['store_id'] ?? 0;

// === VALIDATION ===
if ($order_id <= 0 || $store_id <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// === ALLOWED STATUSES (Store can only update these) ===
$allowed_statuses = [
    'accepted',
    'declined',
    'out_of_stock',
    'ready_for_delivery'  // NEW: Store can mark as ready
    // 'out_for_delivery' // Only admin/delivery person should set this
];

if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// === SECURITY: Verify store owns the order ===
$check_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND store_id = ?");
$check_stmt->bind_param("ii", $order_id, $store_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Order not found or not yours']);
    exit;
}
$check_stmt->close();

// === UPDATE ORDER STATUS ===
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND store_id = ?");
$stmt->bind_param("sii", $status, $order_id, $store_id);

if ($stmt->execute()) {
    // Optional: Log action or trigger notifications
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated to ' . ucfirst(str_replace('_', ' ', $status)),
        'new_status' => $status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}

$stmt->close();
$conn->close();
?>