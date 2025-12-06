<?php
session_start();
include '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $venue_id      = $_POST['venue_id'];
    $full_name     = trim($_POST['full_name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $event_name    = trim($_POST['event_name']);
    $event_date    = $_POST['event_date'];
    $start_time    = $_POST['start_time'];
    $end_time      = $_POST['end_time'];
    $event_details = trim($_POST['event_details']);
    $department    = trim($_POST['department']);


    $errors = [];

    // ✅ Basic Validations
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if ($event_date < date('Y-m-d')) {
        $errors[] = "Event date cannot be in the past.";
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = "End time must be after start time.";
    }

    // ✅ Conflict check — allow only if no approved overlapping booking exists
    $check = $conn->prepare("SELECT * FROM bookings 
        WHERE venue_id = ? 
        AND event_date = ? 
        AND status = 'approved' 
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND end_time <= ?)
        )");

    $check->execute([
        $venue_id, $event_date,
        $end_time, $end_time,
        $start_time, $start_time,
        $start_time, $end_time
    ]);

    if ($check->rowCount() > 0) {
        $errors[] = "This venue is already booked for the selected date and time.";
    }

    // ✅ Return errors if any
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: book_venue.php?id=" . urlencode($venue_id));
        exit;
    }

    // ✅ Save Booking
    $stmt = $conn->prepare("INSERT INTO bookings 
        (user_id, venue_id, full_name, email, phone, event_name, event_date, start_time, end_time, event_details, department, status)
        VALUES 
        (:user_id, :venue_id, :full_name, :email, :phone, :event_name, :event_date, :start_time, :end_time, :event_details, :department, 'pending')");

    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':venue_id', $venue_id);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':event_name', $event_name);
    $stmt->bindParam(':event_date', $event_date);
    $stmt->bindParam(':start_time', $start_time);
    $stmt->bindParam(':end_time', $end_time);
    $stmt->bindParam(':event_details', $event_details);
    $stmt->bindParam(':department', $department);


    if ($stmt->execute()) {
        $booking_id = $conn->lastInsertId();
        $_SESSION['success'] = "Booking successful! We will notify you once approved.";
        $_SESSION['new_booking_id'] = $booking_id;
        header("Location: my_bookings.php?new_booking=" . $booking_id);
        exit;
    } else {
        $_SESSION['error'] = "Failed to process booking. Please try again.";
        header("Location: book_venue.php?id=" . urlencode($venue_id));
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: index.php");
    exit;
}
?>
