<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ලක්way Delivery - Login & Register</title>
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: #f5f5f5;
            padding: 5px;
            border-radius: 10px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .phone-input {
            display: flex;
            gap: 10px;
        }

        .flag-select {
            width: 80px;
            padding: 12px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }

        .flag-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .phone-input input {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            color: #666;
        }

        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn:active {
            transform: translateY(0);
        }

        .register-options {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .register-options h3 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .register-btns {
            display: flex;
            gap: 15px;
        }

        .register-btn {
            flex: 1;
            padding: 14px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .register-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
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

        /* Modal Styles */
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
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: #333;
            font-size: 24px;
        }

        .modal-header p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-weak { width: 33%; background: #ff4757; }
        .strength-medium { width: 66%; background: #ffa502; }
        .strength-strong { width: 100%; background: #26de81; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🚚 ලක්way Delivery</h1>
            <p>Fast & Reliable Delivery Service</p>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="tab-buttons">
            <button class="tab-btn" onclick="switchTab('login')" id="loginTab">Login</button>
            <button class="tab-btn active" onclick="switchTab('register')" id="registerTab">Register</button>
        </div>

        <!-- Login Form -->
        <div id="loginForm" class="form-section">
            <form method="POST" action="process.php">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="loginEmail" required 
                           placeholder="Enter your email" autocomplete="email">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required 
                           placeholder="Enter your password" autocomplete="current-password">
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Remember me</label>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="openForgotPassword(); return false;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn">Login</button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="form-section active">
            <form method="POST" action="process.php" onsubmit="return validateRegister()">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required 
                           placeholder="Enter your email" autocomplete="email">
                </div>

                <div class="form-group">
                    <label>Mobile Number</label>
                    <div class="phone-input">
                        <input type="text" class="flag-select" value="🇱🇰" readonly>
                        <input type="hidden" name="country_code" value="+94">
                        <input type="tel" name="mobile" required 
                               placeholder="71 234 5678" 
                               pattern="[0-9]{9,10}"
                               autocomplete="tel">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="regPassword" required 
                           placeholder="Create a password" 
                           autocomplete="new-password"
                           onkeyup="checkPasswordStrength()">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirmPassword" required 
                           placeholder="Re-enter your password"
                           autocomplete="new-password">
                </div>

                <button type="submit" class="btn">Create Account</button>
            </form>

            <div class="register-options">
                <h3>Business Registration</h3>
                <div class="register-btns">
                    <button class="register-btn" onclick="window.location.href='store_register.php'">
                        🏪 Become a Seller<br><small style="font-size: 12px; font-weight: 400;">Register Here</small>
                    </button>
                    <button class="register-btn" onclick="window.location.href='delivery_register.php'">
                        🛵 Be a Delivery Partner<br><small style="font-size: 12px; font-weight: 400;">Register Here</small>
                    </button>
                </div>
                <div class="login-links" style="margin-top: 15px; text-align: center; font-size: 13px; color: #666;">
                    Already registered? 
                    <a href="store_login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Seller Login</a> | 
                    <a href="delivery_login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Delivery Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeForgotPassword()">×</button>
            
            <div id="emailStep">
                <div class="modal-header">
                    <h2>Forgot Password</h2>
                    <p>Enter your email to receive a verification code</p>
                </div>
                <form onsubmit="sendVerificationCode(); return false;">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="forgotEmail" required placeholder="Enter your email">
                    </div>
                    <button type="submit" class="btn">Send Code</button>
                </form>
            </div>

            <div id="codeStep" style="display: none;">
                <div class="modal-header">
                    <h2>Enter Code</h2>
                    <p>We sent a verification code to your email</p>
                </div>
                <form onsubmit="verifyCode(); return false;">
                    <div class="form-group">
                        <label>Verification Code</label>
                        <input type="text" id="verificationCode" required 
                               placeholder="Enter 6-digit code" maxlength="6">
                    </div>
                    <button type="submit" class="btn">Verify Code</button>
                </form>
            </div>

            <div id="resetStep" style="display: none;">
                <div class="modal-header">
                    <h2>Reset Password</h2>
                    <p>Enter your new password</p>
                </div>
                <form method="POST" action="process.php" onsubmit="return validateReset()">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="email" id="resetEmail">
                    <input type="hidden" name="code" id="resetCode">
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" id="newPassword" required 
                               placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmNewPassword" required 
                               placeholder="Re-enter new password">
                    </div>
                    <button type="submit" class="btn">Reset Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab Switching
        function switchTab(tab) {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            if(tab === 'login') {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
            } else {
                registerTab.classList.add('active');
                loginTab.classList.remove('active');
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
            }
        }

        // Password Strength Checker
        function checkPasswordStrength() {
            const password = document.getElementById('regPassword').value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 0;
            if(password.length >= 8) strength++;
            if(/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if(/[0-9]/.test(password)) strength++;
            if(/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            if(strength <= 1) strengthBar.classList.add('strength-weak');
            else if(strength <= 3) strengthBar.classList.add('strength-medium');
            else strengthBar.classList.add('strength-strong');
        }

        // Validate Register Form
        function validateRegister() {
            const password = document.getElementById('regPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if(password !== confirm) {
                alert('Passwords do not match!');
                return false;
            }

            if(password.length < 8) {
                alert('Password must be at least 8 characters long!');
                return false;
            }

            return true;
        }

        // Forgot Password Modal
        function openForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.add('active');
        }

        function closeForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.remove('active');
            document.getElementById('emailStep').style.display = 'block';
            document.getElementById('codeStep').style.display = 'none';
            document.getElementById('resetStep').style.display = 'none';
        }

        // Send Verification Code
        function sendVerificationCode() {
            const email = document.getElementById('forgotEmail').value;
            
            fetch('process.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=send_reset_code&email=' + encodeURIComponent(email)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('emailStep').style.display = 'none';
                    document.getElementById('codeStep').style.display = 'block';
                    document.getElementById('resetEmail').value = email;
                } else {
                    alert(data.message);
                }
            });
        }

        // Verify Code
        function verifyCode() {
            const code = document.getElementById('verificationCode').value;
            const email = document.getElementById('forgotEmail').value;
            
            fetch('process.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=verify_reset_code&email=' + encodeURIComponent(email) + '&code=' + code
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('codeStep').style.display = 'none';
                    document.getElementById('resetStep').style.display = 'block';
                    document.getElementById('resetCode').value = code;
                } else {
                    alert(data.message);
                }
            });
        }

        // Validate Reset Password
        function validateReset() {
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmNewPassword').value;

            if(newPass !== confirm) {
                alert('Passwords do not match!');
                return false;
            }

            if(newPass.length < 8) {
                alert('Password must be at least 8 characters long!');
                return false;
            }

            return true;
        }

        // Remember Me - Auto-fill from cookie
        window.onload = function() {
            const savedEmail = getCookie('remembered_email');
            if(savedEmail) {
                document.getElementById('loginEmail').value = savedEmail;
                document.getElementById('remember').checked = true;
            }
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
</body>
</html>