<?php
// Include path fix helper
require_once __DIR__ . '/includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/includes/utility.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get token from URL
$token = sanitizeInput($_GET['token'] ?? '');

if (empty($token)) {
    setMessage('Invalid or missing reset token.', 'danger');
    redirect('forgot_password');
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if token exists and is not expired (1 hour expiry)
$query = "SELECT user_id, email, created_at FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$stmt = $db->prepare($query);
$stmt->execute([$token]);
$reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset_request) {
    setMessage('Invalid or expired reset token. Please request a new password reset.', 'danger');
    redirect('forgot_password');
    exit;
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password)) {
        setMessage('Please enter a new password.', 'danger');
    } elseif (strlen($new_password) < 6) {
        setMessage('Password must be at least 6 characters long.', 'danger');
    } elseif ($new_password !== $confirm_password) {
        setMessage('Passwords do not match.', 'danger');
    } else {
        try {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update user password
            $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$hashed_password, $reset_request['user_id']]);
            
            // Delete the used token
            $delete_query = "DELETE FROM password_resets WHERE token = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$token]);
            
            setMessage('Your password has been successfully reset. You can now log in with your new password.', 'success');
            redirect('login');
            exit;
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            setMessage('An error occurred while resetting your password. Please try again.', 'danger');
        }
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0">Reset Your Password</h4>
                </div>
                <div class="card-body">
                    <?php displayMessage(); ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/login">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include_once $base_path . '/includes/footer.php'; ?> 