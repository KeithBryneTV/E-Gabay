<?php
// Include configuration
require_once '../../../config/config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Log the request for debugging
error_log("get_student_profile.php called with user_id: " . ($_GET['user_id'] ?? 'NOT_SET'));

// Check if user is logged in and has admin role
try {
    requireLogin();
    requireRole('admin');
} catch (Exception $e) {
    error_log("Authentication failed in get_student_profile.php: " . $e->getMessage());
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
    // Get student profile
    $query = "SELECT * FROM student_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Student profile query result for user_id $user_id: " . ($profile ? 'Found profile' : 'No profile found'));
    
    if ($profile) {
        echo json_encode(['success' => true, 'profile' => $profile]);
    } else {
        // Profile doesn't exist yet - return empty profile
        $empty_profile = [
            'student_id' => '',
            'course' => '',
            'year_level' => '',
            'section' => ''
        ];
        error_log("Returning empty student profile for user_id $user_id");
        echo json_encode(['success' => true, 'profile' => $empty_profile]);
    }
} catch (Exception $e) {
    error_log("Error getting student profile for user_id $user_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve student profile: ' . $e->getMessage()]);
}
?> 