<?php
// Include configuration
require_once '../../../config/config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Log the request for debugging
error_log("get_user_details.php called with user_id: " . ($_GET['user_id'] ?? 'NOT_SET'));

// Check if user is logged in and has admin role
try {
    requireLogin();
    requireRole('admin');
} catch (Exception $e) {
    error_log("Authentication failed in get_user_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Authentication failed: ' . $e->getMessage()]);
    exit;
}

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = (int)$_GET['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Get user details
    $query = "SELECT user_id, username, first_name, last_name, email, role_id, is_active, created_at, updated_at 
              FROM users 
              WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User details query result for user_id $user_id: " . ($user ? 'Found user' : 'User not found'));
    
    if ($user) {
        // Format dates
        if ($user['created_at']) {
            $user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
        }
        if ($user['updated_at']) {
            $user['updated_at'] = date('Y-m-d H:i:s', strtotime($user['updated_at']));
        }
        
        error_log("Returning user details for user_id $user_id: " . $user['username']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        error_log("User not found for user_id $user_id");
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    error_log("Error getting user details for user_id $user_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve user details: ' . $e->getMessage()]);
}
?> 