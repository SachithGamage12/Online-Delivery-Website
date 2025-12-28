<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$user_query = "SELECT email, mobile FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Fetch user's orders for tracking
$orders_query = "SELECT o.*, s.store_name, s.store_image_path 
                 FROM orders o 
                 JOIN stores s ON o.store_id = s.id 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC 
                 LIMIT 5";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
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

$stores_query = "SELECT * FROM stores WHERE status='approved' AND is_active=1 ORDER BY store_name ASC";
$stores = $conn->query($stores_query)->fetch_all(MYSQLI_ASSOC);

// Set timezone explicitly
date_default_timezone_set('Asia/Colombo');

function isStoreOpen($open, $close, $all24) {
    if ($all24) return true;
    
    // Get current time in seconds since midnight
    $currentTime = strtotime(date('H:i:s'));
    $openTime = strtotime($open);
    $closeTime = strtotime($close);
    
    // Debug output
    $debug = "DEBUG: Current: " . date('H:i:s') . "($currentTime) | Open: $open($openTime) | Close: $close($closeTime)";
    
    // If times couldn't be parsed, assume closed
    if ($openTime === false || $closeTime === false) {
        error_log("Error parsing times: Open='$open', Close='$close'");
        return false;
    }
    
    // Simple comparison
    if ($closeTime < $openTime) {
        // Overnight hours (e.g., 7:00 AM to 2:00 AM next day)
        $result = $currentTime >= $openTime || $currentTime <= $closeTime;
        $debug .= " | Overnight: " . ($result ? "OPEN" : "CLOSED");
    } else {
        // Normal hours (e.g., 7:00 AM to 10:00 PM)
        $result = $currentTime >= $openTime && $currentTime <= $closeTime;
        $debug .= " | Normal: " . ($result ? "OPEN" : "CLOSED");
    }
    
    error_log($debug);
    return $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ලක්way Delivery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* [All your original dashboard CSS remains exactly the same] */
* { margin:0; padding:0; box-sizing:border-box; }
:root{
    --primary:#2d7a4e; --primary-dark:#1e5438; --secondary:#3a9d5d;
    --accent:#fbbf24; --bg-light:#f8faf9; --text-dark:#1a202c;
    --text-gray:#64748b; --success:#10b981; --danger:#ef4444;
    --white:#fff; --shadow-sm:0 2px 8px rgba(0,0,0,.08);
    --shadow-md:0 4px 16px rgba(0,0,0,.12); --shadow-lg:0 8px 24px rgba(0,0,0,.16);
}
body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);color:var(--text-dark);min-height:100vh;}
.header{background:var(--white);box-shadow:var(--shadow-sm);position:sticky;top:0;z-index:1000;}
.header-content{max-width:1600px;margin:auto;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:2rem;}
.logo-section{display:flex;align-items:center;gap:1rem;}
.logo-section img{height:50px;}
.brand-name{font-size:1.75rem;font-weight:800;background:linear-gradient(135deg,var(--primary),var(--secondary));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;}

/* New Order Tracking Button */
.track-orders-btn {
    background: linear-gradient(135deg, var(--accent), #f59e0b);
    color: var(--white);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: var(--shadow-md);
    margin-right: 1rem;
}

.track-orders-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.cart-btn{background:linear-gradient(135deg,var(--primary),var(--secondary));color:var(--white);border:none;
    padding:.75rem 1.5rem;border-radius:50px;font-weight:600;cursor:pointer;display:flex;align-items:center;
    gap:.5rem;box-shadow:var(--shadow-md);}
.cart-btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.cart-badge{background:var(--white);color:var(--primary);border-radius:50%;width:24px;height:24px;
    display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;}

.orders-badge {
    background: var(--white);
    color: #f59e0b;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}

.main-container{max-width:1600px;margin:auto;padding:2.5rem 2rem;}
.page-title{font-size:2.5rem;font-weight:800;margin-bottom:.5rem;}
.page-subtitle{font-size:1.125rem;color:var(--text-gray);}
.stores-scroll-container{overflow-x:auto;scrollbar-width:none;cursor:grab;user-select:none;}
.stores-scroll-container::-webkit-scrollbar{display:none;}
.stores-scroll-container:active{cursor:grabbing;}
.stores-row{display:flex;gap:1.25rem;padding-bottom:1rem;min-width:min-content;}
.store-card{flex:0 0 calc((100% - 6.25rem)/6);min-width:200px;max-width:280px;background:var(--white);
    border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);transition:all .3s;cursor:pointer;position:relative;}
.store-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);}
.store-card.closed{opacity:.65;cursor:not-allowed;}
.store-img-wrapper{position:relative;height:140px;overflow:hidden;}
.store-img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.store-card:hover .store-img{transform:scale(1.05);}
.status-badge{position:absolute;top:.75rem;right:.75rem;padding:.375rem .75rem;border-radius:20px;
    font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:.375rem;backdrop-filter:blur(8px);
    box-shadow:var(--shadow-sm);}
.status-badge.open{background:rgba(16,185,129,.95);color:var(--white);}
.status-badge.closed{background:rgba(239,68,68,.95);color:var(--white);}
.status-dot{width:6px;height:6px;border-radius:50%;background:currentColor;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.9)}}
.store-details{padding:1rem;}
.store-title{font-size:1rem;font-weight:700;margin-bottom:.5rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.store-category{display:inline-block;background:linear-gradient(135deg,var(--primary),var(--secondary));
    color:var(--white);padding:.25rem .625rem;border-radius:12px;font-size:.7rem;font-weight:600;margin-bottom:.625rem;}
.store-location,.store-timing{font-size:.8rem;color:var(--text-gray);display:flex;align-items:center;gap:.375rem;}

/* Order Tracking Modal */
.order-tracking-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.order-tracking-modal.active {
    display: flex;
}

.order-tracking-content {
    background: var(--white);
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-lg);
    animation: slideUp .3s;
}

.order-tracking-header {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: var(--white);
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 20px 20px 0 0;
}

.order-tracking-header h2 {
    font-size: 1.75rem;
    font-weight: 700;
}

.order-tracking-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.order-card {
    background: var(--white);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid var(--primary);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.order-info h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.order-meta {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.order-status {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fef3c7; color: #d97706; }
.status-accepted { background: #d1fae5; color: #065f46; }
.status-preparing { background: #dbeafe; color: #1e40af; }
.status-out_for_delivery { background: #e0e7ff; color: #3730a3; }
.status-delivered { background: #dcfce7; color: #166534; }
.status-declined { background: #fee2e2; color: #991b1b; }
.status-out_of_stock { background: #fef3c7; color: #92400e; }

.order-items {
    margin-bottom: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
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
    color: var(--text-dark);
}

.item-quantity {
    color: var(--text-gray);
    font-size: 0.85rem;
}

.item-price {
    font-weight: 700;
    color: var(--primary);
}

.order-total {
    text-align: right;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    padding-top: 1rem;
    border-top: 2px dashed #e2e8f0;
}

.empty-orders {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-gray);
}

.empty-orders h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.real-time-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--success);
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1rem;
    justify-content: center;
}

.pulse-dot {
    width: 8px;
    height: 8px;
    background: var(--success);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

/* Notification Styles */
.notification-toast {
    position: fixed;
    top: 90px;
    right: 20px;
    background: var(--white);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    padding: 1rem;
    border-left: 4px solid var(--primary);
    max-width: 350px;
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
}

.notification-toast.show {
    transform: translateX(0);
}

.notification-title {
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.notification-message {
    color: var(--text-gray);
    font-size: 0.9rem;
}

.notification-time {
    font-size: 0.75rem;
    color: var(--text-gray);
    margin-top: 0.5rem;
}

/* Rest of your original modal, cart, and responsive styles remain exactly the same */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);
    z-index:2000;align-items:center;justify-content:center;padding:1rem;}
.modal-overlay.active{display:flex;}
.modal-box{background:var(--white);border-radius:20px;max-width:1000px;width:100%;max-height:90vh;
    display:flex;flex-direction:column;box-shadow:var(--shadow-lg);animation:slideUp .3s;}
@keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.modal-header{background:linear-gradient(135deg,var(--primary),var(--secondary));color:var(--white);
    padding:1.5rem 2rem;display:flex;justify-content:space-between;align-items:center;border-radius:20px 20px 0 0;}
.modal-header h2{font-size:1.75rem;font-weight:700;}
.close-btn{background:rgba(255,255,255,.2);border:none;color:var(--white);font-size:1.75rem;
    cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;
    justify-content:center;transition:all .3s;}
.close-btn:hover{background:rgba(255,255,255,.3);transform:rotate(90deg);}
.modal-content{padding:1.5rem;overflow-y:auto;flex:1;}
.items-scroll-container{overflow-x:auto;scrollbar-width:none;cursor:grab;user-select:none;padding-bottom:1rem;}
.items-scroll-container::-webkit-scrollbar{display:none;}
.items-scroll-container:active{cursor:grabbing;}
.items-row{display:flex;gap:1rem;min-width:min-content;padding:0 .5rem;}
.item-raw{
    display:flex;align-items:center;background:var(--white);border-radius:12px;
    padding:0.5rem 0.75rem;min-width:260px;box-shadow:var(--shadow-sm);transition:all .2s;
    gap:0.75rem;
}
.item-raw:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
.item-img{width:60px;height:60px;border-radius:10px;object-fit:cover;flex-shrink:0;}
.item-price{flex:1;text-align:center;font-weight:700;font-size:1.3rem;
    background:linear-gradient(135deg,var(--primary),var(--secondary));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.qty-controls{display:flex;align-items:center;gap:.5rem;}
.qty-btn{background:var(--primary);color:#fff;border:none;width:34px;height:34px;
    border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;
    justify-content:center;transition:.2s;}
.qty-btn:hover{background:var(--primary-dark);transform:scale(1.1);}
.qty-value{min-width:36px;text-align:center;font-weight:600;font-size:1.1rem;}
.cart-panel{position:fixed;top:0;right:-100%;width:100%;max-width:420px;height:100vh;
    background:var(--white);box-shadow:-4px 0 24px rgba(0,0,0,.2);transition:right .3s;z-index:2500;
    display:flex;flex-direction:column;}
.cart-panel.active{right:0;}
.cart-header{background:linear-gradient(135deg,var(--primary),var(--secondary));color:var(--white);
    padding:1.5rem;display:flex;justify-content:space-between;align-items:center;}
.cart-header h3{font-size:1.5rem;font-weight:700;}
.cart-items{flex:1;overflow-y:auto;padding:1.25rem;}
.cart-item{background:#f8faf9;border-radius:12px;padding:1rem;margin-bottom:1rem;
    display:flex;gap:1rem;}
.cart-item-img{width:70px;height:70px;border-radius:10px;object-fit:cover;}
.cart-item-info{flex:1;}
.cart-item-name{font-weight:600;margin-bottom:.375rem;font-size:.95rem;}
.cart-item-price{color:var(--primary);font-weight:700;font-size:1rem;margin-bottom:.5rem;}
.cart-item-controls{display:flex;gap:.625rem;align-items:center;}
.qty-control{background:var(--primary);color:#fff;border:none;width:28px;height:28px;
    border-radius:6px;cursor:pointer;font-weight:700;font-size:1rem;display:flex;
    align-items:center;justify-content:center;transition:.2s;}
.qty-control:hover{background:var(--primary-dark);}
.remove-item-btn{background:var(--danger);color:#fff;border:none;padding:.375rem .75rem;
    border-radius:6px;cursor:pointer;font-size:.75rem;margin-left:auto;transition:.2s;}
.remove-item-btn:hover{background:#dc2626;}
.cart-summary{border-top:2px solid #f1f5f9;padding:1.25rem;}
.summary-row{display:flex;justify-content:space-between;margin-bottom:.75rem;font-size:.95rem;}
.summary-row.total{font-size:1.5rem;font-weight:700;color:var(--primary);padding-top:1rem;
    border-top:2px dashed #e2e8f0;margin-top:1rem;}
.checkout-button{width:100%;background:linear-gradient(135deg,var(--primary),var(--secondary));
    color:#fff;border:none;padding:1rem;border-radius:12px;font-weight:700;font-size:1rem;
    cursor:pointer;margin-top:1rem;transition:.3s;}
.checkout-button:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.get-more-btn{width:100%;background:var(--white);color:var(--primary);border:2px solid var(--primary);
    padding:.75rem;border-radius:12px;font-weight:600;font-size:.95rem;cursor:pointer;
    margin-top:.75rem;transition:.3s;}
.get-more-btn:hover{background:var(--primary);color:#fff;}
.empty-message{text-align:center;padding:3rem 1.25rem;color:var(--text-gray);}
.empty-message h3{font-size:1.25rem;margin-bottom:.5rem;color:var(--text-dark);}
.login-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;
    align-items:center;justify-content:center;padding:1rem;}
.login-modal.active{display:flex;}
.login-modal .modal-content{background:var(--white);border-radius:20px;padding:2rem;max-width:420px;width:100%;
    box-shadow:var(--shadow-lg);position:relative;}
.login-modal .close-modal{position:absolute;top:1rem;right:1rem;font-size:1.5rem;cursor:pointer;}

@media (max-width:1400px){.store-card{flex:0 0 calc((100% - 5rem)/5);}}
@media (max-width:1200px){.store-card{flex:0 0 calc((100% - 3.75rem)/4);}}
@media (max-width:992px){.store-card{flex:0 0 calc((100% - 2.5rem)/3);}}
@media (max-width:768px){
    .header-content{padding:1rem 1.25rem;}
    .main-container{padding:2rem 1.25rem;}
    .store-card{flex:0 0 calc((100% - 1.25rem)/2);min-width:160px;}
    .item-raw{min-width:240px;}
    .modal-box{max-height:100vh;border-radius:0;}
    .modal-header{border-radius:0;}
    .cart-panel{max-width:100%;}
    .track-orders-btn { margin-right: 0.5rem; padding: 0.5rem 1rem; font-size: 0.9rem; }
}
@media (max-width:480px){
    .store-card{flex:0 0 calc(100% - 1rem);min-width:180px;}
    .item-raw{min-width:220px;padding:.5rem;gap:.75rem;}
    .item-img{width:50px;height:50px;}
    .qty-btn{width:30px;height:30px;font-size:1rem;}
    .track-orders-btn span { display: none; }
    .track-orders-btn { padding: 0.5rem; border-radius: 50%; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="logo-section">
            <img src="assets/logo.png" alt="logo" onerror="this.style.display='none'">
            <span class="brand-name">ලක්way Delivery</span>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button class="track-orders-btn" onclick="openOrderTracking()">
                📦 Track Orders
                <?php if (!empty($orders)): ?>
                    <span class="orders-badge"><?php echo count($orders); ?></span>
                <?php endif; ?>
            </button>
            <button class="cart-btn" onclick="toggleCart()">
                Cart <span class="cart-badge" id="cartBadge">0</span>
            </button>
        </div>
    </div>
</div>

<!-- Main Content - Your Original Dashboard -->
<div class="main-container">
    <div class="page-header">
        <h1 class="page-title">Discover Amazing Stores</h1>
        <p class="page-subtitle">Explore local favorites and get delivery to your doorstep</p>
    </div>

    <!-- Debug Information -->

    <div class="stores-section">
        <div class="stores-scroll-container" id="storesScroll">
            <div class="stores-row">
                <?php if(empty($stores)): ?>
                    <div class="empty-message" style="width:100%;text-align:center;">
                        <h3>No Stores Available</h3><p>Check back soon!</p>
                    </div>
                <?php else: 
                    // Debug: Show store data
                    echo "<!-- STORE DATA DEBUG -->";
                    foreach($stores as $s) {
                        echo "<!-- Store: {$s['store_name']} | Open: {$s['opening_time']} | Close: {$s['closing_time']} | 24/7: {$s['open_24_7']} -->";
                    }
                    
                    foreach($stores as $s):
                    $open = isStoreOpen($s['opening_time'],$s['closing_time'],$s['open_24_7']);
                    $img = basename($s['store_image_path']??'');
                    
                    // Debug output for each store
                    $debugInfo = "Store: {$s['store_name']} | Open: {$s['opening_time']} | Close: {$s['closing_time']} | IsOpen: " . ($open ? 'Yes' : 'No');
                    error_log($debugInfo);
                ?>
                    <div class="store-card <?= !$open?'closed':'' ?>" onclick="<?= $open?"openStoreModal({$s['id']},'".htmlspecialchars($s['store_name'])."')":'' ?>">
                        <div class="store-img-wrapper">
                            <img src="uploads/stores/<?= $img ?>" alt="<?= htmlspecialchars($s['store_name']) ?>" class="store-img"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22280%22 height=%22140%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22280%22 height=%22140%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%2394a3b8%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2216%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                            <div class="status-badge <?= $open?'open':'closed' ?>">
                                <span class="status-dot"></span> <?= $open?'Open':'Closed' ?>
                            </div>
                        </div>
                        <div class="store-details">
                            <h3 class="store-title"><?= htmlspecialchars($s['store_name']) ?></h3>
                            <span class="store-category"><?= ucfirst($s['store_type']) ?></span>
                            <div class="store-location"><?= htmlspecialchars($s['address'].', '.$s['city']) ?></div>
                            <div class="store-timing"><?= $s['open_24_7']?'24/7':date('g:i A',strtotime($s['opening_time'])).' - '.date('g:i A',strtotime($s['closing_time'])) ?></div>
                            <!-- Debug info displayed on card -->
                            <div style="font-size: 0.7rem; color: #666; margin-top: 0.5rem; border-top: 1px dashed #ddd; padding-top: 0.5rem;">
                                DB Times: <?= $s['opening_time'] ?> - <?= $s['closing_time'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Order Tracking Modal -->
<div class="order-tracking-modal" id="orderTrackingModal">
    <div class="order-tracking-content">
        <div class="order-tracking-header">
            <h2>Track Your Orders</h2>
            <button class="close-btn" onclick="closeOrderTracking()">×</button>
        </div>
        <div class="order-tracking-body">
            <div class="real-time-indicator">
                <div class="pulse-dot"></div>
                <span>Live Updates Active</span>
            </div>
            <div id="ordersContainer">
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <h3>No Orders Yet</h3>
                        <p>Your orders will appear here once you place them</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" id="order-<?php echo $order['id']; ?>">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <div class="order-meta">
                                        <strong>Store:</strong> <?php echo htmlspecialchars($order['store_name']); ?><br>
                                        <strong>Placed:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?><br>
                                        <strong>Delivery to:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?>
                                    </div>
                                </div>
                                <div class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($order['status'])); ?>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <?php if (isset($order_items[$order['id']])): ?>
                                    <?php foreach ($order_items[$order['id']] as $item): ?>
                                        <div class="order-item">
                                            <div class="item-details">
                                                <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                            </div>
                                            <div class="item-price">LKR <?php echo number_format($item['total_price'], 2); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-total">
                                Total: LKR <?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Your original Store Modal, Cart Panel, and Login Modal remain exactly the same -->
<!-- Store Modal -->
<div class="modal-overlay" id="storeModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <button class="close-btn" onclick="closeStoreModal()">×</button>
        </div>
        <div class="modal-content">
            <div class="items-scroll-container" id="itemsScroll">
                <div class="items-row" id="itemsContainer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Cart Panel -->
<div class="cart-panel" id="cartPanel">
    <div class="cart-header">
        <h3>Your Cart</h3>
        <button class="close-btn" onclick="toggleCart()">×</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-summary">
        <div class="summary-row total">
            <span>Total:</span>
            <span id="totalAmount">LKR 0.00</span>
        </div>
        <button class="checkout-button" onclick="proceedCheckout()">Proceed to Checkout</button>
        <button class="get-more-btn" onclick="toggleCart();closeStoreModal()">Get More Items</button>
    </div>
</div>

<!-- Login Modal -->
<div class="login-modal" id="loginModal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeLoginModal()">×</button>
        <div class="logo" style="text-align:center;margin-bottom:1rem;">
            <h1 style="font-size:1.8rem;">Login Required</h1>
            <p style="color:#666;font-size:0.9rem;">Please log in to proceed to checkout</p>
        </div>
        <form method="POST" action="process.php">
            <input type="hidden" name="action" value="login">
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.9rem;">Email</label>
                <input type="email" name="email" required placeholder="Enter your email" style="padding:0.75rem;font-size:0.9rem;">
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.9rem;">Password</label>
                <input type="password" name="password" required placeholder="Enter your password" style="padding:0.75rem;font-size:0.9rem;">
            </div>
            <button type="submit" class="btn" style="padding:0.75rem;font-size:0.95rem;">Login</button>
        </form>
        <p style="text-align:center;margin-top:1rem;font-size:0.85rem;">
            Don't have an account? <a href="index.php" style="color:var(--primary);text-decoration:underline;">Register</a>
        </p>
    </div>
</div>

<!-- Notification Toast -->
<div class="notification-toast" id="notificationToast">
    <div class="notification-title" id="notificationTitle">Order Update</div>
    <div class="notification-message" id="notificationMessage">Your order status has been updated</div>
    <div class="notification-time" id="notificationTime"></div>
</div>

<script>
// Your original JavaScript functions remain exactly the same
let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
let currentStore = null;
let orderCheckInterval = null;

function saveCart() {
    sessionStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
}

document.addEventListener('DOMContentLoaded', () => {
    initDragScroll(document.getElementById('storesScroll'));
    updateCartDisplay();
    // Start checking for order updates
    checkOrderUpdates();
    orderCheckInterval = setInterval(checkOrderUpdates, 5000);
});

function initDragScroll(el){
    let down=false,startX,scrollLeft;
    el.addEventListener('mousedown',e=>{down=true;startX=e.pageX-el.offsetLeft;scrollLeft=el.scrollLeft;});
    el.addEventListener('mouseleave',()=>down=false);
    el.addEventListener('mouseup',()=>down=false);
    el.addEventListener('mousemove',e=>{if(!down)return;e.preventDefault();const x=e.pageX-el.offsetLeft;const walk=(x-startX)*2;el.scrollLeft=scrollLeft-walk;});
}

function openStoreModal(id,name){
    currentStore=id;
    document.getElementById('modalTitle').textContent=name;
    document.getElementById('storeModal').classList.add('active');
    loadItems(id);
}

function closeStoreModal(){
    document.getElementById('storeModal').classList.remove('active');
}

function loadItems(storeId){
    fetch(`get_store_items.php?store_id=${storeId}`)
        .then(r=>r.json())
        .then(d=>{
            const c=document.getElementById('itemsContainer');
            if(d.items && d.items.length){
                c.innerHTML=d.items.map(i=>{
                    const price=(parseFloat(i.item_price)*1.10).toFixed(2);
                    const img=i.item_image.split('/').pop();
                    const existing = cart.find(x=>x.id===i.id);
                    const qty = existing ? existing.quantity : 0;
                    return `
                        <div class="item-raw">
                            <img src="uploads/items/${img}" alt="${i.item_name}" class="item-img"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect fill=%22%23e2e8f0%22 width=%2260%22 height=%2260%22/%3E%3C/svg%3E'">
                            <div class="item-price">LKR ${price}</div>
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="changeQty(${i.id},-1,${price},'${i.item_name.replace(/'/g,"\\'")}','${img}')">-</button>
                                <span class="qty-value" id="qty-${i.id}">${qty}</span>
                                <button class="qty-btn" onclick="changeQty(${i.id},1,${price},'${i.item_name.replace(/'/g,"\\'")}','${img}')">+</button>
                            </div>
                        </div>`;
                }).join('');
                setTimeout(()=>initDragScroll(document.getElementById('itemsScroll')),100);
            }else c.innerHTML='<div class="empty-message">No items</div>';
        });
}

function changeQty(id,delta,price,name,img){
    const span=document.getElementById(`qty-${id}`);
    let qty=(parseInt(span.textContent)||0)+delta;
    qty=Math.max(0,qty); span.textContent=qty;
    const existing=cart.find(x=>x.id===id);
    if(qty>0){
        if(existing) existing.quantity=qty;
        else cart.push({id,name,price:parseFloat(price),image:img,quantity:qty});
    }else cart=cart.filter(x=>x.id!==id);
    saveCart();
}

function updateCartDisplay(){
    const badge=document.getElementById('cartBadge');
    const totalItems=cart.reduce((s,i)=>s+i.quantity,0);
    badge.textContent=totalItems;

    const itemsDiv=document.getElementById('cartItems');
    if(cart.length===0){
        itemsDiv.innerHTML='<div class="empty-message"><h3>Cart is empty</h3><p>Add items to start</p></div>';
        document.getElementById('totalAmount').textContent='LKR 0.00';
        return;
    }
    itemsDiv.innerHTML=cart.map(i=>`
        <div class="cart-item">
            <img src="uploads/items/${i.image}" alt="${i.name}" class="cart-item-img">
            <div class="cart-item-info">
                <div class="cart-item-name">${i.name}</div>
                <div class="cart-item-price">LKR ${(i.price*i.quantity).toFixed(2)}</div>
                <div class="cart-item-controls">
                    <button class="qty-control" onclick="changeQty(${i.id},-1,${i.price},'${i.name.replace(/'/g,"\\'")}','${i.image}')">-</button>
                    <span class="qty-value">${i.quantity}</span>
                    <button class="qty-control" onclick="changeQty(${i.id},1,${i.price},'${i.name.replace(/'/g,"\\'")}','${i.image}')">+</button>
                    <button class="remove-item-btn" onclick="changeQty(${i.id},-${i.quantity},0,'','')">Remove</button>
                </div>
            </div>
        </div>`).join('');
    const total=cart.reduce((s,i)=>s+(i.price*i.quantity),0);
    document.getElementById('totalAmount').textContent=`LKR ${total.toFixed(2)}`;
}

function toggleCart(){
    const panel = document.getElementById('cartPanel');
    panel.classList.toggle('active');
    if (panel.classList.contains('active')) updateCartDisplay();
}

function proceedCheckout(){
    fetch('check_login.php')
        .then(r => r.json())
        .then(data => {
            if(data.logged_in){
                fetch('save_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(cart)
                }).then(() => {
                    window.location.href = 'checkout.php';
                });
            } else {
                document.getElementById('loginModal').classList.add('active');
            }
        });
}

function closeLoginModal(){
    document.getElementById('loginModal').classList.remove('active');
}

// New Order Tracking Functions
function openOrderTracking() {
    document.getElementById('orderTrackingModal').classList.add('active');
    checkOrderUpdates(); // Refresh orders when opening
}

function closeOrderTracking() {
    document.getElementById('orderTrackingModal').classList.remove('active');
}

function checkOrderUpdates() {
    fetch('get_order_updates.php?user_id=<?php echo $user_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOrdersDisplay(data.orders);
                updateOrdersBadge(data.orders.length);
                
                // Check for status changes and show notifications
                data.orders.forEach(order => {
                    const oldStatus = document.querySelector(`#order-${order.id} .order-status`)?.textContent.trim();
                    const newStatus = order.status.replace('_', ' ');
                    
                    if (oldStatus && oldStatus !== newStatus) {
                        showNotification('Order Updated', `Order #${order.id} is now ${newStatus}`, order.status);
                    }
                });
            }
        })
        .catch(error => console.error('Error checking orders:', error));
}

function updateOrdersDisplay(orders) {
    const container = document.getElementById('ordersContainer');
    
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="empty-orders">
                <h3>No Orders Yet</h3>
                <p>Your orders will appear here once you place them</p>
            </div>
        `;
        return;
    }

    container.innerHTML = orders.map(order => `
        <div class="order-card" id="order-${order.id}">
            <div class="order-header">
                <div class="order-info">
                    <h3>Order #${order.id}</h3>
                    <div class="order-meta">
                        <strong>Store:</strong> ${order.store_name}<br>
                        <strong>Placed:</strong> ${new Date(order.created_at).toLocaleString()}<br>
                        <strong>Delivery to:</strong> ${order.delivery_address}
                    </div>
                </div>
                <div class="order-status status-${order.status}">
                    ${order.status.replace('_', ' ')}
                </div>
            </div>
            
            <div class="order-items">
                ${order.items ? order.items.map(item => `
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name">${item.item_name}</div>
                            <div class="item-quantity">Quantity: ${item.quantity}</div>
                        </div>
                        <div class="item-price">LKR ${parseFloat(item.total_price).toFixed(2)}</div>
                    </div>
                `).join('') : ''}
            </div>
            
            <div class="order-total">
                Total: LKR ${parseFloat(order.total_amount).toFixed(2)}
            </div>
        </div>
    `).join('');
}

function updateOrdersBadge(count) {
    const badge = document.querySelector('.orders-badge');
    const trackBtn = document.querySelector('.track-orders-btn');
    
    if (count > 0) {
        if (badge) {
            badge.textContent = count;
        } else {
            trackBtn.innerHTML = `📦 Track Orders <span class="orders-badge">${count}</span>`;
        }
    } else if (badge) {
        badge.remove();
        trackBtn.innerHTML = '📦 Track Orders';
    }
}

function showNotification(title, message, status) {
    const toast = document.getElementById('notificationToast');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    const timeEl = document.getElementById('notificationTime');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    timeEl.textContent = new Date().toLocaleTimeString();
    
    // Update toast color based on status
    const statusColors = {
        'accepted': '#10b981',
        'preparing': '#3b82f6',
        'out_for_delivery': '#8b5cf6',
        'delivered': '#059669',
        'declined': '#ef4444',
        'out_of_stock': '#f59e0b'
    };
    
    toast.style.borderLeftColor = statusColors[status] || '#2d7a4e';
    
    toast.classList.add('show');
    
    // Show browser notification if permitted
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: message,
            icon: '/assets/logo.png',
            tag: 'order-update'
        });
    }
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 5000);
}

// Request notification permission
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Initialize notification permission
requestNotificationPermission();

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (orderCheckInterval) {
        clearInterval(orderCheckInterval);
    }
});

// Close modals when clicking outside
document.getElementById('orderTrackingModal').addEventListener('click', e => {
    if (e.target === document.getElementById('orderTrackingModal')) {
        closeOrderTracking();
    }
});

document.getElementById('storeModal').addEventListener('click', e => {
    if (e.target === document.getElementById('storeModal')) {
        closeStoreModal();
    }
});

document.getElementById('loginModal').addEventListener('click', e => {
    if (e.target === document.getElementById('loginModal')) {
        closeLoginModal();
    }
});

document.addEventListener('click', e => {
    const panel = document.getElementById('cartPanel');
    const btn = document.querySelector('.cart-btn');
    if (panel.classList.contains('active') && !panel.contains(e.target) && !btn.contains(e.target)) {
        toggleCart();
    }
});
</script>
</body>
</html>