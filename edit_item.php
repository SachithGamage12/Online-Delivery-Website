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

// Check if store is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller' || !isset($_SESSION['store_id'])) {
    header('Location: store_login.php');
    exit();
}

// Get item ID
if (!isset($_GET['id'])) {
    header('Location: store_dashboard.php');
    exit();
}

$item_id = $_GET['id'];
$store_id = $_SESSION['store_id'];

// Fetch item details and verify ownership
$item_query = "SELECT * FROM items WHERE id = ? AND store_id = ?";
$stmt = $conn->prepare($item_query);
$stmt->bind_param("ii", $item_id, $store_id);
$stmt->execute();
$item_result = $stmt->get_result();

if ($item_result->num_rows === 0) {
    header('Location: store_dashboard.php');
    exit();
}

$item = $item_result->fetch_assoc();
$stmt->close();

// Fetch store details for navbar
$store_query = "SELECT * FROM stores WHERE id = ?";
$stmt = $conn->prepare($store_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store_result = $stmt->get_result();
$store = $store_result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - ලක්way Delivery</title>
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

        /* Top Navigation */
        .top-nav {
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

        .logo-section img.logo {
            height: 45px;
            width: auto;
        }

        .logo-section .store-logo {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #667eea;
        }

        .store-name-nav {
            font-weight: 700;
            color: #1e293b;
            font-size: 18px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        /* Main Container */
        .main-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .edit-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }

        .page-header h2 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #64748b;
            font-size: 15px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .current-image-section {
            margin-bottom: 20px;
        }

        .current-image-section h4 {
            font-size: 14px;
            color: #334155;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .current-image {
            max-width: 300px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .image-upload-area {
            border: 3px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .image-upload-area:hover {
            border-color: #667eea;
            background: #f8fafc;
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
            max-width: 300px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert.active {
            display: block;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .top-nav {
                padding: 10px 15px;
            }

            .logo-section img.logo {
                height: 35px;
            }

            .logo-section .store-logo {
                width: 35px;
                height: 35px;
            }

            .store-name-nav {
                font-size: 16px;
            }

            .back-btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .main-container {
                padding: 15px;
                margin: 20px auto;
            }

            .edit-card {
                padding: 25px 20px;
            }

            .page-header h2 {
                font-size: 22px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .current-image,
            .image-preview-container img {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .logo-section img.logo {
                height: 30px;
            }

            .logo-section .store-logo {
                width: 30px;
                height: 30px;
            }

            .store-name-nav {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="logo-section">
            <img src="assets/logo.png" alt="ලක්way" class="logo" onerror="this.style.display='none'">
            <?php 
            $store_image = basename($store['store_image_path'] ?? '');
            ?>
            <img src="uploads/stores/<?php echo $store_image; ?>" 
                 alt="Store" class="store-logo" 
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2245%22 height=%2245%22%3E%3Crect fill=%22%23ddd%22 width=%2245%22 height=%2245%22 rx=%228%22/%3E%3C/svg%3E'">
            <span class="store-name-nav"><?php echo htmlspecialchars($store['store_name']); ?></span>
        </div>
        <div class="nav-right">
            <a href="store_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <div class="edit-card">
            <div class="page-header">
                <h2>Edit Item</h2>
                <p>Update your product information</p>
            </div>

            <div id="alertBox" class="alert"></div>

            <form id="editItemForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="store_id" value="<?php echo $store_id; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Price (LKR) *</label>
                        <input type="number" name="item_price" step="0.01" min="0" value="<?php echo $item['item_price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Count *</label>
                        <input type="number" name="stock_count" min="0" value="<?php echo $item['stock_count']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="food" <?php echo $item['category'] === 'food' ? 'selected' : ''; ?>>Food</option>
                            <option value="beverages" <?php echo $item['category'] === 'beverages' ? 'selected' : ''; ?>>Beverages</option>
                            <option value="groceries" <?php echo $item['category'] === 'groceries' ? 'selected' : ''; ?>>Groceries</option>
                            <option value="electronics" <?php echo $item['category'] === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                            <option value="clothing" <?php echo $item['category'] === 'clothing' ? 'selected' : ''; ?>>Clothing</option>
                            <option value="other" <?php echo $item['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Item Image</label>
                        
                        <div class="current-image-section">
                            <h4>Current Image:</h4>
                            <?php 
                            $item_image = basename($item['item_image'] ?? '');
                            ?>
                            <img src="uploads/items/<?php echo $item_image; ?>" 
                                 alt="Current Item" 
                                 class="current-image"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22300%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%23999%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2218%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                        </div>
                        
                        <div class="image-upload-area" onclick="document.getElementById('newItemImage').click()">
                            <p>📷 Click to upload new image (optional)</p>
                            <small style="color: #64748b;">Leave empty to keep current image</small>
                        </div>
                        <input type="file" id="newItemImage" name="item_image" accept="image/*" style="display: none;" onchange="previewNewImage(this)">
                        
                        <div class="image-preview-container" id="newImagePreview">
                            <h4 style="font-size: 14px; color: #334155; margin-bottom: 12px;">New Image Preview:</h4>
                            <img id="previewImg" src="#" alt="Preview">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Item</button>
                    <a href="store_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewNewImage(input) {
            const preview = document.getElementById('previewImg');
            const container = document.getElementById('newImagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.add('active');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                container.classList.remove('active');
            }
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = 'alert ' + type + ' active';
            
            setTimeout(() => {
                alertBox.classList.remove('active');
            }, 5000);
        }

        document.getElementById('editItemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            fetch('process_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Item updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'store_dashboard.php';
                    }, 1500);
                } else {
                    showAlert('Error: ' + data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Update Item';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error updating item. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Update Item';
            });
        });
    </script>
</body>
</html>