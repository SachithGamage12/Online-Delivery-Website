<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Sun123flower@');
define('DB_NAME', 'lakway_delivery');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $vehicle_type = $conn->real_escape_string($_POST['vehicle_type']);
    $vehicle_number = $conn->real_escape_string($_POST['vehicle_number']);
    $mobile = $conn->real_escape_string($_POST['mobile']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Handle file uploads
    $vehicle_image = uploadFile('vehicle_image', 'vehicles');
    $licence_front = uploadFile('licence_front', 'licences');
    $nic_front = uploadFile('nic_front', 'nic');
    $nic_back = uploadFile('nic_back', 'nic');
    
    if ($vehicle_image && $licence_front && $nic_front && $nic_back) {
        $stmt = $conn->prepare("INSERT INTO delivery_persons (username, vehicle_type, vehicle_number, vehicle_image, licence_front, nic_front, nic_back, mobile, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("sssssssss", $username, $vehicle_type, $vehicle_number, $vehicle_image, $licence_front, $nic_front, $nic_back, $mobile, $password);
        
        if ($stmt->execute()) {
            $success = "Registration submitted successfully! Waiting for admin approval.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Please upload all required documents";
    }
}

function uploadFile($field, $folder) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $filename = uniqid() . '.' . $ext;
    $target = "uploads/{$folder}/" . $filename;
    
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        return $filename;
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Registration - ලක්way Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2d7a4e 0%, #3a9d5d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .form-container {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .file-upload {
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover {
            border-color: #667eea;
            background: #f8fafc;
        }
        .file-upload input {
            display: none;
        }
        .file-info {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle input {
            padding-right: 50px;
        }
        .toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2d7a4e 0%, #3a9d5d 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45, 122, 78, 0.3);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
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
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .login-link a {
            color: #2d7a4e;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Join as Delivery Partner</h1>
            <p>Register to start delivering with ලක්way</p>
        </div>
        
        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Vehicle Type *</label>
                    <select name="vehicle_type" required>
                        <option value="">Select Vehicle Type</option>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="threewheel">Three Wheel</option>
                        <option value="car">Car</option>
                        <option value="van">Van</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Vehicle Number *</label>
                    <input type="text" name="vehicle_number" required placeholder="ABC-1234">
                </div>
                
                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" name="mobile" required placeholder="07XXXXXXXX">
                </div>
                
                <div class="form-group">
                    <label>Vehicle Number Plate Image *</label>
                    <div class="file-upload" onclick="document.getElementById('vehicle_image').click()">
                        <div>📷 Click to upload vehicle number plate</div>
                        <div class="file-info">JPG, PNG, GIF (Max 2MB)</div>
                    </div>
                    <input type="file" id="vehicle_image" name="vehicle_image" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label>Driving Licence Front *</label>
                    <div class="file-upload" onclick="document.getElementById('licence_front').click()">
                        <div>📷 Click to upload licence front</div>
                        <div class="file-info">JPG, PNG, GIF (Max 2MB)</div>
                    </div>
                    <input type="file" id="licence_front" name="licence_front" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label>NIC Front *</label>
                    <div class="file-upload" onclick="document.getElementById('nic_front').click()">
                        <div>📷 Click to upload NIC front</div>
                        <div class="file-info">JPG, PNG, GIF (Max 2MB)</div>
                    </div>
                    <input type="file" id="nic_front" name="nic_front" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label>NIC Back *</label>
                    <div class="file-upload" onclick="document.getElementById('nic_back').click()">
                        <div>📷 Click to upload NIC back</div>
                        <div class="file-info">JPG, PNG, GIF (Max 2MB)</div>
                    </div>
                    <input type="file" id="nic_back" name="nic_back" accept="image/*" required>
                </div>
                
                <div class="form-group password-toggle">
                    <label>Password *</label>
                    <input type="password" name="password" id="password" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword()">👁️</button>
                </div>
                
                <button type="submit" class="btn">Register for Approval</button>
            </form>
            
            <div class="login-link">
                Already registered? <a href="delivery_login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
        }

        // Show selected file names
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const fileInfo = this.previousElementSibling.querySelector('.file-info');
                if (this.files.length > 0) {
                    fileInfo.textContent = 'Selected: ' + this.files[0].name;
                    fileInfo.style.color = '#065f46';
                }
            });
        });
    </script>
</body>
</html>