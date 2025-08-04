<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from output in production
ini_set('log_errors', 1);

// Set content type early
header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'messages' => [],
    'error' => 'Unknown error'
];

try {
    // Include path fix helper
    require_once __DIR__ . '/../includes/path_fix.php';

    // Required includes with absolute paths
    require_once $base_path . '/config/config.php';

    // Include required classes
    require_once $base_path . '/classes/Database.php';
    require_once $base_path . '/classes/Auth.php';
    require_once $base_path . '/classes/Chat.php';

    // Check if user is logged in
    if (!isLoggedIn()) {
        $response['error'] = 'User not logged in';
        echo json_encode($response);
        exit;
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        $response['error'] = 'Database connection failed';
        echo json_encode($response);
        exit;
    }

    // Create chat object
    $chat = new Chat($db);

    // Get user data - handle both session systems
    $user_id = $_SESSION['user_id'] ?? $_SESSION['ID'] ?? null;
    
    if (!$user_id) {
        $response['error'] = 'User ID not found in session';
        echo json_encode($response);
        exit;
    }
    $role = $_SESSION['role_name'] ?? $_SESSION['role'] ?? 'student';

    // Check if required parameters are provided
    if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {
        $response['error'] = 'Chat ID is required';
        echo json_encode($response);
        exit;
    }
    
    $chat_id = (int)$_POST['chat_id'];
    
    // Check if user has access to this chat
    if (!$chat->userHasAccess($chat_id, $user_id)) {
        $response['error'] = 'You do not have access to this chat session';
        echo json_encode($response);
        exit;
    }
    
    // Get last message timestamp if provided
    $last_timestamp = isset($_POST['last_timestamp']) ? $_POST['last_timestamp'] : null;
    
    // Get messages
    if ($last_timestamp) {
        // Get only new messages since last timestamp
        $messages = $chat->getNewMessages($chat_id, $last_timestamp);
    } else {
        // Get all messages (with limit)
        $messages = $chat->getMessages($chat_id, CHAT_MAX_MESSAGES);
    }
    
    // Mark messages as read
    $chat->markMessagesAsRead($chat_id, $user_id);
    
    // Update response
    $response['status'] = 'success';
    $response['messages'] = $messages;
    $response['current_timestamp'] = date('Y-m-d H:i:s');
    unset($response['error']);

} catch (Exception $e) {
    // Log the error
    error_log("Chat API Error: " . $e->getMessage());
    $response['error'] = 'Server error occurred';
}

// Return JSON response
echo json_encode($response);
exit;
?> 