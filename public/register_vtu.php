<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VTU Registration - Venue Booking System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --error: #f56565;
            --warning: #ed8936;
            --bg-light: #f7fafc;
            --bg-white: #ffffff;
            --text-dark: #2d3748;
            --text-light: #4a5568;
            --border: #e2e8f0;
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #e6fffa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .registration-container {
            background: var(--bg-white);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(60px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .vtu-logo {
            width: 70px;
            height: 70px;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .kannada-text {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .form-container {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .step.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .step.inactive {
            background: var(--bg-light);
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Ensure phone input is properly styled */
        input[type="tel"] {
            -webkit-appearance: none;
            -moz-appearance: textfield;
            appearance: none;
        }

        /* Fix for mobile devices */
        @media (max-width: 768px) {
            .form-group input[type="tel"] {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .btn {
            position: relative;
            overflow: hidden;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn::after {
            content: "";
            position: absolute;
            background: rgba(255,255,255,0.3);
            width: 200%;
            height: 200%;
            top: -100%;
            left: -100%;
            transform: rotate(45deg);
            transition: all 0.6s ease;
        }

        .btn:hover::after {
            top: 100%;
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #38a169);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn:disabled::after {
            display: none;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 480px) {
            .registration-container {
                margin: 10px;
            }
            
            .form-container {
                padding: 30px 25px;
            }
            
            .header {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="header">
            <img src="images/vtu_logo.png" alt="VTU Logo" class="vtu-logo">
            <h1>VTU Registration</h1>
            <p>Visvesvaraya Technological University</p>
            <p class="kannada-text">(ವಿಶ್ವೇಶ್ವರಯ್ಯ ತಾಂತ್ರಿಕ ವಿಶ್ವವಿದ್ಯಾಲಯ)</p>
        </div>

        <div class="form-container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="step1-indicator">
                    <i class="fas fa-id-card"></i>
                    <span>VTU Verification</span>
                </div>
            </div>

            <!-- Step 1: VTU Verification -->
            <form id="verification-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="vtu_id">USN / Staff ID</label>
                    <input type="text" id="vtu_id" name="vtu_id" required placeholder="Enter your VTU ID">
                </div>

                <div class="form-group">
                    <label>User Type</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="student" name="user_type" value="student" required>
                            <label for="student">Student</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="staff" name="user_type" value="staff" required>
                            <label for="staff">Staff</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="verify-btn">
                    <i class="fas fa-search"></i>
                    Verify VTU ID
                </button>
            </form>

            <!-- Step 2: Account Creation (Hidden initially) -->
            <div id="account-creation" class="hidden">
                <div class="step-indicator">
                    <div class="step active">
                        <i class="fas fa-user-plus"></i>
                        <span>Create Account</span>
                    </div>
                </div>

                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>VTU ID verified successfully! Create your account below.</span>
                </div>

                <form id="account-form" action="process_registration.php" method="POST">
                    <input type="hidden" id="verified_name" name="verified_name">
                    <input type="hidden" id="verified_vtu_id" name="verified_vtu_id">
                    <input type="hidden" id="verified_user_type" name="verified_user_type">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required placeholder="Choose a username">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email address">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number (optional)" 
                               pattern="[0-9]{10}" title="Please enter a 10-digit phone number" maxlength="10">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Create a password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-check"></i>
                        Create Account
                    </button>
                </form>
            </div>

            <!-- Alert Messages -->
            <div id="alert-container"></div>

            <div class="back-link">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('verification-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const verifyBtn = document.getElementById('verify-btn');
            const alertContainer = document.getElementById('alert-container');
            
            // Show loading state
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            verifyBtn.disabled = true;
            
            // Clear previous alerts
            alertContainer.innerHTML = '';
            
            // Get form data
            const formData = new FormData(this);
            const data = {
                name: formData.get('name'),
                vtu_id: formData.get('vtu_id'),
                user_type: formData.get('user_type')
            };
            
            try {
                const response = await fetch('/venue-booking-system/api/verify_vtu_id.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.valid) {
                    // Verification successful
                    document.getElementById('verified_name').value = result.name;
                    document.getElementById('verified_vtu_id').value = result.id;
                    document.getElementById('verified_user_type').value = data.user_type;
                    
                    // Hide verification form and show account creation
                    document.getElementById('verification-form').style.display = 'none';
                    document.getElementById('step1-indicator').style.display = 'none';
                    document.getElementById('account-creation').classList.remove('hidden');
                } else {
                    // Verification failed
                    showAlert('error', result.error || 'Verification failed');
                }
            } catch (error) {
                showAlert('error', 'Network error. Please try again.');
            }
            
            // Reset button
            verifyBtn.innerHTML = '<i class="fas fa-search"></i> Verify VTU ID';
            verifyBtn.disabled = false;
        });
        
        // Password confirmation validation
        document.getElementById('account-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAlert('error', 'Passwords do not match');
            }
        });
        
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            const icon = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="${icon}"></i>
                    <span>${message}</span>
                </div>
            `;
        }
    </script>
</body>
</html>