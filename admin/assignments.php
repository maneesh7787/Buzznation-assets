<?php
$page_title = 'Asset Assignments';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_request':
                $request_id = intval($_POST['request_id']);
                $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
                
                try {
                    $conn->begin_transaction();
                    
                    // Get request details
                    $stmt = $conn->prepare("SELECT * FROM asset_requests WHERE id = ? AND status = 'pending'");
                    $stmt->bind_param("i", $request_id);
                    $stmt->execute();
                    $request = $stmt->get_result()->fetch_assoc();
                    
                    if ($request) {
                        $asset_ids = explode(',', $request['requested_asset_ids']);
                        $date_issued = date('Y-m-d');
                        
                        // Create assignments for each requested asset
                        foreach ($asset_ids as $asset_id) {
                            $asset_id = intval($asset_id);
                            
                            // Check if asset is still available
                            $stmt = $conn->prepare("SELECT status FROM assets WHERE id = ?");
                            $stmt->bind_param("i", $asset_id);
                            $stmt->execute();
                            $asset = $stmt->get_result()->fetch_assoc();
                            
                            if ($asset && $asset['status'] === 'available') {
                                // Create assignment
                                $notes = 'Requested by employee. ' . ($request['request_reason'] ?: '');
                                $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, employee_id, date_issued, condition_at_issue, notes, acknowledgement_date) VALUES (?, ?, ?, 'good', ?, NOW())");
                                $stmt->bind_param("iiss", $asset_id, $request['employee_id'], $date_issued, $notes);
                                $stmt->execute();
                                
                                // Update asset status
                                $stmt = $conn->prepare("UPDATE assets SET status = 'assigned' WHERE id = ?");
                                $stmt->bind_param("i", $asset_id);
                                $stmt->execute();
                            }
                        }
                        
                        // Update request status
                        $stmt = $conn->prepare("UPDATE asset_requests SET status = 'approved', admin_notes = ?, approved_by = ?, approved_date = NOW() WHERE id = ?");
                        $stmt->bind_param("sii", $admin_notes, $_SESSION['user_id'], $request_id);
                        $stmt->execute();
                        
                        $conn->commit();
                        $message = 'Asset request approved and assets assigned successfully!';
                        $message_type = 'success';
                    } else {
                        throw new Exception('Request not found or already processed');
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error approving request: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'reject_request':
                $request_id = intval($_POST['request_id']);
                $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
                
                try {
                    $stmt = $conn->prepare("UPDATE asset_requests SET status = 'rejected', admin_notes = ?, approved_by = ?, approved_date = NOW() WHERE id = ?");
                    $stmt->bind_param("sii", $admin_notes, $_SESSION['user_id'], $request_id);
                    $stmt->execute();
                    
                    $message = 'Asset request rejected.';
                    $message_type = 'warning';
                } catch (Exception $e) {
                    $message = 'Error rejecting request: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
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
                    
                    // Log audit
                    logAudit('create_assignment', 'assignment', $conn->insert_id, [
                        'asset_id' => $asset_id,
                        'employee_id' => $employee_id,
                        'date_issued' => $date_issued
                    ]);
                    
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
                    
                    // Log audit
                    logAudit('return_asset', 'assignment', $assignment_id, [
                        'asset_id' => $asset_id,
                        'date_returned' => $date_returned,
                        'condition' => $condition_at_return
                    ]);
                    
                    $message = 'Asset returned successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error processing return: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'swap':
                $assignment_id = intval($_POST['assignment_id']);
                $new_employee_id = intval($_POST['new_employee_id']);
                $swap_reason = sanitizeInput($_POST['swap_reason'] ?? '');
                
                try {
                    $conn->begin_transaction();
                    
                    // Get current assignment details
                    $stmt = $conn->prepare("SELECT aa.*, e.name as old_employee_name FROM asset_assignments aa JOIN employees e ON aa.employee_id = e.id WHERE aa.id = ? AND aa.status = 'active'");
                    $stmt->bind_param("i", $assignment_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $current_assignment = $result->fetch_assoc();
                    
                    if (!$current_assignment) {
                        throw new Exception('Assignment not found or not active');
                    }
                    
                    // Get new employee details
                    $stmt = $conn->prepare("SELECT name FROM employees WHERE id = ? AND status = 'active'");
                    $stmt->bind_param("i", $new_employee_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $new_employee = $result->fetch_assoc();
                    
                    if (!$new_employee) {
                        throw new Exception('New employee not found or inactive');
                    }
                    
                    // Mark current assignment as returned (auto-swap)
                    $return_notes = 'Asset swapped to ' . $new_employee['name'] . '. Reason: ' . ($swap_reason ?: 'Asset transfer');
                    $stmt = $conn->prepare("UPDATE asset_assignments SET date_returned = CURDATE(), condition_at_return = condition_at_issue, notes = CONCAT(COALESCE(notes, ''), ' | ', ?), status = 'returned' WHERE id = ?");
                    $stmt->bind_param("si", $return_notes, $assignment_id);
                    $stmt->execute();
                    
                    // Create new assignment to new employee
                    $new_notes = 'Asset transferred from ' . $current_assignment['old_employee_name'] . '. Reason: ' . ($swap_reason ?: 'Asset transfer');
                    $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, employee_id, date_issued, condition_at_issue, notes, acknowledgement_date) VALUES (?, ?, CURDATE(), ?, ?, NOW())");
                    $stmt->bind_param("iiss", $current_assignment['asset_id'], $new_employee_id, $current_assignment['condition_at_issue'], $new_notes);
                    $stmt->execute();
                    
                    // Asset status remains 'assigned' (no change needed)
                    
                    $conn->commit();
                    
                    // Log audit
                    logAudit('swap_asset', 'assignment', $assignment_id, [
                        'old_employee_id' => $current_assignment['employee_id'],
                        'new_employee_id' => $new_employee_id,
                        'asset_id' => $current_assignment['asset_id'],
                        'reason' => $swap_reason
                    ]);
                    
                    $message = 'Asset successfully swapped from ' . $current_assignment['old_employee_name'] . ' to ' . $new_employee['name'] . '!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error swapping asset: ' . $e->getMessage();
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

// Get pending asset requests
$pending_requests = [];
$query = "SELECT ar.*, e.name as employee_name, e.employee_id as emp_id, e.department
          FROM asset_requests ar
          JOIN employees e ON ar.employee_id = e.id
          WHERE ar.status = 'pending'
          ORDER BY ar.created_at ASC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    // Get asset details for this request
    $asset_ids = explode(',', $row['requested_asset_ids']);
    $assets_info = [];
    foreach ($asset_ids as $aid) {
        $aid = intval($aid);
        $stmt = $conn->prepare("SELECT a.*, ac.category_name FROM assets a JOIN asset_categories ac ON a.category_id = ac.id WHERE a.id = ?");
        $stmt->bind_param("i", $aid);
        $stmt->execute();
        $asset = $stmt->get_result()->fetch_assoc();
        if ($asset) {
            $assets_info[] = $asset;
        }
    }
    $row['assets'] = $assets_info;
    $pending_requests[] = $row;
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

<!-- Pending Asset Requests Section -->
<?php if (!empty($pending_requests)): ?>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Asset Requests (<?php echo count($pending_requests); ?>)</h5>
    </div>
    <div class="card-body">
        <?php foreach ($pending_requests as $request): ?>
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-0">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($request['employee_name']); ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($request['emp_id']); ?></span>
                            </h6>
                            <small class="text-muted">
                                Department: <?php echo htmlspecialchars($request['department']); ?> | 
                                Requested: <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?>
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-sm btn-success" onclick="approveRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($request['request_reason']): ?>
                        <p class="mb-2"><strong>Reason:</strong> <?php echo htmlspecialchars($request['request_reason']); ?></p>
                    <?php endif; ?>
                    
                    <p class="mb-2"><strong>Requested Assets (<?php echo count($request['assets']); ?>):</strong></p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Asset Tag</th>
                                    <th>Category</th>
                                    <th>Brand/Model</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($request['assets'] as $asset): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($asset['category_name']); ?></td>
                                        <td>
                                            <?php 
                                            $brand_model = trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''));
                                            echo htmlspecialchars($brand_model ?: 'N/A');
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
                                            $class = $condition_class[$asset['condition_status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($asset['condition_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($asset['status'] === 'available'): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <?php echo ucfirst($asset['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mb-0 mt-2">
                        <i class="fas fa-check-circle"></i> Employee has acknowledged responsibility for these assets.
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Assigned Assets Section -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Assignment List</h5>
        <div>
            <a href="export-assignments-csv.php" class="btn btn-success me-2">
                <i class="fas fa-file-csv"></i> Export to CSV
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
                <i class="fas fa-plus"></i> Assign Asset
            </button>
        </div>
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
                                    <button class="btn btn-sm btn-info me-1" onclick="swapAsset(<?php echo htmlspecialchars(json_encode($assign)); ?>)">
                                        <i class="fas fa-exchange-alt"></i> Swap
                                    </button>
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

<!-- Approve Request Modal -->
<div class="modal fade" id="approveRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Approve Asset Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="approve_request">
                    <input type="hidden" name="request_id" id="approve_request_id">
                    
                    <div class="alert alert-info">
                        <strong>Employee:</strong> <span id="approve_employee_name"></span><br>
                        <strong>Department:</strong> <span id="approve_department"></span><br>
                        <strong>Assets:</strong> <span id="approve_asset_count"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" name="admin_notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Confirm:</strong> Approving this request will assign all available assets to the employee immediately.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Asset Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_request">
                    <input type="hidden" name="request_id" id="reject_request_id">
                    
                    <p>Are you sure you want to reject this asset request?</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection *</label>
                        <textarea class="form-control" name="admin_notes" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Request
                    </button>
                </div>
            </form>
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

<!-- Swap Asset Modal -->
<div class="modal fade" id="swapAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Swap Asset to Another Employee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="swap">
                    <input type="hidden" name="assignment_id" id="swap_assignment_id">
                    
                    <div class="alert alert-info">
                        <strong>Current Employee:</strong> <span id="swap_current_employee"></span><br>
                        <strong>Asset:</strong> <span id="swap_asset_tag"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Employee *</label>
                        <select class="form-select" name="new_employee_id" id="swap_new_employee" required>
                            <option value="">Select New Employee</option>
                            <?php foreach ($active_employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Swap</label>
                        <textarea class="form-control" name="swap_reason" rows="3" placeholder="Optional: Explain why this asset is being transferred..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> This will automatically return the asset from the current employee and assign it to the new employee.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Swap Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveRequest(request) {
    document.getElementById('approve_request_id').value = request.id;
    document.getElementById('approve_employee_name').textContent = request.employee_name;
    document.getElementById('approve_department').textContent = request.department;
    document.getElementById('approve_asset_count').textContent = request.assets.length + ' asset(s)';
    
    var modal = new bootstrap.Modal(document.getElementById('approveRequestModal'));
    modal.show();
}

function rejectRequest(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    
    var modal = new bootstrap.Modal(document.getElementById('rejectRequestModal'));
    modal.show();
}

function returnAsset(assign) {
    document.getElementById('return_assignment_id').value = assign.id;
    document.getElementById('return_employee_name').textContent = assign.employee_name;
    document.getElementById('return_asset_tag').textContent = assign.asset_tag;
    
    var modal = new bootstrap.Modal(document.getElementById('returnAssetModal'));
    modal.show();
}

function swapAsset(assign) {
    document.getElementById('swap_assignment_id').value = assign.id;
    document.getElementById('swap_current_employee').textContent = assign.employee_name;
    document.getElementById('swap_asset_tag').textContent = assign.asset_tag;
    
    // Reset the employee dropdown to prevent selecting the same employee
    var dropdown = document.getElementById('swap_new_employee');
    dropdown.value = '';
    
    // Optionally disable the current employee in the dropdown
    var currentEmpId = assign.employee_id;
    Array.from(dropdown.options).forEach(option => {
        if (option.value == currentEmpId) {
            option.disabled = true;
            option.textContent += ' (Current)';
        } else {
            option.disabled = false;
            option.textContent = option.textContent.replace(' (Current)', '');
        }
    });
    
    var modal = new bootstrap.Modal(document.getElementById('swapAssetModal'));
    modal.show();
}
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
