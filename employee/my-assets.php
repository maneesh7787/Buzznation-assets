<?php
$page_title = 'My Assets';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$conn = getDBConnection();

// Get employee info
$employee = null;
if (isset($_SESSION['employee_id'])) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['employee_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
}

// Get all assets (active and returned)
$all_assets = [];
if ($employee) {
    $query = "SELECT aa.*, a.asset_tag, a.brand, a.model, a.serial_number, 
              ac.category_name
              FROM asset_assignments aa
              JOIN assets a ON aa.asset_id = a.id
              JOIN asset_categories ac ON a.category_id = ac.id
              WHERE aa.employee_id = ?
              ORDER BY aa.date_issued DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_assets[] = $row;
    }
}

$conn->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-laptop"></i> My Assets</h2>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">All My Assets (Current & Historical)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($all_assets)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No assets have been assigned to you yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped data-table">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Category</th>
                            <th>Brand/Model</th>
                            <th>Serial Number</th>
                            <th>Date Issued</th>
                            <th>Date Returned</th>
                            <th>Condition (Issue)</th>
                            <th>Condition (Return)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_assets as $asset): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong></td>
                                <td><?php echo htmlspecialchars($asset['category_name']); ?></td>
                                <td>
                                    <?php 
                                    $brand_model = trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''));
                                    echo htmlspecialchars($brand_model ?: 'N/A');
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($asset['serial_number'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($asset['date_issued'])); ?></td>
                                <td>
                                    <?php 
                                    echo $asset['date_returned'] 
                                        ? date('M d, Y', strtotime($asset['date_returned'])) 
                                        : '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $condition_class = [
                                        'excellent' => 'success',
                                        'good' => 'info',
                                        'fair' => 'warning',
                                        'poor' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $condition_class[$asset['condition_at_issue']] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($asset['condition_at_issue']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($asset['condition_at_return']): ?>
                                        <span class="badge bg-<?php echo $condition_class[$asset['condition_at_return']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($asset['condition_at_return']); ?>
                                        </span>
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
                                    <span class="badge bg-<?php echo $status_class[$asset['status']] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($asset['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Acknowledgement Section -->
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Asset Responsibility Declaration</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <h6><strong>Important Information:</strong></h6>
            <ul class="mb-0">
                <li>You are responsible for all assets assigned to you</li>
                <li>Assets must be returned in good condition during exit or replacement</li>
                <li>Any loss or damage must be reported immediately to IT and HR</li>
                <li>Assets are company property and should be used for business purposes only</li>
                <li>Keep assets secure and do not share with unauthorized persons</li>
            </ul>
        </div>
        
        <?php if ($employee && !empty($all_assets)): ?>
            <p class="mb-0">
                <strong>Last Acknowledgement:</strong> 
                <?php 
                $latest = $all_assets[0];
                if ($latest['acknowledgement_date']) {
                    echo date('M d, Y H:i:s', strtotime($latest['acknowledgement_date']));
                } else {
                    echo '<span class="text-muted">Not acknowledged yet</span>';
                }
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
