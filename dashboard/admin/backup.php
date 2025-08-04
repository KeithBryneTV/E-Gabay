<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title
$page_title = 'Backup & Restore';

// Database credentials
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

// Backup directory
$backup_dir = $base_path . '/backups/';

// Create backup directory if it doesn't exist
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Process backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_backup':
            // Create database backup
            $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_path = $backup_dir . $backup_file;
            
            // Determine mysqldump path
            $mysqldump = 'mysqldump';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $possible = [
                    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                    'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
                ];
                foreach ($possible as $p) {
                    if (file_exists($p)) { $mysqldump = '"' . $p . '"'; break; }
                }
            }

            $command = "$mysqldump --host=" . escapeshellarg($db_host) . " --user=" . escapeshellarg($db_user);
            if ($db_pass) { $command .= " --password=" . escapeshellarg($db_pass); }
            $command .= " " . escapeshellarg($db_name) . " > " . escapeshellarg($backup_path);
            
            // Execute command
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                // Log the backup
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $description = "Created database backup: {$backup_file}";
                
                $db = new Database();
                $conn = $db->getConnection();
                
                $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->execute([$_SESSION['user_id'], 'system', $ip_address, $description]);
                
                setMessage('Database backup created successfully.', 'success');
            } else {
                setMessage('Failed to create database backup. Error: ' . implode(' ', $output), 'danger');
            }
            break;
            
        case 'restore_backup':
            // Restore database from backup
            if (isset($_POST['backup_file'])) {
                $backup_file = $_POST['backup_file'];
                $backup_path = $backup_dir . $backup_file;
                
                // Validate file exists and is a SQL file
                if (file_exists($backup_path) && pathinfo($backup_path, PATHINFO_EXTENSION) === 'sql') {
                    // Determine mysql path
                    $mysqlcli = 'mysql';
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        $poss = [
                            'C:\\xampp\\mysql\\bin\\mysql.exe',
                            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
                        ];
                        foreach ($poss as $p) { if (file_exists($p)) { $mysqlcli = '"' . $p . '"'; break; } }
                    }
                    // Command to restore backup
                    $command = "$mysqlcli --host=" . escapeshellarg($db_host) . " --user=" . escapeshellarg($db_user);
                    if ($db_pass) { $command .= " --password=" . escapeshellarg($db_pass); }
                    $command .= " " . escapeshellarg($db_name) . " < " . escapeshellarg($backup_path);
                    
                    // Execute command
                    $output = [];
                    $return_var = 0;
                    exec($command, $output, $return_var);
                    
                    if ($return_var === 0) {
                        // Log the restore
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $description = "Restored database from backup: {$backup_file}";
                        
                        $db = new Database();
                        $conn = $db->getConnection();
                        
                        $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $log_stmt->execute([$_SESSION['user_id'], 'system', $ip_address, $description]);
                        
                        setMessage('Database restored successfully.', 'success');
                    } else {
                        setMessage('Failed to restore database. Error: ' . implode(' ', $output), 'danger');
                    }
                } else {
                    setMessage('Invalid backup file.', 'danger');
                }
            } else {
                setMessage('No backup file selected.', 'danger');
            }
            break;
            
        case 'delete_backup':
            // Delete backup file
            if (isset($_POST['backup_file'])) {
                $backup_file = $_POST['backup_file'];
                $backup_path = $backup_dir . $backup_file;
                
                // Validate file exists and is a SQL file
                if (file_exists($backup_path) && pathinfo($backup_path, PATHINFO_EXTENSION) === 'sql') {
                    if (unlink($backup_path)) {
                        // Log the deletion
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $description = "Deleted database backup: {$backup_file}";
                        
                        $db = new Database();
                        $conn = $db->getConnection();
                        
                        $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $log_stmt->execute([$_SESSION['user_id'], 'system', $ip_address, $description]);
                        
                        setMessage('Backup file deleted successfully.', 'success');
                    } else {
                        setMessage('Failed to delete backup file.', 'danger');
                    }
                } else {
                    setMessage('Invalid backup file.', 'danger');
                }
            } else {
                setMessage('No backup file selected.', 'danger');
            }
            break;
            
        case 'upload_backup':
            // Upload backup file
            if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['backup_file']['name'];
                $file_tmp = $_FILES['backup_file']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validate file extension
                if ($file_ext === 'sql') {
                    // Generate unique filename
                    $new_file_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                    $upload_path = $backup_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Log the upload
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $description = "Uploaded database backup: {$new_file_name}";
                        
                        $db = new Database();
                        $conn = $db->getConnection();
                        
                        $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $log_stmt->execute([$_SESSION['user_id'], 'system', $ip_address, $description]);
                        
                        setMessage('Backup file uploaded successfully.', 'success');
                    } else {
                        setMessage('Failed to upload backup file.', 'danger');
                    }
                } else {
                    setMessage('Invalid file format. Only SQL files are allowed.', 'danger');
                }
            } else {
                setMessage('No file uploaded or an error occurred.', 'danger');
            }
            break;
    }
}

// Get list of backup files
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($backup_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Backup & Restore</h1>
        <p class="lead">Manage database backups and restoration.</p>
    </div>
</div>

<!-- Backup Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-database me-1"></i>
                Create Backup
            </div>
            <div class="card-body">
                <p>Create a new backup of the current database state.</p>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i> Create Backup
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-upload me-1"></i>
                Upload Backup
            </div>
            <div class="card-body">
                <p>Upload an existing SQL backup file.</p>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_backup">
                    <div class="input-group">
                        <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Backup Files -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-list me-1"></i>
        Available Backups
    </div>
    <div class="card-body">
        <?php if (empty($backup_files)): ?>
            <p class="text-center">No backup files found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backup_files as $file): ?>
                            <tr>
                                <td><?php echo $file['name']; ?></td>
                                <td><?php echo formatFileSize($file['size']); ?></td>
                                <td><?php echo date('M d, Y h:i A', $file['date']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL . 'backups/' . $file['name']; ?>" download class="btn btn-sm btn-primary">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success restore-btn" 
                                                data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                data-file="<?php echo $file['name']; ?>">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-file="<?php echo $file['name']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel">Restore Database</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" name="backup_file" id="restore_file">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> Restoring a database backup will overwrite all current data. This action cannot be undone.
                    </div>
                    
                    <p>Are you sure you want to restore the database from the backup file: <strong id="restore_filename"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Restore Database</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_backup">
                    <input type="hidden" name="backup_file" id="delete_file">
                    
                    <p>Are you sure you want to delete the backup file: <strong id="delete_filename"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Restore modal
    const restoreBtns = document.querySelectorAll('.restore-btn');
    restoreBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const file = this.getAttribute('data-file');
            document.getElementById('restore_file').value = file;
            document.getElementById('restore_filename').textContent = file;
        });
    });
    
    // Delete modal
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const file = this.getAttribute('data-file');
            document.getElementById('delete_file').value = file;
            document.getElementById('delete_filename').textContent = file;
        });
    });
});
</script>

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Include footer
include_once $base_path . '/includes/footer.php';
?> 