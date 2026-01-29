<?php
$page_title = 'Audit Logs';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
requireRole('admin');

$conn = getDBConnection();

// Get filter parameters
$filter_action = $_GET['action'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_entity = $_GET['entity_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($filter_action) {
    $where_conditions[] = "action = ?";
    $params[] = $filter_action;
    $param_types .= 's';
}

if ($filter_user) {
    $where_conditions[] = "username = ?";
    $params[] = $filter_user;
    $param_types .= 's';
}

if ($filter_entity) {
    $where_conditions[] = "entity_type = ?";
    $params[] = $filter_entity;
    $param_types .= 's';
}

if ($filter_date_from) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
    $param_types .= 's';
}

if ($filter_date_to) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
    $param_types .= 's';
}

if ($search) {
    $where_conditions[] = "(username LIKE ? OR action LIKE ? OR details LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM audit_logs $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    if ($param_types) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $total_result = $stmt->get_result();
    $total_count = $total_result->fetch_assoc()['total'];
} else {
    $total_count = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_count / $per_page);

// Get logs with pagination
$query = "SELECT * FROM audit_logs $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
if ($param_types) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

// Get unique values for filters
$actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT DISTINCT username FROM audit_logs WHERE username IS NOT NULL ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$entity_types = $conn->query("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-clipboard-list"></i> Audit Logs</h2>
        <p class="text-muted">Complete activity trail of all system operations</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $filter_action === $act['action'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(getActionDescription($act['action'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="user" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo htmlspecialchars($u['username']); ?>" <?php echo $filter_user === $u['username'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Entity Type</label>
                <select name="entity_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($entity_types as $et): ?>
                        <option value="<?php echo htmlspecialchars($et['entity_type']); ?>" <?php echo $filter_entity === $et['entity_type'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($et['entity_type'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="audit-logs.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
                <a href="export-audit-logs-csv.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Audit Logs Table -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Activity Log (<?php echo number_format($total_count); ?> records)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No audit logs found matching the criteria.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity Type</th>
                            <th>Entity ID</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <small><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars(getActionDescription($log['action'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $log['entity_type'] ? htmlspecialchars(ucfirst($log['entity_type'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $log['entity_id'] ? htmlspecialchars($log['entity_id']) : '-'; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php 
                                        $details = $log['details'];
                                        if (strlen($details) > 100) {
                                            echo htmlspecialchars(substr($details, 0, 100)) . '...';
                                        } else {
                                            echo htmlspecialchars($details ?? '-');
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
