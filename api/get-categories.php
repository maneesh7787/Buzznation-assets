<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$conn = getDBConnection();

try {
    $result = $conn->query("SELECT id, category_name FROM asset_categories WHERE status = 'active' ORDER BY category_name");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch categories'
    ]);
}

$conn->close();
?>
