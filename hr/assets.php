<?php
$page_title = 'Assets - HR';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('hr');

$conn = getDBConnection();

// Get all assignments with employee and asset info
$assignments = [];
$query = "SELECT aa.*, 
          e.name as employee_name, e.employee_id as emp_id, e.department,
          a.asset_tag, a.brand, a.model, ac.category_name
          FROM asset_assignments aa
          JOIN employees e ON aa.employee_id = e.id
          JOIN assets a ON aa.asset_id = a.id
          JOIN asset_categories ac ON a.category_id = ac.id
          ORDER BY aa.created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}

$conn->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-laptop"></i> Asset Assignments</h2>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">All Asset Assignments</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Asset Tag</th>
                        <th>Category</th>
                        <th>Brand/Model</th>
                        <th>Date Issued</th>
                        <th>Date Returned</th>
                        <th>Condition (Issue)</th>
                        <th>Condition (Return)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assign): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assign['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($assign['emp_id']); ?></td>
                            <td><?php echo htmlspecialchars($assign['department']); ?></td>
                            <td><strong><?php echo htmlspecialchars($assign['asset_tag']); ?></strong></td>
                            <td><?php echo htmlspecialchars($assign['category_name']); ?></td>
                            <td>
                                <?php 
                                $brand_model = trim(($assign['brand'] ?? '') . ' ' . ($assign['model'] ?? ''));
                                echo htmlspecialchars($brand_model ?: 'N/A');
                                ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($assign['date_issued'])); ?></td>
                            <td>
                                <?php 
                                echo $assign['date_returned'] 
                                    ? date('M d, Y', strtotime($assign['date_returned'])) 
                                    : '<span class="text-muted">-</span>';
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo ucfirst($assign['condition_at_issue']); ?></span>
                            </td>
                            <td>
                                <?php if ($assign['condition_at_return']): ?>
                                    <span class="badge bg-info"><?php echo ucfirst($assign['condition_at_return']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'success',
                                    'returned' => 'secondary',
                                    'lost' => 'danger',
                                    'damaged' => 'warning'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $status_class[$assign['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($assign['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
