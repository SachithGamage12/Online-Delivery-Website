<?php
session_start();
header('Content-Type: application/json');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'delivery' || !isset($_SESSION['delivery_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$delivery_id = $_SESSION['delivery_id'];
$action = $_POST['action'] ?? '';
$order_id = intval($_POST['order_id'] ?? 0);

// Add debug logging
error_log("Delivery Action: $action, Order ID: $order_id, Delivery ID: $delivery_id");

switch ($action) {
    case 'accept_order':
        acceptOrder($conn, $delivery_id, $order_id);
        break;
        
    case 'mark_out_for_delivery':
        markOutForDelivery($conn, $delivery_id, $order_id);
        break;
        
    case 'mark_delivered':
        markDelivered($conn, $delivery_id, $order_id);
        break;
        
    case 'get_order_details':
        getOrderDetails($conn, $delivery_id, $order_id);
        break;
        
    case 'check_active_order':
        checkActiveOrder($conn, $delivery_id);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();

function checkActiveOrder($conn, $delivery_id) {
    // Check if delivery person has any active orders
    $active_check = $conn->prepare("SELECT COUNT(*) as active_count FROM orders WHERE delivery_person_id = ? AND status IN ('ready_for_delivery', 'accepted', 'out_for_delivery')");
    if (!$active_check) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $active_check->bind_param("i", $delivery_id);
    
    if (!$active_check->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database execute failed: ' . $active_check->error]);
        $active_check->close();
        return;
    }
    
    $active_result = $active_check->get_result();
    $active_data = $active_result->fetch_assoc();
    $active_check->close();
    
    echo json_encode([
        'success' => true,
        'has_active_order' => $active_data['active_count'] > 0,
        'active_count' => $active_data['active_count']
    ]);
}

function acceptOrder($conn, $delivery_id, $order_id) {
    // First check if delivery person already has active order
    $active_check = $conn->prepare("SELECT COUNT(*) as active_count FROM orders WHERE delivery_person_id = ? AND status IN ('ready_for_delivery', 'accepted', 'out_for_delivery')");
    if (!$active_check) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $active_check->bind_param("i", $delivery_id);
    
    if (!$active_check->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database execute failed: ' . $active_check->error]);
        $active_check->close();
        return;
    }
    
    $active_result = $active_check->get_result();
    $active_data = $active_result->fetch_assoc();
    $active_check->close();
    
    if ($active_data['active_count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have an active order. Please complete your current delivery before accepting new orders.']);
        return;
    }
    
    // Check if order is still available and in correct status
    $check_stmt = $conn->prepare("SELECT status, delivery_person_id, delivery_charge FROM orders WHERE id = ?");
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $check_stmt->bind_param("i", $order_id);
    
    if (!$check_stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database execute failed: ' . $check_stmt->error]);
        $check_stmt->close();
        return;
    }
    
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }
    
    if ($order['delivery_person_id'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Order already assigned to another delivery person']);
        return;
    }
    
    if ($order['status'] !== 'ready_for_delivery') {
        echo json_encode(['success' => false, 'message' => 'Order is not ready for delivery. Current status: ' . $order['status']]);
        return;
    }
    
    // Calculate earnings (70% for delivery person)
    $delivery_earnings = $order['delivery_charge'] * 0.7;
    
    // Assign order to delivery person and update status to 'accepted'
    $stmt = $conn->prepare("UPDATE orders SET delivery_person_id = ?, status = 'accepted' WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("ii", $delivery_id, $order_id);
    
    if ($stmt->execute()) {
        // Log the acceptance
        logDeliveryAction($conn, $order_id, $delivery_id, 'accepted', 'Delivery person accepted the order. Earnings: LKR ' . number_format($delivery_earnings, 2));
        
        // Update delivery earnings
        updateDeliveryEarnings($conn, $delivery_id, $delivery_earnings);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order accepted successfully',
            'earnings' => $delivery_earnings,
            'message_with_earnings' => 'Order accepted successfully! You will earn: LKR ' . number_format($delivery_earnings, 2)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept order: ' . $stmt->error]);
    }
    $stmt->close();
}

function markOutForDelivery($conn, $delivery_id, $order_id) {
    // Verify delivery person owns this order and it's in correct status
    $check_stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND delivery_person_id = ?");
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $check_stmt->bind_param("ii", $order_id, $delivery_id);
    
    if (!$check_stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database execute failed: ' . $check_stmt->error]);
        $check_stmt->close();
        return;
    }
    
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
        return;
    }
    
    // Allow both 'accepted' and 'ready_for_delivery' statuses to be marked as out for delivery
    if ($order['status'] !== 'accepted' && $order['status'] !== 'ready_for_delivery') {
        echo json_encode(['success' => false, 'message' => 'Order cannot be marked as out for delivery in current status: ' . $order['status']]);
        return;
    }
    
    // Check if out_for_delivery_at column exists, if not use basic update
    $column_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'out_for_delivery_at'");
    if ($column_check->num_rows > 0) {
        // Column exists, use the full update
        $stmt = $conn->prepare("UPDATE orders SET status = 'out_for_delivery', out_for_delivery_at = NOW() WHERE id = ?");
    } else {
        // Column doesn't exist, use basic update
        $stmt = $conn->prepare("UPDATE orders SET status = 'out_for_delivery' WHERE id = ?");
    }
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        // Log the action
        logDeliveryAction($conn, $order_id, $delivery_id, 'out_for_delivery', 'Order is out for delivery');
        echo json_encode(['success' => true, 'message' => 'Order marked as out for delivery']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $stmt->error]);
    }
    $stmt->close();
}

function markDelivered($conn, $delivery_id, $order_id) {
    // Verify delivery person owns this order and it's in correct status
    $check_stmt = $conn->prepare("SELECT status, delivery_charge, total_amount FROM orders WHERE id = ? AND delivery_person_id = ?");
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $check_stmt->bind_param("ii", $order_id, $delivery_id);
    
    if (!$check_stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database execute failed: ' . $check_stmt->error]);
        $check_stmt->close();
        return;
    }
    
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
        return;
    }
    
    if ($order['status'] !== 'out_for_delivery') {
        echo json_encode(['success' => false, 'message' => 'Order must be out for delivery before marking as delivered. Current status: ' . $order['status']]);
        return;
    }
    
    // Calculate final earnings
    $delivery_earnings = $order['delivery_charge'] * 0.7;
    $company_earnings = $order['delivery_charge'] * 0.3;
    $total_collected = $order['total_amount'];
    
    // Check if delivered_at column exists, if not use basic update
    $column_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivered_at'");
    if ($column_check->num_rows > 0) {
        // Column exists, use the full update
        $stmt = $conn->prepare("UPDATE orders SET status = 'delivered', delivered_at = NOW() WHERE id = ?");
    } else {
        // Column doesn't exist, use basic update
        $stmt = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
    }
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        // Log the delivery completion with earnings information
        logDeliveryAction($conn, $order_id, $delivery_id, 'delivered', 
            "Order delivered successfully. " .
            "Total collected: LKR " . number_format($total_collected, 2) . ". " .
            "Your earnings: LKR " . number_format($delivery_earnings, 2) . ". " .
            "Company earnings: LKR " . number_format($company_earnings, 2)
        );
        
        // Update delivery person stats and earnings
        updateDeliveryStats($conn, $delivery_id, $delivery_earnings);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order marked as delivered successfully',
            'earnings_info' => [
                'delivery_earnings' => $delivery_earnings,
                'company_earnings' => $company_earnings,
                'total_collected' => $total_collected,
                'message' => "Order completed! You earned: LKR " . number_format($delivery_earnings, 2) . 
                           " (Total collected: LKR " . number_format($total_collected, 2) . ")"
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark order as delivered: ' . $stmt->error]);
    }
    $stmt->close();
}

function getOrderDetails($conn, $delivery_id, $order_id) {
    // Get detailed order information including store and customer details
    $query = "SELECT o.*, 
                     s.store_name, s.address as store_address, s.mobile_primary as store_mobile,
                     s.latitude as store_lat, s.longitude as store_lng,
                     u.email as customer_email, u.mobile as customer_mobile, u.full_name as customer_name,
                     dp.username as delivery_person_name, dp.mobile as delivery_person_mobile
              FROM orders o 
              JOIN stores s ON o.store_id = s.id 
              JOIN users u ON o.user_id = u.id 
              LEFT JOIN delivery_persons dp ON o.delivery_person_id = dp.id
              WHERE o.id = ? AND (o.delivery_person_id = ? OR o.delivery_person_id IS NULL)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("ii", $order_id, $delivery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        return;
    }
    
    // Calculate earnings
    $delivery_earnings = $order['delivery_charge'] * 0.7;
    $order['delivery_earnings'] = $delivery_earnings;
    
    // Get order items
    $items_query = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    if ($items_query) {
        $items_query->bind_param("i", $order_id);
        $items_query->execute();
        $items_result = $items_query->get_result();
        $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
        $items_query->close();
    } else {
        $order['items'] = [];
    }
    
    // Get delivery timeline (if table exists)
    $timeline_query = $conn->prepare("SELECT * FROM delivery_timeline WHERE order_id = ? ORDER BY created_at ASC");
    if ($timeline_query) {
        $timeline_query->bind_param("i", $order_id);
        $timeline_query->execute();
        $timeline_result = $timeline_query->get_result();
        $order['timeline'] = $timeline_result->fetch_all(MYSQLI_ASSOC);
        $timeline_query->close();
    } else {
        $order['timeline'] = [];
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
}

function logDeliveryAction($conn, $order_id, $delivery_id, $action, $description) {
    try {
        // Create delivery_timeline table if not exists (without foreign key constraints first)
        $create_table = "CREATE TABLE IF NOT EXISTS delivery_timeline (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            delivery_person_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->query($create_table);
        
        // Log the action
        $stmt = $conn->prepare("INSERT INTO delivery_timeline (order_id, delivery_person_id, action, description) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iiss", $order_id, $delivery_id, $action, $description);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently fail logging - don't break the main functionality
        error_log("Logging error: " . $e->getMessage());
    }
}

function updateDeliveryEarnings($conn, $delivery_id, $earnings) {
    try {
        // Create delivery_earnings table if not exists
        $create_table = "CREATE TABLE IF NOT EXISTS delivery_earnings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            delivery_person_id INT NOT NULL,
            order_id INT,
            earnings DECIMAL(10,2) DEFAULT 0,
            earnings_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->query($create_table);
        
        // Insert earnings record
        $stmt = $conn->prepare("INSERT INTO delivery_earnings (delivery_person_id, order_id, earnings, earnings_date) VALUES (?, NULL, ?, CURDATE())");
        if ($stmt) {
            $stmt->bind_param("id", $delivery_id, $earnings);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Earnings update error: " . $e->getMessage());
    }
}

function updateDeliveryStats($conn, $delivery_id, $earnings = 0) {
    try {
        // Create delivery_stats table if not exists
        $create_table = "CREATE TABLE IF NOT EXISTS delivery_stats (
            delivery_person_id INT PRIMARY KEY,
            total_deliveries INT DEFAULT 0,
            completed_deliveries INT DEFAULT 0,
            total_earnings DECIMAL(10,2) DEFAULT 0,
            today_earnings DECIMAL(10,2) DEFAULT 0,
            rating DECIMAL(3,2) DEFAULT 0,
            last_delivery TIMESTAMP NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->query($create_table);
        
        // Update or insert stats with earnings
        $query = "INSERT INTO delivery_stats (delivery_person_id, total_deliveries, completed_deliveries, total_earnings, today_earnings, last_delivery) 
                  VALUES (?, 1, 1, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE 
                  total_deliveries = total_deliveries + 1,
                  completed_deliveries = completed_deliveries + 1,
                  total_earnings = total_earnings + VALUES(total_earnings),
                  today_earnings = today_earnings + VALUES(today_earnings),
                  last_delivery = NOW()";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("idd", $delivery_id, $earnings, $earnings);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Stats update error: " . $e->getMessage());
    }
}
?>