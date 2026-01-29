<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Redirect based on role
$role = $_SESSION['role'];
if ($role === 'admin') {
    header('Location: /admin/dashboard.php');
} elseif ($role === 'hr') {
    header('Location: /hr/dashboard.php');
} else {
    header('Location: /employee/dashboard.php');
}
exit();
?>
