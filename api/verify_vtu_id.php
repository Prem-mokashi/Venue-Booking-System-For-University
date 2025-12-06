<?php
header('Content-Type: application/json');
include 'excel_reader.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$vtu_id = trim($input['vtu_id'] ?? '');
$user_type = trim($input['user_type'] ?? '');

// Validate input
if (empty($name) || empty($vtu_id) || empty($user_type)) {
    echo json_encode([
        'valid' => false,
        'error' => 'All fields are required'
    ]);
    exit;
}

if (!in_array($user_type, ['student', 'staff'])) {
    echo json_encode([
        'valid' => false,
        'error' => 'Invalid user type'
    ]);
    exit;
}

try {
    $result = VTUExcelReader::verifyVTUID($name, $vtu_id, $user_type);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'error' => 'Verification system error: ' . $e->getMessage()
    ]);
}
?>