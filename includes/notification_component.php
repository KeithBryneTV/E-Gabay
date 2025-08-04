<?php
/**
 * Shared notification component for all roles
 * Include this file in each role's dashboard to display notifications
 */

// Get user data
$user_id = $_SESSION['user_id'];
$role = getUserRoleSafe($user_id);

// Get system notifications
$system_notifications = getSystemNotifications($user_id, 10);

// Get message notifications
$message_notifications = getMessageAndConsultationNotifications($user_id, 10);

// Merge notifications and sort by date
$notifications = array_merge($system_notifications, $message_notifications);
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Notifications</h5>
    </div>
    <div class="card-body">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-3">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">No new notifications</p>
            </div>
        <?php else: ?>
            <div class="list-group notification-list">
                <?php foreach (array_slice($notifications, 0, 5) as $notification): 
                    $type = $notification['notification_type'] ?? $notification['type'] ?? 'info';
                    $is_read = isset($notification['is_read']) && $notification['is_read'] == 0 ? false : true;
                    $link = isset($notification['link']) && $notification['link'] ? $notification['link'] : SITE_URL . '/dashboard/view_notification.php?id=' . $notification['id'];
                    
                    // Set specific links for message notifications
                    if (isset($notification['category']) && $notification['category'] === 'message') {
                        // Extract consultation ID from message for chat redirect
                        if (preg_match('/Consultation #(\d+)/', $notification['message'], $matches)) {
                            $consultation_id = $matches[1];
                            $link = SITE_URL . '/dashboard/' . $role . '/view_consultation.php?id=' . $consultation_id;
                        } elseif (preg_match('/consultation #(\d+)/i', $notification['message'], $matches)) {
                            $consultation_id = $matches[1];
                            $link = SITE_URL . '/dashboard/' . $role . '/view_consultation.php?id=' . $consultation_id;
                        }
                    } elseif (strpos(strtolower($notification['message']), 'message') !== false && strpos(strtolower($notification['message']), 'consultation') !== false) {
                        // Try to extract consultation ID from any message containing both "message" and "consultation"
                        if (preg_match('/#(\d+)/', $notification['message'], $matches)) {
                            $consultation_id = $matches[1];
                            $link = SITE_URL . '/dashboard/' . $role . '/view_consultation.php?id=' . $consultation_id;
                         }
                     } elseif (strpos(strtolower($notification['message']), 'new message from') !== false) {
                        // Handle "New message from" notifications - redirect to chat
                        if (isset($notification['link']) && strpos($notification['link'], 'chat.php') !== false) {
                            $link = $notification['link']; // Use the chat link directly
                        }
                    } elseif (strpos(strtolower($notification['message']), 'has been approved by') !== false) {
                        // Handle "has been approved by" notifications - redirect to consultation view
                        if (isset($notification['link']) && strpos($notification['link'], 'view_consultation.php') !== false) {
                            $link = $notification['link']; // Use the consultation link directly
                        }
                    }
                    
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
                    
                    // Default icon if none set
                    if (!isset($icon)) {
                        $icon = 'info-circle';
                    }
                    
                    // Ensure link is always set for proper clickability  
                    if (empty($link)) {
                        $link = SITE_URL . '/dashboard/view_notification.php?id=' . $notification['id'];
                    }
                ?>
                <a class="list-group-item list-group-item-action <?php echo !$is_read ? 'notification-unread' : ''; ?>" href="<?php echo $link; ?>">
                    <div class="d-flex align-items-center">
                        <div class="notification-icon <?php echo $type; ?> me-3">
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="<?php echo !$is_read ? 'fw-bold' : ''; ?>"><?php echo $notification['message']; ?></div>
                            <div class="notification-time"><?php echo timeAgo($notification['created_at']); ?></div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($notifications) > 5): ?>
                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>/notifications.php" class="btn btn-sm btn-outline-primary">
                        View All (<?php echo count($notifications); ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-unread {
    border-left: 4px solid #0d6efd;
    background-color: #f0f7ff;
}
.notification-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
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
.notification-time {
    font-size: 0.8rem;
    color: #6c757d;
}
</style> 