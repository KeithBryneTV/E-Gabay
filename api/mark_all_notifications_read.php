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
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the user ID
$user_id = $_SESSION['user_id'];

// Mark all system notifications as read
$system_success = markSystemNotificationsAsRead($user_id);

// Mark all chat messages as read
$messages_success = markAllMessagesAsRead($user_id);

// Return the response
header('Content-Type: application/json');
echo json_encode([
    'success' => ($system_success && $messages_success)
]); 