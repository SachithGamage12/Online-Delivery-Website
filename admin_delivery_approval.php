<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if admin is logged in


// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_id = $_POST['delivery_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($delivery_id && in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $stmt = $conn->prepare("UPDATE delivery_persons SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $delivery_id);
        
        if ($stmt->execute()) {
            $message = "Delivery person {$action}d successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating delivery person: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Fetch pending delivery persons
$pending_query = "SELECT * FROM delivery_persons WHERE status = 'pending' ORDER BY created_at DESC";
$pending_result = $conn->query($pending_query);
$pending_deliveries = $pending_result->fetch_all(MYSQLI_ASSOC);

// Fetch approved delivery persons
$approved_query = "SELECT * FROM delivery_persons WHERE status = 'approved' ORDER BY created_at DESC";
$approved_result = $conn->query($approved_query);
$approved_deliveries = $approved_result->fetch_all(MYSQLI_ASSOC);

// Fetch rejected delivery persons
$rejected_query = "SELECT * FROM delivery_persons WHERE status = 'rejected' ORDER BY created_at DESC";
$rejected_result = $conn->query($rejected_query);
$rejected_deliveries = $rejected_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Person Approval - ලක්way Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        /* Header */
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

        .nav-right {
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

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #2d7a4e, #3a9d5d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            font-size: 1.125rem;
            color: #64748b;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab.active {
            color: #2d7a4e;
            border-bottom-color: #2d7a4e;
        }

        .tab:hover {
            color: #2d7a4e;
        }

        .tab-badge {
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 8px;
        }

        /* Delivery Grid */
        .delivery-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        }

        .delivery-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #f1f5f9;
            transition: all 0.3s;
        }

        .delivery-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .delivery-info h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .delivery-meta {
            color: #64748b;
            font-size: 14px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Documents Section */
        .documents-section {
            margin-bottom: 20px;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .document-item {
            text-align: center;
        }

        .document-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .document-image:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .document-label {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }

        /* Actions */
        .delivery-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
        }

        .btn-approve {
            background: #10b981;
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #ef4444;
            color: white;
        }

        .btn-reject:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            grid-column: 1 / -1;
        }

        .empty-state h4 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #1e293b;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #2d7a4e, #3a9d5d);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
        }

        .close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .close-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 20px;
            text-align: center;
        }

        .modal-image {
            max-width: 100%;
            max-height: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }

            .delivery-grid {
                grid-template-columns: 1fr;
            }

            .documents-grid {
                grid-template-columns: 1fr;
            }

            .delivery-header {
                flex-direction: column;
                gap: 10px;
            }

            .delivery-actions {
                flex-direction: column;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <img src="assets/logo.png" alt="ලක්way" onerror="this.style.display='none'">
            <span class="brand-name">Admin Dashboard</span>
        </div>
        <div class="nav-right">
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Delivery Person Approval</h1>
            <p class="page-subtitle">Review and manage delivery partner applications</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('pending')">
                Pending Approval
                <?php if (!empty($pending_deliveries)): ?>
                    <span class="tab-badge"><?php echo count($pending_deliveries); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab" onclick="showTab('approved')">
                Approved
                <?php if (!empty($approved_deliveries)): ?>
                    <span class="tab-badge" style="background: #10b981;"><?php echo count($approved_deliveries); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab" onclick="showTab('rejected')">
                Rejected
                <?php if (!empty($rejected_deliveries)): ?>
                    <span class="tab-badge"><?php echo count($rejected_deliveries); ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Pending Tab -->
        <div class="tab-content" id="pending-tab">
            <div class="delivery-grid">
                <?php if (empty($pending_deliveries)): ?>
                    <div class="empty-state">
                        <h4>No pending applications</h4>
                        <p>New delivery partner applications will appear here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_deliveries as $delivery): ?>
                        <div class="delivery-card">
                            <div class="delivery-header">
                                <div class="delivery-info">
                                    <h3><?php echo htmlspecialchars($delivery['username']); ?></h3>
                                    <div class="delivery-meta">
                                        <strong>Vehicle:</strong> <?php echo ucfirst($delivery['vehicle_type']); ?> - <?php echo htmlspecialchars($delivery['vehicle_number']); ?><br>
                                        <strong>Mobile:</strong> <?php echo htmlspecialchars($delivery['mobile']); ?><br>
                                        <strong>Applied:</strong> <?php echo date('M j, Y g:i A', strtotime($delivery['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-pending">Pending</span>
                            </div>

                            <div class="documents-section">
                                <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 16px;">Documents</h4>
                                <div class="documents-grid">
                                    <div class="document-item">
                                        <img src="uploads/vehicles/<?php echo $delivery['vehicle_image']; ?>" 
                                             alt="Vehicle Number Plate" 
                                             class="document-image"
                                             onclick="openModal('uploads/vehicles/<?php echo $delivery['vehicle_image']; ?>', 'Vehicle Number Plate')">
                                        <div class="document-label">Vehicle Number Plate</div>
                                    </div>
                                    <div class="document-item">
                                        <img src="uploads/licences/<?php echo $delivery['licence_front']; ?>" 
                                             alt="Driving Licence" 
                                             class="document-image"
                                             onclick="openModal('uploads/licences/<?php echo $delivery['licence_front']; ?>', 'Driving Licence Front')">
                                        <div class="document-label">Driving Licence</div>
                                    </div>
                                    <div class="document-item">
                                        <img src="uploads/nic/<?php echo $delivery['nic_front']; ?>" 
                                             alt="NIC Front" 
                                             class="document-image"
                                             onclick="openModal('uploads/nic/<?php echo $delivery['nic_front']; ?>', 'NIC Front')">
                                        <div class="document-label">NIC Front</div>
                                    </div>
                                    <div class="document-item">
                                        <img src="uploads/nic/<?php echo $delivery['nic_back']; ?>" 
                                             alt="NIC Back" 
                                             class="document-image"
                                             onclick="openModal('uploads/nic/<?php echo $delivery['nic_back']; ?>', 'NIC Back')">
                                        <div class="document-label">NIC Back</div>
                                    </div>
                                </div>
                            </div>

                            <div class="delivery-actions">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this delivery person?')">Approve</button>
                                </form>
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="delivery_id" value="<?php echo $delivery['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this delivery person?')">Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approved Tab -->
        <div class="tab-content" id="approved-tab" style="display: none;">
            <div class="delivery-grid">
                <?php if (empty($approved_deliveries)): ?>
                    <div class="empty-state">
                        <h4>No approved delivery persons</h4>
                        <p>Approved delivery partners will appear here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($approved_deliveries as $delivery): ?>
                        <div class="delivery-card">
                            <div class="delivery-header">
                                <div class="delivery-info">
                                    <h3><?php echo htmlspecialchars($delivery['username']); ?></h3>
                                    <div class="delivery-meta">
                                        <strong>Vehicle:</strong> <?php echo ucfirst($delivery['vehicle_type']); ?> - <?php echo htmlspecialchars($delivery['vehicle_number']); ?><br>
                                        <strong>Mobile:</strong> <?php echo htmlspecialchars($delivery['mobile']); ?><br>
                                        <strong>Approved:</strong> <?php echo date('M j, Y g:i A', strtotime($delivery['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-approved">Approved</span>
                            </div>

                            <div class="documents-section">
                                <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 16px;">Documents</h4>
                                <div class="documents-grid">
                                    <div class="document-item">
                                        <img src="uploads/vehicles/<?php echo $delivery['vehicle_image']; ?>" 
                                             alt="Vehicle Number Plate" 
                                             class="document-image"
                                             onclick="openModal('uploads/vehicles/<?php echo $delivery['vehicle_image']; ?>', 'Vehicle Number Plate')">
                                        <div class="document-label">Vehicle Number Plate</div>
                                    </div>
                                    <div class="document-item">
                                        <img src="uploads/licences/<?php echo $delivery['licence_front']; ?>" 
                                             alt="Driving Licence" 
                                             class="document-image"
                                             onclick="openModal('uploads/licences/<?php echo $delivery['licence_front']; ?>', 'Driving Licence Front')">
                                        <div class="document-label">Driving Licence</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rejected Tab -->
        <div class="tab-content" id="rejected-tab" style="display: none;">
            <div class="delivery-grid">
                <?php if (empty($rejected_deliveries)): ?>
                    <div class="empty-state">
                        <h4>No rejected applications</h4>
                        <p>Rejected applications will appear here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rejected_deliveries as $delivery): ?>
                        <div class="delivery-card">
                            <div class="delivery-header">
                                <div class="delivery-info">
                                    <h3><?php echo htmlspecialchars($delivery['username']); ?></h3>
                                    <div class="delivery-meta">
                                        <strong>Vehicle:</strong> <?php echo ucfirst($delivery['vehicle_type']); ?> - <?php echo htmlspecialchars($delivery['vehicle_number']); ?><br>
                                        <strong>Mobile:</strong> <?php echo htmlspecialchars($delivery['mobile']); ?><br>
                                        <strong>Rejected:</strong> <?php echo date('M j, Y g:i A', strtotime($delivery['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-rejected">Rejected</span>
                            </div>

                            <div class="documents-section">
                                <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 16px;">Documents</h4>
                                <div class="documents-grid">
                                    <div class="document-item">
                                        <img src="uploads/vehicles/<?php echo $delivery['vehicle_image']; ?>" 
                                             alt="Vehicle Number Plate" 
                                             class="document-image"
                                             onclick="openModal('uploads/vehicles/<?php echo $delivery['vehicle_image']; ?>', 'Vehicle Number Plate')">
                                        <div class="document-label">Vehicle Number Plate</div>
                                    </div>
                                    <div class="document-item">
                                        <img src="uploads/licences/<?php echo $delivery['licence_front']; ?>" 
                                             alt="Driving Licence" 
                                             class="document-image"
                                             onclick="openModal('uploads/licences/<?php echo $delivery['licence_front']; ?>', 'Driving Licence Front')">
                                        <div class="document-label">Driving Licence</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal" id="imageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Document Image</h3>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Document" class="modal-image">
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').style.display = 'block';
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        function openModal(imageSrc, title) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'admin_logout.php';
            }
        }

        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>