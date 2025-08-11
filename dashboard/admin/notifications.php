<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Include required files
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/includes/utility.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Utility.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title and current page for navigation highlighting
$page_title = 'Admin Notifications';
$current_page = 'notifications';

// Get user data
$user_id = $_SESSION['user_id'];
$role = getUserRole();

// Clean up orphaned admin notifications before displaying
$cleanup_results = cleanupAdminNotifications($user_id);

// Show cleanup message if notifications were automatically cleaned
if (!isset($_GET['cleanup_orphaned'])) {
    $auto_cleaned = ($cleanup_results['deleted_consultation_notifications'] ?? 0) + 
                   ($cleanup_results['deleted_user_notifications'] ?? 0) + 
                   ($cleanup_results['deleted_orphaned_notifications'] ?? 0);
    
    if ($auto_cleaned > 0) {
        $_SESSION['message'] = "Auto-cleanup: Removed {$auto_cleaned} orphaned notifications.";
        $_SESSION['message_type'] = 'info';
    }
}

// Mark notification as read if requested
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notification_id = $_GET['id'];
    markSystemNotificationsAsRead($user_id, $notification_id);
    
    // Redirect back to notifications page
    header("Location: " . rtrim(SITE_URL, '/') . "/dashboard/admin/notifications.php");
    exit;
}

// Clear (delete) single notification if requested
if (isset($_GET['clear']) && isset($_GET['id'])) {
    $notification_id = $_GET['id'];
    clearNotification($user_id, $notification_id);
    header("Location: " . rtrim(SITE_URL, '/') . "/dashboard/admin/notifications.php");
    exit;
}

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    markSystemNotificationsAsRead($user_id);
    
    // Redirect back to notifications page
    header("Location: " . rtrim(SITE_URL, '/') . "/dashboard/admin/notifications.php");
    exit;
}

// Clear all notifications if requested
if (isset($_GET['clear_all'])) {
    clearSystemNotifications($user_id);
    header("Location: " . rtrim(SITE_URL, '/') . "/dashboard/admin/notifications.php");
    exit;
}

// Manual cleanup if requested
if (isset($_GET['cleanup_orphaned'])) {
    $manual_cleanup = cleanupAdminNotifications($user_id);
    $total_cleaned = ($manual_cleanup['deleted_consultation_notifications'] ?? 0) + 
                    ($manual_cleanup['deleted_user_notifications'] ?? 0) + 
                    ($manual_cleanup['deleted_orphaned_notifications'] ?? 0);
    
    if ($total_cleaned > 0) {
        $_SESSION['message'] = "Cleanup completed! Removed {$total_cleaned} orphaned notifications.";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "No orphaned notifications found to clean up.";
        $_SESSION['message_type'] = 'info';
    }
    
    header("Location: " . rtrim(SITE_URL, '/') . "/dashboard/admin/notifications.php");
    exit;
}

// Get notifications
// Get system notifications
$system_notifications = getSystemNotifications($user_id, 100);

// Get message notifications
$message_notifications = getMessageAndConsultationNotifications($_SESSION['user_id'], 100);

// Merge notifications and sort by date
$notifications = array_merge($system_notifications, $message_notifications);
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="notification-page-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i> Admin Notifications
                    </h5>
                    <div class="btn-group">
                        <?php if (!empty($notifications)): ?>
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/notifications.php?mark_all_read=1" class="btn btn-light btn-sm">
                            <i class="fas fa-check-double me-1"></i> Mark All as Read
                        </a>
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/notifications.php?clear_all=1" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash me-1"></i> Clear All
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/notifications.php?cleanup_orphaned=1" class="btn btn-warning btn-sm" title="Clean up notifications pointing to deleted data">
                            <i class="fas fa-broom me-1"></i> Clean Orphaned
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php displayMessage(); ?>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash mb-3"></i>
                            <h4>No Notifications</h4>
                            <p>You don't have any notifications at this time.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="notification-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 10%">Type</th>
                                        <th style="width: 50%">Message</th>
                                        <th style="width: 15%">Date</th>
                                        <th style="width: 10%">Status</th>
                                        <th style="width: 10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    foreach ($notifications as $notification): 
                                        $type = $notification['notification_type'] ?? $notification['type'] ?? 'info';
                                        $is_read = isset($notification['is_read']) && $notification['is_read'] == 0 ? false : true;
                                        $icon = 'info-circle';
                                        $link = isset($notification['link']) && $notification['link'] ? $notification['link'] : '#';
                                        $notification_id = $notification['id'] ?? '';
                                        
                                        // Set icon based on type or category
                                        if (isset($notification['category'])) {
                                            switch ($notification['category']) {
                                                case 'message':
                                                    $icon = 'envelope';
                                                    break;
                                                case 'consultation':
                                                    $icon = 'calendar';
                                                    break;
                                                case 'system':
                                                    $icon = 'cog';
                                                    break;
                                                default:
                                                    $icon = 'bell';
                                            }
                                        } elseif (strpos($notification['message'], 'message') !== false) {
                                            $icon = 'envelope';
                                        } elseif (strpos($notification['message'], 'consultation') !== false) {
                                            $icon = 'calendar';
                                        }

                                        // Get badge color based on type
                                        $badge_color = 'primary';
                                        switch ($type) {
                                            case 'success':
                                                $badge_color = 'success';
                                                break;
                                            case 'warning':
                                                $badge_color = 'warning';
                                                break;
                                            case 'danger':
                                                $badge_color = 'danger';
                                                break;
                                            default:
                                                $badge_color = 'primary';
                                        }
                                    ?>
                                    <tr class="<?php echo !$is_read ? 'unread' : ''; ?>">
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge_color; ?>">
                                                <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                                <?php echo ucfirst($type); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $notification['message']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($notification['created_at'])); ?></td>
                                        <td>
                                            <?php if ($is_read): ?>
                                                <span class="badge bg-secondary">Read</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">New</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!$is_read): ?>
                                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/notifications.php?mark_read=1&id=<?php echo $notification_id; ?>" class="btn btn-outline-primary" title="Mark as Read">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/notifications.php?clear=1&id=<?php echo $notification_id; ?>" class="btn btn-outline-danger" title="Clear">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <a href="<?php echo ($link && $link != '#') ? $link : rtrim(SITE_URL, '/') . '/dashboard/view_notification.php?id=' . $notification_id; ?>" class="btn btn-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
        </div>
    </div>
</div>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 