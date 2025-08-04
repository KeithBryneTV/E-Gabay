<?php
// Include path fix helper
require_once __DIR__ . '/../includes/path_fix.php';

// Include required files
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/includes/utility.php';
require_once $base_path . '/classes/Database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'count' => 0]);
    exit;
}

// Get the user ID
$user_id = $_SESSION['user_id'];

// Get the notification count
$notification_count = getTotalNotificationCount($user_id);

// Return the response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count' => $notification_count
]); 