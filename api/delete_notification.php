<?php
// Include path fix helper
require_once __DIR__ . '/../includes/path_fix.php';

// Include required files
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/includes/utility.php';
require_once $base_path . '/classes/Database.php';

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the user ID
$user_id = $_SESSION['user_id'];

// Get the request body
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['notification_id']) ? $data['notification_id'] : null;

if (!$notification_id) {
    echo json_encode(['error' => 'Notification ID is required']);
    exit;
}

// Use the clearNotification function from utility.php
$success = clearNotification($user_id, $notification_id);

// Return the response
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Notification deleted successfully' : 'Failed to delete notification'
]);
exit;
?> 