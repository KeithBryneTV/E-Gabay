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

// Get the request body
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['notification_id']) ? $data['notification_id'] : null;

if (!$notification_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Notification ID is required']);
    exit;
}

// Check if the notification is a message notification or a system notification
if (strpos($notification_id, 'msg_') === 0) {
    // Extract the message ID from the notification ID
    $message_id = substr($notification_id, 4);
    
    // Mark the message as read
    $db = (new Database())->getConnection();
    $query = "UPDATE chat_messages SET is_read = 1 WHERE id = ? AND chat_id IN (
                SELECT id FROM chat_sessions WHERE student_id = ? OR counselor_id = ?
              )";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$message_id, $user_id, $user_id]);
        $success = true;
    } catch (Exception $e) {
        error_log("Error marking message as read: " . $e->getMessage());
        $success = false;
    }
} elseif (strpos($notification_id, 'cons_') === 0) {
    // For consultation notifications, we just mark it as seen since there's no direct read flag
    // This is handled through the view counter in the view consultation page
    $success = true;
} else {
    // It's a system notification
    $success = markSystemNotificationsAsRead($user_id, $notification_id);
}

// Return the response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success
]); 