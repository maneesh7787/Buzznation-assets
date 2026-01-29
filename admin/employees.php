<?php
$page_title = 'Manage Employees';
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
                $employee_id = sanitizeInput($_POST['employee_id']);
                $name = sanitizeInput($_POST['name']);
                $department = sanitizeInput($_POST['department']);
                $designation = sanitizeInput($_POST['designation']);
                $date_of_joining = sanitizeInput($_POST['date_of_joining']);
                $work_location = sanitizeInput($_POST['work_location']);
                $email = sanitizeInput($_POST['email']);
                $phone = sanitizeInput($_POST['phone']);
                $username = sanitizeInput($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                try {
                    $conn->begin_transaction();
                    
                    // Insert employee
                    $stmt = $conn->prepare("INSERT INTO employees (employee_id, name, department, designation, date_of_joining, work_location, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssss", $employee_id, $name, $department, $designation, $date_of_joining, $work_location, $email, $phone);
                    $stmt->execute();
                    $emp_db_id = $conn->insert_id;
                    
                    // Create user account
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, employee_id) VALUES (?, ?, 'employee', ?)");
                    $stmt->bind_param("ssi", $username, $password, $emp_db_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = 'Employee added successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error adding employee: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitizeInput($_POST['name']);
                $department = sanitizeInput($_POST['department']);
                $designation = sanitizeInput($_POST['designation']);
                $date_of_joining = sanitizeInput($_POST['date_of_joining']);
                $work_location = sanitizeInput($_POST['work_location']);
                $email = sanitizeInput($_POST['email']);
                $phone = sanitizeInput($_POST['phone']);
                
                try {
                    $stmt = $conn->prepare("UPDATE employees SET name = ?, department = ?, designation = ?, date_of_joining = ?, work_location = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssssssi", $name, $department, $designation, $date_of_joining, $work_location, $email, $phone, $id);
                    $stmt->execute();
                    
                    $message = 'Employee updated successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating employee: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id']);
                $status = sanitizeInput($_POST['status']);
                
                try {
                    $stmt = $conn->prepare("UPDATE employees SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $status, $id);
                    $stmt->execute();
                    
                    $message = 'Employee status updated successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating status: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'change_password':
                $emp_id = intval($_POST['emp_id']);
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                try {
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE employee_id = ?");
                    $stmt->bind_param("si", $new_password, $emp_id);
                    $stmt->execute();
                    
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error changing password: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                try {
                    $conn->begin_transaction();
                    
                    // Delete user account
                    $stmt = $conn->prepare("DELETE FROM users WHERE employee_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    // Delete employee
                    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = 'Employee deleted successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = 'Error deleting employee: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all employees
$employees = [];
$result = $conn->query("SELECT * FROM employees ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-users"></i> Manage Employees</h2>
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
        <h5 class="mb-0">Employee List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-plus"></i> Add Employee
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Joining Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?php echo $emp['id']; ?></td>
                            <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($emp['date_of_joining'])); ?></td>
                            <td><?php echo htmlspecialchars($emp['work_location']); ?></td>
                            <td>
                                <?php if ($emp['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-info" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="changePassword(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['name']); ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $emp['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Toggle Status">
                                        <i class="fas fa-<?php echo $emp['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee ID *</label>
                            <input type="text" class="form-control" name="employee_id" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department *</label>
                            <input type="text" class="form-control" name="department" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation *</label>
                            <input type="text" class="form-control" name="designation" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Joining *</label>
                            <input type="date" class="form-control" name="date_of_joining" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Work Location *</label>
                            <input type="text" class="form-control" name="work_location" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="edit_employee_id" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department *</label>
                            <input type="text" class="form-control" name="department" id="edit_department" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation *</label>
                            <input type="text" class="form-control" name="designation" id="edit_designation" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Joining *</label>
                            <input type="date" class="form-control" name="date_of_joining" id="edit_date_of_joining" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Work Location *</label>
                            <input type="text" class="form-control" name="work_location" id="edit_work_location" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="emp_id" id="pwd_emp_id">
                    <p>Change password for: <strong id="pwd_emp_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editEmployee(emp) {
    document.getElementById('edit_id').value = emp.id;
    document.getElementById('edit_employee_id').value = emp.employee_id;
    document.getElementById('edit_name').value = emp.name;
    document.getElementById('edit_department').value = emp.department;
    document.getElementById('edit_designation').value = emp.designation;
    document.getElementById('edit_date_of_joining').value = emp.date_of_joining;
    document.getElementById('edit_work_location').value = emp.work_location;
    document.getElementById('edit_email').value = emp.email || '';
    document.getElementById('edit_phone').value = emp.phone || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    modal.show();
}

function changePassword(empId, empName) {
    document.getElementById('pwd_emp_id').value = empId;
    document.getElementById('pwd_emp_name').textContent = empName;
    
    var modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
