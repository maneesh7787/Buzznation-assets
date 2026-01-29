<?php
/**
 * Audit Logging Helper
 * Provides functions to log all system activities for security and compliance
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Log an audit event
 * 
 * @param string $action The action being performed (e.g., 'login', 'create_asset', 'assign_asset')
 * @param string $entity_type The type of entity being acted upon (e.g., 'user', 'asset', 'assignment')
 * @param int $entity_id The ID of the entity being acted upon
 * @param mixed $details Additional details about the action (can be array, will be JSON encoded)
 * @return bool Success status
 */
function logAudit($action, $entity_type = null, $entity_id = null, $details = null) {
    try {
        $conn = getDBConnection();
        
        // Get user information
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'system';
        
        // Get IP address
        $ip_address = getClientIP();
        
        // Get user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if ($user_agent && strlen($user_agent) > 255) {
            $user_agent = substr($user_agent, 0, 255);
        }
        
        // Convert details to JSON if it's an array
        if (is_array($details)) {
            $details = json_encode($details);
        }
        
        // Insert audit log
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisss", $user_id, $username, $action, $entity_type, $entity_id, $details, $ip_address, $user_agent);
        $result = $stmt->execute();
        
        $stmt->close();
        $conn->close();
        
        return $result;
    } catch (Exception $e) {
        // Log to error log but don't fail the main operation
        error_log("Audit log failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get client IP address
 * Handles various proxy scenarios
 * 
 * @return string Client IP address
 */
function getClientIP() {
    $ip_keys = array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Get formatted action description for display
 * 
 * @param string $action The action code
 * @return string Human-readable action description
 */
function getActionDescription($action) {
    $descriptions = [
        'login' => 'User Login',
        'logout' => 'User Logout',
        'login_failed' => 'Failed Login Attempt',
        
        'create_asset' => 'Create Asset',
        'update_asset' => 'Update Asset',
        'delete_asset' => 'Delete Asset',
        
        'create_assignment' => 'Assign Asset',
        'return_asset' => 'Return Asset',
        'swap_asset' => 'Swap Asset',
        
        'create_employee' => 'Add Employee',
        'update_employee' => 'Update Employee',
        'delete_employee' => 'Delete Employee',
        'change_employee_status' => 'Change Employee Status',
        'change_employee_password' => 'Change Employee Password',
        
        'create_category' => 'Add Category',
        'update_category' => 'Update Category',
        'delete_category' => 'Delete Category',
        
        'submit_asset_request' => 'Submit Asset Request',
        'approve_asset_request' => 'Approve Asset Request',
        'reject_asset_request' => 'Reject Asset Request',
        
        'export_assets' => 'Export Assets',
        'export_assignments' => 'Export Assignments',
        'export_audit_logs' => 'Export Audit Logs',
        
        'change_password' => 'Change Password',
        'update_profile' => 'Update Profile',
    ];
    
    return $descriptions[$action] ?? ucfirst(str_replace('_', ' ', $action));
}
?>
