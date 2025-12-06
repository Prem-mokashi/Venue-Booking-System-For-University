<?php
/**
 * Enhanced Audit Helper Functions
 * Comprehensive audit logging with improved performance and security
 */

class AuditLogger {
    private $conn;
    private $batchLogs = [];
    private $batchSize = 10;
    
    public function __construct($connection) {
        $this->conn = $connection;
        
        // Initialize database if needed
        $this->initializeAuditTable();
    }
    
    /**
     * Initialize audit logs table with proper indexing
     */
    private function initializeAuditTable() {
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT,
                action VARCHAR(255) NOT NULL,
                booking_id INT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                session_id VARCHAR(255),
                request_method VARCHAR(10),
                request_uri TEXT,
                response_code INT,
                execution_time DECIMAL(10,4),
                memory_usage INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_admin_id (admin_id),
                INDEX idx_booking_id (booking_id),
                INDEX idx_created_at (created_at),
                INDEX idx_action (action),
                INDEX idx_ip_address (ip_address),
                INDEX idx_composite (admin_id, created_at, action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->conn->exec($sql);
        } catch (Exception $e) {
            error_log("Failed to initialize audit table: " . $e->getMessage());
        }
    }
    
    /**
     * Enhanced audit logging with comprehensive context
     */
    public function log($adminId, $action, $bookingId = null, $details = null, $additionalContext = []) {
        try {
            $logEntry = [
                'admin_id' => $adminId,
                'action' => $action,
                'booking_id' => $bookingId,
                'details' => $details,
                'ip_address' => $this->getClientIpAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'session_id' => session_id(),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'response_code' => http_response_code() ?: 200,
                'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                'memory_usage' => memory_get_peak_usage(true),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add additional context if provided
            if (!empty($additionalContext)) {
                $logEntry['details'] = json_encode(array_merge(
                    ['message' => $details],
                    $additionalContext
                ));
            }
            
            // Add to batch for performance
            $this->batchLogs[] = $logEntry;
            
            // Flush batch if it reaches the limit
            if (count($this->batchLogs) >= $this->batchSize) {
                $this->flushBatch();
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Flush batch logs to database
     */
    public function flushBatch() {
        if (empty($this->batchLogs)) {
            return;
        }
        
        try {
            $sql = "INSERT INTO audit_logs (
                admin_id, action, booking_id, details, ip_address, user_agent,
                session_id, request_method, request_uri, response_code,
                execution_time, memory_usage, created_at
            ) VALUES ";
            
            $placeholders = [];
            $values = [];
            
            foreach ($this->batchLogs as $log) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $values = array_merge($values, array_values($log));
            }
            
            $sql .= implode(', ', $placeholders);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);
            
            // Clear batch
            $this->batchLogs = [];
            
        } catch (Exception $e) {
            error_log("Batch audit logging failed: " . $e->getMessage());
            $this->batchLogs = []; // Clear batch to prevent memory issues
        }
    }
    
    /**
     * Get the real client IP address
     */
    private function getClientIpAddress() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_CLIENT_IP',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Destructor to ensure batch is flushed
     */
    public function __destruct() {
        $this->flushBatch();
    }
}

// Global audit logger instance
$auditLogger = null;

function getAuditLogger($conn) {
    global $auditLogger;
    if (!$auditLogger) {
        $auditLogger = new AuditLogger($conn);
    }
    return $auditLogger;
}

// Enhanced logging functions with better context
function logAuditAction($conn, $adminId, $action, $bookingId = null, $details = null, $context = []) {
    $logger = getAuditLogger($conn);
    return $logger->log($adminId, $action, $bookingId, $details, $context);
}

function logLogin($conn, $adminId, $adminName, $successful = true) {
    $action = $successful ? 'Admin Login Success' : 'Admin Login Failed';
    $details = $successful ? 
        "User {$adminName} successfully logged into the admin dashboard" :
        "Failed login attempt for user {$adminName}";
    
    $context = [
        'event_type' => 'authentication',
        'success' => $successful,
        'admin_name' => $adminName,
        'login_time' => date('c'),
        'user_agent_parsed' => [
            'browser' => getBrowserName($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'platform' => getPlatformName($_SERVER['HTTP_USER_AGENT'] ?? '')
        ]
    ];
    
    return logAuditAction($conn, $adminId, $action, null, $details, $context);
}

function logLogout($conn, $adminId, $adminName) {
    $details = "User {$adminName} logged out of the admin dashboard";
    $context = [
        'event_type' => 'authentication',
        'admin_name' => $adminName,
        'logout_time' => date('c'),
        'session_duration' => isset($_SESSION['login_time']) ? 
            time() - $_SESSION['login_time'] : 'unknown'
    ];
    
    return logAuditAction($conn, $adminId, 'Admin Logout', null, $details, $context);
}

function logBookingStatusChange($conn, $adminId, $bookingId, $oldStatus, $newStatus, $venueName, $eventDate, $eventName = '', $remarks = '') {
    $details = "Status changed from '{$oldStatus}' to '{$newStatus}' for booking #{$bookingId} - {$venueName} on {$eventDate}";
    if ($remarks) {
        $details .= ". Remarks: {$remarks}";
    }
    
    $context = [
        'event_type' => 'booking_management',
        'booking_id' => $bookingId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'venue_name' => $venueName,
        'event_name' => $eventName,
        'event_date' => $eventDate,
        'admin_remarks' => $remarks,
        'change_timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, "Updated booking status to " . ucfirst($newStatus), $bookingId, $details, $context);
}

function logVenueAction($conn, $adminId, $action, $venueId, $venueName, $additionalData = []) {
    $details = "Venue action: {$action} on venue #{$venueId} - {$venueName}";
    
    $context = array_merge([
        'event_type' => 'venue_management',
        'venue_id' => $venueId,
        'venue_name' => $venueName,
        'action_type' => $action,
        'timestamp' => date('c')
    ], $additionalData);
    
    return logAuditAction($conn, $adminId, $action, null, $details, $context);
}

function logReportGeneration($conn, $adminId, $reportType, $filters = [], $recordCount = 0) {
    $details = "Generated {$reportType} report with {$recordCount} records";
    if (!empty($filters)) {
        $details .= " using filters: " . implode(', ', array_map(function($k, $v) {
            return "{$k}: {$v}";
        }, array_keys($filters), $filters));
    }
    
    $context = [
        'event_type' => 'report_generation',
        'report_type' => $reportType,
        'filters_applied' => $filters,
        'record_count' => $recordCount,
        'generation_time' => date('c'),
        'file_size' => 0 // Can be updated after file generation
    ];
    
    return logAuditAction($conn, $adminId, 'Generated Report', null, $details, $context);
}

function logDataExport($conn, $adminId, $exportType, $recordCount, $filters = [], $fileSize = 0) {
    $details = "Exported {$recordCount} records as {$exportType}";
    if (!empty($filters)) {
        $filterStr = implode(', ', array_map(function($k, $v) {
            return "{$k}: {$v}";
        }, array_keys($filters), $filters));
        $details .= " with filters: {$filterStr}";
    }
    
    $context = [
        'event_type' => 'data_export',
        'export_type' => $exportType,
        'record_count' => $recordCount,
        'filters_applied' => $filters,
        'file_size_bytes' => $fileSize,
        'export_time' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, 'Data Export', null, $details, $context);
}

function logBulkAction($conn, $adminId, $action, $affectedRecords, $criteria = []) {
    $details = "Performed bulk action '{$action}' on {$affectedRecords} records";
    if (!empty($criteria)) {
        $details .= " matching criteria: " . json_encode($criteria);
    }
    
    $context = [
        'event_type' => 'bulk_operation',
        'action_type' => $action,
        'affected_count' => $affectedRecords,
        'selection_criteria' => $criteria,
        'execution_time' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, 'Bulk Action', null, $details, $context);
}

function logSystemAction($conn, $adminId, $action, $details = null, $context = []) {
    $defaultContext = [
        'event_type' => 'system_action',
        'timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, $action, null, $details, array_merge($defaultContext, $context));
}

function logSecurityEvent($conn, $adminId, $eventType, $severity, $details, $context = []) {
    $action = "Security Event: {$eventType}";
    $enhancedDetails = "[{$severity}] {$details}";
    
    $securityContext = array_merge([
        'event_type' => 'security',
        'security_event' => $eventType,
        'severity_level' => $severity,
        'timestamp' => date('c'),
        'requires_attention' => in_array($severity, ['high', 'critical'])
    ], $context);
    
    return logAuditAction($conn, $adminId, $action, null, $enhancedDetails, $securityContext);
}

function logApiAccess($conn, $adminId, $endpoint, $method, $responseCode, $responseTime = null) {
    $details = "API access: {$method} {$endpoint} - Response: {$responseCode}";
    if ($responseTime) {
        $details .= " - Time: {$responseTime}ms";
    }
    
    $context = [
        'event_type' => 'api_access',
        'endpoint' => $endpoint,
        'http_method' => $method,
        'response_code' => $responseCode,
        'response_time_ms' => $responseTime,
        'timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, 'API Access', null, $details, $context);
}

function logConfigurationChange($conn, $adminId, $configType, $oldValue, $newValue, $affectedSystem = '') {
    $details = "Configuration changed: {$configType}";
    if ($affectedSystem) {
        $details .= " in {$affectedSystem}";
    }
    
    $context = [
        'event_type' => 'configuration_change',
        'config_type' => $configType,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'affected_system' => $affectedSystem,
        'change_timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, 'Configuration Change', null, $details, $context);
}

// Specialized logging functions for different modules
function logEmailSent($conn, $adminId, $bookingId, $recipient, $emailType, $success = true) {
    $action = $success ? 'Email Sent Successfully' : 'Email Send Failed';
    $details = "Email '{$emailType}' sent to {$recipient} for booking #{$bookingId}";
    
    $context = [
        'event_type' => 'communication',
        'communication_type' => 'email',
        'recipient' => $recipient,
        'email_type' => $emailType,
        'success' => $success,
        'timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, $action, $bookingId, $details, $context);
}

function logUserInteraction($conn, $adminId, $interactionType, $targetUserId, $details = '') {
    $action = "User Interaction: {$interactionType}";
    
    $context = [
        'event_type' => 'user_interaction',
        'interaction_type' => $interactionType,
        'target_user_id' => $targetUserId,
        'timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, $action, null, $details, $context);
}

// Query functions for audit data retrieval
function getRecentAudits($conn, $limit = 10, $adminId = null) {
    try {
        $query = "
            SELECT al.*, u.username as admin_name 
            FROM audit_logs al 
            LEFT JOIN users u ON al.admin_id = u.id 
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($adminId) {
            $query .= " AND al.admin_id = ?";
            $params[] = $adminId;
        }
        
        $query .= " ORDER BY al.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get recent audits: " . $e->getMessage());
        return [];
    }
}

function getAuditStats($conn, $days = 30, $adminId = null) {
    try {
        $query = "
            SELECT 
                COUNT(*) as total_actions,
                COUNT(DISTINCT admin_id) as active_admins,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                COUNT(DISTINCT booking_id) as affected_bookings,
                AVG(execution_time) as avg_execution_time,
                AVG(memory_usage) as avg_memory_usage,
                action,
                COUNT(*) as action_count
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $params = [$days];
        
        if ($adminId) {
            $query .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        
        $query .= " GROUP BY action ORDER BY action_count DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get audit stats: " . $e->getMessage());
        return [];
    }
}

function getSecurityEvents($conn, $days = 7, $severity = null) {
    try {
        $query = "
            SELECT al.*, u.full_name as admin_name
            FROM audit_logs al 
            LEFT JOIN users u ON al.admin_id = u.id 
            WHERE al.action LIKE 'Security Event:%'
            AND al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $params = [$days];
        
        if ($severity) {
            $query .= " AND al.details LIKE ?";
            $params[] = "[{$severity}]%";
        }
        
        $query .= " ORDER BY al.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get security events: " . $e->getMessage());
        return [];
    }
}

function getAdminActivity($conn, $adminId, $days = 30) {
    try {
        $query = "
            SELECT 
                DATE(created_at) as activity_date,
                COUNT(*) as action_count,
                COUNT(DISTINCT action) as unique_actions,
                MIN(created_at) as first_action,
                MAX(created_at) as last_action,
                AVG(execution_time) as avg_response_time
            FROM audit_logs 
            WHERE admin_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY activity_date DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$adminId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get admin activity: " . $e->getMessage());
        return [];
    }
}

function getBookingAuditTrail($conn, $bookingId) {
    try {
        $query = "
            SELECT al.*, u.full_name as admin_name, b.event_name, v.name as venue_name
            FROM audit_logs al 
            LEFT JOIN users u ON al.admin_id = u.id 
            LEFT JOIN bookings b ON al.booking_id = b.id
            LEFT JOIN venues v ON b.venue_id = v.id
            WHERE al.booking_id = ?
            ORDER BY al.created_at ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get booking audit trail: " . $e->getMessage());
        return [];
    }
}

// Performance monitoring functions
function logPerformanceMetrics($conn, $adminId, $operation, $executionTime, $memoryUsage, $recordsProcessed = 0) {
    $details = "Performance: {$operation} - Time: {$executionTime}s, Memory: " . formatBytes($memoryUsage) . ", Records: {$recordsProcessed}";
    
    $context = [
        'event_type' => 'performance_monitoring',
        'operation' => $operation,
        'execution_time_seconds' => $executionTime,
        'memory_usage_bytes' => $memoryUsage,
        'records_processed' => $recordsProcessed,
        'timestamp' => date('c')
    ];
    
    return logAuditAction($conn, $adminId, 'Performance Metrics', null, $details, $context);
}

// Maintenance and cleanup functions
function cleanupOldAudits($conn, $keepDays = 365) {
    try {
        $stmt = $conn->prepare("
            DELETE FROM audit_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$keepDays]);
        
        $deletedCount = $stmt->rowCount();
        
        // Log the cleanup action
        logSystemAction($conn, null, 'Audit Cleanup', "Cleaned up {$deletedCount} old audit records older than {$keepDays} days");
        
        return $deletedCount;
    } catch (Exception $e) {
        error_log("Failed to cleanup old audits: " . $e->getMessage());
        return false;
    }
}

function optimizeAuditTable($conn) {
    try {
        $conn->exec("OPTIMIZE TABLE audit_logs");
        
        // Get table size info
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total_records,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'audit_logs'
        ");
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        logSystemAction($conn, null, 'Table Optimization', "Optimized audit_logs table - {$info['total_records']} records, {$info['size_mb']} MB");
        
        return $info;
    } catch (Exception $e) {
        error_log("Failed to optimize audit table: " . $e->getMessage());
        return false;
    }
}

// Alert and monitoring functions
function checkAuditHealth($conn) {
    try {
        $issues = [];
        
        // Check for gaps in logging
        $stmt = $conn->query("
            SELECT COUNT(*) as count 
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $recentCount = $stmt->fetchColumn();
        
        if ($recentCount < 10) {
            $issues[] = "Low audit activity: only {$recentCount} logs in last 24 hours";
        }
        
        // Check for suspicious patterns
        $stmt = $conn->query("
            SELECT admin_id, COUNT(*) as action_count 
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY admin_id 
            HAVING action_count > 100
        ");
        $highActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($highActivity as $activity) {
            $issues[] = "High activity: Admin ID {$activity['admin_id']} performed {$activity['action_count']} actions in the last hour";
        }
        
        // Check table size
        $stmt = $conn->query("
            SELECT COUNT(*) as total_records
            FROM audit_logs
        ");
        $totalRecords = $stmt->fetchColumn();
        
        if ($totalRecords > 1000000) {
            $issues[] = "Large audit table: {$totalRecords} records - consider archiving old data";
        }
        
        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'total_records' => $totalRecords,
            'recent_activity' => $recentCount,
            'check_time' => date('c')
        ];
        
    } catch (Exception $e) {
        error_log("Audit health check failed: " . $e->getMessage());
        return [
            'healthy' => false,
            'issues' => ['Health check failed: ' . $e->getMessage()],
            'check_time' => date('c')
        ];
    }
}

// Utility functions
function getBrowserName($userAgent) {
    if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
    if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
    if (strpos($userAgent, 'Safari') !== false) return 'Safari';
    if (strpos($userAgent, 'Edge') !== false) return 'Edge';
    if (strpos($userAgent, 'Opera') !== false) return 'Opera';
    return 'Unknown';
}

function getPlatformName($userAgent) {
    if (strpos($userAgent, 'Windows') !== false) return 'Windows';
    if (strpos($userAgent, 'Macintosh') !== false) return 'macOS';
    if (strpos($userAgent, 'Linux') !== false) return 'Linux';
    if (strpos($userAgent, 'Android') !== false) return 'Android';
    if (strpos($userAgent, 'iPhone') !== false) return 'iOS';
    return 'Unknown';
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;
    
    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }
    
    return round($size, $precision) . ' ' . $units[$index];
}

function maskSensitiveData($data, $fieldsToMask = ['password', 'token', 'key', 'secret']) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $fieldsToMask)) {
                $data[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $data[$key] = maskSensitiveData($value, $fieldsToMask);
            }
        }
    }
    return $data;
}

// Advanced query functions for reporting
function getAuditSummaryByDateRange($conn, $startDate, $endDate, $groupBy = 'day') {
    try {
        $dateFormat = match($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };
        
        $query = "
            SELECT 
                DATE_FORMAT(created_at, ?) as period,
                COUNT(*) as total_actions,
                COUNT(DISTINCT admin_id) as unique_admins,
                COUNT(DISTINCT booking_id) as unique_bookings,
                AVG(execution_time) as avg_execution_time
            FROM audit_logs 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY period
            ORDER BY period DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$dateFormat, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get audit summary: " . $e->getMessage());
        return [];
    }
}

function getTopActions($conn, $days = 30, $limit = 10) {
    try {
        $query = "
            SELECT 
                action,
                COUNT(*) as frequency,
                COUNT(DISTINCT admin_id) as admin_count,
                COUNT(DISTINCT booking_id) as booking_count,
                MAX(created_at) as last_occurrence,
                AVG(execution_time) as avg_execution_time
            FROM audit_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action
            ORDER BY frequency DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get top actions: " . $e->getMessage());
        return [];
    }
}

function getFailedActions($conn, $days = 7) {
    try {
        $query = "
            SELECT al.*, u.full_name as admin_name
            FROM audit_logs al
            LEFT JOIN users u ON al.admin_id = u.id
            WHERE (al.action LIKE '%failed%' 
                   OR al.action LIKE '%error%' 
                   OR al.response_code >= 400)
            AND al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY al.created_at DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get failed actions: " . $e->getMessage());
        return [];
    }
}

// Export functions
function exportAuditToCSV($conn, $filters = [], $filename = null) {
    if (!$filename) {
        $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
    }
    
    try {
        // Build query with filters
        $query = "
            SELECT 
                al.id,
                al.created_at,
                COALESCE(u.full_name, 'System') as admin_name,
                al.action,
                al.booking_id,
                b.event_name,
                v.name as venue_name,
                al.details,
                al.ip_address,
                al.response_code,
                al.execution_time
            FROM audit_logs al
            LEFT JOIN users u ON al.admin_id = u.id
            LEFT JOIN bookings b ON al.booking_id = b.id
            LEFT JOIN venues v ON b.venue_id = v.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters (similar to main query)
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                switch ($key) {
                    case 'admin_id':
                        $query .= " AND al.admin_id = ?";
                        $params[] = $value;
                        break;
                    case 'from_date':
                        $query .= " AND DATE(al.created_at) >= ?";
                        $params[] = $value;
                        break;
                    case 'to_date':
                        $query .= " AND DATE(al.created_at) <= ?";
                        $params[] = $value;
                        break;
                }
            }
        }
        
        $query .= " ORDER BY al.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Date/Time', 'Admin', 'Action', 'Booking ID', 
            'Event Name', 'Venue', 'Details', 'IP Address', 
            'Response Code', 'Execution Time (s)'
        ]);
        
        // CSV data
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['admin_name'],
                $row['action'],
                $row['booking_id'] ?? '',
                $row['event_name'] ?? '',
                $row['venue_name'] ?? '',
                $row['details'] ?? '',
                $row['ip_address'],
                $row['response_code'] ?? '',
                $row['execution_time'] ?? ''
            ]);
        }
        
        fclose($output);
        return true;
        
    } catch (Exception $e) {
        error_log("CSV export failed: " . $e->getMessage());
        return false;
    }
}

// Scheduled maintenance function
function performScheduledMaintenance($conn) {
    try {
        $results = [];
        
        // Clean up old logs (keep 2 years)
        $cleaned = cleanupOldAudits($conn, 730);
        $results['cleanup'] = $cleaned;
        
        // Optimize table
        $optimized = optimizeAuditTable($conn);
        $results['optimization'] = $optimized;
        
        // Check health
        $health = checkAuditHealth($conn);
        $results['health_check'] = $health;
        
        // Log maintenance completion
        logSystemAction($conn, null, 'Scheduled Maintenance', 
            "Audit maintenance completed - Cleaned: {$cleaned} records, Health: " . ($health['healthy'] ? 'Good' : 'Issues found'));
        
        return $results;
    } catch (Exception $e) {
        error_log("Scheduled maintenance failed: " . $e->getMessage());
        return false;
    }
}

// Security analysis functions
function detectAnomalousActivity($conn, $adminId = null, $hours = 24) {
    try {
        $query = "
            SELECT 
                admin_id,
                COUNT(*) as action_count,
                COUNT(DISTINCT ip_address) as ip_count,
                COUNT(DISTINCT action) as action_variety,
                MIN(created_at) as first_activity,
                MAX(created_at) as last_activity,
                AVG(execution_time) as avg_execution_time
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ";
        
        $params = [$hours];
        
        if ($adminId) {
            $query .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        
        $query .= "
            GROUP BY admin_id
            HAVING action_count > 50 
               OR ip_count > 3 
               OR action_variety > 10
            ORDER BY action_count DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $anomalies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log any anomalies found
        foreach ($anomalies as $anomaly) {
            logSecurityEvent($conn, $anomaly['admin_id'], 'Anomalous Activity Detected', 'medium',
                "Admin ID {$anomaly['admin_id']} performed {$anomaly['action_count']} actions from {$anomaly['ip_count']} IPs in {$hours} hours");
        }
        
        return $anomalies;
    } catch (Exception $e) {
        error_log("Anomaly detection failed: " . $e->getMessage());
        return [];
    }
}

// Initialize audit logger on file include
if (isset($conn)) {
    $auditLogger = new AuditLogger($conn);
}
?>