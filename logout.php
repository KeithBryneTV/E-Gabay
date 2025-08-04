<?php
require_once __DIR__ . '/includes/path_fix.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");  
header("Expires: 0");

require_once $base_path . '/config/config.php';

// Get database connection
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';

// Create database and auth objects
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

session_start();

// Store user info before destroying session for logging
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Unknown';

// Log the logout activity
if ($user_id) {
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            'logout',
            'User logged out',
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log logout activity: " . $e->getMessage());
    }
}

// Completely destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    // Unset all session variables
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
}

// Additional security headers
header("Cache-Control: no-cache, no-store, must-revalidate, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Clear-Site-Data: \"cache\", \"cookies\", \"storage\"");

// Redirect to login page
header("Location: " . SITE_URL . "/login.php");
exit();
?> 