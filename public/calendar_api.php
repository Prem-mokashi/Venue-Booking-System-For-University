<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../api/config.php';

// Clear any output buffer and set proper headers
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Only allow logged-in users to access calendar data
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Fetch all approved bookings with venue information
    $stmt = $conn->prepare("
        SELECT 
            b.event_date,
            b.start_time,
            b.end_time,
            b.event_name,
            b.full_name,
            b.department,
            v.name as venue_name
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        WHERE b.status = 'approved'
        ORDER BY b.event_date, b.start_time
    ");
    
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group bookings by date
    $groupedBookings = [];
    foreach ($bookings as $booking) {
        $date = $booking['event_date'];
        if (!isset($groupedBookings[$date])) {
            $groupedBookings[$date] = [];
        }
        $groupedBookings[$date][] = $booking;
    }
    
    // Clear buffer and send JSON response
    ob_clean();
    echo json_encode($groupedBookings);
    
} catch (Exception $e) {
    // Clear buffer and send error response
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>