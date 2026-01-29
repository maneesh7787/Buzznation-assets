<?php
$page_title = 'Asset Assignments';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign':
                $asset_id = intval($_POST['asset_id']);
                $employee_id = intval($_POST['employee_id']);
                $date_issued = sanitizeInput($_POST['date_issued']);
                $condition_at_issue = sanitizeInput($_POST['condition_at_issue']);
                $notes = sanitizeInput($_POST['notes']);
                
                try {
                    $conn->begin_transaction();
                    
                    // Create assignment
                    $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, employee_id, date_issued, condition_at_issue, notes, acknowledgement_date) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iisss", $asset_id, $employee_id, $date_issued, $condition_at_issue, $notes);
                    $stmt->execute();
                    
                    // Update asset status
                    $stmt = $conn->prepare("UPDATE assets SET status = 'assigned' WHERE id = ?");
                    $stmt->bind_param("i", $asset_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = 'Asset assigned successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error assigning asset: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'return':
                $assignment_id = intval($_POST['assignment_id']);
                $date_returned = sanitizeInput($_POST['date_returned']);
                $condition_at_return = sanitizeInput($_POST['condition_at_return']);
                $return_notes = sanitizeInput($_POST['return_notes']);
                
                try {
                    $conn->begin_transaction();
                    
                    // Get asset_id
                    $stmt = $conn->prepare("SELECT asset_id FROM asset_assignments WHERE id = ?");
                    $stmt->bind_param("i", $assignment_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $asset_id = $result->fetch_assoc()['asset_id'];
                    
                    // Update assignment
                    $stmt = $conn->prepare("UPDATE asset_assignments SET date_returned = ?, condition_at_return = ?, notes = CONCAT(COALESCE(notes, ''), ' | Return: ', ?), status = 'returned' WHERE id = ?");
                    $stmt->bind_param("sssi", $date_returned, $condition_at_return, $return_notes, $assignment_id);
                    $stmt->execute();
                    
                    // Update asset status
                    $stmt = $conn->prepare("UPDATE assets SET status = 'available', condition_status = ? WHERE id = ?");
                    $stmt->bind_param("si", $condition_at_return, $asset_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = 'Asset returned successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error processing return: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

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

// Get available assets for assignment
$available_assets = [];
$query = "SELECT a.*, c.category_name 
          FROM assets a 
          JOIN asset_categories c ON a.category_id = c.id 
          WHERE a.status = 'available'
          ORDER BY a.asset_tag";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $available_assets[] = $row;
}

// Get active employees for assignment
$active_employees = [];
$result = $conn->query("SELECT * FROM employees WHERE status = 'active' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $active_employees[] = $row;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-handshake"></i> Asset Assignments</h2>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Assignment List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
            <i class="fas fa-plus"></i> Assign Asset
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Asset Tag</th>
                        <th>Category</th>
                        <th>Brand/Model</th>
                        <th>Date Issued</th>
                        <th>Date Returned</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assign): ?>
                        <tr>
                            <td><?php echo $assign['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($assign['employee_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($assign['emp_id']); ?></small>
                            </td>
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
                            <td class="table-actions">
                                <?php if ($assign['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="returnAsset(<?php echo htmlspecialchars(json_encode($assign)); ?>)">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Asset Modal -->
<div class="modal fade" id="assignAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Asset to Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee *</label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($active_employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset *</label>
                            <select class="form-select" name="asset_id" required>
                                <option value="">Select Asset</option>
                                <?php foreach ($available_assets as $asset): ?>
                                    <option value="<?php echo $asset['id']; ?>">
                                        <?php echo htmlspecialchars($asset['asset_tag'] . ' - ' . $asset['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Issued *</label>
                            <input type="date" class="form-control" name="date_issued" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condition at Issue *</label>
                            <select class="form-select" name="condition_at_issue" required>
                                <option value="excellent">Excellent</option>
                                <option value="good" selected>Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Asset Modal -->
<div class="modal fade" id="returnAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="return">
                    <input type="hidden" name="assignment_id" id="return_assignment_id">
                    
                    <div class="mb-3">
                        <strong>Employee:</strong> <span id="return_employee_name"></span><br>
                        <strong>Asset:</strong> <span id="return_asset_tag"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date Returned *</label>
                        <input type="date" class="form-control" name="date_returned" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition at Return *</label>
                        <select class="form-select" name="condition_at_return" required>
                            <option value="excellent">Excellent</option>
                            <option value="good" selected>Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Notes</label>
                        <textarea class="form-control" name="return_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Process Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function returnAsset(assign) {
    document.getElementById('return_assignment_id').value = assign.id;
    document.getElementById('return_employee_name').textContent = assign.employee_name;
    document.getElementById('return_asset_tag').textContent = assign.asset_tag;
    
    var modal = new bootstrap.Modal(document.getElementById('returnAssetModal'));
    modal.show();
}
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
