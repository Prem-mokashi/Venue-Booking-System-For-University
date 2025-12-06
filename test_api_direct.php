<?php
echo "<h2>Testing API Direct Access</h2>";

// Test 1: Include the API file directly
echo "<h3>Test 1: Direct Include</h3>";
ob_start();
include 'api/get_bookings.php';
$output = ob_get_clean();
echo "<pre>Output: " . htmlspecialchars($output) . "</pre>";

// Test 2: Use curl to test HTTP access
echo "<h3>Test 2: HTTP Request</h3>";
$url = 'http://localhost/venue-booking-system/api/get_bookings.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<pre>Response: " . htmlspecialchars($response) . "</pre>";

// Test 3: Check if there are any PHP errors
echo "<h3>Test 3: Error Check</h3>";
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'api/config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'approved'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ Database query successful: " . $result['count'] . " approved bookings</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>