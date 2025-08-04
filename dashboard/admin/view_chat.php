<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Chat.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title
$page_title = 'View Chat Session';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create chat object
$chat = new Chat($db);

// Check if chat ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('Invalid chat session ID.', 'danger');
    redirect(SITE_URL . '/dashboard/admin/consultations.php');
    exit;
}

$chat_id = (int)$_GET['id'];

// Get chat session details
$chat_session = $chat->getSessionById($chat_id);

// Check if chat session exists
if (!$chat_session) {
    setMessage('Chat session not found.', 'danger');
    redirect(SITE_URL . '/dashboard/admin/consultations.php');
    exit;
}

// Get student details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$chat_session['student_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get counselor details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$chat_session['counselor_id']]);
$counselor = $stmt->fetch(PDO::FETCH_ASSOC);

// Get chat messages
$messages = $chat->getMessages($chat_id);

// Get consultation details if linked to a consultation
$consultation = null;
if ($chat_session['consultation_id']) {
    $query = "SELECT * FROM consultation_requests WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_session['consultation_id']]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle chat actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'close_chat':
                // Close chat session
                if ($chat->closeSession($chat_id)) {
                    setMessage('Chat session closed successfully.', 'success');
                } else {
                    setMessage('Failed to close chat session.', 'danger');
                }
                break;
        }
        
        // Redirect to refresh the page and prevent form resubmission
        redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Chat Session</h1>
        <p class="lead">View chat conversation between student and counselor.</p>
    </div>
</div>

<!-- Back Button -->
<div class="row mb-4">
    <div class="col-12">
        <?php if ($consultation): ?>
            <a href="<?php echo SITE_URL; ?>/dashboard/admin/view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Consultation
            </a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Consultations
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Chat Session Information -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-comments me-1"></i>
                    <?php echo $chat_session['subject']; ?>
                </div>
                <div>
                    <?php if ($chat_session['status'] === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Closed</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="chat-container" style="height: 500px; overflow-y: auto;">
                    <?php if (empty($messages)): ?>
                        <p class="text-center">No messages in this chat session.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <?php
                            $is_student = $message['user_id'] == $chat_session['student_id'];
                            $sender_name = $is_student ? $student['first_name'] . ' ' . $student['last_name'] : $counselor['first_name'] . ' ' . $counselor['last_name'];
                            ?>
                            <div class="chat-message <?php echo $is_student ? 'student' : 'counselor'; ?> mb-3">
                                <div class="d-flex <?php echo $is_student ? '' : 'flex-row-reverse'; ?>">
                                    <div class="chat-avatar">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px;">
                                    </div>
                                    <div class="chat-bubble <?php echo $is_student ? 'student-bubble' : 'counselor-bubble'; ?> mx-2 p-3 rounded">
                                        <div class="chat-info d-flex justify-content-between mb-1">
                                            <small class="text-muted"><?php echo $sender_name; ?></small>
                                            <small class="text-muted"><?php echo formatDate($message['created_at'], 'M d, Y h:i A'); ?></small>
                                        </div>
                                        <div class="chat-text">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted">Created: <?php echo formatDate($chat_session['created_at'], 'M d, Y h:i A'); ?></small>
                    </div>
                    <div>
                        <?php if ($chat_session['status'] === 'active'): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post" class="d-inline">
                                <input type="hidden" name="action" value="close_chat">
                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to close this chat session?');">
                                    <i class="fas fa-times me-1"></i> Close Chat
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Chat Information -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Chat Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Subject:</h6>
                    <p><?php echo $chat_session['subject']; ?></p>
                </div>
                
                <div class="mb-3">
                    <h6>Status:</h6>
                    <?php if ($chat_session['status'] === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Closed</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <h6>Created:</h6>
                    <p><?php echo formatDate($chat_session['created_at'], 'M d, Y h:i A'); ?></p>
                </div>
                
                <?php if ($chat_session['status'] !== 'active'): ?>
                <div class="mb-3">
                    <h6>Closed:</h6>
                    <p><?php echo formatDate($chat_session['updated_at'], 'M d, Y h:i A'); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <h6>Messages:</h6>
                    <p><?php echo count($messages); ?></p>
                </div>
                
                <?php if ($consultation): ?>
                <div class="mb-3">
                    <h6>Linked Consultation:</h6>
                    <p>
                        <a href="<?php echo SITE_URL; ?>/dashboard/admin/view_consultation.php?id=<?php echo $consultation['id']; ?>">
                            Consultation #<?php echo $consultation['id']; ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Student Information -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-graduate me-1"></i>
                Student
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Student Avatar" class="rounded-circle img-thumbnail" style="width: 80px; height: 80px;">
                    <h6 class="mt-2"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                    <p class="text-muted small"><?php echo $student['email']; ?></p>
                </div>
                
                <div class="text-center">
                    <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php?action=view&id=<?php echo $student['user_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-user me-1"></i> View Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Counselor Information -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-tie me-1"></i>
                Counselor
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Counselor Avatar" class="rounded-circle img-thumbnail" style="width: 80px; height: 80px;">
                    <h6 class="mt-2"><?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?></h6>
                    <p class="text-muted small"><?php echo $counselor['email']; ?></p>
                </div>
                
                <div class="text-center">
                    <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php?action=view&id=<?php echo $counselor['user_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-user me-1"></i> View Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.student-bubble {
    background-color: #f0f0f0;
    border-radius: 18px 18px 18px 0;
    max-width: 75%;
}

.counselor-bubble {
    background-color: #dcf8c6;
    border-radius: 18px 18px 0 18px;
    max-width: 75%;
}

.chat-container {
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to the bottom of the chat container
    const chatContainer = document.querySelector('.chat-container');
    chatContainer.scrollTop = chatContainer.scrollHeight;
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 