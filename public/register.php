<?php
session_start();
include '../api/config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $role = "user";

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role) 
                            VALUES (:username, :password, :full_name, :email, :phone, :role)");
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":password", $password);
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":role", $role);

    if ($stmt->execute()) {
        header("Location: login.php?msg=registered");
        exit();
    } else {
        $error = "Something went wrong!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - VTU Venue Booking</title>
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

        .container {
            background: white;
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.8s ease-out;
            text-align: center;
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

        input[type="text"], input[type="password"], input[type="email"], input[type="tel"] {
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

        .login-link, .back-link {
            margin-top: 15px;
            font-size: 14px;
            color: #2c3e50;
            text-decoration: none;
            display: block;
        }

        .back-link:hover, .login-link a:hover {
            text-decoration: underline;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php"><img src="images/vtu_logo.png" alt="VTU Logo"></a>
        <h2>Register - VTU Belagavi</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>

        <a href="login.php" class="login-link">Already have an account? Login here</a>
        <a href="index.php" class="back-link">← Back to Home</a>
        <div class="footer-note">© <?= date('Y') ?> VTU Venue Booking System</div>
    </div>

</body>
</html>
