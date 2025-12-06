<?php
session_start();
include '../api/config.php';

$loggedIn = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'] ?? '';
        $loggedIn = true;
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VTU Venue Booking - Login</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .login-container, .redirect-container {
            background: white;
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(60px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        img {
            width: 70px;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        h2 {
            margin-bottom: 18px;
            color: #2c3e50;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus {
            border-color: #3498db;
            box-shadow: 0 0 6px rgba(52, 152, 219, 0.4);
            outline: none;
        }

        button {
            position: relative;
            overflow: hidden;
            width: 100%;
            padding: 12px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        button:hover {
            background: #1a242f;
            transform: scale(1.02);
        }

        button::after {
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

        button:hover::after {
            top: 100%;
            left: 100%;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .success {
            color: green;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .vtu-text {
            font-size: 15px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .footer-note {
            margin-top: 25px;
            font-size: 13px;
            color: #777;
        }

        .redirect-message {
            font-size: 18px;
            color: #2c3e50;
            animation: fadePulse 1.2s infinite;
        }

        @keyframes fadePulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        .forgot-password {
            margin-top: 10px;
            text-align: center;
        }

        .forgot-password a {
            color: #2c3e50;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php if ($loggedIn): ?>
    <div class="redirect-container">
        <img src="images/vtu_logo.png" alt="VTU Logo">
        <h2>Welcome!</h2>
        <p class="redirect-message">Logging you in, please wait...</p>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = "index.php";
        }, 2000);
    </script>
<?php else: ?>
    <div class="login-container">
        <img src="images/vtu_logo.png" alt="VTU Logo">
        <h2>VTU Belagavi Login</h2>
        <div class="vtu-text">
            Visvesvaraya Technological University<br>(ವಿಶ್ವೇಶ್ವರಯ್ಯ ತಾಂತ್ರಿಕ ವಿಶ್ವವಿದ್ಯಾಲಯ)
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
            <div class="success">Registration successful. Please login.</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

        <p style="margin-top: 15px;">Don't have an account? <a href="register_vtu.php">Register with VTU ID</a></p>
        <div class="footer-note">© <?= date('Y') ?> VTU Venue Booking System</div>
    </div>
<?php endif; ?>

</body>
</html>
