<?php
session_start();
include '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit();
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Check if booking belongs to the user
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = "Invalid booking or you do not have permission.";
    header("Location: user_dashboard.php");
    exit();
}

// Update status to cancelled
$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$booking_id]);

$_SESSION['success'] = "Booking cancelled successfully. Admin will be informed.";
header("Location: user_dashboard.php");
exit();
?>
