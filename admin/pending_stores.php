<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Database configuration

require_once '../config/database.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get statistics - UPDATED FOR NEW SCHEMA
$stats_query = "SELECT 
    COUNT(*) as total_stores,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_stores,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_stores,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_stores
    FROM stores";
$stats_result = $conn->query($stats_query);

if (!$stats_result) {
    die("Statistics query failed: " . $conn->error);
}

$stats = $stats_result->fetch_assoc();

// Get filter
$filter = $_GET['filter'] ?? 'pending';
$where_clause = match($filter) {
    'pending' => "WHERE status = 'pending'",
    'approved' => "WHERE status = 'approved'",
    'rejected' => "WHERE status = 'rejected'",
    default => ""
};

// Get stores list - UPDATED: No JOIN needed, email is in stores table
$query = "SELECT * FROM stores $where_clause ORDER BY registration_date DESC";
$stores = $conn->query($query);

if (!$stores) {
    die("Stores query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 40px;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .navbar .admin-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }

        .stat-card.pending .number { color: #ffa502; }
        .stat-card.approved .number { color: #26de81; }
        .stat-card.rejected .number { color: #ff4757; }

        .filter-tabs {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
            text-decoration: none;
        }

        .tab:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .stores-container {
            display: grid;
            gap: 20px;
        }

        .store-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .store-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .store-header {
            display: flex;
            gap: 20px;
            align-items: start;
            margin-bottom: 20px;
        }

        .store-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #f0f0f0;
        }

        .store-info h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 5px;
        }

        .store-type {
            display: inline-block;
            padding: 4px 12px;
            background: #f0f0f0;
            color: #666;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .store-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-item strong {
            display: block;
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .detail-item span {
            color: #333;
            font-size: 14px;
        }

        .documents {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .doc-link:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .actions {
            display: flex;
            gap: 10px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-approve {
            background: #26de81;
            color: white;
        }

        .btn-approve:hover {
            background: #20c972;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #ff4757;
            color: white;
        }

        .btn-reject:hover {
            background: #ee384e;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .modal textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            min-height: 100px;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏪 ලක්way Delivery - Admin Panel</h1>
        <div class="admin-info">
            <span>Store Management</span>
            <a href="admin_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-<?= $_SESSION['admin_message_type'] ?>">
                <?= htmlspecialchars($_SESSION['admin_message']) ?>
            </div>
            <?php 
            unset($_SESSION['admin_message']);
            unset($_SESSION['admin_message_type']);
            ?>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Stores</h3>
                <div class="number"><?php echo $stats['total_stores']; ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending Approval</h3>
                <div class="number"><?php echo $stats['pending_stores']; ?></div>
            </div>
            <div class="stat-card approved">
                <h3>Approved Stores</h3>
                <div class="number"><?php echo $stats['approved_stores']; ?></div>
            </div>
            <div class="stat-card rejected">
                <h3>Rejected Stores</h3>
                <div class="number"><?php echo $stats['rejected_stores']; ?></div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <div class="tabs">
                <a href="?filter=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All Stores
                </a>
                <a href="?filter=pending" class="tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    Pending (<?php echo $stats['pending_stores']; ?>)
                </a>
                <a href="?filter=approved" class="tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                    Approved (<?php echo $stats['approved_stores']; ?>)
                </a>
                <a href="?filter=rejected" class="tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                    Rejected (<?php echo $stats['rejected_stores']; ?>)
                </a>
            </div>
        </div>

        <!-- Stores List -->
        <div class="stores-container">
            <?php if ($stores->num_rows > 0): ?>
                <?php while($store = $stores->fetch_assoc()): ?>
                    <div class="store-card">
                        <div class="store-header">
                            <?php 
                            // Extract just the filename from full path
                            $image_filename = basename($store['store_image_path']);
                            ?>
                            <img src="../uploads/stores/<?php echo $image_filename; ?>" 
                                 alt="<?php echo htmlspecialchars($store['store_name']); ?>" 
                                 class="store-image"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Crect fill=%22%23ddd%22 width=%22120%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%23999%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2224%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                            
                            <div class="store-info">
                                <h2>
                                    <?php echo htmlspecialchars($store['store_name']); ?>
                                    <?php
                                    $status_class = $store['status'];
                                    $status_text = match($store['status']) {
                                        'pending' => '⏳ Pending',
                                        'approved' => '✓ Approved',
                                        'rejected' => '✗ Rejected',
                                        default => 'Unknown'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </h2>
                                <span class="store-type"><?php echo ucfirst($store['store_type']); ?></span>
                                <p style="color: #666; font-size: 14px; margin-top: 5px;">
                                    Registered: <?php echo date('M d, Y', strtotime($store['registration_date'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="store-details">
                            <div class="detail-item">
                                <strong>📧 Email</strong>
                                <span><?php echo htmlspecialchars($store['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>📱 Mobile</strong>
                                <span><?php echo htmlspecialchars($store['country_code'] . ' ' . $store['mobile_primary']); ?></span>
                                <?php if($store['mobile_secondary']): ?>
                                    <br><span><?php echo htmlspecialchars($store['country_code'] . ' ' . $store['mobile_secondary']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="detail-item">
                                <strong>📄 BR Number</strong>
                                <span><?php echo htmlspecialchars($store['br_number']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>📍 Location</strong>
                                <span><?php echo htmlspecialchars($store['city']); ?><?php echo $store['postal_code'] ? ', ' . htmlspecialchars($store['postal_code']) : ''; ?></span>
                            </div>
                        </div>

                        <div class="detail-item" style="margin-bottom: 15px;">
                            <strong>🏠 Address</strong>
                            <span><?php echo htmlspecialchars($store['address']); ?></span>
                        </div>

                        <div class="documents">
                            <?php 
                            // Extract just the filename from full path
                            $br_filename = basename($store['br_image_path']);
                            ?>
                            <a href="../uploads/stores/<?php echo $br_filename; ?>" 
                               target="_blank" class="doc-link">
                                📄 View BR Certificate
                            </a>
                            <?php if($store['food_certificate_path']): ?>
                                <?php $food_cert_filename = basename($store['food_certificate_path']); ?>
                                <a href="../uploads/stores/<?php echo $food_cert_filename; ?>" 
                                   target="_blank" class="doc-link">
                                    🏥 View Food Certificate
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if($store['status'] == 'pending'): ?>
                            <div class="actions">
                                <button class="btn btn-approve" 
                                        onclick="approveStore(<?php echo $store['id']; ?>, '<?php echo htmlspecialchars($store['store_name']); ?>')">
                                    ✓ Approve Store
                                </button>
                                <button class="btn btn-reject" 
                                        onclick="openRejectModal(<?php echo $store['id']; ?>, '<?php echo htmlspecialchars($store['store_name']); ?>')">
                                    ✗ Reject
                                </button>
                            </div>
                        <?php elseif($store['status'] == 'rejected' && $store['admin_notes']): ?>
                            <div class="detail-item" style="background: #fff3cd; margin-top: 15px;">
                                <strong>❌ Rejection Reason</strong>
                                <span><?php echo htmlspecialchars($store['admin_notes']); ?></span>
                            </div>
                        <?php elseif($store['status'] == 'approved' && $store['approved_date']): ?>
                            <div class="detail-item" style="background: #d4edda; margin-top: 15px;">
                                <strong>✅ Approved</strong>
                                <span><?php echo date('M d, Y', strtotime($store['approved_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <h3>No stores found</h3>
                    <p>There are no <?php echo $filter !== 'all' ? $filter : ''; ?> stores at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h2>❌ Reject Store</h2>
            <p style="color: #666; margin-bottom: 15px;">Store: <strong id="rejectStoreName"></strong></p>
            <textarea id="rejectionReason" placeholder="Enter reason for rejection..."></textarea>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button class="btn btn-reject" onclick="submitRejection()">Reject Store</button>
            </div>
        </div>
    </div>

    <script>
        let currentStoreId = null;

        function approveStore(storeId, storeName) {
            if (confirm(`Are you sure you want to approve "${storeName}"?`)) {
                window.location.href = `process_store_approval.php?action=approve&id=${storeId}`;
            }
        }

        function openRejectModal(storeId, storeName) {
            currentStoreId = storeId;
            document.getElementById('rejectStoreName').textContent = storeName;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
            document.getElementById('rejectionReason').value = '';
            currentStoreId = null;
        }

        function submitRejection() {
            const reason = document.getElementById('rejectionReason').value.trim();
            
            if (!reason) {
                alert('Please enter a reason for rejection');
                return;
            }

            window.location.href = `process_store_approval.php?action=reject&id=${currentStoreId}&reason=${encodeURIComponent(reason)}`;
        }

        // Close modal on outside click
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>