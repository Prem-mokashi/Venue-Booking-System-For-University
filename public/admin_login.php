<?php
session_start();
include '../api/config.php';

$loggedIn = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email']; // ✅ Add email to session
        $loggedIn = true;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - VTU Booking</title>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #dbe6f6, #c5796d);
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
      width: 80px;
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
    .redirect-message {
      font-size: 18px;
      color: #2c3e50;
      animation: fadePulse 1.2s infinite;
    }
    @keyframes fadePulse {
      0%, 100% { opacity: 0.6; }
      50% { opacity: 1; }
    }
    .footer-note {
      margin-top: 25px;
      font-size: 13px;
      color: #777;
    }
    .back-link {
      margin-top: 15px;
      display: block;
      text-decoration: none;
      font-size: 14px;
      color: #4ca1af;
    }
    .back-link:hover {
      color: #2c3e50;
    }
  </style>
</head>
<body>

<?php if ($loggedIn): ?>
  <!-- Redirect screen -->
  <div class="redirect-container">
    <img src="../public/images/vtu_logo.png" alt="VTU Logo">
    <h2>Welcome Admin!</h2>
    <p class="redirect-message">Redirecting to dashboard...</p>
  </div>
  <script>
    setTimeout(() => {
      window.location.href = "admin_dashboard.php";
    }, 2000);
  </script>
<?php else: ?>
  <!-- Login form -->
  <div class="login-container">
    <img src="../public/images/vtu_logo.png" alt="VTU Logo">
    <h2>Admin Login</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="username" placeholder="Admin Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>

    <a class="back-link" href="index.php">&larr; Back to Home</a>
    <div class="footer-note">© <?= date('Y') ?> VTU Booking Portal</div>
  </div>
<?php endif; ?>

</body>
</html>