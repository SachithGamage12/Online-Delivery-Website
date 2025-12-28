<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check if delivery person is logged in and approved
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'delivery' || !isset($_SESSION['delivery_id'])) {
    header('Location: delivery_login.php');
    exit();
}

$delivery_id = $_SESSION['delivery_id'];

// Fetch delivery person details
$delivery_query = "SELECT * FROM delivery_persons WHERE id = ?";
$stmt = $conn->prepare($delivery_query);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$delivery_result = $stmt->get_result();
$delivery_person = $delivery_result->fetch_assoc();
$stmt->close();

// Check if delivery person has any active orders
$active_order_check = "SELECT COUNT(*) as active_count FROM orders WHERE delivery_person_id = ? AND status IN ('ready_for_delivery', 'accepted', 'out_for_delivery')";
$stmt = $conn->prepare($active_order_check);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$active_result = $stmt->get_result();
$active_count = $active_result->fetch_assoc()['active_count'];
$stmt->close();

$has_active_order = $active_count > 0;

// Fetch my assigned orders (only active ones)
$my_orders_query = "SELECT o.*, s.store_name, s.address as store_address, s.mobile_primary as store_mobile,
                           s.latitude as store_lat, s.longitude as store_lng,
                           u.email as customer_email, u.mobile as customer_mobile
                    FROM orders o 
                    JOIN stores s ON o.store_id = s.id 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.delivery_person_id = ? AND o.status IN ('ready_for_delivery', 'out_for_delivery')
                    ORDER BY 
                        CASE 
                            WHEN o.status = 'ready_for_delivery' THEN 1
                            WHEN o.status = 'out_for_delivery' THEN 2
                            ELSE 3
                        END,
                        o.created_at DESC";
$stmt = $conn->prepare($my_orders_query);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$my_orders_result = $stmt->get_result();
$my_orders = $my_orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch completed orders for history
$completed_orders_query = "SELECT o.*, s.store_name, s.address as store_address,
                                  u.email as customer_email, u.mobile as customer_mobile
                           FROM orders o 
                           JOIN stores s ON o.store_id = s.id 
                           JOIN users u ON o.user_id = u.id 
                           WHERE o.delivery_person_id = ? AND o.status = 'delivered'
                           ORDER BY o.delivered_at DESC LIMIT 20";
$stmt = $conn->prepare($completed_orders_query);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$completed_orders_result = $stmt->get_result();
$completed_orders = $completed_orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch order items for my orders
$my_order_items = [];
if (!empty($my_orders)) {
    $order_ids = array_column($my_orders, 'id');
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    $items_query = "SELECT * FROM order_items WHERE order_id IN ($placeholders)";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $stmt->execute();
    $items_result = $stmt->get_result();
    while ($item = $items_result->fetch_assoc()) {
        $my_order_items[$item['order_id']][] = $item;
    }
    $stmt->close();
}

// Fetch order items for completed orders
$completed_order_items = [];
if (!empty($completed_orders)) {
    $order_ids = array_column($completed_orders, 'id');
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    $items_query = "SELECT * FROM order_items WHERE order_id IN ($placeholders)";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $stmt->execute();
    $items_result = $stmt->get_result();
    while ($item = $items_result->fetch_assoc()) {
        $completed_order_items[$item['order_id']][] = $item;
    }
    $stmt->close();
}

// Calculate earnings
$earnings_query = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(delivery_charge) as total_earnings,
                    SUM(delivery_charge) * 0.7 as delivery_person_earnings,
                    SUM(total_amount - delivery_charge) as company_earnings
                   FROM orders 
                   WHERE delivery_person_id = ? AND status = 'delivered' 
                   AND DATE(delivered_at) = CURDATE()";
$stmt = $conn->prepare($earnings_query);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$earnings_result = $stmt->get_result();
$earnings = $earnings_result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - ලක්way Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key="Your_Google_Api_Key"&libraries=geometry"></script>
    <style>
               * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            padding-top: 70px;
        }
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-section img {
            height: 45px;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #dc2626;
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        .section-header h2 {
            font-size: 20px;
            color: #1e293b;
        }
        .badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .orders-grid {
            display: grid;
            gap: 15px;
        }
        .order-card {
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .order-info h3 {
            color: #1e293b;
            margin-bottom: 5px;
        }
        .order-meta {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }
        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-navigate-store {
            background: #3b82f6;
            color: white;
        }
        .btn-navigate-store:hover:not(:disabled) {
            background: #2563eb;
        }
        .btn-out-delivery {
            background: #f59e0b;
            color: white;
        }
        .btn-out-delivery:hover:not(:disabled) {
            background: #d97706;
        }
        .btn-navigate-customer {
            background: #8b5cf6;
            color: white;
        }
        .btn-navigate-customer:hover:not(:disabled) {
            background: #7c3aed;
        }
        .btn-delivered {
            background: #10b981;
            color: white;
        }
        .btn-delivered:hover:not(:disabled) {
            background: #059669;
        }
        .order-items {
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
            margin-top: 15px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8fafc;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }
        .item-quantity {
            color: #64748b;
            font-size: 13px;
        }
        .item-price {
            font-weight: 700;
            color: #2d7a4e;
            font-size: 14px;
        }
        .order-total {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            color: #2d7a4e;
            padding-top: 15px;
            border-top: 2px dashed #e2e8f0;
            margin-top: 15px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        .empty-state h4 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        /* Animation Styles */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0,-8px,0);
            }
            70% {
                transform: translate3d(0,-4px,0);
            }
            90% {
                transform: translate3d(0,-2px,0);
            }
        }
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(3);
                opacity: 0;
            }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        .bounce-animation {
            animation: bounce 2s infinite;
        }
        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.6);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        
        .delivery-guy-animation {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            z-index: 1000;
            pointer-events: none;
        }
        .delivery-guy {
            width: 100%;
            height: 100%;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-ready {
            background: #fef3c7;
            color: #d97706;
        }
        .status-out {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }
        
        /* Popup Styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }
        .popup-overlay.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }
        .popup-content {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.4s ease;
            position: relative;
        }
        .popup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            position: relative;
        }
        .popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .popup-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        .popup-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .popup-subtitle {
            opacity: 0.9;
            font-size: 13px;
        }
        .order-counter {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
        }
        .popup-body {
            padding: 20px;
        }
        .popup-section {
            margin-bottom: 20px;
        }
        .popup-section-title {
            font-size: 13px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 15px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-value {
            color: #1e293b;
            text-align: right;
            font-size: 14px;
            word-break: break-word;
        }
        .popup-items {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-top: 10px;
        }
        .popup-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            gap: 10px;
        }
        .popup-item:last-child {
            border-bottom: none;
        }
        .popup-total {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        .popup-total-label {
            font-size: 13px;
            color: #059669;
            margin-bottom: 5px;
        }
        .popup-total-amount {
            font-size: 26px;
            font-weight: 700;
            color: #10b981;
        }
        .popup-footer {
            padding: 0 20px 20px 20px;
            display: flex;
            gap: 10px;
        }
        .popup-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .popup-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .popup-btn-accept {
            background: #10b981;
            color: white;
        }
        .popup-btn-accept:hover:not(:disabled) {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }
        .popup-btn-close {
            background: #ef4444;
            color: white;
        }
        .popup-btn-close:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        /* Map Modal */
        .map-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 3000;
            animation: fadeIn 0.3s ease;
        }
        .map-modal.active {
            display: flex;
            flex-direction: column;
        }
        .map-header {
            background: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .map-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        .map-close {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .map-container {
            flex: 1;
            position: relative;
        }
        #map {
            width: 100%;
            height: 100%;
        }
        .map-info {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10;
        }
        .map-info h4 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #1e293b;
        }
        .map-info p {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0;
        }
        .map-info .distance {
            font-size: 15px;
            font-weight: 700;
            color: #667eea;
            margin-top: 8px;
        }
        .map-actions {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .map-action-btn {
            padding: 12px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .map-action-btn.out-delivery {
            background: #f59e0b;
        }
        .map-action-btn.delivered {
            background: #10b981;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }
            .header {
                padding: 10px 15px;
                height: 60px;
            }
            .brand-name {
                font-size: 16px;
            }
            .user-info span {
                font-size: 14px;
            }
            .logout-btn {
                padding: 6px 12px;
                font-size: 13px;
            }
            .main-container {
                padding: 15px;
            }
            .section {
                padding: 15px;
                border-radius: 12px;
            }
            .section-header h2 {
                font-size: 18px;
            }
            .order-card {
                padding: 15px;
            }
            .order-header {
                flex-direction: column;
                gap: 12px;
            }
            .order-actions {
                width: 100%;
            }
            .btn {
                flex: 1;
                min-width: 0;
                padding: 10px 12px;
                font-size: 12px;
            }
            .popup-content {
                max-height: 95vh;
            }
            .popup-header {
                padding: 15px;
            }
            .popup-title {
                font-size: 18px;
            }
            .popup-body {
                padding: 15px;
            }
            .popup-footer {
                padding: 0 15px 15px 15px;
            }
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            .info-value {
                text-align: left;
            }
            .map-info {
                font-size: 12px;
            }
            .map-info h4 {
                font-size: 14px;
            }
            .delivery-guy-animation {
                width: 60px;
                height: 60px;
                bottom: 15px;
                right: 15px;
            }
            .delivery-guy {
                font-size: 18px;
            }
            .map-actions {
                bottom: 10px;
                left: 10px;
                right: 10px;
                transform: none;
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .logo-section img {
                height: 35px;
            }
            .brand-name {
                font-size: 14px;
            }
            .user-info {
                gap: 8px;
            }
            .user-info span {
                display: none;
            }
            .order-meta {
                font-size: 13px;
            }
            .item-name {
                font-size: 13px;
            }
            .popup-title {
                font-size: 16px;
            }
            .popup-total-amount {
                font-size: 22px;
            }
        }
        .nav-tabs {
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav-tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            background: transparent;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #64748b;
        }
        .nav-tab.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .earnings-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .earnings-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .earnings-stat {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .earnings-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .earnings-value {
            font-size: 20px;
            font-weight: 700;
        }
        .completed-order-card {
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            background: white;
        }
        .completed-order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .delivery-earnings {
            background: #10b981;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .no-active-order {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .no-active-order h3 {
            color: #64748b;
            margin-bottom: 10px;
        }
        .no-active-order p {
            color: #94a3b8;
            margin-bottom: 20px;
        }
        .refresh-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .refresh-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Delivery Guy Animation -->
    <div class="delivery-guy-animation" id="deliveryGuyAnimation" style="display: none;">
        <div class="delivery-guy float-animation">🚚</div>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <img src="assets/logo.png" alt="ලක්way" onerror="this.style.display='none'">
            <span class="brand-name">Delivery Dashboard</span>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($delivery_person['username']); ?></span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <!-- Order Popup -->
    <div class="popup-overlay" id="orderPopup">
        <div class="popup-content">
            <div class="popup-header">
                <button class="popup-close" onclick="closePopup()">×</button>
                <div class="popup-title">New Order Available!</div>
                <div class="popup-subtitle">Please review the order details</div>
                <div class="order-counter" id="orderCounter"></div>
            </div>
            <div class="popup-body" id="popupBody"></div>
            <div class="popup-footer">
                <button class="popup-btn popup-btn-accept" onclick="acceptCurrentOrder()" id="acceptBtn">
                    Accept Order
                </button>
                <button class="popup-btn popup-btn-close" onclick="closePopup()">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="map-modal" id="mapModal">
        <div class="map-header">
            <div class="map-title" id="mapTitle">Navigate to Store</div>
            <button class="map-close" onclick="closeMap()">Close</button>
        </div>
        <div class="map-container">
            <div class="map-info" id="mapInfo"></div>
            <div id="map"></div>
            <div class="map-actions" id="mapActions"></div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Earnings Card -->
        <div class="earnings-card">
            <h2 style="color: white; margin-bottom: 10px;">Today's Earnings</h2>
            <div class="earnings-stats">
                <div class="earnings-stat">
                    <div class="earnings-label">Total Deliveries</div>
                    <div class="earnings-value"><?php echo $earnings['total_deliveries'] ?? 0; ?></div>
                </div>
                <div class="earnings-stat">
                    <div class="earnings-label">Your Earnings (70%)</div>
                    <div class="earnings-value">LKR <?php echo number_format($earnings['delivery_person_earnings'] ?? 0, 2); ?></div>
                </div>
                <div class="earnings-stat">
                    <div class="earnings-label">Company Earnings (30%)</div>
                    <div class="earnings-value">LKR <?php echo number_format($earnings['company_earnings'] ?? 0, 2); ?></div>
                </div>
                <div class="earnings-stat">
                    <div class="earnings-label">Total Collected</div>
                    <div class="earnings-value">LKR <?php echo number_format($earnings['total_earnings'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="switchTab('active')">Active Orders</button>
            <button class="nav-tab" onclick="switchTab('completed')">Completed Orders</button>
        </div>

        <!-- Active Orders Tab -->
        <div class="tab-content active" id="activeTab">
            <div class="section">
                <div class="section-header">
                    <h2>My Active Orders</h2>
                    <span class="badge"><?php echo count($my_orders); ?> orders</span>
                </div>
                
                <?php if (!$has_active_order): ?>
                    <div class="no-active-order">
                        <h3>No Active Orders</h3>
                        <p>You don't have any active orders at the moment. New orders will appear here automatically.</p>
                        <button class="refresh-btn" onclick="checkForNewOrders()">Check for New Orders</button>
                    </div>
                <?php else: ?>
                    <div class="orders-grid" id="myOrdersGrid">
                        <?php foreach ($my_orders as $order): ?>
                            <div class="order-card" id="my-order-<?php echo $order['id']; ?>">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h3>Order #<?php echo $order['id']; ?></h3>
                                        <div class="order-meta">
                                            <strong>Store:</strong> <?php echo htmlspecialchars($order['store_name']); ?><br>
                                            <strong>Address:</strong> <?php echo htmlspecialchars($order['store_address']); ?><br>
                                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                            <strong>Deliver to:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?><br>
                                            <strong>Status:</strong> 
                                            <span class="status-badge 
                                                <?php 
                                                if ($order['status'] === 'ready_for_delivery') echo 'status-ready';
                                                elseif ($order['status'] === 'out_for_delivery') echo 'status-out';
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="order-actions">
                                        <?php if ($order['status'] === 'ready_for_delivery'): ?>
                                            <button class="btn btn-navigate-store pulse-animation" 
                                                    onclick="navigateToStore(<?php echo $order['id']; ?>, <?php echo $order['store_lat']; ?>, <?php echo $order['store_lng']; ?>, '<?php echo addslashes($order['store_name']); ?>', '<?php echo addslashes($order['store_address']); ?>')"
                                                    id="navStoreBtn-<?php echo $order['id']; ?>">
                                                📍 Navigate to Store
                                            </button>
                                        <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                                            <button class="btn btn-navigate-customer bounce-animation" 
                                                    onclick="navigateToCustomer(<?php echo $order['id']; ?>, <?php echo $order['user_lat']; ?>, <?php echo $order['user_lng']; ?>, 'Customer Location', '<?php echo addslashes($order['delivery_address']); ?>')"
                                                    id="navCustomerBtn-<?php echo $order['id']; ?>">
                                                🏠 Navigate to Customer
                                            </button>
                                            <button class="btn btn-delivered" 
                                                    onclick="markDelivered(<?php echo $order['id']; ?>)"
                                                    id="deliverBtn-<?php echo $order['id']; ?>">
                                                ✅ Mark Delivered
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="order-items">
                                    <?php if (isset($my_order_items[$order['id']])): ?>
                                        <?php foreach ($my_order_items[$order['id']] as $item): ?>
                                            <div class="order-item">
                                                <div class="item-details">
                                                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                    <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                                </div>
                                                <div class="item-price">LKR <?php echo number_format($item['total_price'], 2); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-total">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Total: LKR <?php echo number_format($order['total_amount'], 2); ?></strong><br>
                                            <small style="color: #64748b;">Delivery Charge: LKR <?php echo number_format($order['delivery_charge'], 2); ?></small>
                                        </div>
                                        <div class="delivery-earnings">
                                            Your Earnings: LKR <?php echo number_format($order['delivery_charge'] * 0.7, 2); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completed Orders Tab -->
        <div class="tab-content" id="completedTab">
            <div class="section">
                <div class="section-header">
                    <h2>Completed Orders</h2>
                    <span class="badge"><?php echo count($completed_orders); ?> orders</span>
                </div>
                
                <?php if (empty($completed_orders)): ?>
                    <div class="empty-state">
                        <h4>No completed orders yet</h4>
                        <p>Your completed orders will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($completed_orders as $order): ?>
                            <div class="completed-order-card">
                                <div class="completed-order-header">
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <div class="delivery-earnings">
                                        +LKR <?php echo number_format($order['delivery_charge'] * 0.7, 2); ?>
                                    </div>
                                </div>
                                <div class="order-meta">
                                    <strong>Store:</strong> <?php echo htmlspecialchars($order['store_name']); ?><br>
                                    <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                    <strong>Delivered to:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?><br>
                                    <strong>Completed:</strong> <?php echo date('M j, Y g:i A', strtotime($order['delivered_at'] ?? $order['created_at'])); ?>
                                </div>
                                
                                <div class="order-items">
                                    <?php if (isset($completed_order_items[$order['id']])): ?>
                                        <?php foreach ($completed_order_items[$order['id']] as $item): ?>
                                            <div class="order-item">
                                                <div class="item-details">
                                                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                    <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                                </div>
                                                <div class="item-price">LKR <?php echo number_format($item['total_price'], 2); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-total">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Total Collected: LKR <?php echo number_format($order['total_amount'], 2); ?></strong><br>
                                            <small style="color: #64748b;">
                                                Delivery Charge: LKR <?php echo number_format($order['delivery_charge'], 2); ?> | 
                                                Your Share (70%): LKR <?php echo number_format($order['delivery_charge'] * 0.7, 2); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
// Add these new functions
function switchTab(tabName) {
    // Update active tab
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`.nav-tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
    
    // Update active content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(tabName + 'Tab').classList.add('active');
}

let availableOrders = [];
let currentOrderIndex = 0;
let autoSwitchInterval = null;
let map = null;
let deliveryMarker = null;
let destinationMarker = null;
let directionsRenderer = null;
let directionsService = null;
let watchId = null;
let currentOrderId = null;
let currentDestinationType = null; // 'store' or 'customer'
let currentDestination = null;

// Add ripple effect to buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn') || e.target.classList.contains('popup-btn') || e.target.classList.contains('map-action-btn')) {
        createRipple(e);
    }
});

function createRipple(event) {
    const button = event.currentTarget;
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    const radius = diameter / 2;

    circle.style.width = circle.style.height = diameter + 'px';
    circle.style.left = (event.clientX - button.getBoundingClientRect().left - radius) + 'px';
    circle.style.top = (event.clientY - button.getBoundingClientRect().top - radius) + 'px';
    circle.classList.add('ripple');

    const ripple = button.getElementsByClassName('ripple')[0];
    if (ripple) {
        ripple.remove();
    }

    button.appendChild(circle);
}

function showDeliveryGuyAnimation() {
    const animation = document.getElementById('deliveryGuyAnimation');
    animation.style.display = 'block';
    
    setTimeout(() => {
        animation.style.display = 'none';
    }, 3000);
}

function navigateToStore(orderId, storeLat, storeLng, storeName, storeAddress) {
    currentOrderId = orderId;
    currentDestinationType = 'store';
    currentDestination = {
        lat: storeLat,
        lng: storeLng,
        name: storeName,
        address: storeAddress
    };
    
    document.getElementById('mapTitle').textContent = 'Navigate to Store';
    showMap(storeLat, storeLng, storeName, storeAddress, 'store');
}

function navigateToCustomer(orderId, customerLat, customerLng, locationName, locationAddress) {
    currentOrderId = orderId;
    currentDestinationType = 'customer';
    currentDestination = {
        lat: customerLat,
        lng: customerLng,
        name: locationName,
        address: locationAddress
    };
    
    document.getElementById('mapTitle').textContent = 'Navigate to Customer';
    showMap(customerLat, customerLng, locationName, locationAddress, 'customer');
}

function markOutForDelivery() {
    if (!currentOrderId) return;
    
    const button = document.querySelector('.map-action-btn.out-delivery');
    if (button) {
        button.disabled = true;
        button.innerHTML = '🔄 Updating...';
    }
    
    // Show delivery guy animation
    showDeliveryGuyAnimation();
    
    fetch('delivery_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_out_for_delivery&order_id=${currentOrderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (button) {
                button.innerHTML = '✅ On the way!';
                button.style.background = '#10b981';
            }
            
            setTimeout(() => {
                closeMap();
                location.reload();
            }, 2000);
        } else {
            alert('Error: ' + data.message);
            if (button) {
                button.disabled = false;
                button.innerHTML = '🚚 Out for Delivery';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating order');
        if (button) {
            button.disabled = false;
            button.innerHTML = '🚚 Out for Delivery';
        }
    });
}

function markDelivered(orderId) {
    const button = document.getElementById('deliverBtn-' + orderId);
    const originalText = button.innerHTML;
    
    if (confirm('Have you delivered this order and collected payment?')) {
        button.innerHTML = '✅ Confirming...';
        button.disabled = true;
        
        fetch('delivery_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_delivered&order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.add('shake-animation');
                button.innerHTML = '🎉 Delivered!';
                
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert('Error: ' + data.message);
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating order');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

function showMap(destLat, destLng, destName, destAddress, destinationType) {
    document.getElementById('mapModal').classList.add('active');
    
    // Update map actions based on destination type
    updateMapActions(destinationType);
    
    document.getElementById('mapInfo').innerHTML = '<p style="color: #4285F4; font-weight: bold;">📍 Getting your GPS location...</p>';
    
    if (!navigator.geolocation) {
        alert('❌ Your browser does not support GPS location.');
        closeMap();
        return;
    }

    console.log('🔍 Requesting high-accuracy GPS location...');
    
    let bestAccuracy = Infinity;
    let bestPosition = null;
    let attempts = 0;
    const maxAttempts = 3;
    
    function tryGetLocation() {
        attempts++;
        console.log(`📡 GPS attempt ${attempts}/${maxAttempts}...`);
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const accuracy = position.coords.accuracy;
                
                console.log(`📍 Got location (Attempt ${attempts}):`, {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: accuracy + ' meters'
                });
                
                if (accuracy < bestAccuracy) {
                    bestAccuracy = accuracy;
                    bestPosition = position;
                    
                    document.getElementById('mapInfo').innerHTML = 
                        `<p style="color: #10b981; font-weight: bold;">✅ GPS Lock: ${accuracy.toFixed(0)}m accuracy</p>`;
                }
                
                if (accuracy < 50 || attempts >= maxAttempts) {
                    console.log('✅ Using GPS location');
                    
                    initMap(
                        bestPosition.coords.latitude, 
                        bestPosition.coords.longitude, 
                        destLat, 
                        destLng, 
                        destName, 
                        destAddress,
                        destinationType
                    );
                    startLocationTracking(destLat, destLng, destName, destAddress, destinationType);
                } else {
                    setTimeout(tryGetLocation, 1000);
                }
            },
            (error) => {
                console.error('❌ GPS Error (Attempt ' + attempts + '):', error);
                
                if (attempts >= maxAttempts || error.code === error.PERMISSION_DENIED) {
                    let errorMsg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = '🚫 LOCATION PERMISSION DENIED\n\nPlease allow location access in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = '📡 GPS SIGNAL UNAVAILABLE\n\nPlease go outside or near a window.';
                            break;
                        case error.TIMEOUT:
                            errorMsg = '⏱️ GPS TIMEOUT\n\nPlease try again.';
                            break;
                        default:
                            errorMsg = 'Unknown GPS error. Please try again.';
                    }
                    
                    alert(errorMsg);
                    closeMap();
                } else {
                    setTimeout(tryGetLocation, 1000);
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }
    
    tryGetLocation();
}

function updateMapActions(destinationType) {
    const mapActions = document.getElementById('mapActions');
    let actionsHTML = '';
    
    if (destinationType === 'store') {
        actionsHTML = `
            <button class="map-action-btn out-delivery" onclick="markOutForDelivery()">
                🚚 Mark Out for Delivery
            </button>
        `;
    } else if (destinationType === 'customer') {
        actionsHTML = `
            <button class="map-action-btn delivered" onclick="markDelivered(${currentOrderId})">
                ✅ Mark Delivered
            </button>
        `;
    }
    
    mapActions.innerHTML = actionsHTML;
}

function initMap(driverLat, driverLng, destLat, destLng, destName, destAddress, destinationType) {
    const driverPos = { lat: parseFloat(driverLat), lng: parseFloat(driverLng) };
    const destPos = { lat: parseFloat(destLat), lng: parseFloat(destLng) };
    
    console.log('Initializing map - Driver:', driverPos, 'Destination:', destPos);
    
    map = new google.maps.Map(document.getElementById('map'), {
        center: driverPos,
        zoom: 14,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        zoomControl: true,
        styles: [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ]
    });
    
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: map,
        suppressMarkers: true,
        polylineOptions: {
            strokeColor: '#000000',
            strokeWeight: 5,
            strokeOpacity: 0.8
        },
        preserveViewport: true
    });
    
    // Driver marker
    deliveryMarker = new google.maps.Marker({
        position: driverPos,
        map: map,
        title: 'Your Location',
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: '#4285F4',
            fillOpacity: 1,
            strokeColor: '#FFFFFF',
            strokeWeight: 3
        },
        zIndex: 1000
    });
    
    // Pulse circle around driver
    const pulseCircle = new google.maps.Circle({
        strokeColor: '#4285F4',
        strokeOpacity: 0.4,
        strokeWeight: 2,
        fillColor: '#4285F4',
        fillOpacity: 0.15,
        map: map,
        center: driverPos,
        radius: 80,
        zIndex: 999
    });
    
    // Destination marker with different colors based on type
    const destinationIcon = destinationType === 'store' 
        ? 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        : 'https://maps.google.com/mapfiles/ms/icons/red-dot.png';
        
    destinationMarker = new google.maps.Marker({
        position: destPos,
        map: map,
        title: destName,
        icon: {
            url: destinationIcon,
            scaledSize: new google.maps.Size(40, 40)
        },
        zIndex: 998
    });
    
    const infoWindow = new google.maps.InfoWindow({
        content: `<div style="padding: 8px; font-family: Arial, sans-serif;">
                    <div style="font-weight: bold; margin-bottom: 4px;">${destName}</div>
                    <div style="font-size: 12px; color: #666;">${destAddress}</div>
                  </div>`
    });
    
    destinationMarker.addListener('click', () => {
        infoWindow.open(map, destinationMarker);
    });
    
    calculateRoute(driverPos, destPos, pulseCircle);
    updateMapInfo(driverPos, destPos, destName, destAddress, destinationType);
}

function calculateRoute(origin, destination, pulseCircle) {
    if (!directionsService || !directionsRenderer) {
        console.error('Directions service not initialized');
        return;
    }
    
    const request = {
        origin: origin,
        destination: destination,
        travelMode: google.maps.TravelMode.DRIVING,
        provideRouteAlternatives: false
    };
    
    directionsService.route(request, (result, status) => {
        if (status === 'OK') {
            directionsRenderer.setDirections(result);
            
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(origin);
            bounds.extend(destination);
            map.fitBounds(bounds, {
                top: 150,
                right: 50,
                bottom: 50,
                left: 50
            });
            
            console.log('✅ Route displayed successfully');
        } else {
            console.error('❌ Directions API Error:', status);
            
            let errorMsg = '';
            if (status === 'REQUEST_DENIED') {
                errorMsg = '⚠️ Directions API not enabled.';
            } else if (status === 'ZERO_RESULTS') {
                errorMsg = 'No route found between these locations.';
            } else {
                errorMsg = 'Unable to calculate route: ' + status;
            }
            
            alert(errorMsg);
            
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(origin);
            bounds.extend(destination);
            map.fitBounds(bounds, {
                top: 150,
                right: 50,
                bottom: 50,
                left: 50
            });
        }
    });
}

function startLocationTracking(destLat, destLng, destName, destAddress, destinationType) {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
    
    let pulseCircle = null;
    let locationAttempts = 0;
    
    console.log('🔄 Starting continuous GPS tracking...');
    
    watchId = navigator.geolocation.watchPosition(
        (position) => {
            locationAttempts++;
            const newPos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            
            const accuracy = position.coords.accuracy;
            const speed = position.coords.speed;
            const heading = position.coords.heading;
            
            console.log(`🔄 Location Update #${locationAttempts}:`, {
                latitude: newPos.lat,
                longitude: newPos.lng,
                accuracy: accuracy.toFixed(0) + 'm'
            });
            
            if (deliveryMarker) {
                deliveryMarker.setPosition(newPos);
                
                if (heading !== null && heading !== undefined) {
                    deliveryMarker.setIcon({
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: '#4285F4',
                        fillOpacity: 1,
                        strokeColor: '#FFFFFF',
                        strokeWeight: 3,
                        rotation: heading
                    });
                }
            }
            
            if (pulseCircle) {
                pulseCircle.setCenter(newPos);
                pulseCircle.setRadius(Math.max(accuracy, 80));
            } else {
                pulseCircle = new google.maps.Circle({
                    strokeColor: '#4285F4',
                    strokeOpacity: 0.4,
                    strokeWeight: 2,
                    fillColor: '#4285F4',
                    fillOpacity: 0.15,
                    map: map,
                    center: newPos,
                    radius: Math.max(accuracy, 80),
                    zIndex: 999
                });
            }
            
            const destPos = { lat: parseFloat(destLat), lng: parseFloat(destLng) };
            
            const request = {
                origin: newPos,
                destination: destPos,
                travelMode: google.maps.TravelMode.DRIVING
            };
            
            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                }
            });
            
            updateMapInfo(newPos, destPos, destName, destAddress, destinationType);
            
            if (!map.getBounds().contains(new google.maps.LatLng(newPos.lat, newPos.lng))) {
                map.panTo(newPos);
            }
        },
        (error) => {
            console.error('❌ Tracking error:', error.code, error.message);
        },
        {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 0
        }
    );
}

function updateMapInfo(driverPos, destPos, destName, destAddress, destinationType) {
    const distance = google.maps.geometry.spherical.computeDistanceBetween(
        new google.maps.LatLng(driverPos.lat, driverPos.lng),
        new google.maps.LatLng(destPos.lat, destPos.lng)
    );
    
    const distanceKm = (distance / 1000).toFixed(2);
    const distanceM = distance.toFixed(0);
    const destinationTypeText = destinationType === 'store' ? 'Store' : 'Customer';
    
    document.getElementById('mapInfo').innerHTML = `
        <h4>${destName}</h4>
        <p>${destAddress}</p>
        <p><strong>Destination:</strong> ${destinationTypeText}</p>
        <p class="distance">Distance: ${distanceKm} km (${distanceM} m)</p>
        <p style="font-size: 12px; color: #10b981; margin-top: 8px;">📍 Your location is updating in real-time</p>
    `;
}

function closeMap() {
    document.getElementById('mapModal').classList.remove('active');
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    map = null;
    deliveryMarker = null;
    destinationMarker = null;
    directionsRenderer = null;
    directionsService = null;
    currentOrderId = null;
    currentDestinationType = null;
    currentDestination = null;
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'delivery_logout.php';
    }
}

function showPopup() {
    if (availableOrders.length > 0) {
        document.getElementById('orderPopup').classList.add('active');
        displayOrder(0);
        startAutoSwitch();
    }
}

// FIXED: closePopup function - don't clear availableOrders when manually closed
function closePopup() {
    document.getElementById('orderPopup').classList.remove('active');
    stopAutoSwitch();
    // Don't clear availableOrders array here - this allows popup to show again
}

function startAutoSwitch() {
    if (availableOrders.length > 1) {
        stopAutoSwitch();
        autoSwitchInterval = setInterval(() => {
            let nextIndex = (currentOrderIndex + 1) % availableOrders.length;
            displayOrder(nextIndex);
        }, 8000);
    }
}

function stopAutoSwitch() {
    if (autoSwitchInterval) {
        clearInterval(autoSwitchInterval);
        autoSwitchInterval = null;
    }
}

function displayOrder(index) {
    if (availableOrders.length === 0) return;
    
    currentOrderIndex = index;
    const order = availableOrders[index];
    
    document.getElementById('orderCounter').textContent = 
        `Order ${index + 1} of ${availableOrders.length}`;
    
    let itemsHTML = '';
    if (order.items && order.items.length > 0) {
        itemsHTML = order.items.map(item => `
            <div class="popup-item">
                <div class="item-details">
                    <div class="item-name">${item.item_name}</div>
                    <div class="item-quantity">Qty: ${item.quantity}</div>
                </div>
                <div class="item-price">LKR ${parseFloat(item.total_price).toFixed(2)}</div>
            </div>
        `).join('');
    }
    
    const deliveryEarnings = (order.delivery_charge * 0.7).toFixed(2);
    
    document.getElementById('popupBody').innerHTML = `
        <div class="popup-section">
            <div class="popup-section-title">Order Information</div>
            <div class="info-row">
                <span class="info-label">Order ID</span>
                <span class="info-value">#${order.id}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Distance</span>
                <span class="info-value">${parseFloat(order.delivery_distance).toFixed(2)} km</span>
            </div>
            <div class="info-row" style="background: #f0fdf4; border-radius: 8px; padding: 15px;">
                <span class="info-label">Your Earnings</span>
                <span class="info-value" style="color: #10b981; font-weight: 700;">LKR ${deliveryEarnings}</span>
            </div>
        </div>
        
        <div class="popup-section">
            <div class="popup-section-title">Store Details</div>
            <div class="info-row">
                <span class="info-label">Store Name</span>
                <span class="info-value">${order.store_name}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Address</span>
                <span class="info-value">${order.store_address}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Mobile</span>
                <span class="info-value">${order.store_mobile}</span>
            </div>
        </div>
        
        <div class="popup-section">
            <div class="popup-section-title">Customer Details</div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value">${order.customer_email}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Delivery Address</span>
                <span class="info-value">${order.delivery_address}</span>
            </div>
        </div>
        
        <div class="popup-section">
            <div class="popup-section-title">Order Items</div>
            <div class="popup-items">
                ${itemsHTML}
            </div>
        </div>
        
        <div class="popup-total">
            <div class="popup-total-label">Total Amount to Collect</div>
            <div class="popup-total-amount">LKR ${parseFloat(order.total_amount).toFixed(2)}</div>
            <div style="font-size: 12px; color: #059669; margin-top: 5px;">
                Includes delivery charge: LKR ${parseFloat(order.delivery_charge).toFixed(2)}
            </div>
        </div>
    `;
}

function acceptCurrentOrder() {
    const order = availableOrders[currentOrderIndex];
    const acceptBtn = document.getElementById('acceptBtn');
    
    // Check if already has active order
    fetch('check_active_order.php')
        .then(response => response.json())
        .then(data => {
            if (data.has_active_order) {
                alert('You already have an active order. Please complete it before accepting new orders.');
                return;
            }
            
            if (confirm(`Accept Order #${order.id}?\n\nYou will earn: LKR ${(order.delivery_charge * 0.7).toFixed(2)}`)) {
                acceptBtn.disabled = true;
                acceptBtn.innerHTML = '🔄 Accepting...';
                
                fetch('delivery_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=accept_order&order_id=${order.id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        acceptBtn.innerHTML = '✅ Accepted!';
                        acceptBtn.style.background = '#10b981';
                        
                        availableOrders.splice(currentOrderIndex, 1);
                        
                        if (availableOrders.length > 0) {
                            if (currentOrderIndex >= availableOrders.length) {
                                displayOrder(availableOrders.length - 1);
                            } else {
                                displayOrder(currentOrderIndex);
                            }
                        } else {
                            closePopup();
                        }
                        
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + data.message);
                        acceptBtn.disabled = false;
                        acceptBtn.innerHTML = 'Accept Order';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error accepting order');
                    acceptBtn.disabled = false;
                    acceptBtn.innerHTML = 'Accept Order';
                });
            }
        })
        .catch(error => {
            console.error('Error checking active order:', error);
            alert('Error checking order status');
        });
}

// UPDATED: Check for new orders only if no active orders
function checkForNewOrders() {
    // First check if delivery person has any active orders
    fetch('check_active_order.php')
        .then(response => response.json())
        .then(data => {
            if (data.has_active_order) {
                // If they have active orders, don't show new orders
                availableOrders = [];
                closePopup();
                console.log('Has active order, not showing new orders');
            } else {
                // If no active orders, check for available orders
                fetch('get_delivery_orders.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.ready_orders) {
                            const newOrders = data.ready_orders;
                            
                            if (newOrders.length > 0) {
                                const orderIds = newOrders.map(o => o.id).sort().join(',');
                                const currentIds = availableOrders.map(o => o.id).sort().join(',');
                                
                                if (orderIds !== currentIds) {
                                    availableOrders = newOrders;
                                    
                                    // Always show popup if there are new orders and no active orders
                                    if (!document.getElementById('orderPopup').classList.contains('active')) {
                                        showPopup();
                                    } else {
                                        stopAutoSwitch();
                                        displayOrder(0);
                                        startAutoSwitch();
                                    }
                                }
                            } else {
                                availableOrders = [];
                                closePopup();
                            }
                        }
                    })
                    .catch(error => console.error('Error checking orders:', error));
            }
        })
        .catch(error => {
            console.error('Error checking active order:', error);
            // If there's an error checking active orders, proceed with checking new orders
            fetch('get_delivery_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.ready_orders) {
                        const newOrders = data.ready_orders;
                        
                        if (newOrders.length > 0) {
                            const orderIds = newOrders.map(o => o.id).sort().join(',');
                            const currentIds = availableOrders.map(o => o.id).sort().join(',');
                            
                            if (orderIds !== currentIds) {
                                availableOrders = newOrders;
                                
                                if (!document.getElementById('orderPopup').classList.contains('active')) {
                                    showPopup();
                                } else {
                                    stopAutoSwitch();
                                    displayOrder(0);
                                    startAutoSwitch();
                                }
                            }
                        } else {
                            availableOrders = [];
                            closePopup();
                        }
                    }
                })
                .catch(error => console.error('Error checking orders:', error));
        });
}

// Check immediately on load
checkForNewOrders();

// Then check every 10 seconds (UPDATED from 5 to 10 seconds)
setInterval(checkForNewOrders, 10000);
    </script>
</body>
</html>

