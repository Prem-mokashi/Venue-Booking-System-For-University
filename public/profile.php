<?php
session_start();
include '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Update query
    $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $update_stmt->execute([$full_name, $email, $phone, $user_id]);

    // Refresh user data after update
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<script>alert('Profile updated successfully!');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; }
        .profile-container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; }
        h2 { text-align: center; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1a242f; }
        a { display: block; margin-top: 15px; text-align: center; text-decoration: none; color: #2c3e50; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>My Profile</h2>
        <form method="post">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Enter your full name">

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Enter your email address">

            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter your phone number">

            <button type="submit">Update Profile</button>
        </form>
        <a href="user_dashboard.php">&larr; Back to Dashboard</a>
    </div>
</body>
</html>
