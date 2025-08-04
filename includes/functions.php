<?php
// General functions for the application

/**
 * Get all counselors
 * 
 * @return array Array of counselors
 */
function getAllCounselors() {
    global $db;
    
    $query = "SELECT u.*, cp.specialization, cp.availability 
              FROM users u
              LEFT JOIN counselor_profiles cp ON u.user_id = cp.user_id
              WHERE u.role_id = ? AND u.is_active = 1
              ORDER BY u.last_name, u.first_name";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([ROLE_COUNSELOR]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting counselors: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all students
 * 
 * @return array Array of students
 */
function getAllStudents() {
    global $db;
    
    $query = "SELECT u.*, sp.student_id as student_number, sp.course, sp.year_level, sp.section
              FROM users u
              LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
              WHERE u.role_id = ? AND u.is_active = 1
              ORDER BY u.last_name, u.first_name";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([ROLE_STUDENT]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting students: " . $e->getMessage());
        return [];
    }
}

/**
 * Get student profile
 * 
 * @param int $user_id User ID
 * @return array Student profile
 */
function getStudentProfile($user_id) {
    global $db;
    
    $query = "SELECT sp.*, u.first_name, u.last_name, u.email
              FROM student_profiles sp
              JOIN users u ON sp.user_id = u.user_id
              WHERE sp.user_id = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting student profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Get counselor profile
 * 
 * @param int $user_id User ID
 * @return array Counselor profile
 */
function getCounselorProfile($user_id) {
    global $db;
    
    $query = "SELECT cp.*, u.first_name, u.last_name, u.email
              FROM counselor_profiles cp
              JOIN users u ON cp.user_id = u.user_id
              WHERE cp.user_id = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting counselor profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Get consultation statistics
 * 
 * @return array Consultation statistics
 */
function getConsultationStats() {
    global $db;
    
    $query = "SELECT 
              COUNT(*) as total_consultations,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_consultations,
              SUM(CASE WHEN status = 'live' THEN 1 ELSE 0 END) as active_consultations,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_consultations,
              SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_consultations
              FROM consultation_requests";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Define default values to ensure all keys exist
        $defaults = [
            'total_consultations' => 0,
            'pending_consultations' => 0,
            'active_consultations' => 0,
            'completed_consultations' => 0,
            'cancelled_consultations' => 0
        ];
        
        // Merge the defaults with the result
        return array_merge($defaults, $result ? $result : []);
    } catch (Exception $e) {
        error_log("Error getting consultation stats: " . $e->getMessage());
        return [
            'total_consultations' => 0,
            'pending_consultations' => 0,
            'active_consultations' => 0,
            'completed_consultations' => 0,
            'cancelled_consultations' => 0
        ];
    }
}

/**
 * Get user statistics
 * 
 * @return array User statistics
 */
function getUserStats() {
    global $db;
    
    $query = "SELECT 
              COUNT(*) as total_users,
              SUM(CASE WHEN role_id = ? THEN 1 ELSE 0 END) as student_count,
              SUM(CASE WHEN role_id = ? THEN 1 ELSE 0 END) as counselor_count,
              SUM(CASE WHEN role_id = ? THEN 1 ELSE 0 END) as admin_count,
              SUM(CASE WHEN role_id = ? THEN 1 ELSE 0 END) as staff_count
              FROM users
              WHERE is_active = 1";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([ROLE_STUDENT, ROLE_COUNSELOR, ROLE_ADMIN, ROLE_STAFF]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Define default values to ensure all keys exist
        $defaults = [
            'total_users' => 0,
            'student_count' => 0,
            'counselor_count' => 0,
            'admin_count' => 0,
            'staff_count' => 0
        ];
        
        // Merge the defaults with the result
        return array_merge($defaults, $result ? $result : []);
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        return [
            'total_users' => 0,
            'student_count' => 0,
            'counselor_count' => 0,
            'admin_count' => 0,
            'staff_count' => 0
        ];
    }
}

/**
 * Get recent consultations
 * 
 * @param int $limit Number of consultations to get
 * @return array Recent consultations
 */
function getRecentConsultations($limit = 5) {
    global $db;
    
    $query = "SELECT cr.*, 
              u1.first_name as student_first_name, u1.last_name as student_last_name,
              u2.first_name as counselor_first_name, u2.last_name as counselor_last_name
              FROM consultation_requests cr
              JOIN users u1 ON cr.student_id = u1.user_id
              LEFT JOIN users u2 ON cr.counselor_id = u2.user_id
              ORDER BY cr.created_at DESC
              LIMIT ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting recent consultations: " . $e->getMessage());
        return [];
    }
}

/**
 * Get consultation feedback
 * 
 * @param int $consultation_id Consultation ID
 * @return array Feedback
 */
function getConsultationFeedback($consultation_id) {
    global $db;
    
    $query = "SELECT f.*, u.first_name, u.last_name
              FROM feedback f
              JOIN users u ON f.student_id = u.user_id
              WHERE f.consultation_id = ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$consultation_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting feedback: " . $e->getMessage());
        return false;
    }
}

/**
 * Add notification
 * 
 * @param int $user_id User ID
 * @param string $message Notification message
 * @param string $type Notification type
 * @param int $reference_id Reference ID (optional)
 * @return bool True on success, false on failure
 */
function addNotification($user_id, $message, $type, $reference_id = null) {
    global $db;
    
    $query = "INSERT INTO notifications (user_id, message, type, reference_id)
              VALUES (?, ?, ?, ?)";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $message, $type, $reference_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error adding notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user notifications
 * 
 * @param int $user_id User ID
 * @param int $limit Number of notifications to get
 * @return array User notifications
 */
function getUserNotifications($user_id, $limit = 10) {
    global $db;
    
    $query = "SELECT *
              FROM notifications
              WHERE user_id = ?
              ORDER BY created_at DESC
              LIMIT ?";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 * 
 * @param int $user_id User ID
 * @return int Unread notification count
 */
function getUnreadNotificationCount($user_id) {
    global $db;
    
    $query = "SELECT COUNT(*) as count
              FROM notifications
              WHERE user_id = ? AND is_read = 0";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notifications as read
 * 
 * @param int $user_id User ID
 * @param int $notification_id Notification ID (optional)
 * @return bool True on success, false on failure
 */
function markNotificationsAsRead($user_id, $notification_id = null) {
    global $db;
    
    $query = "UPDATE notifications
              SET is_read = 1
              WHERE user_id = ?";
    
    if ($notification_id) {
        $query .= " AND id = ?";
    }
    
    try {
        $stmt = $db->prepare($query);
        if ($notification_id) {
            $stmt->execute([$user_id, $notification_id]);
        } else {
            $stmt->execute([$user_id]);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error marking notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Add security headers to prevent caching and back button access
 */
function addSecurityHeaders() {
    header("Cache-Control: no-cache, no-store, must-revalidate, private");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Enhanced session validation with timeout check
 */
function validateUserSession() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check for session timeout (2 hours)
    if (isset($_SESSION['last_activity'])) {
        $timeout = 7200; // 2 hours in seconds
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Check if session token exists and is valid
    if (!isset($_SESSION['session_token']) || strlen($_SESSION['session_token']) < 32) {
        return false;
    }
    
    return true;
}

/**
 * Completely clear user session and browser cache
 */
function clearUserSession() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Add cache clearing headers
    header("Cache-Control: no-cache, no-store, must-revalidate, private");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Clear-Site-Data: \"cache\", \"cookies\", \"storage\"");
}

/**
 * Generate anti-back-button JavaScript
 */
function generateAntiBackScript() {
    return '
    <script>
        // Prevent back button after logout
        (function() {
            if (window.history && window.history.pushState) {
                // Add a fake history entry
                window.history.pushState(null, null, window.location.href);
                
                // Listen for back button
                window.addEventListener("popstate", function() {
                    // Just prevent back navigation without aggressive session checks
                    window.history.pushState(null, null, window.location.href);
                });
            }
            
            // Prevent page from being cached
            window.addEventListener("beforeunload", function() {
                // Force refresh if user comes back
            });
            
            // Session check is handled by auth.php - no aggressive checks here
        })();
    </script>';
}
?> 