<?php
include 'api/config.php';

echo "<h2>Testing Calendar API</h2>";

// Check if there are any bookings in the database
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings");
$stmt->execute();
$total = $stmt->fetch();
echo "<p>Total bookings in database: " . $total['total'] . "</p>";

// Check approved bookings
$stmt = $conn->prepare("SELECT COUNT(*) as approved FROM bookings WHERE status = 'approved'");
$stmt->execute();
$approved = $stmt->fetch();
echo "<p>Approved bookings: " . $approved['approved'] . "</p>";

// Get sample bookings
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.event_date,
        b.event_name,
        b.status,
        b.user_id,
        b.start_time,
        b.end_time,
        v.name as venue_name,
        u.username
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'approved'
    ORDER BY b.event_date ASC
    LIMIT 5
");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Sample Approved Bookings:</h3>";
if (count($bookings) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Event</th><th>Venue</th><th>Date</th><th>Time</th><th>User</th></tr>";
    foreach ($bookings as $booking) {
        echo "<tr>";
        echo "<td>" . $booking['id'] . "</td>";
        echo "<td>" . htmlspecialchars($booking['event_name']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['venue_name']) . "</td>";
        echo "<td>" . $booking['event_date'] . "</td>";
        echo "<td>" . $booking['start_time'] . " - " . $booking['end_time'] . "</td>";
        echo "<td>" . htmlspecialchars($booking['username']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No approved bookings found.</p>";
}

// Test the API directly
echo "<h3>API Response Test:</h3>";
$current_user_id = 1; // Simulate a user session

// Format bookings by date (same as API)
$bookingsByDate = [];
foreach ($bookings as $booking) {
    $date = $booking['event_date'];
    if (!isset($bookingsByDate[$date])) {
        $bookingsByDate[$date] = [];
    }
    
    $bookingsByDate[$date][] = [
        'id' => $booking['id'],
        'event' => $booking['event_name'],
        'venue' => $booking['venue_name'],
        'user' => ($current_user_id && $booking['user_id'] == $current_user_id) ? 'current' : 'other',
        'username' => $booking['username'],
        'status' => $booking['status'],
        'start_time' => $booking['start_time'],
        'end_time' => $booking['end_time'],
        'time' => date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time']))
    ];
}

echo "<pre>";
print_r($bookingsByDate);
echo "</pre>";

echo "<p><a href='api/get_bookings.php' target='_blank'>Test API directly</a></p>";
?>