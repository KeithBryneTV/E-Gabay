<?php
// dashboard/view_notification.php
// Show details of a single notification (all roles)

require_once __DIR__ . '/../includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/includes/utility.php';

// Require login
if (!isLoggedIn()) {
    header('Location: ' . rtrim(SITE_URL, '/') . '/login');
    exit;
}

$user_id = $_SESSION['user_id'];
$role     = getUserRoleSafe($user_id);
$notif_id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';

if (!$notif_id) {
    redirect(rtrim(SITE_URL, '/') . '/dashboard/' . $role . '/notifications.php');
}

// If prefixed ids (msg_/cons_) simply redirect to link resolution logic in JS pages
if (strpos($notif_id, 'msg_') === 0 || strpos($notif_id, 'cons_') === 0) {
    // Fallback: mark read then redirect back
    markSystemNotificationsAsRead($user_id, $notif_id);
    redirect(rtrim(SITE_URL, '/') . '/dashboard/' . $role . '/notifications.php');
}

// Fetch notification
$db  = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM system_notifications WHERE id = ? AND (user_id = ? OR target_role = ?)");
$stmt->execute([str_replace('sys_', '', $notif_id), $user_id, $role]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    setMessage('Notification not found or access denied.', 'danger');
    redirect(rtrim(SITE_URL, '/') . '/dashboard/' . $role . '/notifications.php');
}

// Mark as read if not yet
if ($notification['is_read'] == 0) {
    markSystemNotificationsAsRead($user_id, 'sys_' . $notification['id']);
}

$page_title   = 'Notification Detail';
$current_page = 'notifications';
include_once $base_path . '/includes/header.php';
?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow mt-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Notification</h5>
                    <a href="<?php echo SITE_URL . '/dashboard/' . $role . '/notifications.php'; ?>" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($notification['message']); ?></h5>
                    <p class="text-muted mb-2"><i class="fas fa-clock me-1"></i> <?php echo formatDate($notification['created_at'], 'M d, Y h:i A'); ?></p>
                    <p><span class="badge bg-<?php echo $notification['notification_type']; ?> text-uppercase"><?php echo $notification['notification_type']; ?></span></p>

                    <?php if ($notification['link']): ?>
                    <a href="<?php echo $notification['link']; ?>" class="btn btn-primary" target="_blank"><i class="fas fa-external-link-alt me-1"></i> Open Related Page</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once $base_path . '/includes/footer.php'; ?> 