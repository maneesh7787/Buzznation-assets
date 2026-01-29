<?php
$page_title = 'Employee Dashboard';
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

// Get assigned assets
$assigned_assets = [];
if ($employee) {
    $query = "SELECT aa.*, a.asset_tag, a.brand, a.model, a.serial_number, 
              ac.category_name
              FROM asset_assignments aa
              JOIN assets a ON aa.asset_id = a.id
              JOIN asset_categories ac ON a.category_id = ac.id
              WHERE aa.employee_id = ? AND aa.status = 'active'
              ORDER BY aa.date_issued DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_assets[] = $row;
    }
}

$conn->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> My Dashboard</h2>
    </div>
</div>

<!-- Employee Info Card -->
<?php if ($employee): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> My Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['name']); ?></p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                            <p><strong>Designation:</strong> <?php echo htmlspecialchars($employee['designation']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date of Joining:</strong> <?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?></p>
                            <p><strong>Work Location:</strong> <?php echo htmlspecialchars($employee['work_location']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Assets Assigned to Me</h6>
                        <h2 class="mb-0"><?php echo count($assigned_assets); ?></h2>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-laptop fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Assets -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> My Assigned Assets</h5>
            </div>
            <div class="card-body">
                <?php if (empty($assigned_assets)): ?>
                    <p class="text-muted text-center mb-0">No assets currently assigned to you.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Asset Tag</th>
                                    <th>Category</th>
                                    <th>Brand/Model</th>
                                    <th>Serial Number</th>
                                    <th>Date Issued</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_assets as $asset): ?>
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
                                            <span class="badge bg-success">Active</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
