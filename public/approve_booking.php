<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

include '../api/config.php';

$id = $_GET['id'] ?? 0;
if ($id) {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: admin_dashboard.php");
exit;
?>