<?php
session_start();

// === DATABASE CONFIG ===
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// === AUTH CHECK ===
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller' || !isset($_SESSION['store_id'])) {
    header('Location: store_login.php');
    exit();
}
$store_id = $_SESSION['store_id'];

// === FETCH STORE ===
$stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();
$stmt->close();

// === FETCH ITEMS ===
$stmt = $conn->prepare("SELECT * FROM items WHERE store_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === FETCH PENDING ORDERS ===
$stmt = $conn->prepare("
    SELECT o.*, u.email AS customer_email, u.mobile AS customer_mobile
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.store_id = ? AND o.status = 'pending'
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$pending_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === PENDING ORDER ITEMS ===
$pending_items = [];
if (!empty($pending_orders)) {
    $ids = array_column($pending_orders, 'id');
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $pending_items[$row['order_id']][] = $row;
    }
    $stmt->close();
}

// === ACCEPTED + READY + OUT_FOR_DELIVERY ORDERS ===
$stmt = $conn->prepare("
    SELECT o.*, u.email AS customer_email, u.mobile AS customer_mobile,
           dp.username AS delivery_person_name, dp.vehicle_type, dp.vehicle_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN delivery_persons dp ON o.delivery_person_id = dp.id
    WHERE o.store_id = ? AND o.status IN ('accepted', 'ready_for_delivery', 'out_for_delivery')
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$all_ready = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Split
$accepted_orders = array_filter($all_ready, fn($o) => $o['status'] === 'accepted');
$ready_orders    = array_filter($all_ready, fn($o) => in_array($o['status'], ['ready_for_delivery', 'out_for_delivery']));

// === ORDER ITEMS FOR ACCEPTED/READY ===
$ready_items = [];
if (!empty($all_ready)) {
    $ids = array_column($all_ready, 'id');
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ready_items[$row['order_id']][] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Dashboard - ලක්way Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 80px;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.05"><path d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/></g></g></svg>') repeat;
            pointer-events: none;
            z-index: 0;
        }
        
        /* TOP NAV */
        .top-nav { 
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }
        .logo-section { 
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .logo-section img.logo { 
            height: 48px;
            filter: drop-shadow(0 2px 8px rgba(99, 102, 241, 0.3));
        }
        .store-logo { 
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease;
        }
        .store-logo:hover {
            transform: scale(1.05);
        }
        .store-info {
            display: flex;
            flex-direction: column;
        }
        .store-name-nav { 
            font-weight: 800;
            color: var(--dark);
            font-size: 20px;
            line-height: 1.2;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .store-status {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 600;
        }
        .nav-right { 
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .wakelock-btn {
            padding: 10px 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        .wakelock-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .wakelock-btn.active {
            background: linear-gradient(135deg, var(--success), #059669);
        }
        .wakelock-icon {
            width: 16px;
            height: 16px;
        }
        .menu-toggle { 
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--dark);
            padding: 8px;
        }
        .logout-btn { 
            padding: 11px 22px;
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        .logout-btn:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .mobile-menu { 
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-xl);
            z-index: 999;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mobile-menu.active { 
            max-height: 600px;
        }
        .mobile-menu-content { 
            padding: 24px;
        }
        .main-container { 
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
            position: relative;
            z-index: 1;
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            box-shadow: var(--shadow-md);
        }
        .stat-label {
            font-size: 13px;
            color: var(--gray-500);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-top: 8px;
        }

        /* ORDERS SECTION */
        .orders-section { 
            background: white;
            padding: 28px;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            margin-bottom: 28px;
            border: 2px solid var(--gray-100);
            transition: all 0.3s ease;
        }
        .orders-section:hover {
            border-color: var(--primary-light);
        }
        .orders-header { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--gray-100);
        }
        .orders-title { 
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .notification-badge { 
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border-radius: 12px;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            padding: 0 10px;
            box-shadow: var(--shadow-md);
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        .orders-grid { 
            display: grid;
            gap: 20px;
        }
        .order-card { 
            border: 3px solid var(--gray-100);
            border-radius: 20px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, rgba(255,255,255,1) 0%, rgba(248,250,252,1) 100%);
            position: relative;
            overflow: hidden;
        }
        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .order-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-xl);
            transform: translateY(-3px);
        }
        .order-card:hover::before {
            opacity: 1;
        }
        .order-header { 
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 16px;
        }
        .order-info h4 { 
            color: var(--dark);
            margin-bottom: 12px;
            font-size: 20px;
            font-weight: 800;
        }
        .order-meta { 
            color: var(--gray-600);
            font-size: 14px;
            line-height: 1.8;
            font-weight: 500;
        }
        .order-actions { 
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-action { 
            padding: 10px 18px;
            border: none;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            white-space: nowrap;
        }
        .btn-accept { 
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }
        .btn-accept:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-decline { 
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }
        .btn-decline:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-out-of-stock { 
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
        }
        .btn-out-of-stock:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-mark-ready { 
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .btn-mark-ready:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .order-items { 
            border-top: 2px solid var(--gray-100);
            padding-top: 20px;
            margin-top: 16px;
        }
        .order-item { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .order-item:last-child { 
            border-bottom: none;
        }
        .item-details { 
            flex: 1;
        }
        .item-name { 
            font-weight: 700;
            color: var(--dark);
            font-size: 15px;
        }
        .item-quantity { 
            color: var(--gray-500);
            font-size: 13px;
            margin-top: 4px;
            font-weight: 600;
        }
        .item-price { 
            font-weight: 800;
            color: var(--primary);
            font-size: 16px;
        }
        .order-total { 
            text-align: right;
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            padding-top: 20px;
            border-top: 3px dashed var(--gray-200);
            margin-top: 20px;
        }
        .empty-orders { 
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-400);
        }
        .empty-orders h4 { 
            font-size: 20px;
            margin-bottom: 10px;
            font-weight: 700;
            color: var(--gray-500);
        }

        .status-accepted { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .status-ready_for_delivery { 
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .status-out_for_delivery { 
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }

        .real-time-indicator { 
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--success);
            font-size: 14px;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.1);
            padding: 8px 16px;
            border-radius: 12px;
        }
        .pulse-dot { 
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 8px var(--success);
        }
        @keyframes pulse { 
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }

        .notification-toast { 
            position: fixed;
            top: 100px;
            right: 24px;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            padding: 20px;
            border-left: 6px solid var(--primary);
            max-width: 400px;
            z-index: 10000;
            transform: translateX(500px);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .notification-toast.show { 
            transform: translateX(0);
        }
        .notification-title { 
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 16px;
        }
        .notification-message { 
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 500;
        }

        /* ADD ITEM */
        .add-item-highlight { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }
        .add-item-highlight::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .add-item-highlight h2 { 
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }
        .add-item-highlight p { 
            font-size: 16px;
            margin-bottom: 24px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }
        .btn-add-item { 
            padding: 14px 32px;
            background: white;
            color: var(--primary);
            border: none;
            border-radius: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            font-size: 15px;
            position: relative;
            z-index: 1;
        }
        .btn-add-item:hover { 
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        .add-item-form { 
            background: white;
            padding: 32px;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            margin-bottom: 28px;
            display: none;
            border: 2px solid var(--gray-100);
        }
        .add-item-form.active { 
            display: block;
        }
        .form-header { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--gray-100);
        }
        .form-header h3 { 
            font-size: 26px;
            color: var(--dark);
            font-weight: 800;
        }
        .form-grid { 
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .form-group label { 
            display: block;
            margin-bottom: 10px;
            color: var(--gray-700);
            font-weight: 700;
            font-size: 14px;
        }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: 500;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .image-upload-area { 
            border: 3px dashed var(--gray-300);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--gray-50);
        }
        .image-upload-area:hover { 
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }
        .image-preview-container { 
            margin-top: 20px;
            display: none;
            text-align: center;
        }
        .image-preview-container.active { 
            display: block;
        }
        .image-preview-container img { 
            max-width: 100%;
            max-height: 400px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
        }
        .form-actions { 
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }
        .btn { 
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            flex: 1;
        }
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-secondary { 
            background: var(--gray-200);
            color: var(--gray-700);
            flex: 1;
        }
        .btn-secondary:hover { 
            background: var(--gray-300);
        }

        /* ITEMS */
        .items-section { 
            background: white;
            padding: 32px;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gray-100);
        }
        .section-header { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--gray-100);
        }
        .section-header h3 { 
            font-size: 26px;
            color: var(--dark);
            font-weight: 800;
        }
        .items-count { 
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 8px 18px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 800;
            box-shadow: var(--shadow-md);
        }
        .items-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }
        .item-card { 
            background: white;
            border: 3px solid var(--gray-100);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow);
        }
        .item-card:hover { 
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }
        .item-image { 
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .item-card:hover .item-image {
            transform: scale(1.05);
        }
        .item-info { 
            padding: 20px;
        }
        .item-name { 
            font-size: 16px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .item-price { 
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .item-stock { 
            color: var(--gray-500);
            font-size: 13px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .item-actions { 
            display: flex;
            gap: 10px;
        }
        .btn-sm { 
            padding: 10px 16px;
            font-size: 13px;
            flex: 1;
            font-weight: 700;
            border-radius: 10px;
        }
        .btn-edit { 
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
        }
        .btn-edit:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .btn-delete { 
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }
        .btn-delete:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .empty-state { 
            text-align: center;
            padding: 80px 20px;
            color: var(--gray-400);
            grid-column: 1/-1;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            .top-nav {
                padding: 12px 16px;
            }
            .menu-toggle {
                display: block;
            }
            .logout-btn, .wakelock-btn {
                display: none;
            }
            .mobile-menu {
                display: block;
            }
            .mobile-menu .logout-btn, .mobile-menu .wakelock-btn {
                display: block;
                width: 100%;
                margin-bottom: 12px;
            }
            .store-name-nav {
                font-size: 16px;
            }
            .store-status {
                font-size: 11px;
            }
            .logo-section img.logo {
                height: 36px;
            }
            .store-logo {
                width: 40px;
                height: 40px;
            }
            .main-container {
                padding: 16px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .orders-section, .items-section, .add-item-form {
                padding: 20px;
            }
            .orders-title {
                font-size: 20px;
            }
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-actions {
                width: 100%;
                justify-content: space-between;
                margin-top: 12px;
            }
            .btn-action {
                flex: 1;
                text-align: center;
                font-size: 12px;
                padding: 9px 12px;
            }
            .notification-toast {
                left: 16px;
                right: 16px;
                max-width: none;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .items-grid {
                grid-template-columns: 1fr;
            }
            .item-image {
                height: 300px;
            }
            .add-item-highlight {
                padding: 28px 20px;
            }
            .add-item-highlight h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .stat-value {
                font-size: 28px;
            }
            .order-actions {
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- TOP NAV -->
<div class="top-nav">
    <div class="logo-section">
        <img src="assets/logo.png" alt="ලක්way" class="logo" onerror="this.style.display='none'">
        <img src="uploads/stores/<?php echo htmlspecialchars(basename($store['store_image_path'] ?? '')); ?>"
             alt="Store" class="store-logo"
             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2252%22 height=%2252%22%3E%3Crect fill=%22%236366f1%22 width=%2252%22 height=%2252%22 rx=%2214%22/%3E%3C/svg%3E'">
        <div class="store-info">
            <span class="store-name-nav"><?php echo htmlspecialchars($store['store_name']); ?></span>
            <span class="store-status">● Online</span>
        </div>
    </div>
    <div class="nav-right">
        <div class="real-time-indicator">
            <div class="pulse-dot"></div>
            <span>Live</span>
        </div>
        <button class="wakelock-btn" id="wakelockBtn" onclick="toggleWakeLock()">
            <span class="wakelock-icon">🔒</span>
            <span id="wakelockText">Keep Awake</span>
        </button>
        <button class="logout-btn" onclick="logout()">Logout</button>
        <button class="menu-toggle" onclick="toggleMobileMenu()">☰</button>
    </div>
</div>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-content">
        <button class="wakelock-btn" id="wakelockBtnMobile" onclick="toggleWakeLock()" style="width: 100%; justify-content: center;">
            <span class="wakelock-icon">🔒</span>
            <span id="wakelockTextMobile">Keep Awake</span>
        </button>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>
</div>

<!-- TOAST -->
<div class="notification-toast" id="notificationToast">
    <div class="notification-title" id="notificationTitle">Order Update</div>
    <div class="notification-message" id="notificationMessage"></div>
</div>

<div class="main-container">

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">🔔</div>
            </div>
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value" id="pendingCount"><?php echo count($pending_orders); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-label">Preparing</div>
            <div class="stat-value" id="acceptedCount"><?php echo count($accepted_orders); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚚</div>
            <div class="stat-label">Ready/Delivering</div>
            <div class="stat-value" id="readyCount"><?php echo count($ready_orders); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🛍️</div>
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?php echo count($items); ?></div>
        </div>
    </div>

    <!-- PENDING ORDERS -->
    <div class="orders-section">
        <div class="orders-header">
            <div class="orders-title">
                <span>🔔 New Orders</span>
                <?php if (!empty($pending_orders)): ?>
                    <span class="notification-badge" id="pendingBadge"><?php echo count($pending_orders); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="orders-grid" id="pendingGrid">
            <?php if (empty($pending_orders)): ?>
                <div class="empty-orders">
                    <h4>No new orders</h4>
                    <p>New orders will appear here in real-time</p>
                </div>
            <?php else: foreach ($pending_orders as $o): ?>
                <div class="order-card" id="pending-<?php echo $o['id']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <h4>Order #<?php echo $o['id']; ?></h4>
                            <div class="order-meta">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($o['customer_email']); ?><br>
                                <strong>Mobile:</strong> <?php echo htmlspecialchars($o['customer_mobile']); ?><br>
                                <strong>Distance:</strong> <?php echo number_format($o['delivery_distance'], 2); ?> km<br>
                                <strong>Address:</strong> <?php echo htmlspecialchars($o['delivery_address']); ?>
                            </div>
                        </div>
                        <div class="order-actions">
                            <button class="btn-action btn-accept" onclick="updateStatus(<?php echo $o['id']; ?>,'accepted')">✓ Accept</button>
                            <button class="btn-action btn-out-of-stock" onclick="updateStatus(<?php echo $o['id']; ?>,'out_of_stock')">⚠ Out of Stock</button>
                            <button class="btn-action btn-decline" onclick="updateStatus(<?php echo $o['id']; ?>,'declined')">✕ Decline</button>
                        </div>
                    </div>
                    <div class="order-items">
                        <?php foreach (($pending_items[$o['id']] ?? []) as $it): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($it['item_name']); ?></div>
                                    <div class="item-quantity">Qty: <?php echo $it['quantity']; ?></div>
                                </div>
                                <div class="item-price">LKR <?php echo number_format($it['total_price'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">Total: LKR <?php echo number_format($o['total_amount'], 2); ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ACCEPTED ORDERS -->
    <div class="orders-section">
        <div class="orders-header">
            <div class="orders-title">
                <span>📦 Preparing Orders</span>
                <?php if (!empty($accepted_orders)): ?>
                    <span class="notification-badge" id="acceptedBadge"><?php echo count($accepted_orders); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="orders-grid" id="acceptedGrid">
            <?php if (empty($accepted_orders)): ?>
                <div class="empty-orders">
                    <h4>No orders in preparation</h4>
                    <p>Accepted orders will appear here</p>
                </div>
            <?php else: foreach ($accepted_orders as $o): ?>
                <div class="order-card" id="accepted-<?php echo $o['id']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <h4>Order #<?php echo $o['id']; ?></h4>
                            <div class="order-meta">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($o['customer_email']); ?><br>
                                <strong>Mobile:</strong> <?php echo htmlspecialchars($o['customer_mobile']); ?><br>
                                <strong>Address:</strong> <?php echo htmlspecialchars($o['delivery_address']); ?>
                            </div>
                        </div>
                        <div class="order-actions">
                            <button class="btn-action btn-mark-ready" onclick="markReady(<?php echo $o['id']; ?>)">✓ Mark Ready</button>
                        </div>
                    </div>
                    <div class="order-items">
                        <?php foreach (($ready_items[$o['id']] ?? []) as $it): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($it['item_name']); ?></div>
                                    <div class="item-quantity">Qty: <?php echo $it['quantity']; ?></div>
                                </div>
                                <div class="item-price">LKR <?php echo number_format($it['total_price'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">Total: LKR <?php echo number_format($o['total_amount'], 2); ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- READY FOR DELIVERY -->
    <div class="orders-section">
        <div class="orders-header">
            <div class="orders-title">
                <span>🚚 Ready for Delivery</span>
                <?php if (!empty($ready_orders)): ?>
                    <span class="notification-badge" id="readyBadge"><?php echo count($ready_orders); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="orders-grid" id="readyGrid">
            <?php if (empty($ready_orders)): ?>
                <div class="empty-orders">
                    <h4>No orders ready</h4>
                    <p>Mark orders as ready when prepared</p>
                </div>
            <?php else: foreach ($ready_orders as $o): ?>
                <div class="order-card" id="ready-<?php echo $o['id']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <h4>Order #<?php echo $o['id']; ?></h4>
                            <div class="order-meta">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($o['customer_email']); ?><br>
                                <strong>Mobile:</strong> <?php echo htmlspecialchars($o['customer_mobile']); ?><br>
                                <strong>Address:</strong> <?php echo htmlspecialchars($o['delivery_address']); ?><br>
                                <strong>Status:</strong> <span class="status-<?php echo $o['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $o['status'])); ?>
                                </span>
                                <?php if ($o['delivery_person_name']): ?>
                                    <br><strong>Delivery:</strong> <?php echo htmlspecialchars($o['delivery_person_name']); ?>
                                    (<?php echo ucfirst($o['vehicle_type']); ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="order-items">
                        <?php foreach (($ready_items[$o['id']] ?? []) as $it): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($it['item_name']); ?></div>
                                    <div class="item-quantity">Qty: <?php echo $it['quantity']; ?></div>
                                </div>
                                <div class="item-price">LKR <?php echo number_format($it['total_price'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">Total to Collect: LKR <?php echo number_format($o['total_amount'], 2); ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ADD ITEM -->
    <div class="add-item-highlight" id="addItemHighlight">
        <h2>✨ Add Your Products</h2>
        <p>Start selling by adding your amazing products to the store</p>
        <button class="btn-add-item" onclick="showAddForm()">+ Add New Item</button>
    </div>

    <div class="add-item-form" id="addItemForm">
        <div class="form-header">
            <h3>Add New Product</h3>
            <button class="btn btn-secondary" onclick="hideAddForm()">Cancel</button>
        </div>
        <form id="itemForm" enctype="multipart/form-data">
            <input type="hidden" name="store_id" value="<?php echo $store_id; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="item_name" required placeholder="e.g., Fresh Mango">
                </div>
                <div class="form-group">
                    <label>Price (LKR) *</label>
                    <input type="number" name="item_price" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock_count" min="0" required placeholder="0">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="food">Food</option>
                        <option value="beverages">Beverages</option>
                        <option value="groceries">Groceries</option>
                        <option value="electronics">Electronics</option>
                        <option value="clothing">Clothing</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1/-1;">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe your product..."></textarea>
                </div>
                <div class="form-group" style="grid-column: 1/-1;">
                    <label>Product Image</label>
                    <div class="image-upload-area" onclick="document.getElementById('itemImg').click()">
                        📸 Click to upload image
                    </div>
                    <input type="file" id="itemImg" name="item_image" accept="image/*" style="display: none;" onchange="previewImg(this)">
                    <div class="image-preview-container" id="imgPrev">
                        <img id="prevImg" src="#" alt="Preview">
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Save Product</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- ITEMS LIST -->
    <div class="items-section">
        <div class="section-header">
            <h3>🛍️ Your Products</h3>
            <span class="items-count"><?php echo count($items); ?> Items</span>
        </div>
        <div class="items-grid">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <h4>No products yet</h4>
                    <p>Click "Add New Item" to get started</p>
                </div>
            <?php else: foreach ($items as $it): ?>
                <div class="item-card" data-item-id="<?php echo $it['id']; ?>">
                    <img src="uploads/items/<?php echo htmlspecialchars(basename($it['item_image'] ?? '')); ?>"
                         alt="<?php echo htmlspecialchars($it['item_name']); ?>" class="item-image"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22300%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%2394a3b8%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2220%22 font-weight=%22bold%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                    <div class="item-info">
                        <h4 class="item-name"><?php echo htmlspecialchars($it['item_name']); ?></h4>
                        <div class="item-price">LKR <?php echo number_format($it['item_price'], 2); ?></div>
                        <div class="item-stock">Stock: <?php echo $it['stock_count']; ?></div>
                        <div class="item-actions">
                            <button class="btn btn-edit btn-sm" onclick="editItem(<?php echo $it['id']; ?>)">✏️ Edit</button>
                            <button class="btn btn-delete btn-sm" onclick="deleteItem(<?php echo $it['id']; ?>)">🗑️ Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

</div>

<script>
// Wake Lock
let wakeLock = null;

async function toggleWakeLock() {
    try {
        if (wakeLock !== null) {
            await wakeLock.release();
            wakeLock = null;
            document.getElementById('wakelockBtn').classList.remove('active');
            document.getElementById('wakelockBtnMobile').classList.remove('active');
            document.getElementById('wakelockText').textContent = 'Keep Awake';
            document.getElementById('wakelockTextMobile').textContent = 'Keep Awake';
            showToast('Screen Lock', 'Screen can now sleep');
        } else {
            wakeLock = await navigator.wakeLock.request('screen');
            document.getElementById('wakelockBtn').classList.add('active');
            document.getElementById('wakelockBtnMobile').classList.add('active');
            document.getElementById('wakelockText').textContent = 'Screen Awake';
            document.getElementById('wakelockTextMobile').textContent = 'Screen Awake';
            showToast('Screen Lock Active', 'Screen will stay awake');
            
            wakeLock.addEventListener('release', () => {
                document.getElementById('wakelockBtn').classList.remove('active');
                document.getElementById('wakelockBtnMobile').classList.remove('active');
                document.getElementById('wakelockText').textContent = 'Keep Awake';
                document.getElementById('wakelockTextMobile').textContent = 'Keep Awake';
            });
        }
    } catch (err) {
        showToast('Error', 'Wake Lock not supported');
    }
}

// Re-acquire wake lock when page becomes visible
document.addEventListener('visibilitychange', async () => {
    if (wakeLock !== null && document.visibilityState === 'visible') {
        try {
            wakeLock = await navigator.wakeLock.request('screen');
        } catch (err) {
            console.error('Wake lock failed:', err);
        }
    }
});

function toggleMobileMenu() { 
    document.getElementById('mobileMenu').classList.toggle('active'); 
}

function showAddForm() { 
    document.getElementById('addItemHighlight').style.display = 'none'; 
    document.getElementById('addItemForm').classList.add('active'); 
}

function hideAddForm() { 
    document.getElementById('addItemHighlight').style.display = 'block'; 
    document.getElementById('addItemForm').classList.remove('active'); 
    document.getElementById('itemForm').reset(); 
    document.getElementById('imgPrev').classList.remove('active'); 
}

function previewImg(input) { 
    const preview = document.getElementById('prevImg');
    const container = document.getElementById('imgPrev'); 
    if (input.files && input.files[0]) { 
        const reader = new FileReader(); 
        reader.onload = e => {
            preview.src = e.target.result;
            container.classList.add('active');
        }; 
        reader.readAsDataURL(input.files[0]); 
    } 
}

function logout() { 
    if (confirm('Are you sure you want to logout?')) location.href = 'logout.php'; 
}

document.getElementById('itemForm').onsubmit = e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('process_item.php', {method: 'POST', body: formData})
        .then(r => r.json())
        .then(d => { 
            if (d.success) { 
                showToast('Success!', 'Product added successfully'); 
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error', d.message); 
            }
        });
};

function editItem(id) { 
    location.href = 'edit_item.php?id=' + id; 
}

function deleteItem(id) {
    if (!confirm('Delete this product?')) return;
    fetch('process_item.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&item_id=' + id
    })
    .then(r => r.json())
    .then(d => { 
        if (d.success) { 
            document.querySelector(`[data-item-id="${id}"]`).remove(); 
            showToast('Deleted', 'Product removed');
        } else {
            showToast('Error', d.message); 
        }
    });
}

function updateStatus(id, status) {
    if (!confirm(`Set order #${id} to ${status.replace('_', ' ')}?`)) return;
    fetch('update_order_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `order_id=${id}&status=${status}&store_id=<?php echo $store_id; ?>`
    })
    .then(r => r.json())
    .then(d => { 
        if (d.success) { 
            document.getElementById(`pending-${id}`)?.remove(); 
            showToast('Order Updated', `#${id} ${status === 'accepted' ? 'accepted' : 'updated'}`); 
            pollAll(); 
        } else {
            showToast('Error', d.message); 
        }
    });
}

function markReady(id) {
    if (!confirm(`Mark order #${id} as ready for delivery?`)) return;
    fetch('update_order_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `order_id=${id}&status=ready_for_delivery&store_id=<?php echo $store_id; ?>`
    })
    .then(r => r.json())
    .then(d => { 
        if (d.success) { 
            document.getElementById(`accepted-${id}`)?.remove(); 
            showToast('Order Ready!', `Order #${id} is ready for pickup`); 
            pollAll(); 
        } else {
            showToast('Error', d.message); 
        }
    });
}

function showToast(title, message) {
    const toast = document.getElementById('notificationToast');
    document.getElementById('notificationTitle').textContent = title;
    document.getElementById('notificationMessage').textContent = message;
    toast.classList.add('show');
    setTimeout(() => { toast.classList.remove('show'); }, 5000);
}

function pollAll() {
    ['pending', 'accepted', 'ready'].forEach(section => 
        fetch(`get_${section}_orders.php?store_id=<?php echo $store_id; ?>`)
            .then(r => r.json())
            .then(d => renderOrders(section, d))
            .catch(err => console.error('Poll error:', err))
    );
}

function renderOrders(section, data) {
    if (!data.success) return;
    
    const grid = document.getElementById(`${section}Grid`);
    const countEl = document.getElementById(`${section}Count`);
    const badge = document.getElementById(`${section}Badge`);
    
    // Update count in stats
    if (countEl) countEl.textContent = data.orders.length;
    
    if (data.orders.length === 0) {
        grid.innerHTML = `<div class="empty-orders">
            <h4>No ${section === 'pending' ? 'new' : section === 'accepted' ? 'preparing' : 'ready'} orders</h4>
            <p>${section === 'pending' ? 'New orders will appear here' : 'Orders will appear when ' + section}</p>
        </div>`;
        badge?.remove();
        return;
    }
    
    // Update badge
    const titleEl = grid.previousElementSibling.querySelector('.orders-title');
    if (!badge && titleEl) {
        titleEl.innerHTML += `<span class="notification-badge" id="${section}Badge">${data.orders.length}</span>`;
    } else if (badge) {
        badge.textContent = data.orders.length;
    }
    
    grid.innerHTML = data.orders.map(o => `
        <div class="order-card" id="${section}-${o.id}">
            <div class="order-header">
                <div class="order-info">
                    <h4>Order #${o.id}</h4>
                    <div class="order-meta">
                        <strong>Customer:</strong> ${o.customer_email}<br>
                        <strong>Mobile:</strong> ${o.customer_mobile}<br>
                        ${section === 'pending' ? `<strong>Distance:</strong> ${parseFloat(o.delivery_distance).toFixed(2)} km<br>` : ''}
                        <strong>Address:</strong> ${o.delivery_address}
                        ${o.status && section === 'ready' ? `<br><strong>Status:</strong> <span class="status-${o.status}">${o.status.replace(/_/g, ' ')}</span>` : ''}
                        ${o.delivery_person_name ? `<br><strong>Delivery:</strong> ${o.delivery_person_name} (${o.vehicle_type})` : ''}
                    </div>
                </div>
                <div class="order-actions">
                    ${section === 'pending' ? `
                        <button class="btn-action btn-accept" onclick="updateStatus(${o.id},'accepted')">✓ Accept</button>
                        <button class="btn-action btn-out-of-stock" onclick="updateStatus(${o.id},'out_of_stock')">⚠ Out of Stock</button>
                        <button class="btn-action btn-decline" onclick="updateStatus(${o.id},'declined')">✕ Decline</button>
                    ` : section === 'accepted' ? `
                        <button class="btn-action btn-mark-ready" onclick="markReady(${o.id})">✓ Mark Ready</button>
                    ` : ''}
                </div>
            </div>
            <div class="order-items">
                ${o.items ? o.items.map(i => `
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name">${i.item_name}</div>
                            <div class="item-quantity">Qty: ${i.quantity}</div>
                        </div>
                        <div class="item-price">LKR ${parseFloat(i.total_price).toFixed(2)}</div>
                    </div>
                `).join('') : ''}
            </div>
            <div class="order-total">${section === 'ready' ? 'Total to Collect:' : 'Total:'} LKR ${parseFloat(o.total_amount).toFixed(2)}</div>
        </div>
    `).join('');
}

// Initial load and polling
pollAll();
setInterval(pollAll, 10000);

// Sound notification for new orders
let lastPendingCount = <?php echo count($pending_orders); ?>;

function checkNewOrders() {
    fetch(`get_pending_orders.php?store_id=<?php echo $store_id; ?>`)
        .then(r => r.json())
        .then(d => {
            if (d.success && d.orders.length > lastPendingCount) {
                playNotificationSound();
                showToast('🔔 New Order!', `You have ${d.orders.length} pending orders`);
            }
            lastPendingCount = d.orders.length;
        })
        .catch(err => console.error('Check error:', err));
}

function playNotificationSound() {
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBQA=');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Audio play failed:', e));
    } catch (e) {
        console.log('Audio not supported');
    }
}

setInterval(checkNewOrders, 15000);
</script>
</body>
</html>