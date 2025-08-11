<?php
// Force timezone to Asia/Manila to prevent timezone issues
date_default_timezone_set('Asia/Manila');

// Include path fix helper
if (!defined('BASE_PATH_DEFINED')) {
    require_once __DIR__ . '/../includes/path_fix.php';
    define('BASE_PATH_DEFINED', true);
}

// Site configuration settings
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'E-GABAY ASC');
}
if (!defined('SITE_DESC')) {
    define('SITE_DESC', 'Academic Support and Counseling System');
}

// Define site URL - simplified version
if (!defined('SITE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Get the base directory from the current script
    $script_dir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    
    // Clean up the path
    $base_path = str_replace('\\', '/', $script_dir);
    $base_path = rtrim($base_path, '/');
    
    // Handle root directory case
    if ($base_path === '' || $base_path === '.' || $base_path === '/') {
        $base_path = '';
    }
    
    // Ensure no double slashes in final URL
    $site_url = $protocol . "://" . $host . $base_path;
    $site_url = rtrim($site_url, '/'); // Remove trailing slash
    
    define('SITE_URL', $site_url);
}

// Define roles
if (!defined('ROLE_STUDENT')) {
    define('ROLE_STUDENT', 1);
    define('ROLE_COUNSELOR', 2);
    define('ROLE_ADMIN', 3);
    define('ROLE_STAFF', 4);
}

// Define consultation statuses
if (!defined('STATUS_PENDING')) {
    define('STATUS_PENDING', 'pending');
    define('STATUS_LIVE', 'live');
    define('STATUS_COMPLETED', 'completed');
    define('STATUS_CANCELLED', 'cancelled');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session settings - must be set before session_start()
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// Error reporting - change to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/database.php';

// Include utility functions
if (file_exists(__DIR__ . '/../classes/Utility.php')) {
    require_once __DIR__ . '/../classes/Utility.php';
}

// Include authentication functions
if (file_exists(__DIR__ . '/../includes/auth.php')) {
    require_once __DIR__ . '/../includes/auth.php';
}

// Include utility functions
if (file_exists(__DIR__ . '/../includes/utility.php')) {
    require_once __DIR__ . '/../includes/utility.php';
}

// Include general functions
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Include maintenance check
$current_page = $_SERVER['PHP_SELF'];
if (!strpos($current_page, 'maintenance.php') && 
    !strpos($current_page, 'fix_settings_table.php') && 
    !strpos($current_page, 'fix_chat_table.php')) {
    if (file_exists(__DIR__ . '/../maintenance_check.php')) {
        require_once __DIR__ . '/../maintenance_check.php';
    }
}

// Chat settings
define('CHAT_REFRESH_INTERVAL', 2000); // 2 seconds
define('CHAT_MAX_MESSAGES', 100);
define('CHAT_ENABLE_TYPING_INDICATOR', true);
?> 