<?php
$page_title = 'Manage Asset Categories';
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
            case 'add':
                $category_name = sanitizeInput($_POST['category_name']);
                $description = sanitizeInput($_POST['description']);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO asset_categories (category_name, description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $category_name, $description);
                    $stmt->execute();
                    
                    $message = 'Category added successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error adding category: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $category_name = sanitizeInput($_POST['category_name']);
                $description = sanitizeInput($_POST['description']);
                
                try {
                    $stmt = $conn->prepare("UPDATE asset_categories SET category_name = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $category_name, $description, $id);
                    $stmt->execute();
                    
                    $message = 'Category updated successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating category: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id']);
                $status = sanitizeInput($_POST['status']);
                
                try {
                    $stmt = $conn->prepare("UPDATE asset_categories SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $status, $id);
                    $stmt->execute();
                    
                    $message = 'Category status updated successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating status: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                try {
                    // Check if category is in use
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM assets WHERE category_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->fetch_assoc()['count'];
                    
                    if ($count > 0) {
                        $message = 'Cannot delete category: It is currently assigned to ' . $count . ' asset(s).';
                        $message_type = 'warning';
                    } else {
                        $stmt = $conn->prepare("DELETE FROM asset_categories WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        
                        $message = 'Category deleted successfully!';
                        $message_type = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Error deleting category: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all categories
$categories = [];
$result = $conn->query("SELECT c.*, COUNT(a.id) as asset_count 
                        FROM asset_categories c 
                        LEFT JOIN assets a ON c.id = a.category_id 
                        GROUP BY c.id 
                        ORDER BY c.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-list"></i> Manage Asset Categories</h2>
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
        <h5 class="mb-0">Asset Categories</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Asset Count</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                            <td><span class="badge bg-info"><?php echo $cat['asset_count']; ?></span></td>
                            <td>
                                <?php if ($cat['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-info" onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $cat['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Toggle Status">
                                        <i class="fas fa-<?php echo $cat['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="category_name" id="edit_category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_category_name').value = cat.category_name;
    document.getElementById('edit_description').value = cat.description || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
