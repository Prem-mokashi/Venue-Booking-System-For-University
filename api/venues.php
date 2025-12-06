<?php
include 'config.php';

$stmt = $conn->prepare("SELECT * FROM venues");
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($venues);
?>