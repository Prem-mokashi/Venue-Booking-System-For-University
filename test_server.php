<?php
echo "<h1>✅ Server is Working!</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

echo "<h2>Available Pages:</h2>";
echo "<ul>";
echo "<li><a href='public/index.php'>Home Page</a></li>";
echo "<li><a href='public/user_dashboard.php'>User Dashboard</a></li>";
echo "<li><a href='public/my_bookings.php'>My Bookings</a></li>";
echo "<li><a href='public/venues.php'>Venues</a></li>";
echo "<li><a href='public/login.php'>Login</a></li>";
echo "<li><a href='public/register_vtu.php'>Register</a></li>";
echo "</ul>";

echo "<h2>Project Structure Check:</h2>";
$files = [
    'public/index.php',
    'public/user_dashboard.php', 
    'public/my_bookings.php',
    'public/generate_booking_pdf.php',
    'api/config.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}
?>