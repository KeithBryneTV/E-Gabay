<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Include required files
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Utility.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title
$page_title = 'Send Notifications';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_notification'])) {
        $message = sanitizeInput($_POST['message']);
        $type = sanitizeInput($_POST['type']);
        $category = sanitizeInput($_POST['category']);
        $target = sanitizeInput($_POST['target']);
        $link = sanitizeInput($_POST['link'] ?? '');
        
        // Validate inputs
        if (empty($message)) {
            setMessage('Message is required.', 'danger');
        } else {
            $success = false;
            
            // Send notification based on target
            if ($target === 'all') {
                // Send to all users
                $query = "SELECT user_id FROM users WHERE is_active = 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $success = true;
                foreach ($users as $user) {
                    if (!addSystemNotification($user['user_id'], $message, $type, $category, $link)) {
                        $success = false;
                    }
                }
            } elseif ($target === 'specific_user') {
                // Send to specific user
                $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
                
                if ($user_id > 0) {
                    $success = addSystemNotification($user_id, $message, $type, $category, $link);
                } else {
                    setMessage('Please select a valid user.', 'danger');
                }
            } else {
                // Send to specific role
                $success = addRoleBroadcastNotification($message, $target, $type, $category, $link);
            }
            
            if ($success) {
                setMessage('Notification sent successfully.', 'success');
                
                // Log the action
                logAction('send_notification', "Admin sent a notification to " . ($target === 'all' ? 'all users' : $target));
                
                // Redirect to avoid form resubmission
                header("Location: " . SITE_URL . "/dashboard/admin/send_notification.php");
                exit;
            } else {
                setMessage('Failed to send notification.', 'danger');
            }
        }
    }
}

// Get users for dropdown
$query = "SELECT user_id, CONCAT(first_name, ' ', last_name, ' (', email, ')') as user_name FROM users WHERE is_active = 1 ORDER BY first_name, last_name";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Send Notifications</h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Create New Notification</h5>
                </div>
                
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <input type="hidden" name="send_notification" value="1">
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Notification Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="3" required><?php echo $_POST['message'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Notification Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="info" <?php echo (isset($_POST['type']) && $_POST['type'] == 'info') ? 'selected' : ''; ?>>Information (Blue)</option>
                                    <option value="success" <?php echo (isset($_POST['type']) && $_POST['type'] == 'success') ? 'selected' : ''; ?>>Success (Green)</option>
                                    <option value="warning" <?php echo (isset($_POST['type']) && $_POST['type'] == 'warning') ? 'selected' : ''; ?>>Warning (Yellow)</option>
                                    <option value="danger" <?php echo (isset($_POST['type']) && $_POST['type'] == 'danger') ? 'selected' : ''; ?>>Danger (Red)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="system" <?php echo (isset($_POST['category']) && $_POST['category'] == 'system') ? 'selected' : ''; ?>>System</option>
                                    <option value="announcement" <?php echo (isset($_POST['category']) && $_POST['category'] == 'announcement') ? 'selected' : ''; ?>>Announcement</option>
                                    <option value="event" <?php echo (isset($_POST['category']) && $_POST['category'] == 'event') ? 'selected' : ''; ?>>Event</option>
                                    <option value="maintenance" <?php echo (isset($_POST['category']) && $_POST['category'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="link" class="form-label">Link URL (Optional)</label>
                            <input type="url" class="form-control" id="link" name="link" value="<?php echo $_POST['link'] ?? ''; ?>" placeholder="e.g., https://example.com or /dashboard/admin/index.php">
                            <div class="form-text">URL where users will be directed when clicking the notification</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="target" class="form-label">Send To</label>
                            <select class="form-select" id="target" name="target">
                                <option value="all">All Users</option>
                                <option value="student">Students Only</option>
                                <option value="counselor">Counselors Only</option>
                                <option value="admin">Administrators Only</option>
                                <option value="staff">Staff Only</option>
                                <option value="specific_user">Specific User</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="userSelectContainer" style="display: none;">
                            <label for="user_id" class="form-label">Select User</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">Select a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>"><?php echo $user['user_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center preview-notification">
                                        <div class="notification-icon info me-3" id="previewIcon">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <div id="previewMessage">Notification message preview</div>
                                            <div class="notification-time">Just now</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Tips</h5>
                </div>
                
                <div class="card-body">
                    <h6><i class="fas fa-check-circle text-success me-2"></i> Notification Types:</h6>
                    <ul>
                        <li><strong>Information:</strong> General updates and information</li>
                        <li><strong>Success:</strong> Positive events or confirmations</li>
                        <li><strong>Warning:</strong> Important notices requiring attention</li>
                        <li><strong>Danger:</strong> Critical alerts or errors</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-check-circle text-success me-2"></i> Role-Specific Notifications:</h6>
                    <ul>
                        <li><strong>Students:</strong> Receive education-related updates</li>
                        <li><strong>Counselors:</strong> Get alerts about consultation requests</li>
                        <li><strong>Administrators:</strong> System-wide notifications</li>
                        <li><strong>Staff:</strong> Department-specific updates</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-check-circle text-success me-2"></i> Best Practices:</h6>
                    <ul>
                        <li>Keep messages clear and concise</li>
                        <li>Use appropriate notification types</li>
                        <li>Include links when additional action is needed</li>
                        <li>Target the right audience to avoid notification fatigue</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Important Notes</h5>
                </div>
                
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-exclamation-circle text-warning me-2"></i> All notifications are logged in the system</li>
                        <li class="mb-2"><i class="fas fa-exclamation-circle text-warning me-2"></i> Mass notifications should be used sparingly</li>
                        <li><i class="fas fa-exclamation-circle text-warning me-2"></i> Users can view all their notifications in the notifications page</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetSelect = document.getElementById('target');
    const userSelectContainer = document.getElementById('userSelectContainer');
    
    // Show/hide user select based on target
    targetSelect.addEventListener('change', function() {
        if (this.value === 'specific_user') {
            userSelectContainer.style.display = 'block';
        } else {
            userSelectContainer.style.display = 'none';
        }
    });
    
    // Initialize
    if (targetSelect.value === 'specific_user') {
        userSelectContainer.style.display = 'block';
    }
    
    // Live preview functionality
    const messageInput = document.getElementById('message');
    const typeSelect = document.getElementById('type');
    const previewMessage = document.getElementById('previewMessage');
    const previewIcon = document.getElementById('previewIcon');
    
    function updatePreview() {
        // Update message
        previewMessage.textContent = messageInput.value || 'Notification message preview';
        
        // Update icon type
        previewIcon.className = 'notification-icon me-3 ' + typeSelect.value;
        
        // Update icon
        const iconElement = previewIcon.querySelector('i');
        let iconClass = 'fas ';
        
        switch(typeSelect.value) {
            case 'success':
                iconClass += 'fa-check-circle';
                break;
            case 'warning':
                iconClass += 'fa-exclamation-triangle';
                break;
            case 'danger':
                iconClass += 'fa-exclamation-circle';
                break;
            default:
                iconClass += 'fa-info-circle';
        }
        
        iconElement.className = iconClass;
    }
    
    messageInput.addEventListener('input', updatePreview);
    typeSelect.addEventListener('change', updatePreview);
    
    // Initial preview update
    updatePreview();
});
</script>

<style>
.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
}

.notification-icon.info {
    background-color: #cce5ff;
    color: #0d6efd;
}

.notification-icon.success {
    background-color: #d4edda;
    color: #198754;
}

.notification-icon.warning {
    background-color: #fff3cd;
    color: #ffc107;
}

.notification-icon.danger {
    background-color: #f8d7da;
    color: #dc3545;
}

.preview-notification {
    padding: 10px;
    border: 1px dashed #dee2e6;
    border-radius: 5px;
}

.notification-time {
    font-size: 0.8rem;
    color: #6c757d;
}
</style>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 