<?php
/* 1. ALWAYS FIRST – loads $conn */
require '../api/config.php';

/* 2. Auth check */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* 3. Get filter parameters */
$venueFilter = $_GET['venue'] ?? '';
$fromMonth = $_GET['from'] ?? '';
$toMonth = $_GET['to'] ?? '';

/* 4. Build WHERE conditions for filtering */
$whereConditions = ['1=1'];
$params = [];

if ($venueFilter) {
    $whereConditions[] = "v.name = ?";
    $params[] = $venueFilter;
}

if ($fromMonth) {
    $whereConditions[] = "DATE_FORMAT(b.event_date, '%Y-%m') >= ?";
    $params[] = $fromMonth;
}

if ($toMonth) {
    $whereConditions[] = "DATE_FORMAT(b.event_date, '%Y-%m') <= ?";
    $params[] = $toMonth;
}

$whereClause = implode(' AND ', $whereConditions);

/* 5. Venue Statistics with Utilization */
$venueQuery = "
    SELECT 
        v.name, 
        v.capacity,
        COUNT(b.id) as booking_count,
        ROUND((COUNT(b.id) / NULLIF(v.capacity, 0)) * 100, 2) as utilization_rate
    FROM venues v 
    LEFT JOIN bookings b ON v.id = b.venue_id AND $whereClause
    GROUP BY v.id, v.name, v.capacity
    ORDER BY booking_count DESC
";

$stmt = $conn->prepare($venueQuery);
$stmt->execute($params);
$venueStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 6. Monthly Booking Trends */
$monthlyQuery = "
    SELECT 
        DATE_FORMAT(b.event_date, '%Y-%m') as month,
        COUNT(*) as bookings
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    WHERE $whereClause
    AND b.event_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
";

$stmt = $conn->prepare($monthlyQuery);
$stmt->execute($params);
$monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 7. Success Rate Trends */
$successQuery = "
    SELECT 
        DATE_FORMAT(b.created_at, '%Y-%m') as month,
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
        ROUND((SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    WHERE $whereClause
    AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    HAVING total_bookings > 0
    ORDER BY month
";

$stmt = $conn->prepare($successQuery);
$stmt->execute($params);
$successRate = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 8. Peak Hours Analysis */
$timeQuery = "
    SELECT 
        HOUR(STR_TO_DATE(b.start_time, '%H:%i')) as hour,
        COUNT(*) as booking_count
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    WHERE $whereClause
    AND b.start_time IS NOT NULL 
    AND b.start_time != ''
    GROUP BY hour
    HAVING hour IS NOT NULL
    ORDER BY hour
";

$stmt = $conn->prepare($timeQuery);
$stmt->execute($params);
$timeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 9. Top 10 Most Active Users */
$usersQuery = "
    SELECT 
        u.full_name as name,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings
    FROM users u
    JOIN bookings b ON u.id = b.user_id
    JOIN venues v ON b.venue_id = v.id
    WHERE $whereClause
    GROUP BY u.id, u.full_name
    ORDER BY total_bookings DESC
    LIMIT 10
";

$stmt = $conn->prepare($usersQuery);
$stmt->execute($params);
$topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 10. Advance Booking Patterns */
$advanceQuery = "
    SELECT 
        CASE 
            WHEN DATEDIFF(b.event_date, DATE(b.created_at)) <= 1 THEN 'Same/Next Day'
            WHEN DATEDIFF(b.event_date, DATE(b.created_at)) <= 7 THEN '1-7 Days'
            WHEN DATEDIFF(b.event_date, DATE(b.created_at)) <= 14 THEN '1-2 Weeks'
            WHEN DATEDIFF(b.event_date, DATE(b.created_at)) <= 30 THEN '2-4 Weeks'
            ELSE 'Over 1 Month'
        END as advance_period,
        COUNT(*) as booking_count
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    WHERE $whereClause
    AND b.event_date >= DATE(b.created_at)
    GROUP BY advance_period
    ORDER BY FIELD(advance_period, 'Same/Next Day', '1-7 Days', '1-2 Weeks', '2-4 Weeks', 'Over 1 Month')
";

$stmt = $conn->prepare($advanceQuery);
$stmt->execute($params);
$advanceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 11. Return JSON response */
header('Content-Type: application/json');
echo json_encode([
    'venueStats' => $venueStats,
    'monthlyStats' => $monthlyStats,
    'successRate' => $successRate,
    'timeStats' => $timeStats,
    'topUsers' => $topUsers,
    'advanceStats' => $advanceStats,
    'filters' => [
        'venue' => $venueFilter,
        'from' => $fromMonth,
        'to' => $toMonth
    ]
]);
?>