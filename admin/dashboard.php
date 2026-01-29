<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total employees
$result = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
$stats['total_employees'] = $result->fetch_assoc()['count'];

// Total assets
$result = $conn->query("SELECT COUNT(*) as count FROM assets");
$stats['total_assets'] = $result->fetch_assoc()['count'];

// Assigned assets
$result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'assigned'");
$stats['assigned_assets'] = $result->fetch_assoc()['count'];

// Available assets
$result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'available'");
$stats['available_assets'] = $result->fetch_assoc()['count'];

// Asset categories
$result = $conn->query("SELECT COUNT(*) as count FROM asset_categories WHERE status = 'active'");
$stats['categories'] = $result->fetch_assoc()['count'];

// Recent assignments
$recent_assignments = [];
$query = "SELECT aa.*, e.name as employee_name, e.employee_id as emp_id, 
          a.asset_tag, ac.category_name
          FROM asset_assignments aa
          JOIN employees e ON aa.employee_id = e.id
          JOIN assets a ON aa.asset_id = a.id
          JOIN asset_categories ac ON a.category_id = ac.id
          WHERE aa.status = 'active'
          ORDER BY aa.created_at DESC
          LIMIT 10";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $recent_assignments[] = $row;
}

$conn->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Employees</h6>
                        <h2 class="mb-0"><?php echo $stats['total_employees']; ?></h2>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Assets</h6>
                        <h2 class="mb-0"><?php echo $stats['total_assets']; ?></h2>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-laptop fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Assigned Assets</h6>
                        <h2 class="mb-0"><?php echo $stats['assigned_assets']; ?></h2>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-handshake fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Available Assets</h6>
                        <h2 class="mb-0"><?php echo $stats['available_assets']; ?></h2>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-box fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Asset Categories</h6>
                        <h2 class="mb-0"><?php echo $stats['categories']; ?></h2>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-list fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Assignments -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Asset Assignments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_assignments)): ?>
                    <p class="text-muted text-center mb-0">No recent assignments found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Employee ID</th>
                                    <th>Asset Tag</th>
                                    <th>Category</th>
                                    <th>Date Issued</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['employee_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['emp_id']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['asset_tag']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['category_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($assignment['date_issued'])); ?></td>
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
