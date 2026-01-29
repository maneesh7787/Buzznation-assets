<?php
$page_title = 'Request Assets';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('employee');

$conn = getDBConnection();
$message = '';
$message_type = '';

// Get employee info
$employee = null;
if (isset($_SESSION['employee_id'])) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['employee_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_request') {
    $asset_ids = $_POST['asset_ids'] ?? [];
    $request_reason = sanitizeInput($_POST['request_reason'] ?? '');
    $acknowledgement = isset($_POST['acknowledgement']) ? 1 : 0;
    
    if (empty($asset_ids)) {
        $message = 'Please select at least one asset to request.';
        $message_type = 'danger';
    } elseif (!$acknowledgement) {
        $message = 'You must acknowledge the terms before submitting your request.';
        $message_type = 'danger';
    } else {
        try {
            $asset_ids_str = implode(',', array_map('intval', $asset_ids));
            $stmt = $conn->prepare("INSERT INTO asset_requests (employee_id, requested_asset_ids, request_reason, acknowledgement, acknowledgement_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("issi", $employee['id'], $asset_ids_str, $request_reason, $acknowledgement);
            $stmt->execute();
            
            $message = 'Your asset request has been submitted successfully! The admin will review it shortly.';
            $message_type = 'success';
            
            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $message = 'Error submitting request: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get available assets
$available_assets = [];
$query = "SELECT a.*, ac.category_name 
          FROM assets a
          JOIN asset_categories ac ON a.category_id = ac.id
          WHERE a.status = 'available'
          ORDER BY ac.category_name, a.asset_tag";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $available_assets[] = $row;
}

// Get employee's pending requests
$pending_requests = [];
if ($employee) {
    $query = "SELECT * FROM asset_requests 
              WHERE employee_id = ? AND status = 'pending'
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-file-import"></i> Request Assets</h2>
        <p class="text-muted">Submit a request to receive assets from the organization.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Pending Requests Section -->
<?php if (!empty($pending_requests)): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-clock"></i> My Pending Requests</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Number of Assets</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_requests as $request): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                            <td>
                                <?php 
                                $asset_count = count(explode(',', $request['requested_asset_ids']));
                                echo $asset_count . ' asset' . ($asset_count > 1 ? 's' : '');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['request_reason'] ?: 'N/A'); ?></td>
                            <td><span class="badge bg-warning">Pending</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Request Form -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> New Asset Request</h5>
    </div>
    <div class="card-body">
        <?php if (empty($available_assets)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> There are no available assets to request at this time. Please check back later.
            </div>
        <?php else: ?>
            <form method="POST" id="assetRequestForm">
                <input type="hidden" name="action" value="submit_request">
                
                <!-- Asset Selection -->
                <div class="mb-4">
                    <label class="form-label"><strong>Select Assets to Request:</strong> <span class="text-danger">*</span></label>
                    <p class="text-muted small">You can select multiple assets by checking the boxes.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">Select</th>
                                    <th>Asset Tag</th>
                                    <th>Category</th>
                                    <th>Brand/Model</th>
                                    <th>Condition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $current_category = '';
                                foreach ($available_assets as $asset): 
                                    if ($current_category !== $asset['category_name']) {
                                        $current_category = $asset['category_name'];
                                        echo '<tr class="table-secondary"><td colspan="5"><strong>' . htmlspecialchars($current_category) . '</strong></td></tr>';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="asset_ids[]" value="<?php echo $asset['id']; ?>" class="form-check-input asset-checkbox">
                                        </td>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text">
                        <span id="selectedCount" class="text-primary fw-bold">0</span> asset(s) selected
                    </div>
                </div>
                
                <!-- Request Reason -->
                <div class="mb-4">
                    <label for="request_reason" class="form-label"><strong>Reason for Request:</strong></label>
                    <textarea name="request_reason" id="request_reason" class="form-control" rows="3" placeholder="Please explain why you need these assets..."></textarea>
                    <div class="form-text">Optional: Provide context for your request</div>
                </div>
                
                <!-- Acknowledgement -->
                <div class="mb-4">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h6 class="card-title text-warning"><i class="fas fa-exclamation-triangle"></i> Important Notice</h6>
                            <p class="mb-3">By requesting these assets, I acknowledge that:</p>
                            <ul class="mb-3">
                                <li>I will be responsible for the proper care and use of these assets</li>
                                <li>I will report any damage or loss immediately to IT/Admin</li>
                                <li>I will return these assets when requested or upon leaving the organization</li>
                                <li>I understand that misuse or loss may result in disciplinary action</li>
                            </ul>
                            <div class="form-check">
                                <input type="checkbox" name="acknowledgement" id="acknowledgement" class="form-check-input" required>
                                <label for="acknowledgement" class="form-check-label">
                                    <strong>I have read and agree to the above terms</strong> <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Update selected count
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    const countDisplay = document.getElementById('selectedCount');
    const submitBtn = document.getElementById('submitBtn');
    
    function updateCount() {
        const count = document.querySelectorAll('.asset-checkbox:checked').length;
        countDisplay.textContent = count;
        submitBtn.disabled = count === 0;
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCount);
    });
    
    updateCount();
});
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
