<?php
session_start();
include 'config.php';

// Check if admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    } else {
        header("Location: ../public/admin_dashboard.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE bookings SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $booking_id]);

    header("Location: ../public/admin_dashboard.php");
    exit;
}
