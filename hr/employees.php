<?php
$page_title = 'Employees - HR';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('hr');

$conn = getDBConnection();

// Get all employees
$employees = [];
$result = $conn->query("SELECT * FROM employees ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

$conn->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-users"></i> Employees</h2>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Employee List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Joining Date</th>
                        <th>Location</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($emp['date_of_joining'])); ?></td>
                            <td><?php echo htmlspecialchars($emp['work_location']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($emp['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($emp['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
