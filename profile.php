<?php
$page_title = 'My Profile';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$conn = getDBConnection();
$message = '';
$message_type = '';

$current_user = getCurrentUser();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = 'New passwords do not match.';
        $message_type = 'danger';
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $current_user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password_hash, $current_user['id']);
            $stmt->execute();
            
            $message = 'Password changed successfully!';
            $message_type = 'success';
        } else {
            $message = 'Current password is incorrect.';
            $message_type = 'danger';
        }
    }
}

// Get employee info if employee role
$employee = null;
if ($current_user['role'] === 'employee' && $current_user['employee_id']) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $current_user['employee_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Account Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($current_user['username']); ?></p>
                <p><strong>Role:</strong> <span class="badge bg-info"><?php echo strtoupper($current_user['role']); ?></span></p>
                
                <?php if ($employee): ?>
                    <hr>
                    <h6>Employee Details</h6>
                    <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['name']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                    <p><strong>Designation:</strong> <?php echo htmlspecialchars($employee['designation']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
