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
    'error' => 'Unknown error'
];

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['error'] = 'Invalid request method';
        echo json_encode($response);
        exit;
    }

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
    $role = $_SESSION['role_name'] ?? $_SESSION['role'] ?? $_SESSION['AccountType'] ?? 'student';
    
    // Convert AccountType to role_name format if needed
    if ($role === 'STUDENT') $role = 'student';
    if ($role === 'ADMIN') $role = 'counselor';
    if ($role === 'COUNSELOR') $role = 'counselor';
    
    if (!$user_id) {
        $response['error'] = 'User ID not found in session';
        echo json_encode($response);
        exit;
    }

    // Log incoming request for debugging
    error_log("Send message API called - User ID: $user_id, Role: $role, POST data: " . json_encode($_POST));
    
    // Check if required parameters are provided
    if (!isset($_POST['chat_id']) || empty($_POST['chat_id']) || 
        !isset($_POST['message']) || empty(trim($_POST['message']))) {
        $response['error'] = 'Chat ID and message are required';
        error_log("Missing required parameters: chat_id=" . ($_POST['chat_id'] ?? 'missing') . ", message=" . ($_POST['message'] ?? 'missing'));
        echo json_encode($response);
        exit;
    }
    
    $chat_id = (int)$_POST['chat_id'];
    $message = sanitizeInput($_POST['message']);
    
    // Check if user has access to this chat
    if (!$chat->userHasAccess($chat_id, $user_id)) {
        $response['error'] = 'You do not have access to this chat session';
        error_log("User $user_id does not have access to chat $chat_id");
        echo json_encode($response);
        exit;
    }
    
    // Verify chat session exists and is active
    $query = "SELECT id FROM chat_sessions WHERE id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_id]);
    
    if ($stmt->rowCount() === 0) {
        $response['error'] = 'Chat session is not active';
        error_log("Chat session $chat_id is not active or does not exist");
        echo json_encode($response);
        exit;
    }
    
    // Initialize file variables
    $file_path = null;
    $file_name = null;
    $file_size = null;
    $message_type = 'user';
    
    // Check if a file was uploaded
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Create uploads directory if it doesn't exist
        $upload_dir = $base_path . '/uploads/chat_files/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Get file details
        $temp_file = $_FILES['file']['tmp_name'];
        $original_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_type = $_FILES['file']['type'];
        
        // Generate a unique filename
        $file_name = $original_name;
        $unique_name = uniqid('chat_') . '_' . $file_name;
        $file_path = 'uploads/chat_files/' . $unique_name;
        $full_path = $base_path . '/' . $file_path;
        
        // Move the uploaded file
        if (move_uploaded_file($temp_file, $full_path)) {
            // Update message with file info
            $message = sanitizeInput($_POST['message']);
            if (strpos($message, 'Sending file:') !== false) {
                // Replace placeholder message with a better formatted one
                $message = "📎 Shared a file: $file_name";
            }
        } else {
            $response['error'] = 'Failed to upload file';
            error_log("Failed to upload file: " . $file_name);
            echo json_encode($response);
            exit;
        }
    }
    
    // Send message with file info if applicable
    $message_id = $chat->sendMessage($chat_id, $user_id, $message, $message_type, $file_path, $file_name, $file_size);
    
    if (!$message_id) {
        $response['error'] = 'Failed to send message';
        error_log("Failed to send message for chat $chat_id, user $user_id");
        echo json_encode($response);
        exit;
    }
    
    // Update chat session timestamp
    $query = "UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_id]);
    
    // Get the message data
    $query = "SELECT cm.*, u.first_name, u.last_name, u.role_id 
              FROM chat_messages cm
              LEFT JOIN users u ON cm.user_id = u.user_id
              WHERE cm.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$message_id]);
    $message_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update response
    $response['status'] = 'success';
    $response['message'] = $message_data;
    $response['timestamp'] = date('Y-m-d H:i:s');
    unset($response['error']);

} catch (Exception $e) {
    // Log the error
    error_log("Send Message API Error: " . $e->getMessage());
    $response['error'] = 'Server error occurred';
}

// Return JSON response
echo json_encode($response);
exit;
?> 