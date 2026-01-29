<?php
/**
 * Export Audit Logs to CSV
 * Exports complete audit trail with applied filters
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
requireRole('admin');

$conn = getDBConnection();

// Log export action
logAudit('export_audit_logs', null, null, 'Exported audit logs to CSV');

// Get filter parameters (same as audit-logs.php)
$filter_action = $_GET['action'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_entity = $_GET['entity_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($filter_action) {
    $where_conditions[] = "action = ?";
    $params[] = $filter_action;
    $param_types .= 's';
}

if ($filter_user) {
    $where_conditions[] = "username = ?";
    $params[] = $filter_user;
    $param_types .= 's';
}

if ($filter_entity) {
    $where_conditions[] = "entity_type = ?";
    $params[] = $filter_entity;
    $param_types .= 's';
}

if ($filter_date_from) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
    $param_types .= 's';
}

if ($filter_date_to) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
    $param_types .= 's';
}

if ($search) {
    $where_conditions[] = "(username LIKE ? OR action LIKE ? OR details LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get all matching logs (no pagination for export)
$query = "SELECT * FROM audit_logs $where_clause ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    if ($param_types) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=audit_logs_' . date('Y-m-d_His') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 encoding (helps with Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Timestamp',
    'User ID',
    'Username',
    'Action',
    'Action Description',
    'Entity Type',
    'Entity ID',
    'Details',
    'IP Address',
    'User Agent'
]);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['created_at'],
        $row['user_id'] ?? '',
        $row['username'] ?? 'System',
        $row['action'],
        getActionDescription($row['action']),
        $row['entity_type'] ?? '',
        $row['entity_id'] ?? '',
        $row['details'] ?? '',
        $row['ip_address'] ?? '',
        $row['user_agent'] ?? ''
    ]);
}

fclose($output);
$conn->close();
exit();
?>
