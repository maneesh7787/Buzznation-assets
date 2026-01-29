<?php
$page_title = 'Manage Assets';
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
            case 'add':
                $asset_tag = sanitizeInput($_POST['asset_tag']);
                $category_id = intval($_POST['category_id']);
                $brand = sanitizeInput($_POST['brand']);
                $model = sanitizeInput($_POST['model']);
                $serial_number = sanitizeInput($_POST['serial_number']);
                $purchase_date = sanitizeInput($_POST['purchase_date']);
                $purchase_cost = sanitizeInput($_POST['purchase_cost']);
                $condition_status = sanitizeInput($_POST['condition_status']);
                $notes = sanitizeInput($_POST['notes']);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO assets (asset_tag, category_id, brand, model, serial_number, purchase_date, purchase_cost, condition_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sissssdss", $asset_tag, $category_id, $brand, $model, $serial_number, $purchase_date, $purchase_cost, $condition_status, $notes);
                    $stmt->execute();
                    $asset_id = $conn->insert_id;
                    
                    // Log audit
                    logAudit('create_asset', 'asset', $asset_id, [
                        'asset_tag' => $asset_tag,
                        'category_id' => $category_id,
                        'brand' => $brand,
                        'model' => $model
                    ]);
                    
                    $message = 'Asset added successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error adding asset: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $asset_tag = sanitizeInput($_POST['asset_tag']);
                $category_id = intval($_POST['category_id']);
                $brand = sanitizeInput($_POST['brand']);
                $model = sanitizeInput($_POST['model']);
                $serial_number = sanitizeInput($_POST['serial_number']);
                $purchase_date = sanitizeInput($_POST['purchase_date']);
                $purchase_cost = sanitizeInput($_POST['purchase_cost']);
                $status = sanitizeInput($_POST['status']);
                $condition_status = sanitizeInput($_POST['condition_status']);
                $notes = sanitizeInput($_POST['notes']);
                
                try {
                    $stmt = $conn->prepare("UPDATE assets SET asset_tag = ?, category_id = ?, brand = ?, model = ?, serial_number = ?, purchase_date = ?, purchase_cost = ?, status = ?, condition_status = ?, notes = ? WHERE id = ?");
                    $stmt->bind_param("sissssdsssi", $asset_tag, $category_id, $brand, $model, $serial_number, $purchase_date, $purchase_cost, $status, $condition_status, $notes, $id);
                    $stmt->execute();
                    
                    // Log audit
                    logAudit('update_asset', 'asset', $id, [
                        'asset_tag' => $asset_tag,
                        'status' => $status
                    ]);
                    
                    $message = 'Asset updated successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating asset: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                try {
                    // Check if asset is assigned
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM asset_assignments WHERE asset_id = ? AND status = 'active'");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->fetch_assoc()['count'];
                    
                    if ($count > 0) {
                        $message = 'Cannot delete asset: It is currently assigned to an employee.';
                        $message_type = 'warning';
                    } else {
                        $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        
                        // Log audit
                        logAudit('delete_asset', 'asset', $id, 'Asset deleted');
                        
                        $message = 'Asset deleted successfully!';
                        $message_type = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Error deleting asset: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all assets with category info
$assets = [];
$query = "SELECT a.*, c.category_name 
          FROM assets a 
          JOIN asset_categories c ON a.category_id = c.id 
          ORDER BY a.created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

// Get categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM asset_categories WHERE status = 'active' ORDER BY category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-laptop"></i> Manage Assets</h2>
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
        <h5 class="mb-0">Asset List</h5>
        <div>
            <button class="btn btn-success" onclick="exportTableToCSV('assets_export.csv')">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                <i class="fas fa-plus"></i> Add Asset
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asset Tag</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Condition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><?php echo $asset['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong></td>
                            <td><?php echo htmlspecialchars($asset['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($asset['brand'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($asset['model'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($asset['serial_number'] ?? ''); ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'available' => 'success',
                                    'assigned' => 'primary',
                                    'maintenance' => 'warning',
                                    'retired' => 'secondary'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $status_class[$asset['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($asset['status']); ?>
                                </span>
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
                                <span class="badge bg-<?php echo $condition_class[$asset['condition_status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($asset['condition_status']); ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-info" onclick="editAsset(<?php echo htmlspecialchars(json_encode($asset)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $asset['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Tag *</label>
                            <input type="text" class="form-control" name="asset_tag" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control" name="brand">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control" name="model">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" class="form-control" name="serial_number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Cost</label>
                            <input type="number" step="0.01" class="form-control" name="purchase_cost">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condition *</label>
                            <select class="form-select" name="condition_status" required>
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
                    <button type="submit" class="btn btn-primary">Add Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Asset Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Tag *</label>
                            <input type="text" class="form-control" name="asset_tag" id="edit_asset_tag" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control" name="brand" id="edit_brand">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control" name="model" id="edit_model">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" class="form-control" name="serial_number" id="edit_serial_number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" id="edit_purchase_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Cost</label>
                            <input type="number" step="0.01" class="form-control" name="purchase_cost" id="edit_purchase_cost">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="available">Available</option>
                                <option value="assigned">Assigned</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condition *</label>
                            <select class="form-select" name="condition_status" id="edit_condition_status" required>
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAsset(asset) {
    document.getElementById('edit_id').value = asset.id;
    document.getElementById('edit_asset_tag').value = asset.asset_tag;
    document.getElementById('edit_category_id').value = asset.category_id;
    document.getElementById('edit_brand').value = asset.brand || '';
    document.getElementById('edit_model').value = asset.model || '';
    document.getElementById('edit_serial_number').value = asset.serial_number || '';
    document.getElementById('edit_purchase_date').value = asset.purchase_date || '';
    document.getElementById('edit_purchase_cost').value = asset.purchase_cost || '';
    document.getElementById('edit_status').value = asset.status;
    document.getElementById('edit_condition_status').value = asset.condition_status;
    document.getElementById('edit_notes').value = asset.notes || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
    modal.show();
}
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
