<?php
// Utility functions for the application

// Include required classes
require_once __DIR__ . '/../classes/Database.php';

/**
 * Format date in Philippine timezone
 * 
 * @param string $date Date string
 * @param string $format Format string (default: 'Y-m-d')
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d') {
    if (!$date) return '';
    
    // Create DateTime object with UTC and convert to Philippine timezone
    try {
        $dateTime = new DateTime($date, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Manila'));
        return $dateTime->format($format);
    } catch (Exception $e) {
        // Fallback for invalid dates
        $timestamp = strtotime($date);
        if ($timestamp === false) return '';
        return date($format, $timestamp);
    }
}

/**
 * Format time in Philippine timezone
 * 
 * @param string $time Time string
 * @param string $format Format string (default: 'h:i A')
 * @return string Formatted time
 */
function formatTime($time, $format = 'h:i A') {
    if (!$time) return '';
    
    // Force local timezone and prevent UTC conversion
    try {
        // Set timezone to Asia/Manila first
        date_default_timezone_set('Asia/Manila');
        
        // Create DateTime object with local timezone
        $dateTime = new DateTime($time, new DateTimeZone('Asia/Manila'));
        return $dateTime->format($format);
    } catch (Exception $e) {
        // Fallback for invalid times
        date_default_timezone_set('Asia/Manila');
        $timestamp = strtotime($time);
        if ($timestamp === false) return '';
        return date($format, $timestamp);
    }
}

/**
 * Calculate time ago in Philippine timezone
 * 
 * @param string $datetime Date and time string
 * @return string Time ago
 */
function timeAgo($datetime) {
    try {
        // Create DateTime objects in Philippine timezone
        $timeObj = new DateTime($datetime, new DateTimeZone('UTC'));
        $timeObj->setTimezone(new DateTimeZone('Asia/Manila'));
        
        $nowObj = new DateTime('now', new DateTimeZone('Asia/Manila'));
        
        $diff = $nowObj->getTimestamp() - $timeObj->getTimestamp();
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    } catch (Exception $e) {
        // Fallback to old method
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } else {
            return formatDate($datetime, 'M d, Y');
        }
    }
}


/**
 * Sanitize input
 * 
 * @param string $input Input string
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Display a message to the user
 * 
 * @return void
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        echo '<div class="alert alert-' . $message_type . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        // Clear the message
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

/**
 * Set message in session
 * 
 * @param string $message Message
 * @param string $type Message type (success, danger, warning, info)
 * @return void
 */
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Redirect to URL
 * 
 * @param string $url URL
 * @return void
 */
function redirect($url) {
    // If the URL is relative (no scheme), prepend SITE_URL
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = rtrim(SITE_URL, '/') . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit;
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Check if feedback exists for a consultation
 * 
 * @param int $consultation_id Consultation ID
 * @param int $student_id Student ID
 * @return bool True if feedback exists, false otherwise
 */
function hasFeedback($consultation_id, $student_id) {
    global $db;
    
    $query = "SELECT COUNT(*) FROM feedback WHERE consultation_id = ? AND student_id = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$consultation_id, $student_id]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Error checking feedback: " . $e->getMessage());
        return false;
    }
}

/**
 * Get system settings
 * 
 * @param string $key Setting key
 * @return string Setting value
 */
function getSetting($key) {
    global $db;
    
    $query = "SELECT value FROM settings WHERE setting_key = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : '';
    } catch (Exception $e) {
        error_log("Error getting setting: " . $e->getMessage());
        return '';
    }
}

/**
 * Update system setting
 * 
 * @param string $key Setting key
 * @param string $value Setting value
 * @return bool True if successful, false otherwise
 */
function updateSetting($key, $value) {
    global $db;
    
    $query = "INSERT INTO settings (setting_key, value) 
              VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE value = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user system notifications
 * 
 * @param int $user_id User ID
 * @param int $limit Number of notifications to get
 * @param string $category Filter by category (optional)
 * @return array User notifications
 */
function getSystemNotifications($user_id, $limit = 10, $category = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user role using our safe function
    $role = getUserRoleSafe($user_id);
    
    // Check if the system_notifications table exists
    try {
        $check_query = "SHOW TABLES LIKE 'system_notifications'";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute();
        $table_exists = $check_stmt->fetch();
        
        if (!$table_exists) {
            // Table doesn't exist, return empty array
            return [];
        }
    } catch (Exception $e) {
        error_log("Error checking system_notifications table: " . $e->getMessage());
        return [];
    }
    
    // Clean up orphaned system notifications
    cleanupOrphanedNotifications($db);
    
    $limit = intval($limit);
    $query = "SELECT sn.*
              FROM system_notifications sn
              LEFT JOIN users u ON sn.user_id = u.user_id
              WHERE (sn.user_id = ? OR sn.target_role = ?)
              AND (sn.user_id IS NULL OR u.is_active = 1)";
              
    if ($category) {
        $query .= " AND sn.category = ?";
    }
    
    $query .= " ORDER BY sn.created_at DESC
                LIMIT {$limit}";
    
    try {
        $stmt = $db->prepare($query);
        
        if ($category) {
            $stmt->execute([$user_id, $role, $category]);
        } else {
            $stmt->execute([$user_id, $role]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting system notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread system notification count
 * 
 * @param int $user_id User ID
 * @param string $category Filter by category (optional)
 * @return int Unread notification count
 */
function getUnreadSystemNotificationCount($user_id, $category = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user role using our safe function
    $role = getUserRoleSafe($user_id);
    
    $query = "SELECT COUNT(*) as count
              FROM system_notifications sn
              LEFT JOIN users u ON sn.user_id = u.user_id
              WHERE (sn.user_id = ? OR sn.target_role = ?) 
              AND sn.is_read = 0
              AND (sn.user_id IS NULL OR u.is_active = 1)";
              
    if ($category) {
        $query .= " AND sn.category = ?";
    }
    
    try {
        $stmt = $db->prepare($query);
        
        if ($category) {
            $stmt->execute([$user_id, $role, $category]);
        } else {
            $stmt->execute([$user_id, $role]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting unread system notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark system notifications as read
 * 
 * @param int $user_id User ID
 * @param int $notification_id Notification ID (optional)
 * @param string $category Notification category (optional)
 * @return bool True on success, false on failure
 */
function markSystemNotificationsAsRead($user_id, $notification_id = null, $category = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user role using our safe function
    $role = getUserRoleSafe($user_id);
    
    $query = "UPDATE system_notifications
              SET is_read = 1, read_at = NOW()
              WHERE user_id = ? OR target_role = ?";
    
    $params = [$user_id, $role];
    
    if ($notification_id) {
        // Handle the case where notification_id is in format 'sys_123'
        if (strpos($notification_id, 'sys_') === 0) {
            $id = substr($notification_id, 4);
            $query .= " AND id = ?";
            $params[] = $id;
        } else {
            $query .= " AND id = ?";
            $params[] = $notification_id;
        }
    }
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return true;
    } catch (Exception $e) {
        error_log("Error marking system notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete (clear) system notifications
 *
 * @param int $user_id User ID
 * @param int|null $notification_id Notification ID (optional)
 * @param string|null $category Category filter (optional)
 * @return bool True on success, false otherwise
 */
function clearSystemNotifications($user_id, $notification_id = null, $category = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Determine role for role-based notifications
    $role = getUserRoleSafe($user_id);

    // If admin is clearing all notifications, run orphaned cleanup first
    if ($role === 'admin' && !$notification_id && !$category) {
        cleanupAdminNotifications($user_id);
    }

    $query = "DELETE FROM system_notifications WHERE (user_id = ? OR target_role = ?)";
    $params = [$user_id, $role];

    if ($notification_id) {
        if (strpos($notification_id, 'sys_') === 0) {
            $notification_id = substr($notification_id, 4);
        }
        $query .= " AND id = ?";
        $params[] = $notification_id;
    }

    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }

    try {
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("Error clearing system notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * General clear notification helper (supports msg_, cons_, sys_)
 */
function clearNotification($user_id, $notification_id) {
    if (!$notification_id) return false;

    if (strpos($notification_id, 'msg_') === 0) {
        // mark chat message read
        $message_id = substr($notification_id, 4);
        $db = (new Database())->getConnection();
        $query = "UPDATE chat_messages SET is_read = 1 WHERE id = ? AND (user_id != ?)";
        try {
            $stmt = $db->prepare($query);
            return $stmt->execute([$message_id, $user_id]);
        } catch(Exception $e) {
            error_log('Error clearing message notif: '.$e->getMessage());
            return false;
        }
    }
    if (strpos($notification_id, 'cons_') === 0) {
        // No persistence; treat as cleared
        return true;
    }
    // default sys_
    return clearSystemNotifications($user_id, $notification_id);
}

/**
 * Get consolidated notification count for the topbar bell
 * 
 * @param int $user_id User ID
 * @return int Total notification count
 */
function getTotalNotificationCount($user_id) {
    $message_count = getUnreadMessageAndConsultationCount($user_id);
    $system_count = getUnreadSystemNotificationCount($user_id);
    
    return $message_count + $system_count;
}

/**
 * Get unread message and consultation notification count for a user
 * 
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function getUnreadMessageAndConsultationCount($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        // Check for unread messages (excluding deleted/inactive users)
        $query = "SELECT COUNT(*) as count FROM chat_messages cm
                  JOIN chat_sessions cs ON cm.chat_id = cs.id
                  JOIN consultation_requests cr ON cs.consultation_id = cr.id
                  JOIN users u ON cm.user_id = u.user_id
                  JOIN users us ON cs.student_id = us.user_id
                  JOIN users uc ON cs.counselor_id = uc.user_id
                  WHERE ((cs.student_id = ? AND cm.user_id != ?) OR (cs.counselor_id = ? AND cm.user_id != ?))
                  AND cm.is_read = 0
                  AND u.is_active = 1
                  AND us.is_active = 1
                  AND uc.is_active = 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $unread_messages = $result ? $result['count'] : 0;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
    
    // Check for pending/approved consultations based on role
    $role = getUserRoleSafe($user_id);
    
    if ($role == 'student') {
        // For students: check for approved consultations
        $query = "SELECT COUNT(*) as count FROM consultation_requests cr
                  JOIN users uc ON cr.counselor_id = uc.user_id
                  JOIN users us ON cr.student_id = us.user_id
                  WHERE cr.student_id = ? AND cr.status = 'live' AND 
                  cr.updated_at > (NOW() - INTERVAL 24 HOUR)
                  AND uc.is_active = 1 AND us.is_active = 1";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $pending_consultations = $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error getting approved consultations: " . $e->getMessage());
            $pending_consultations = 0;
        }
    } elseif ($role == 'counselor') {
        // For counselors: check for assigned consultations
        $query = "SELECT COUNT(*) as count FROM consultation_requests cr
                  JOIN users us ON cr.student_id = us.user_id
                  JOIN users uc ON cr.counselor_id = uc.user_id
                  WHERE cr.counselor_id = ? AND cr.status = 'live' AND 
                  cr.updated_at > (NOW() - INTERVAL 24 HOUR)
                  AND us.is_active = 1 AND uc.is_active = 1";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $pending_consultations = $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error getting assigned consultations: " . $e->getMessage());
            $pending_consultations = 0;
        }
    } else {
        $pending_consultations = 0;
    }
    
    return $unread_messages + $pending_consultations;
}

/**
 * Get user's role name safely
 * 
 * @param int $user_id Optional user ID (uses SESSION user_id if not provided)
 * @return string User's role name or empty string if not found
 */
function getUserRoleSafe($user_id = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return '';
    }
    
    $role = '';
    
    try {
        // Check if the role_name column exists in users table
        $check_query = "SHOW COLUMNS FROM users LIKE 'role_name'";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute();
        $role_column_exists = $check_stmt->fetch();
        
        if ($role_column_exists) {
            // Use role_name directly
            $role_query = "SELECT role_name FROM users WHERE user_id = ?";
            $role_stmt = $db->prepare($role_query);
            $role_stmt->execute([$user_id]);
            $result = $role_stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $role = $result['role_name'];
            }
        } else {
            // Use role_id and map it
            $role_query = "SELECT role_id FROM users WHERE user_id = ?";
            $role_stmt = $db->prepare($role_query);
            $role_stmt->execute([$user_id]);
            $result = $role_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                switch ($result['role_id']) {
                    case 1:
                        $role = 'student';
                        break;
                    case 2:
                        $role = 'counselor';
                        break;
                    case 3:
                        $role = 'admin';
                        break;
                    case 4:
                        $role = 'staff';
                        break;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error determining user role: " . $e->getMessage());
    }
    
    return $role;
}

/**
 * Clean up orphaned notifications that reference deleted data
 * 
 * @param PDO $db Database connection
 * @return array Cleanup results with counts of affected records
 */
function cleanupOrphanedNotifications($db) {
    $results = [
        'orphaned_messages' => 0,
        'orphaned_sessions' => 0,
        'orphaned_consultations' => 0,
        'orphaned_system_notifications' => 0,
        'errors' => []
    ];
    
    try {
        // Clean up chat messages that reference deleted users or sessions
        $cleanup_queries = [
            // Mark chat messages as read if the user who sent them no longer exists
            [
                'query' => "UPDATE chat_messages cm 
                           LEFT JOIN users u ON cm.user_id = u.user_id 
                           SET cm.is_read = 1 
                           WHERE u.user_id IS NULL OR u.is_active = 0",
                'result_key' => 'orphaned_messages'
            ],
            
            // Mark chat messages as read if the chat session no longer exists
            [
                'query' => "UPDATE chat_messages cm 
                           LEFT JOIN chat_sessions cs ON cm.chat_id = cs.id 
                           SET cm.is_read = 1 
                           WHERE cs.id IS NULL",
                'result_key' => 'orphaned_sessions'
            ],
            
            // Mark chat messages as read if the consultation request no longer exists
            [
                'query' => "UPDATE chat_messages cm 
                           JOIN chat_sessions cs ON cm.chat_id = cs.id 
                           LEFT JOIN consultation_requests cr ON cs.consultation_id = cr.id 
                           SET cm.is_read = 1 
                           WHERE cr.id IS NULL",
                'result_key' => 'orphaned_consultations'
            ],
            
            // Delete system notifications that reference deleted users
            [
                'query' => "DELETE sn FROM system_notifications sn 
                           LEFT JOIN users u ON sn.user_id = u.user_id 
                           WHERE sn.user_id IS NOT NULL AND (u.user_id IS NULL OR u.is_active = 0)",
                'result_key' => 'orphaned_system_notifications'
            ]
        ];
        
        foreach ($cleanup_queries as $cleanup) {
            $stmt = $db->prepare($cleanup['query']);
            $stmt->execute();
            $results[$cleanup['result_key']] = $stmt->rowCount();
        }
        
    } catch (Exception $e) {
        $error_msg = "Error cleaning up orphaned notifications: " . $e->getMessage();
        error_log($error_msg);
        $results['errors'][] = $error_msg;
    }
    
    return $results;
}

/**
 * Run a comprehensive cleanup of orphaned notifications (can be called periodically)
 * 
 * @return array Cleanup results
 */
function runNotificationCleanup() {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        return ['error' => 'Database connection failed'];
    }
    
    $results = cleanupOrphanedNotifications($db);
    
    // Log the cleanup results
    $total_cleaned = array_sum(array_filter($results, 'is_numeric'));
    error_log("Notification cleanup completed. Total records processed: {$total_cleaned}");
    
    return $results;
}

/**
 * Clean up admin notifications that point to deleted data
 * 
 * @param int $admin_user_id Admin user ID
 * @return array Cleanup results
 */
function cleanupAdminNotifications($admin_user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        return ['error' => 'Database connection failed'];
    }
    
    $results = [
        'deleted_consultation_notifications' => 0,
        'deleted_user_notifications' => 0,
        'deleted_orphaned_notifications' => 0,
        'errors' => []
    ];
    
    try {
        // Clean up consultation notifications pointing to deleted consultations
        $consultation_cleanup = "DELETE sn FROM system_notifications sn 
                               WHERE (sn.user_id = ? OR sn.target_role = 'admin') 
                               AND sn.category = 'consultation' 
                               AND sn.link IS NOT NULL 
                               AND sn.link REGEXP 'consultation.*id=[0-9]+' 
                               AND NOT EXISTS (
                                   SELECT 1 FROM consultation_requests cr 
                                   WHERE CONCAT('/dashboard/admin/view_consultation.php?id=', cr.id) = SUBSTRING(sn.link, LOCATE('/dashboard/', sn.link))
                                   OR CONCAT('/dashboard/counselor/view_consultation.php?id=', cr.id) = SUBSTRING(sn.link, LOCATE('/dashboard/', sn.link))
                               )";
        $stmt = $db->prepare($consultation_cleanup);
        $stmt->execute([$admin_user_id]);
        $results['deleted_consultation_notifications'] = $stmt->rowCount();
        
        // Clean up user-related notifications pointing to deleted/inactive users
        $user_cleanup = "DELETE sn FROM system_notifications sn 
                        LEFT JOIN users u ON sn.user_id = u.user_id 
                        WHERE sn.user_id IS NOT NULL 
                        AND (u.user_id IS NULL OR u.is_active = 0)";
        $stmt = $db->prepare($user_cleanup);
        $stmt->execute();
        $results['deleted_user_notifications'] = $stmt->rowCount();
        
        // Clean up very old notifications (older than 30 days) to prevent buildup
        $old_cleanup = "DELETE FROM system_notifications 
                       WHERE (user_id = ? OR target_role = 'admin') 
                       AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $db->prepare($old_cleanup);
        $stmt->execute([$admin_user_id]);
        $results['deleted_orphaned_notifications'] = $stmt->rowCount();
        
    } catch (Exception $e) {
        $error_msg = "Error cleaning up admin notifications: " . $e->getMessage();
        error_log($error_msg);
        $results['errors'][] = $error_msg;
    }
    
    return $results;
}

/**
 * Get message and consultation notifications for a user
 * 
 * @param int $user_id User ID
 * @param int $limit Limit
 * @return array Notifications
 */
function getMessageAndConsultationNotifications($user_id, $limit = 5) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    $notifications = [];
    $role = getUserRoleSafe($user_id);
    
    // First, clean up orphaned notifications
    cleanupOrphanedNotifications($db);
    
    $limit = intval($limit);
    $query = "SELECT cm.*, cs.id as chat_session_id, cs.subject,
              u.first_name, u.last_name,
              cr.is_anonymous, cr.student_id AS cr_student_id
              FROM chat_messages cm
              JOIN chat_sessions cs ON cm.chat_id = cs.id
              JOIN consultation_requests cr ON cs.consultation_id = cr.id
              JOIN users u ON cm.user_id = u.user_id
              JOIN users us ON cs.student_id = us.user_id
              JOIN users uc ON cs.counselor_id = uc.user_id
              WHERE ((cs.student_id = ? AND cm.user_id != ?) OR (cs.counselor_id = ? AND cm.user_id != ?))
              AND cm.is_read = 0
              AND u.is_active = 1
              AND us.is_active = 1
              AND uc.is_active = 1
              ORDER BY cm.created_at DESC
              LIMIT {$limit}";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        $unread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($unread_messages as $message) {
            if (isset($message['message_type']) && $message['message_type'] == 'system') {
                $sender_name = 'System';
            } else {
                if ($role == 'counselor' && $message['is_anonymous'] && $message['cr_student_id'] == $message['user_id']) {
                    $sender_name = 'Anonymous Student';
                } else {
                    $sender_name = $message['first_name'] . ' ' . $message['last_name'];
                }
            }
            
            $notifications[] = [
                'id' => 'msg_' . $message['id'],
                'message' => "New message from {$sender_name} in {$message['subject']}",
                'created_at' => $message['created_at'],
                'is_read' => 0,
                'link' => rtrim(SITE_URL, '/') . '/dashboard/' . $role . '/chat.php?chat_id=' . $message['chat_session_id']
            ];
        }
    } catch (Exception $e) {
        error_log("Error getting unread messages: " . $e->getMessage());
    }
    
    // Get consultation notifications based on role
    if ($role == 'student') {
        // For students: get approved consultations
        $query = "SELECT cr.*, u.first_name, u.last_name
                  FROM consultation_requests cr
                  JOIN users u ON cr.counselor_id = u.user_id
                  JOIN users us ON cr.student_id = us.user_id
                  WHERE cr.student_id = ? AND cr.status = 'live' AND 
                  cr.updated_at > (NOW() - INTERVAL 24 HOUR)
                  AND u.is_active = 1 AND us.is_active = 1
                  ORDER BY cr.updated_at DESC
                  LIMIT {$limit}";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($consultations as $consultation) {
                $notifications[] = [
                    'id' => 'cons_' . $consultation['id'],
                    'message' => "Your consultation has been approved by {$consultation['first_name']} {$consultation['last_name']}",
                    'created_at' => $consultation['updated_at'],
                    'is_read' => 0,
                    'link' => rtrim(SITE_URL, '/') . '/dashboard/student/view_consultation.php?id=' . $consultation['id']
                ];
            }
        } catch (Exception $e) {
            error_log("Error getting approved consultations: " . $e->getMessage());
        }
    } elseif ($role == 'counselor') {
        // For counselors: get assigned consultations
        $query = "SELECT cr.*, u.first_name, u.last_name
                  FROM consultation_requests cr
                  JOIN users u ON cr.student_id = u.user_id
                  JOIN users uc ON cr.counselor_id = uc.user_id
                  WHERE cr.counselor_id = ? AND cr.status = 'live' AND 
                  cr.updated_at > (NOW() - INTERVAL 24 HOUR)
                  AND u.is_active = 1 AND uc.is_active = 1
                  ORDER BY cr.updated_at DESC
                  LIMIT {$limit}";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($consultations as $consultation) {
                $student_name = $consultation['is_anonymous'] ? 'Anonymous Student' : 
                               $consultation['first_name'] . ' ' . $consultation['last_name'];
                
                $notifications[] = [
                    'id' => 'cons_' . $consultation['id'],
                    'message' => "New consultation assigned with {$student_name}",
                    'created_at' => $consultation['updated_at'],
                    'is_read' => 0,
                    'link' => rtrim(SITE_URL, '/') . '/dashboard/counselor/view_consultation.php?id=' . $consultation['id']
                ];
            }
        } catch (Exception $e) {
            error_log("Error getting assigned consultations: " . $e->getMessage());
        }
    }
    
    // Sort notifications by created_at
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit the number of notifications
    return array_slice($notifications, 0, $limit);
}

/**
 * Mark all messages as read for a user
 * 
 * @param int $user_id User ID
 * @return bool True on success, false on failure
 */
function markAllMessagesAsRead($user_id) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    $role = getUserRoleSafe($user_id);
    
    if ($role == 'student') {
        $field = 'student_id';
    } elseif ($role == 'counselor') {
        $field = 'counselor_id';
    } else {
        return false;
    }
    
    // Mark all chat messages as read
    $query = "UPDATE chat_messages cm
              JOIN chat_sessions cs ON cm.chat_id = cs.id
              SET cm.is_read = 1
              WHERE cs.$field = ? AND cm.user_id != ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error marking messages as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user action to system_logs table
 * 
 * @param string $action The action taken
 * @param string $details Details of the action
 * @param string $ip_address Optional IP address
 * @return bool True if logged successfully, false otherwise
 */
function logAction($action, $details, $ip_address = null) {
    global $db;
    
    // Get current user ID
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Get IP address if not provided
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // Log the action
    $query = "INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $action, $details, $ip_address]);
        return true;
    } catch (Exception $e) {
        error_log("Error logging action: " . $e->getMessage());
        return false;
    }
}

/**
 * Add system notification to a user or role
 * 
 * @param int $user_id User ID (or null for role-based notification)
 * @param string $message Notification message
 * @param string $type Notification type (info, success, warning, danger)
 * @param string $category Notification category
 * @param string $link Optional link for the notification
 * @return bool True on success, false on failure
 */
function addSystemNotification($user_id, $message, $type = 'info', $category = 'system', $link = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    $target_role = null;
    
    // If user_id is a role name (string), set it as target_role
    if (is_string($user_id) && !is_numeric($user_id)) {
        $target_role = $user_id;
        $user_id = null;
    }
    
    $query = "INSERT INTO system_notifications 
              (user_id, target_role, message, notification_type, category, link) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $target_role, $message, $type, $category, $link]);
        return true;
    } catch (Exception $e) {
        error_log("Error adding system notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Add system notification to all users with a specific role
 * 
 * @param string $role Target role name
 * @param string $message Notification message
 * @param string $type Notification type (info, success, warning, danger)
 * @param string $category Notification category
 * @param string $link Optional link for the notification
 * @return bool True on success, false on failure
 */
function addRoleBroadcastNotification($message, $role, $type = 'info', $category = 'system', $link = null) {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Direct database insert to ensure it works
    $query = "INSERT INTO system_notifications 
              (user_id, target_role, message, notification_type, category, link) 
              VALUES (NULL, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$role, $message, $type, $category, $link]);
        return true;
    } catch (Exception $e) {
        error_log("Error adding role broadcast notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's avatar URL (profile picture or default)
 */
function getUserAvatarUrl($user_id) {
    $db = (new Database())->getConnection();
    $query = "SELECT profile_picture FROM users WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    global $base_path;
    $uploadsDir = SITE_URL . '/uploads/profile_pictures/';
    if ($row && !empty($row['profile_picture']) && file_exists($base_path . '/uploads/profile_pictures/' . $row['profile_picture'])) {
        return $uploadsDir . $row['profile_picture'];
    }
    return SITE_URL . '/assets/images/default-avatar.png';
}

/**
 * Send email via SMTP/PHP mail using settings table
 */
function sendEmail($to, $subject, $htmlBody) {
    $smtp_host = getSetting('smtp_host');
    $smtp_port = (int)getSetting('smtp_port', 587);
    $smtp_username = getSetting('smtp_username');
    $smtp_password = getSetting('smtp_password');
    $smtp_encryption = getSetting('smtp_encryption', 'tls');
    $from_name = getSetting('email_from_name', SITE_NAME);
    $from_address = getSetting('email_from_address');

    if (!$smtp_host || !$smtp_username || !$smtp_password || !$from_address) {
        return false; // incomplete config
    }

    if (file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            if ($smtp_encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $smtp_port;
            $mail->setFrom($from_address, $from_name);
            $mail->addAddress($to);
            // Embed ASC logo
            // Use web URL for logo instead of attachment to prevent PNG files being sent
            $logoHtml = '<img src="'.SITE_URL.'/assets/images/egabay-logo.png" alt="EGABAY ASC Logo" style="height:60px; max-width:200px; display:block; margin:0 auto;">';
            $htmlBody = str_replace('{{logo}}', $logoHtml, $htmlBody);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Email error: '.$e->getMessage());
            return false;
        }
    } else {
        $headers = "From: {$from_name} <{$from_address}>\r\n".
                   "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $htmlBody, $headers);
    }
}

/**
 * Build HTML email template with ASC logo and consistent styles
 */
function buildEmailTemplate($bodyHtml) {
    
    $html  = '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>';
    $html .= '<body style="font-family:Arial,Helvetica,sans-serif;background:#f8f9fa;padding:20px;margin:0;">';
    $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:auto;background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">';
    $html .= '<tr><td style="text-align:center;padding:30px 20px;background:linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);">{{logo}}</td></tr>';
    $html .= '<tr><td style="padding:30px 25px;color:#212529;line-height:1.6;">'.$bodyHtml.'</td></tr>';
    $html .= '<tr><td style="background:#f1f3f5;color:#6c757d;font-size:12px;text-align:center;padding:20px 15px;">'.SITE_NAME.' • This is an automated message, please do not reply.</td></tr>';
    $html .= '</table></body></html>';
    return $html;
}

/**
 * Send email using custom template from database
 */
function sendEmailTemplate($to, $templateName, $variables = []) {
    $db = (new Database())->getConnection();
    
    // Get template from database
    $query = "SELECT * FROM email_templates WHERE template_name = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$templateName]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        error_log("Email template '$templateName' not found or inactive");
        // Create basic fallback for missing templates
        if ($templateName === 'user_verification') {
            $template = [
                'template_subject' => 'Verify Your Email Address - {{site_name}}',
                'header_title' => 'Welcome to {{site_name}}!',
                'greeting_text' => 'Hello {{first_name}},',
                'main_message' => 'Thank you for registering. Please verify your email address by clicking the button below.',
                'button_text' => 'Verify Email Address',
                'use_structured_editor' => 1
            ];
        } elseif ($templateName === 'password_reset') {
            $template = [
                'template_subject' => 'Reset Your Password - {{site_name}}',
                'header_title' => 'Password Reset Request',
                'greeting_text' => 'Hello {{first_name}},',
                'main_message' => 'We received a request to reset your password. Click the button below to reset it.',
                'button_text' => 'Reset Password',
                'use_structured_editor' => 1
            ];
        } else {
            return false;
        }
    }
    
    // Prepare default variables
    $defaultVariables = [
        '{{site_name}}' => getSetting('site_name', SITE_NAME),
        '{{admin_email}}' => getSetting('admin_email', '')
    ];
    
    // Handle logo - use custom logo if available, otherwise default
    if (!empty($template['custom_logo']) && file_exists($GLOBALS['base_path'] . '/uploads/email_logos/' . $template['custom_logo'])) {
        $defaultVariables['{{logo}}'] = '<img src="' . SITE_URL . '/uploads/email_logos/' . $template['custom_logo'] . '" alt="' . getSetting('site_name', SITE_NAME) . ' Logo" style="height:60px; max-width:200px;">';
    } else {
        $defaultVariables['{{logo}}'] = '<img src="' . SITE_URL . '/assets/images/egabay-logo.png" alt="' . getSetting('site_name', SITE_NAME) . ' Logo" style="height:60px;">';
    }
    
    // Merge with provided variables
    $allVariables = array_merge($defaultVariables, $variables);
    
    // Use structured template if available, otherwise fall back to template_body
    if ($template['use_structured_editor'] && !empty($template['header_title'])) {
        // Build email from structured fields
        $body = buildStructuredEmailTemplate([
            'header_title' => $template['header_title'],
            'greeting_text' => $template['greeting_text'],
            'main_message' => $template['main_message'],
            'button_text' => $template['button_text'],
            'button_link' => determineButtonLink($templateName, $variables),
            'fallback_message' => $template['fallback_message'],
            'footer_note' => $template['footer_note']
        ], $template['custom_logo']);
    } else {
        // Use legacy template_body
        $body = $template['template_body'];
    }
    
    // Replace variables in subject and body
    $subject = str_replace(array_keys($allVariables), array_values($allVariables), $template['template_subject']);
    $body = str_replace(array_keys($allVariables), array_values($allVariables), $body);
    
    // Send email
    return sendEmail($to, $subject, $body);
}

/**
 * Build email HTML from structured template fields
 */
function buildStructuredEmailTemplate($fields, $custom_logo = null) {
    // Handle logo
    if ($custom_logo && file_exists($GLOBALS['base_path'] . '/uploads/email_logos/' . $custom_logo)) {
        $logo_html = '<img src="' . SITE_URL . '/uploads/email_logos/' . $custom_logo . '" alt="{{site_name}} Logo" style="height:60px; max-width:200px;">';
    } else {
        $logo_html = '<img src="' . SITE_URL . '/assets/images/egabay-logo.png" alt="{{site_name}} Logo" style="height:60px;">';
    }
    
    // Build button HTML if button text is provided
    $button_html = '';
    if (!empty($fields['button_text']) && !empty($fields['button_link'])) {
        $button_html = '
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $fields['button_link'] . '" 
               style="background-color: #007bff; color: white; padding: 12px 30px; 
                      text-decoration: none; border-radius: 5px; font-weight: bold; 
                      display: inline-block; text-decoration: none;">' . $fields['button_text'] . '</a>
        </div>';
    }
    
    // Build complete email HTML
    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{site_name}}</title>
    </head>
    <body style="font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 0; margin: 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="text-align: center; padding: 20px 0; background: #0d6efd;">
                    ' . $logo_html . '
                </td>
            </tr>
            <tr>
                <td style="padding: 30px 25px; color: #212529;">
                    <h2 style="color: #0d6efd; margin-top: 0; margin-bottom: 20px; font-size: 24px;">' . $fields['header_title'] . '</h2>
                    <p style="font-size: 16px; margin-bottom: 20px; color: #495057;">' . $fields['greeting_text'] . '</p>
                    <div style="margin: 25px 0; font-size: 15px; line-height: 1.6; color: #495057;">' . $fields['main_message'] . '</div>
                    ' . $button_html . '
                    ' . (!empty($fields['fallback_message']) ? '<div style="margin: 25px 0; font-size: 14px; color: #6c757d; line-height: 1.5;">' . $fields['fallback_message'] . '</div>' : '') . '
                </td>
            </tr>
            <tr>
                <td style="background: #f1f3f5; color: #6c757d; font-size: 12px; text-align: center; padding: 20px 15px;">
                    ' . (!empty($fields['footer_note']) ? $fields['footer_note'] . '<br><br>' : '') . '
                    {{site_name}} • This is an automated message, please do not reply.
                </td>
            </tr>
        </table>
    </body>
    </html>';
    
    return $html;
}

/**
 * Determine the appropriate button link based on template name and variables
 */
function determineButtonLink($templateName, $variables) {
    switch ($templateName) {
        case 'user_verification':
            return isset($variables['{{verification_link}}']) ? $variables['{{verification_link}}'] : '{{verification_link}}';
        case 'password_reset':
            return isset($variables['{{reset_link}}']) ? $variables['{{reset_link}}'] : '{{reset_link}}';
        case 'welcome_message':
            return isset($variables['{{dashboard_link}}']) ? $variables['{{dashboard_link}}'] : '{{dashboard_link}}';
        case 'consultation_notification':
            return isset($variables['{{consultation_link}}']) ? $variables['{{consultation_link}}'] : '{{consultation_link}}';
        case 'general_notification':
            return isset($variables['{{action_link}}']) ? $variables['{{action_link}}'] : '{{action_link}}';
        default:
            return '#';
    }
}

/**
 * Send user verification email using template
 */
function sendVerificationEmail($userEmail, $firstName, $verificationToken) {
    $variables = [
        '{{first_name}}' => $firstName,
        '{{email}}' => $userEmail,
        '{{verification_link}}' => rtrim(SITE_URL, '/') . '/verify.php?token=' . $verificationToken
    ];
    
    // Try template first
    $result = sendEmailTemplate($userEmail, 'user_verification', $variables);
    
    // If template fails, send basic HTML email
    if (!$result) {
        $subject = 'Verify Your Email Address - ' . SITE_NAME;
        $verification_link = rtrim(SITE_URL, '/') . '/verify.php?token=' . $verificationToken;
        
        $htmlBody = buildEmailTemplate('
            <h2>Welcome to ' . SITE_NAME . '!</h2>
            <p>Hello ' . htmlspecialchars($firstName) . ',</p>
            <p>Thank you for registering with ' . SITE_NAME . '. To complete your registration, please verify your email address by clicking the button below:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $verification_link . '" 
                   style="background-color: #007bff; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; font-weight: bold; 
                          display: inline-block;">Verify Email Address</a>
            </div>
            <p>If the button doesn\'t work, you can also copy and paste this link into your browser:</p>
            <p><a href="' . $verification_link . '">' . $verification_link . '</a></p>
            <p>This verification link will expire in 24 hours for security reasons.</p>
            <p>If you did not create an account, you can safely ignore this email.</p>
        ');
        
        return sendEmail($userEmail, $subject, $htmlBody);
    }
    
    return $result;
}

/**
 * Send password reset email using template
 */
function sendPasswordResetEmail($userEmail, $firstName, $resetToken) {
    $variables = [
        '{{first_name}}' => $firstName,
        '{{email}}' => $userEmail,
        '{{reset_link}}' => rtrim(SITE_URL, '/') . '/reset_password.php?token=' . $resetToken
    ];
    
    // Try template first
    $result = sendEmailTemplate($userEmail, 'password_reset', $variables);
    
    // If template fails, send basic HTML email
    if (!$result) {
        $subject = 'Reset Your Password - ' . SITE_NAME;
        $reset_link = rtrim(SITE_URL, '/') . '/reset_password.php?token=' . $resetToken;
        
        $htmlBody = buildEmailTemplate('
            <h2>Password Reset Request</h2>
            <p>Hello ' . htmlspecialchars($firstName) . ',</p>
            <p>We received a request to reset your password for your ' . SITE_NAME . ' account.</p>
            <p>Click the button below to reset your password:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $reset_link . '" 
                   style="background-color: #dc3545; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; font-weight: bold; 
                          display: inline-block;">Reset Password</a>
            </div>
            <p>If the button doesn\'t work, you can also copy and paste this link into your browser:</p>
            <p><a href="' . $reset_link . '">' . $reset_link . '</a></p>
            <p>This password reset link will expire in 1 hour for security reasons.</p>
            <p>If you did not request a password reset, you can safely ignore this email.</p>
        ');
        
        return sendEmail($userEmail, $subject, $htmlBody);
    }
    
    return $result;
}

/**
 * Send welcome email using template
 */
function sendWelcomeEmail($userEmail, $firstName, $lastName, $username) {
    $variables = [
        '{{first_name}}' => $firstName,
        '{{last_name}}' => $lastName,
        '{{username}}' => $username,
        '{{email}}' => $userEmail,
        '{{dashboard_link}}' => rtrim(SITE_URL, '/') . '/dashboard/'
    ];
    
    return sendEmailTemplate($userEmail, 'welcome_message', $variables);
}

/**
 * Send consultation notification email using template
 */
function sendConsultationNotificationEmail($userEmail, $firstName, $notificationMessage, $consultationData = []) {
    $variables = [
        '{{first_name}}' => $firstName,
        '{{email}}' => $userEmail,
        '{{notification_message}}' => $notificationMessage,
        '{{consultation_date}}' => $consultationData['date'] ?? 'TBA',
        '{{consultation_time}}' => $consultationData['time'] ?? 'TBA', 
        '{{counselor_name}}' => $consultationData['counselor_name'] ?? 'TBA',
        '{{consultation_status}}' => $consultationData['status'] ?? 'Pending',
        '{{consultation_link}}' => $consultationData['link'] ?? SITE_URL . '/dashboard/'
    ];
    
    return sendEmailTemplate($userEmail, 'consultation_notification', $variables);
}

/**
 * Send general notification email using template
 */
function sendGeneralNotificationEmail($userEmail, $firstName, $subject, $messageContent, $actionText = 'View Details', $actionLink = null) {
    $variables = [
        '{{first_name}}' => $firstName,
        '{{email}}' => $userEmail,
        '{{subject}}' => $subject,
        '{{message_content}}' => $messageContent,
        '{{action_text}}' => $actionText,
        '{{action_link}}' => $actionLink ?? SITE_URL . '/dashboard/'
    ];
    
    return sendEmailTemplate($userEmail, 'general_notification', $variables);
}
?> 