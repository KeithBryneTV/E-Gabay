<?php
// File upload API for chat attachments
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

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

    require_once __DIR__ . '/../includes/path_fix.php';
    require_once $base_path . '/config/config.php';
    require_once $base_path . '/classes/Database.php';
    require_once $base_path . '/classes/Auth.php';
    require_once $base_path . '/classes/Chat.php';

    // Check if user is logged in
    if (!isLoggedIn()) {
        $response['error'] = 'User not logged in';
        echo json_encode($response);
        exit;
    }

    // Check if chat_id is provided
    if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {
        $response['error'] = 'Chat ID is required';
        echo json_encode($response);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $response['error'] = 'No file uploaded or upload error';
        echo json_encode($response);
        exit;
    }

    $chat_id = (int)$_POST['chat_id'];
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        $response['error'] = 'Database connection failed';
        echo json_encode($response);
        exit;
    }

    // Create chat object and verify access
    $chat = new Chat($db);
    
    if (!$chat->userHasAccess($chat_id, $user_id)) {
        $response['error'] = 'You do not have access to this chat session';
        echo json_encode($response);
        exit;
    }

    // File validation
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv',
        'application/zip', 'application/x-rar-compressed'
    ];

    $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf',
        'doc', 'docx',
        'xls', 'xlsx',
        'ppt', 'pptx',
        'txt', 'csv',
        'zip', 'rar'
    ];

    // Check file size
    if ($file['size'] > $maxFileSize) {
        $response['error'] = 'File size exceeds 10MB limit';
        echo json_encode($response);
        exit;
    }

    // Get file info
    $originalName = $file['name'];
    $fileSize = $file['size'];
    $mimeType = $file['type'];
    $pathInfo = pathinfo($originalName);
    $extension = strtolower($pathInfo['extension'] ?? '');

    // Validate file type
    if (!in_array($mimeType, $allowedTypes) || !in_array($extension, $allowedExtensions)) {
        $response['error'] = 'File type not allowed. Allowed types: images, PDF, Word, Excel, PowerPoint, text, zip files';
        echo json_encode($response);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = $base_path . '/uploads/chat_files';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $response['error'] = 'Failed to create upload directory';
            echo json_encode($response);
            exit;
        }
    }

    // Generate unique filename
    $filename = 'chat_' . $chat_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . '/' . $filename;
    $relativeFilePath = 'uploads/chat_files/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $response['error'] = 'Failed to save uploaded file';
        echo json_encode($response);
        exit;
    }

    // Save file message to database
    $message = "📎 Shared a file: " . $originalName;
    $message_id = $chat->sendMessage($chat_id, $user_id, $message, 'file', $relativeFilePath, $originalName, $fileSize);

    if (!$message_id) {
        // Delete the uploaded file if database save failed
        unlink($filePath);
        $response['error'] = 'Failed to save file message';
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

    // Success response
    $response['status'] = 'success';
    $response['message'] = $message_data;
    $response['file_url'] = SITE_URL . '/' . $relativeFilePath;
    $response['timestamp'] = date('Y-m-d H:i:s');
    unset($response['error']);

} catch (Exception $e) {
    error_log("File Upload API Error: " . $e->getMessage());
    $response['error'] = 'Server error occurred';
}

echo json_encode($response);
exit;
?> 