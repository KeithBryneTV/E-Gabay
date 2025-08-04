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

// Get the user ID
$user_id = $_SESSION['user_id'];

// Get limit parameter
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

// Sanitize the limit
$limit = max(1, min(50, $limit));

// Get system notifications
$system_notifications = getSystemNotifications($user_id, $limit);

// Get message notifications
$message_notifications = getMessageAndConsultationNotifications($user_id, $limit);

// Merge notifications and sort by date
$notifications = array_merge($system_notifications, $message_notifications);
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to requested number of notifications
$notifications = array_slice($notifications, 0, $limit);

// Return the response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'notifications' => $notifications
]); 