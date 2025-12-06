<?php
// Prevent any output before JSON
ob_start();

session_start();
include 'config.php';

// Clear any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Allow access for both logged in users and guests (for calendar viewing)
$current_user_id = $_SESSION['user_id'] ?? null;

$user_id = $current_user_id;

try {
    // Get all approved bookings for the calendar
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
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format bookings by date
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
            'user' => ($user_id && $booking['user_id'] == $user_id) ? 'current' : 'other',
            'username' => $booking['username'],
            'status' => $booking['status'],
            'start_time' => $booking['start_time'],
            'end_time' => $booking['end_time'],
            'time' => date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookingsByDate
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>