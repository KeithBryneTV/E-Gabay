<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Chat.php';

// Check if user is logged in and has counselor role
requireRole('counselor');

// Set page title
$page_title = 'My Messages';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create chat object
$chat = new Chat($db);

// Process close chat action if submitted from chat page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close_chat') {
    $chat_id = (int)$_POST['chat_id'];
    
    // Get the consultation ID for this chat
    $query = "SELECT consultation_id FROM chat_sessions WHERE id = ? AND counselor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_id, $user_id]);
    $chat_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Close chat session
    $query = "UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ? AND counselor_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$chat_id, $user_id])) {
        // Send system message
        $chat->sendMessage($chat_id, 0, "Chat session closed by counselor.", 'system');
        
        // Update consultation status to completed if this chat is linked to a consultation
        if (!empty($chat_data['consultation_id'])) {
            require_once $base_path . '/classes/Consultation.php';
            $consultation = new Consultation($db);
            $consultation->completeRequest($chat_data['consultation_id']);
        }
        
        setMessage('Chat session closed successfully.', 'success');
    } else {
        setMessage('Failed to close chat session.', 'danger');
    }
}

// Get active chat sessions
$query = "SELECT cs.*, 
          u.first_name, u.last_name, u.email,
          cr.issue_description, cr.issue_category, cr.is_anonymous,
          (SELECT COUNT(*) FROM chat_messages WHERE chat_id = cs.id AND user_id != ? AND is_read = 0) as unread_count
          FROM chat_sessions cs
          JOIN users u ON cs.student_id = u.user_id
          JOIN consultation_requests cr ON cs.consultation_id = cr.id
          WHERE cs.counselor_id = ? AND cs.status = 'active'
          ORDER BY cs.updated_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $user_id]);
$active_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get closed chat sessions
$query = "SELECT cs.*, 
          u.first_name, u.last_name, u.email,
          cr.issue_description, cr.issue_category, cr.is_anonymous
          FROM chat_sessions cs
          JOIN users u ON cs.student_id = u.user_id
          JOIN consultation_requests cr ON cs.consultation_id = cr.id
          WHERE cs.counselor_id = ? AND cs.status = 'closed'
          ORDER BY cs.updated_at DESC
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$closed_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>My Messages</h1>
        <p class="text-muted">Manage your chat sessions with students</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Active Conversations</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($active_chats)): ?>
                    <div class="p-4 text-center">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p>No active conversations found.</p>
                        <p class="text-muted">Start a conversation from the consultations page.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($active_chats as $chat): ?>
                            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?chat_id=<?php echo $chat['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($chat['is_anonymous']): ?>
                                                <span class="text-muted">Anonymous Student</span>
                                            <?php else: ?>
                                                <?php echo $chat['first_name'] . ' ' . $chat['last_name']; ?>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="mb-1">
                                            <?php 
                                            echo !empty($chat['issue_category']) ? 
                                                $chat['issue_category'] : 
                                                'General Consultation'; 
                                            ?>
                                        </p>
                                        <small>
                                            <i class="fas fa-clock me-1"></i> 
                                            <?php echo timeAgo($chat['updated_at']); ?>
                                        </small>
                                    </div>
                                    <?php if ($chat['unread_count'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $chat['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Closed Conversations</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($closed_chats)): ?>
                    <div class="p-4 text-center">
                        <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                        <p>No closed conversations found.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($closed_chats as $chat): ?>
                            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?chat_id=<?php echo $chat['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($chat['is_anonymous']): ?>
                                                <span class="text-muted">Anonymous Student</span>
                                            <?php else: ?>
                                                <?php echo $chat['first_name'] . ' ' . $chat['last_name']; ?>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="mb-1">
                                            <?php 
                                            echo !empty($chat['issue_category']) ? 
                                                $chat['issue_category'] : 
                                                'General Consultation'; 
                                            ?>
                                        </p>
                                        <small>
                                            <i class="fas fa-clock me-1"></i> 
                                            Closed <?php echo timeAgo($chat['updated_at']); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-secondary">Closed</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Quick Tips</h5>
            </div>
            <div class="card-body">
                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Effective Communication</h6>
                <ul class="mb-4">
                    <li>Use clear and concise language</li>
                    <li>Ask open-ended questions</li>
                    <li>Show empathy and understanding</li>
                    <li>Provide actionable advice when appropriate</li>
                </ul>
                
                <h6><i class="fas fa-shield-alt text-primary me-2"></i>Privacy Guidelines</h6>
                <ul class="mb-4">
                    <li>Respect student confidentiality</li>
                    <li>Do not share sensitive information</li>
                    <li>Be mindful of what you type</li>
                    <li>Close chat sessions when complete</li>
                </ul>
                
                <h6><i class="fas fa-exclamation-triangle text-danger me-2"></i>Crisis Response</h6>
                <p>If a student expresses thoughts of self-harm or harm to others, follow the emergency protocol and contact the appropriate authorities immediately.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 