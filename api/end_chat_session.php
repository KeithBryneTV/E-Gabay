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
    require_once $base_path . '/classes/Consultation.php';

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
    
    // Create consultation object
    $consultation = new Consultation($db);

    // Get user data - handle both session systems
    $user_id = $_SESSION['user_id'] ?? $_SESSION['ID'] ?? null;
    $role = $_SESSION['role_name'] ?? $_SESSION['role'] ?? $_SESSION['AccountType'] ?? 'student';
    
    // Convert AccountType to role_name format if needed
    if ($role === 'STUDENT') $role = 'student';
    
    if (!$user_id) {
        $response['error'] = 'User ID not found in session';
        echo json_encode($response);
        exit;
    }

    // Log incoming request for debugging
    error_log("End Chat API called - User ID: $user_id, Role: $role, POST data: " . json_encode($_POST));
    
    // Check if required parameters are provided
    if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {
        $response['error'] = 'Chat ID is required';
        echo json_encode($response);
        exit;
    }
    
    $chat_id = (int)$_POST['chat_id'];
    $consultation_id = isset($_POST['consultation_id']) && !empty($_POST['consultation_id']) ? (int)$_POST['consultation_id'] : null;
    
    // Check if user has access to this chat and is a student
    if (!$chat->userHasAccess($chat_id, $user_id) || $role !== 'student') {
        $response['error'] = 'You do not have permission to end this chat session';
        echo json_encode($response);
        exit;
    }
    
    // Get the chat session details
    $chat_session = $chat->getSessionById($chat_id);
    
    if (!$chat_session) {
        $response['error'] = 'Chat session not found';
        echo json_encode($response);
        exit;
    }
    
    if ($chat_session['status'] !== 'active') {
        $response['error'] = 'This chat session is already closed';
        echo json_encode($response);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Step 1: Close the chat session
        if (!$chat->closeSession($chat_id)) {
            throw new Exception('Failed to close chat session');
        }
        
        // Step 2: Add a system message that the student ended the chat
        $chat->sendMessage(
            $chat_id, 
            null, 
            "Chat session ended by student. The issue has been resolved.", 
            "system"
        );
        
        // Step 3: If there's a consultation ID, update its status to completed
        if ($consultation_id) {
            // Update consultation to completed status
            if (!$consultation->updateStatus($consultation_id, 'completed')) {
                throw new Exception('Failed to update consultation status');
            }
        }
        
        // Commit transaction if all steps succeeded
        $db->commit();
        
        // Update response
        $response['status'] = 'success';
        $response['message'] = 'Chat ended successfully';
        $response['consultation_id'] = $consultation_id;
        unset($response['error']);
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->rollBack();
        $response['error'] = $e->getMessage();
        error_log("Transaction failed: " . $e->getMessage());
    }

} catch (Exception $e) {
    // Log the error
    error_log("End Chat API Error: " . $e->getMessage());
    $response['error'] = 'Server error occurred';
}

// Return JSON response
echo json_encode($response);
exit; 