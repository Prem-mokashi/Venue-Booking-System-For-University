<?php
$host = 'localhost';
$db   = 'venue_booking';   // replace with your actual database name
$user = 'root';
$pass = 'root';                     // enter your MySQL password here if you have one
$port = '3306';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}
?>
