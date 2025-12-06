<?php
include '../api/config.php';

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    echo $stmt->fetchColumn() > 0 ? 'taken' : 'available';
}
?>
