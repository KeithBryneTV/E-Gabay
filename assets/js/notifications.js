/**
 * Notifications JavaScript functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notifications
    initializeNotifications();
    
    // Add event listeners for Mark All as Read button
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }
    
    // Add polling for new notifications if user is logged in
    if (document.getElementById('notificationsDropdown')) {
        // Poll every 60 seconds
        setInterval(updateNotificationCount, 60000);
    }
});

/**
 * Initialize notifications
 */
function initializeNotifications() {
    const notificationBell = document.getElementById('notificationsDropdown');
    
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            // When dropdown is clicked, check for new notifications
            updateNotifications();
        });
    }
}

/**
 * Mark a notification as read
 */
function markNotificationRead(event, notificationId) {
    if (!notificationId) return;
    
    // Don't mark as read if clicking on action buttons
    if (event.target.classList.contains('notification-action')) {
        event.preventDefault();
        return;
    }
    
    const BASE_URL = document.querySelector('meta[name="base-url"]').content;
    fetch(BASE_URL + '/api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the notification in the UI
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
                const unreadDot = notificationItem.querySelector('.text-primary');
                if (unreadDot) unreadDot.remove();
            }
            
            // Update the notification count
            updateNotificationCount();
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead() {
    const BASE_URL = document.querySelector('meta[name="base-url"]').content;
    fetch(BASE_URL + '/api/mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all notifications in the UI
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            unreadNotifications.forEach(notification => {
                notification.classList.remove('unread');
                const unreadDot = notification.querySelector('.text-primary');
                if (unreadDot) unreadDot.remove();
            });
            
            // Update the notification count
            const notificationBadge = document.querySelector('.notification-badge');
            if (notificationBadge) notificationBadge.style.display = 'none';
            
            // Update the notification header
            const notificationHeader = document.querySelector('.notification-header .badge');
            if (notificationHeader) notificationHeader.style.display = 'none';
            
            // Hide the mark all as read button
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (markAllReadBtn) markAllReadBtn.parentNode.style.display = 'none';
        }
    })
    .catch(error => console.error('Error marking all notifications as read:', error));
}

/**
 * Update notification count
 */
function updateNotificationCount() {
    const BASE_URL = document.querySelector('meta[name="base-url"]').content;
    fetch(BASE_URL + '/api/get_notification_count.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.count !== undefined) {
            // Update the badge
            const notificationBadge = document.querySelector('.notification-badge');
            if (data.count > 0) {
                // Create badge if it doesn't exist
                if (!notificationBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    document.getElementById('notificationsDropdown').appendChild(badge);
                } else {
                    // Update existing badge
                    notificationBadge.textContent = data.count > 99 ? '99+' : data.count;
                    notificationBadge.style.display = 'block';
                }
            } else if (notificationBadge) {
                // Hide badge if count is 0
                notificationBadge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error updating notification count:', error));
}

/**
 * Update notifications list
 */
function updateNotifications() {
    const BASE_URL = document.querySelector('meta[name="base-url"]').content;
    fetch(BASE_URL + '/api/get_notifications.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.notifications) {
            // Update the notifications list
            const notificationList = document.querySelector('.notification-list');
            if (notificationList && data.notifications.length > 0) {
                notificationList.innerHTML = '';
                
                data.notifications.forEach(notification => {
                    const isRead = notification.is_read == 0 ? false : true;
                    let icon = 'info-circle';
                    
                    // Set icon based on type or category
                    if (notification.category) {
                        switch (notification.category) {
                            case 'message':
                                icon = 'envelope';
                                break;
                            case 'consultation':
                                icon = 'calendar';
                                break;
                            case 'system':
                                icon = 'cog';
                                break;
                            default:
                                icon = 'bell';
                        }
                    } else if (notification.message.includes('message')) {
                        icon = 'envelope';
                    } else if (notification.message.includes('consultation')) {
                        icon = 'calendar';
                    }
                    
                    const notificationItem = document.createElement('a');
                    notificationItem.className = `dropdown-item notification-item ${!isRead ? 'unread' : ''}`;
                    notificationItem.href = notification.link || '#';
                    notificationItem.dataset.notificationId = notification.id;
                    notificationItem.onclick = function(event) {
                        markNotificationRead(event, notification.id);
                    };
                    
                    notificationItem.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="notification-icon ${notification.notification_type || notification.type || 'info'}">
                                <i class="fas fa-${icon}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div>${notification.message}</div>
                                <div class="notification-time">${timeAgo(notification.created_at)}</div>
                            </div>
                            ${!isRead ? '<span class="ms-2 text-primary"><i class="fas fa-circle" style="font-size: 0.5rem;"></i></span>' : ''}
                        </div>
                    `;
                    
                    notificationList.appendChild(notificationItem);
                });
                
                // Update the header badge
                const headerBadge = document.querySelector('.notification-header .badge');
                const unreadCount = data.notifications.filter(notification => notification.is_read == 0).length;
                
                if (headerBadge) {
                    if (unreadCount > 0) {
                        headerBadge.textContent = `${unreadCount} new`;
                        headerBadge.style.display = 'inline-block';
                    } else {
                        headerBadge.style.display = 'none';
                    }
                }
                
                // Update the footer
                const markAllReadBtn = document.getElementById('markAllReadBtn');
                if (markAllReadBtn) {
                    markAllReadBtn.parentNode.style.display = unreadCount > 0 ? 'inline' : 'none';
                }
            } else {
                // No notifications
                notificationList.innerHTML = `
                    <div class="dropdown-item notification-item text-muted text-center py-3">
                        <i class="fas fa-bell-slash me-2"></i> No notifications
                    </div>
                `;
            }
        }
    })
    .catch(error => console.error('Error updating notifications:', error));
}

/**
 * Format time ago
 */
function timeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diff = Math.floor((now - past) / 1000); // seconds
    
    if (diff < 60) {
        return 'Just now';
    } else if (diff < 3600) {
        const mins = Math.floor(diff / 60);
        return `${mins} minute${mins > 1 ? 's' : ''} ago`;
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else if (diff < 2592000) {
        const weeks = Math.floor(diff / 604800);
        return `${weeks} week${weeks > 1 ? 's' : ''} ago`;
    } else if (diff < 31536000) {
        const months = Math.floor(diff / 2592000);
        return `${months} month${months > 1 ? 's' : ''} ago`;
    } else {
        const years = Math.floor(diff / 31536000);
        return `${years} year${years > 1 ? 's' : ''} ago`;
    }
} 