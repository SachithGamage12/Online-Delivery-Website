<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Registration - ලක්way Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBhCfrw-m2Vs7ywvZ82JuGjr5-WBV4Y9rk&libraries=places"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --light-gray: #f3f4f6;
            --border: #e5e7eb;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.4;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            max-width: 960px;
            margin: 0 auto;
            padding: 0;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 48px 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-30px, 30px) rotate(180deg); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 24px;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
        }

        .back-link::before {
            content: '←';
            font-size: 18px;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-content h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .header-content p {
            font-size: 18px;
            opacity: 0.95;
            font-weight: 400;
        }

        .form-container {
            padding: 40px;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 32px;
            font-size: 14px;
            line-height: 1.6;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .alert::before {
            content: 'ℹ️';
            font-size: 20px;
            flex-shrink: 0;
        }

        .alert strong {
            font-weight: 600;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin: 40px 0 24px 0;
            padding-bottom: 12px;
            border-bottom: 3px solid var(--light-gray);
            position: relative;
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
        }

        .required {
            color: var(--danger);
            font-weight: 700;
        }

        .optional {
            color: var(--gray);
            font-weight: 400;
            font-size: 13px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            color: var(--dark);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        /* Time Input Styling */
        .time-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .time-input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .time-input-wrapper .time-icon {
            font-size: 20px;
            color: var(--gray);
        }

        .time-input-wrapper input[type="time"] {
            border: none;
            padding: 0;
            flex: 1;
            font-size: 15px;
            font-weight: 500;
        }

        .time-input-wrapper input[type="time"]:focus {
            box-shadow: none;
            transform: none;
        }

        /* Checkbox for 24/7 */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
            background: var(--light-gray);
            border-radius: 12px;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkbox-group:hover {
            background: #e5e7eb;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }

        .phone-group {
            display: flex;
            gap: 0;
            position: relative;
        }

        .phone-prefix {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-right: none;
            border-radius: 12px 0 0 12px;
            background: var(--light-gray);
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
            white-space: nowrap;
            min-width: 100px;
        }

        .phone-prefix .flag {
            font-size: 20px;
            line-height: 1;
        }

        .phone-prefix .code {
            color: var(--gray);
        }

        .phone-group input[type="tel"] {
            flex: 1;
            border-radius: 0 12px 12px 0 !important;
            min-width: 0;
        }

        .phone-group input[type="tel"]:focus {
            border-left: 2px solid var(--primary);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .eye-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: var(--gray);
            user-select: none;
            transition: color 0.3s;
            padding: 4px;
        }

        .eye-icon:hover {
            color: var(--primary);
        }

        .password-strength {
            height: 6px;
            background: var(--light-gray);
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 3px;
        }

        .strength-weak {
            width: 33%;
            background: var(--danger);
        }

        .strength-medium {
            width: 66%;
            background: var(--warning);
        }

        .strength-strong {
            width: 100%;
            background: var(--success);
        }

        .upload-box {
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--light-gray);
            position: relative;
            overflow: hidden;
        }

        .upload-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .upload-box:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .upload-box:hover::before {
            opacity: 1;
        }

        .upload-box.has-file {
            border-color: var(--success);
            background: #f0fdf4;
        }

        .upload-box input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.7;
        }

        .upload-text {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .upload-text strong {
            color: var(--primary);
            font-weight: 600;
        }

        .upload-text small {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            opacity: 0.8;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            margin-top: 16px;
            display: none;
            box-shadow: var(--shadow);
        }

        .address-search-container {
            position: relative;
        }

        .pac-container {
            border-radius: 12px;
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-lg);
            margin-top: 8px;
            font-family: 'Inter', sans-serif;
        }

        .pac-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.2s;
        }

        .pac-item:hover {
            background: #f8faff;
        }

        .pac-item-query {
            color: var(--primary);
            font-weight: 600;
        }

        .map-preview {
            height: 300px;
            border-radius: 16px;
            border: 2px solid var(--border);
            margin-top: 16px;
            overflow: hidden;
            display: none;
            box-shadow: var(--shadow);
        }

        .address-details {
            background: linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%);
            padding: 20px;
            border-radius: 16px;
            margin-top: 16px;
            display: none;
            border: 1px solid #e0e7ff;
        }

        .address-details.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .address-field {
            margin-bottom: 12px;
            font-size: 14px;
            line-height: 1.6;
        }

        .address-field:last-child {
            margin-bottom: 0;
        }

        .address-field strong {
            color: var(--dark);
            display: inline-block;
            width: 110px;
            font-weight: 600;
        }

        .location-coordinates {
            background: rgba(102, 126, 234, 0.1);
            padding: 12px 16px;
            border-radius: 10px;
            margin-top: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--primary-dark);
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
            margin-top: 32px;
            letter-spacing: 0.3px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.5);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: var(--gray);
            font-size: 14px;
        }

        .footer-text a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-text a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                border-radius: 20px;
            }

            .header-section {
                padding: 32px 24px;
            }

            .header-content h1 {
                font-size: 28px;
            }

            .header-content p {
                font-size: 16px;
            }

            .form-container {
                padding: 24px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .section-title {
                font-size: 18px;
                margin: 32px 0 20px 0;
            }

            .upload-box {
                padding: 24px 16px;
            }

            .map-preview {
                height: 250px;
            }

            .btn {
                padding: 16px;
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .header-content h1 {
                font-size: 24px;
            }

            .header-content p {
                font-size: 14px;
            }

            .form-container {
                padding: 20px;
            }

            .form-grid {
                gap: 16px;
            }

            .phone-prefix {
                min-width: 85px;
                padding: 14px 12px;
                font-size: 14px;
            }

            .phone-prefix .flag {
                font-size: 18px;
            }

            .alert {
                padding: 12px 16px;
                font-size: 13px;
            }
        }

        .btn.loading {
            position: relative;
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="index.php" class="back-link">Back to Login</a>
            <div class="header-content">
                <h1>🏪 Become a Seller</h1>
                <p>Join ලක්way Delivery and grow your business</p>
            </div>
        </div>

        <div class="form-container">
            <div class="alert alert-info">
                <div>
                    <strong>Important Notice:</strong> Please use the map search below to select your exact store location. This ensures delivery drivers can find your store easily and improves customer experience.
                </div>
            </div>

            <form method="POST" action="process_store.php" enctype="multipart/form-data" id="storeForm" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="register_store">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="address" id="hiddenAddress">
                <input type="hidden" name="city" id="hiddenCity">
                <input type="hidden" name="postal_code" id="hiddenPostalCode">

                <!-- Store Information -->
                <div class="section-title">📋 Store Information</div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Store Name <span class="required">*</span></label>
                        <input type="text" name="store_name" required placeholder="Enter your store name">
                    </div>

                    <div class="form-group full-width">
                        <label>Store Image <span class="required">*</span></label>
                        <div class="upload-box" onclick="document.getElementById('storeImage').click()" id="storeImageBox">
                            <input type="file" id="storeImage" name="store_image" accept="image/*" required onchange="previewImage(this, 'storeImagePreview', 'storeImageBox')">
                            <div class="upload-icon">🖼️</div>
                            <div class="upload-text">
                                <strong>Click to upload</strong> or drag and drop<br>
                                <small>PNG, JPG, JPEG (Max 5MB)</small>
                            </div>
                            <img id="storeImagePreview" class="preview-image">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Store Type <span class="required">*</span></label>
                        <select name="store_type" required>
                            <option value="">Select store type</option>
                            <option value="restaurant">🍽️ Restaurant</option>
                            <option value="cafe">☕ Café</option>
                            <option value="bakery">🥐 Bakery</option>
                            <option value="grocery">🛒 Grocery Store</option>
                            <option value="pharmacy">💊 Pharmacy</option>
                            <option value="convenience">🏪 Convenience Store</option>
                            <option value="other">📦 Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Business Registration (BR) Number <span class="required">*</span></label>
                        <input type="text" name="br_number" required placeholder="e.g., PV 12345">
                    </div>

                    <div class="form-group full-width">
                        <label>BR Certificate Image <span class="required">*</span></label>
                        <div class="upload-box" onclick="document.getElementById('brImage').click()" id="brImageBox">
                            <input type="file" id="brImage" name="br_image" accept="image/*,application/pdf" required onchange="previewImage(this, 'brImagePreview', 'brImageBox')">
                            <div class="upload-icon">📄</div>
                            <div class="upload-text">
                                <strong>Click to upload</strong> BR Certificate<br>
                                <small>PNG, JPG, PDF (Max 5MB)</small>
                            </div>
                            <img id="brImagePreview" class="preview-image">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Food Safety Certificate <span class="optional">(Optional)</span></label>
                        <div class="upload-box" onclick="document.getElementById('foodCert').click()" id="foodCertBox">
                            <input type="file" id="foodCert" name="food_certificate" accept="image/*,application/pdf" onchange="previewImage(this, 'foodCertPreview', 'foodCertBox')">
                            <div class="upload-icon">🏥</div>
                            <div class="upload-text">
                                <strong>Click to upload</strong> Food Certificate<br>
                                <small>PNG, JPG, PDF (Max 5MB)</small>
                            </div>
                            <img id="foodCertPreview" class="preview-image">
                        </div>
                    </div>
                </div>

                <!-- Operating Hours -->
                <div class="section-title">⏰ Operating Hours</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Opening Time <span class="required">*</span></label>
                        <div class="time-input-wrapper">
                            <span class="time-icon">🕐</span>
                            <input type="time" name="opening_time" id="openingTime" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Closing Time <span class="required">*</span></label>
                        <div class="time-input-wrapper">
                            <span class="time-icon">🕘</span>
                            <input type="time" name="closing_time" id="closingTime" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <div class="checkbox-group" onclick="toggleCheckbox('open247')">
                            <input type="checkbox" name="open_24_7" id="open247" value="1" onchange="toggle24Hours()">
                            <label for="open247">🌙 Open 24/7 (24 hours a day)</label>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="section-title">📞 Contact Information</div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" required placeholder="store@example.com">
                    </div>

                    <div class="form-group">
                        <label>Primary Mobile Number <span class="required">*</span></label>
                        <div class="phone-group">
                            <div class="phone-prefix">
                                <span class="flag">🇱🇰</span>
                                <span class="code">+94</span>
                            </div>
                            <input type="hidden" name="country_code" value="+94">
                            <input type="tel" name="mobile_primary" required placeholder="71 234 5678" pattern="[0-9]{9,10}" maxlength="10">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Secondary Mobile Number <span class="optional">(Optional)</span></label>
                        <div class="phone-group">
                            <div class="phone-prefix">
                                <span class="flag">🇱🇰</span>
                                <span class="code">+94</span>
                            </div>
                            <input type="tel" name="mobile_secondary" placeholder="77 234 5678" pattern="[0-9]{9,10}" maxlength="10">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Store Location <span class="required">*</span></label>
                        <div class="address-search-container">
                            <input type="text" id="addressSearch" name="address_search" 
                                   placeholder="🔍 Search for your store location..." 
                                   autocomplete="off">
                        </div>
                        
                        <div id="mapPreview" class="map-preview"></div>
                        
                        <div id="addressDetails" class="address-details">
                            <div class="address-field"><strong>📍 Address:</strong> <span id="displayAddress"></span></div>
                            <div class="address-field"><strong>🏙️ City:</strong> <span id="displayCity"></span></div>
                            <div class="address-field"><strong>📮 Postal Code:</strong> <span id="displayPostalCode"></span></div>
                            <div class="location-coordinates">
                                📌 Coordinates: <span id="displayCoordinates"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="section-title">🔒 Account Security</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Create a strong password" 
                                   onkeyup="checkPasswordStrength()">
                            <span class="eye-icon" onclick="togglePassword('password', this)">👁️</span>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="confirmPassword" name="confirm_password" required 
                                   placeholder="Re-enter password">
                            <span class="eye-icon" onclick="togglePassword('confirmPassword', this)">👁️</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    Submit for Admin Approval 🚀
                </button>

                <p class="footer-text">
                    Already have an account? <a href="store_login.php">Login here</a>
                </p>
            </form>
        </div>
    </div>

    <script>
        let map, marker, autocomplete;

        function initMap() {
            const defaultLocation = { lat: 6.9271, lng: 79.8612 };
            map = new google.maps.Map(document.getElementById('mapPreview'), {
                center: defaultLocation,
                zoom: 15,
                styles: [
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    }
                ]
            });
            marker = new google.maps.Marker({
                map,
                position: defaultLocation,
                draggable: true,
                animation: google.maps.Animation.DROP
            });
            
            autocomplete = new google.maps.places.Autocomplete(
                document.getElementById('addressSearch'),
                {
                    types: ['establishment', 'geocode'],
                    componentRestrictions: { country: 'lk' }
                }
            );
            
            autocomplete.addListener('place_changed', onPlaceChanged);
            marker.addListener('dragend', onMarkerDragEnd);
        }

        function onPlaceChanged() {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                alert("No details available. Please select from the dropdown suggestions.");
                return;
            }
            updateLocation(place.geometry.location, place);
        }

        function onMarkerDragEnd() {
            const position = marker.getPosition();
            reverseGeocode(position);
        }

        function updateLocation(location, place = null) {
            map.setCenter(location);
            map.setZoom(17);
            marker.setPosition(location);
            
            document.getElementById('mapPreview').style.display = 'block';
            document.getElementById('latitude').value = location.lat();
            document.getElementById('longitude').value = location.lng();
            document.getElementById('displayCoordinates').textContent = 
                location.lat().toFixed(6) + ', ' + location.lng().toFixed(6);
            
            if (place) {
                updateAddressDetails(place);
            } else {
                reverseGeocode(location);
            }
        }

        function reverseGeocode(location) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: location }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    updateAddressDetails(results[0]);
                }
            });
        }

        function updateAddressDetails(place) {
            let address = place.formatted_address || '';
            let city = '';
            let postalCode = '';

            if (place.address_components) {
                for (const component of place.address_components) {
                    const type = component.types[0];
                    if (['locality', 'sublocality_level_1', 'sublocality', 'administrative_area_level_2'].includes(type) && !city) {
                        city = component.long_name;
                    }
                    if (type === 'postal_code') {
                        postalCode = component.long_name;
                    }
                }
            }

            document.getElementById('hiddenAddress').value = address;
            document.getElementById('hiddenCity').value = city || 'Colombo';
            document.getElementById('hiddenPostalCode').value = postalCode || '';

            document.getElementById('displayAddress').textContent = address;
            document.getElementById('displayCity').textContent = city || 'Colombo';
            document.getElementById('displayPostalCode').textContent = postalCode || 'N/A';

            document.getElementById('addressDetails').classList.add('active');
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }

        function toggle24Hours() {
            const checkbox = document.getElementById('open247');
            const openingTime = document.getElementById('openingTime');
            const closingTime = document.getElementById('closingTime');

            if (checkbox.checked) {
                openingTime.value = '00:00';
                closingTime.value = '23:59';
                openingTime.disabled = true;
                closingTime.disabled = true;
                openingTime.parentElement.style.opacity = '0.5';
                closingTime.parentElement.style.opacity = '0.5';
            } else {
                openingTime.disabled = false;
                closingTime.disabled = false;
                openingTime.parentElement.style.opacity = '1';
                closingTime.parentElement.style.opacity = '1';
                openingTime.value = '';
                closingTime.value = '';
            }
        }

        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            checkbox.checked = !checkbox.checked;
            toggle24Hours();
        }

        function previewImage(input, previewId, boxId) {
            const file = input.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById(previewId);
                if (file.type.includes('image')) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                document.getElementById(boxId).classList.add('has-file');
                const uploadText = document.getElementById(boxId).querySelector('.upload-text');
                uploadText.innerHTML = '<strong>✅ Uploaded:</strong><br><small>' + file.name + '</small>';
            };
            reader.readAsDataURL(file);
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            const address = document.getElementById('hiddenAddress').value;
            const openingTime = document.getElementById('openingTime').value;
            const closingTime = document.getElementById('closingTime').value;
            const open247 = document.getElementById('open247').checked;

            if (password !== confirmPassword) {
                alert('❌ Passwords do not match!');
                return false;
            }

            if (password.length < 8) {
                alert('❌ Password must be at least 8 characters long!');
                return false;
            }

            if (!latitude || !longitude || !address) {
                alert('📍 Please select your store location from the map!');
                return false;
            }

            if (!open247 && (!openingTime || !closingTime)) {
                alert('⏰ Please set your store operating hours or check 24/7 option!');
                return false;
            }

            if (!open247 && openingTime >= closingTime) {
                alert('⏰ Closing time must be after opening time!');
                return false;
            }

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.classList.add('loading');
            btn.textContent = 'Submitting...';
            
            return true;
        }

        document.querySelectorAll('.upload-box').forEach(box => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                box.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                box.addEventListener(eventName, () => {
                    box.style.borderColor = 'var(--primary)';
                    box.style.background = '#f8faff';
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                box.addEventListener(eventName, () => {
                    box.style.borderColor = '';
                    box.style.background = '';
                }, false);
            });

            box.addEventListener('drop', (e) => {
                const input = box.querySelector('input[type="file"]');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    input.files = files;
                    const event = new Event('change', { bubbles: true });
                    input.dispatchEvent(event);
                }
            }, false);
        });

        window.onload = initMap;
    </script>
</body>
</html>