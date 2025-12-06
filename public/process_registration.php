<?php
session_start();
include '../api/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register_vtu.php");
    exit;
}

$verified_name = trim($_POST['verified_name'] ?? '');
$verified_vtu_id = trim($_POST['verified_vtu_id'] ?? '');
$verified_user_type = trim($_POST['verified_user_type'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// Validate input
if (empty($verified_name) || empty($verified_vtu_id) || empty($verified_user_type) || 
    empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    $_SESSION['error'] = 'All fields are required';
    header("Location: register_vtu.php");
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match';
    header("Location: register_vtu.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters long';
    header("Location: register_vtu.php");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address';
    header("Location: register_vtu.php");
    exit;
}

try {
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Username already exists';
        header("Location: register_vtu.php");
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email address already registered';
        header("Location: register_vtu.php");
        exit;
    }
    
    // Check if VTU ID already registered (prevent duplicate registrations)
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE vtu_id = ?");
    $stmt->execute([$verified_vtu_id]);
    $existing_user = $stmt->fetch();
    if ($existing_user) {
        $_SESSION['error'] = 'This VTU ID (' . htmlspecialchars($verified_vtu_id) . ') is already registered and locked. Each USN/Staff ID can only be used once for security purposes.';
        header("Location: register_vtu.php");
        exit;
    }
    
    // Create new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, vtu_id, id_verified, user_type, full_name, phone) VALUES (?, ?, ?, ?, 1, ?, ?, ?)");
    $result = $stmt->execute([$username, $email, $hashed_password, $verified_vtu_id, $verified_user_type, $verified_name, $phone]);
    
    if ($result) {
        $_SESSION['success'] = 'Registration successful! You can now login.';
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header("Location: register_vtu.php");
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header("Location: register_vtu.php");
    exit;
}
?>