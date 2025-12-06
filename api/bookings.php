<?php
include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'];
$venue_id = $data['venue_id'];
$booking_date = $data['booking_date'];

$stmt = $conn->prepare("INSERT INTO bookings (user_id, venue_id, booking_date, status) VALUES (?, ?, ?, 'pending')");
if ($stmt->execute([$user_id, $venue_id, $booking_date])) {
    echo json_encode(["message" => "Booking successful!"]);
} else {
    echo json_encode(["message" => "Booking failed."]);
}
?>