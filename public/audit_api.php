<?php
require '../api/config.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $query = "SELECT al.*, u.username as admin_name 
              FROM audit_logs al 
              LEFT JOIN users u ON al.admin_id = u.id 
              WHERE 1";
    
    $params = [];
    
    // Filter by admin
    if (isset($_GET['admin_id']) && $_GET['admin_id'] !== '') {
        $query .= " AND al.admin_id = ?";
        $params[] = $_GET['admin_id'];
    }
    
    // Filter by date range
    if (isset($_GET['from_date']) && $_GET['from_date'] !== '') {
        $query .= " AND DATE(al.created_at) >= ?";
        $params[] = $_GET['from_date'];
    }
    
    if (isset($_GET['to_date']) && $_GET['to_date'] !== '') {
        $query .= " AND DATE(al.created_at) <= ?";
        $params[] = $_GET['to_date'];
    }
    
    $query .= " ORDER BY al.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get list of admins for filter dropdown
    $adminQuery = "SELECT DISTINCT u.id, u.username as name 
                   FROM audit_logs al 
                   JOIN users u ON al.admin_id = u.id 
                   ORDER BY u.username";
    $adminStmt = $conn->prepare($adminQuery);
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'logs' => $logs,
        'admins' => $admins
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>