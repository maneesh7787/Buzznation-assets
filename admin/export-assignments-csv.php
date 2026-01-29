<?php
/**
 * Export Asset Assignments to CSV
 * Exports: Employee name, email, asset name, brand, serial number, issue date, acknowledge date
 * Multiple assets per employee are grouped together
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Query to get all active and historical assignments with employee and asset details
$query = "SELECT 
            e.name as employee_name,
            e.email as employee_email,
            ac.category_name as asset_name,
            a.brand,
            a.serial_number,
            aa.date_issued,
            aa.acknowledgement_date,
            aa.status,
            a.model
          FROM asset_assignments aa
          JOIN employees e ON aa.employee_id = e.id
          JOIN assets a ON aa.asset_id = a.id
          JOIN asset_categories ac ON a.category_id = ac.id
          ORDER BY e.name ASC, aa.date_issued DESC";

$result = $conn->query($query);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=asset_assignments_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 encoding (helps with Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Employee Name',
    'Email ID',
    'Asset Name',
    'Brand',
    'Model',
    'Serial Number',
    'Issue Date',
    'Acknowledge Date',
    'Status'
]);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['employee_name'] ?? 'N/A',
        $row['employee_email'] ?? 'N/A',
        $row['asset_name'] ?? 'N/A',
        $row['brand'] ?? 'N/A',
        $row['model'] ?? 'N/A',
        $row['serial_number'] ?? 'N/A',
        $row['date_issued'] ? date('Y-m-d', strtotime($row['date_issued'])) : 'N/A',
        $row['acknowledgement_date'] ? date('Y-m-d H:i:s', strtotime($row['acknowledgement_date'])) : 'N/A',
        ucfirst($row['status'] ?? 'N/A')
    ]);
}

fclose($output);
$conn->close();
exit();
?>
