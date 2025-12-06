<?php
$host = 'localhost';
$db   = 'venue_booking';  // 👈 Your database name
$user = 'root';           // 👈 Your DB username (by default root for XAMPP)
$pass = '';               // 👈 Your DB password (empty by default on XAMPP)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // show errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch as array
    PDO::ATTR_EMULATE_PREPARES   => false,                 // use real prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
