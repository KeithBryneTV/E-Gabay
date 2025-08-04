<?php
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/path_fix.php';
require_once $base_path . '/config/config.php';

session_start();

// Check if user is logged in
$isValid = false;
$user_id = null;

if (isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role_name'])) {
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $timeout = 7200; // 2 hours
        if (time() - $_SESSION['last_activity'] <= $timeout) {
            $isValid = true;
            $user_id = $_SESSION['user_id'];
            $_SESSION['last_activity'] = time(); // Update last activity
        }
    }
}

// If session is invalid, clean it up
if (!$isValid) {
    session_unset();
    session_destroy();
}

echo json_encode([
    'valid' => $isValid,
    'user_id' => $user_id,
    'timestamp' => time()
]);
exit();
?> 