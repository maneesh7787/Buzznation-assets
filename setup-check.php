<?php
/**
 * Setup Check - Verify environment and configuration
 * Access this file to check if your environment is properly configured
 */

// Enable error display for this diagnostic page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$checks = [];
$allPassed = true;

// Check PHP Version
$phpVersion = phpversion();
$checks['PHP Version'] = [
    'status' => version_compare($phpVersion, '7.4', '>='),
    'message' => $phpVersion . ' (Required: 7.4+)',
    'required' => true
];
if (!$checks['PHP Version']['status']) $allPassed = false;

// Check required PHP extensions
$requiredExtensions = ['mysqli', 'session', 'json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    $checks["PHP Extension: $ext"] = [
        'status' => extension_loaded($ext),
        'message' => extension_loaded($ext) ? 'Installed' : 'Missing',
        'required' => true
    ];
    if (!extension_loaded($ext)) $allPassed = false;
}

// Check if config file exists
$configExists = file_exists(__DIR__ . '/config/database.php');
$checks['Configuration File'] = [
    'status' => $configExists,
    'message' => $configExists ? 'config/database.php exists' : 'config/database.php not found',
    'required' => true
];
if (!$configExists) $allPassed = false;

// Check database connection (only if config exists)
if ($configExists) {
    try {
        require_once __DIR__ . '/config/database.php';
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            $checks['Database Server'] = [
                'status' => false,
                'message' => 'Cannot connect: ' . $conn->connect_error,
                'required' => true
            ];
            $allPassed = false;
        } else {
            $checks['Database Server'] = [
                'status' => true,
                'message' => 'Connected to MySQL server',
                'required' => true
            ];
            
            // Check if database exists
            $dbExists = $conn->select_db(DB_NAME);
            if (!$dbExists) {
                $checks['Database: ' . DB_NAME] = [
                    'status' => false,
                    'message' => 'Database does not exist. Run: CREATE DATABASE ' . DB_NAME . ';',
                    'required' => true
                ];
                $allPassed = false;
            } else {
                $checks['Database: ' . DB_NAME] = [
                    'status' => true,
                    'message' => 'Database exists',
                    'required' => true
                ];
                
                // Check if tables exist
                $result = $conn->query("SHOW TABLES");
                $tableCount = $result ? $result->num_rows : 0;
                
                if ($tableCount === 0) {
                    $checks['Database Tables'] = [
                        'status' => false,
                        'message' => 'No tables found. Import database_schema.sql',
                        'required' => true
                    ];
                    $allPassed = false;
                } else {
                    $checks['Database Tables'] = [
                        'status' => true,
                        'message' => "$tableCount tables found",
                        'required' => true
                    ];
                }
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        $checks['Database Connection'] = [
            'status' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'required' => true
        ];
        $allPassed = false;
    }
}

// Check file permissions
$writableDirectories = [];
if (is_writable(__DIR__)) {
    $checks['File Permissions'] = [
        'status' => true,
        'message' => 'Application directory is writable',
        'required' => false
    ];
} else {
    $checks['File Permissions'] = [
        'status' => false,
        'message' => 'Application directory is not writable (may cause issues)',
        'required' => false
    ];
}

// Check .htaccess
$htaccessExists = file_exists(__DIR__ . '/.htaccess');
$checks['.htaccess File'] = [
    'status' => $htaccessExists,
    'message' => $htaccessExists ? '.htaccess exists' : '.htaccess not found (URL rewriting may not work)',
    'required' => false
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Check - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .check-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .status-icon {
            font-size: 1.2em;
            margin-right: 10px;
        }
        .status-pass {
            color: #28a745;
        }
        .status-fail {
            color: #dc3545;
        }
        .overall-status {
            font-size: 3em;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="check-container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0">
                    <i class="fas fa-cogs"></i> Setup Check
                </h2>
                <p class="mb-0 mt-2">Asset Management System</p>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if ($allPassed): ?>
                        <div class="overall-status status-pass">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4 class="text-success">All Checks Passed!</h4>
                        <p>Your system is ready. <a href="/login.php" class="btn btn-primary mt-3">Go to Login</a></p>
                    <?php else: ?>
                        <div class="overall-status status-fail">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h4 class="text-danger">Setup Issues Found</h4>
                        <p>Please resolve the issues below before using the application.</p>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <h5 class="mb-3">System Requirements:</h5>
                <div class="list-group">
                    <?php foreach ($checks as $name => $check): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <span class="status-icon <?php echo $check['status'] ? 'status-pass' : 'status-fail'; ?>">
                                    <i class="fas fa-<?php echo $check['status'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($name); ?></strong>
                                    <?php if ($check['required']): ?>
                                        <span class="badge bg-danger ms-2">Required</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($check['message']); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!$allPassed): ?>
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle"></i> Quick Setup Guide:</h6>
                        <ol class="mb-0">
                            <li>Ensure you have PHP 7.4+ installed with required extensions</li>
                            <li>Create MySQL database: <code>CREATE DATABASE asset_management;</code></li>
                            <li>Import schema: <code>mysql -u root -p asset_management &lt; database_schema.sql</code></li>
                            <li>Verify config/database.php has correct credentials</li>
                            <li>Refresh this page to verify all checks pass</li>
                        </ol>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 text-center">
                    <a href="setup-check.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Recheck
                    </a>
                    <a href="/INSTALLATION.md" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-book"></i> View Installation Guide
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-white">
                <i class="fas fa-shield-alt"></i> This file should be removed or restricted in production
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
