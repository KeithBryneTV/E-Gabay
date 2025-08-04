<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Chat.php';
require_once $base_path . '/classes/Consultation.php';

// Check if user is logged in and has counselor role
requireRole('counselor');

// Set page title
$page_title = 'Chat Session';

// Get user data - handle both session systems
$user_id = $_SESSION['user_id'] ?? $_SESSION['ID'] ?? null;

// Debug logging
error_log("Counselor Chat Access - Session data: " . json_encode([
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'ID' => $_SESSION['ID'] ?? 'not set',
    'detected_user_id' => $user_id,
    'role_name' => $_SESSION['role_name'] ?? 'not set',
    'AccountType' => $_SESSION['AccountType'] ?? 'not set'
]));

if (!$user_id) {
    setMessage('User session not found. Please login again.', 'danger');
    redirect(SITE_URL . '/login.php');
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create chat object
$chat = new Chat($db);

// Initialize variables
$chat_id = null;
$consultation_id = null;
$chat_session = null;
$consultation = null;
$messages = [];
$student = null;
$is_anonymous = false;

// Check if chat_id is provided
if (isset($_GET['chat_id']) && !empty($_GET['chat_id'])) {
    $chat_id = (int)$_GET['chat_id'];
    
    // Get chat session details - remove counselor_id restriction to allow viewing closed chats
    $query = "SELECT cs.*, 
              cr.issue_description, cr.issue_category, cr.status as consultation_status, cr.is_anonymous,
              u.first_name, u.last_name, u.email
              FROM chat_sessions cs
              LEFT JOIN consultation_requests cr ON cs.consultation_id = cr.id
              LEFT JOIN users u ON cs.student_id = u.user_id
              WHERE cs.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_id]);
    $chat_session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug logging for chat session
    error_log("Counselor Chat Access - Chat ID: $chat_id, User ID: $user_id, Chat Session: " . json_encode($chat_session));
    
    if (!$chat_session) {
        setMessage('Chat session not found.', 'danger');
        redirect(SITE_URL . '/dashboard/counselor/consultations.php');
        exit;
    }
    
    // Check if user has access to this chat
    if ($chat_session['counselor_id'] != $user_id) {
        setMessage('You do not have permission to access this chat session.', 'danger');
        redirect(SITE_URL . '/dashboard/counselor/consultations.php');
        exit;
    }
    
    $consultation_id = $chat_session['consultation_id'];
    $is_anonymous = isset($chat_session['is_anonymous']) ? (bool)$chat_session['is_anonymous'] : false;
    
    // Mark all messages as read
    $chat->markMessagesAsRead($chat_id, $user_id);
    
    // Get student info
    $query = "SELECT u.*, sp.* 
              FROM users u
              LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
              WHERE u.user_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_session['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}
// Check if consultation_id is provided (for starting a new chat)
elseif (isset($_GET['consultation_id']) && !empty($_GET['consultation_id'])) {
    $consultation_id = (int)$_GET['consultation_id'];
    
    // Get consultation details
    $query = "SELECT cr.*, 
              u.first_name, u.last_name, u.email
              FROM consultation_requests cr
              JOIN users u ON cr.student_id = u.user_id
              WHERE cr.id = ? AND cr.counselor_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_id, $user_id]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consultation) {
        setMessage('Consultation not found or you do not have permission to access it.', 'danger');
        redirect(SITE_URL . '/dashboard/counselor/consultations.php');
        exit;
    }
    
    // Check if consultation is active
    if ($consultation['status'] !== 'live') {
        setMessage('You can only chat for active consultations.', 'warning');
        redirect(SITE_URL . '/dashboard/counselor/view_consultation.php?id=' . $consultation_id);
        exit;
    }
    
    $is_anonymous = (bool)$consultation['is_anonymous'];
    
    // Check if a chat session already exists
    $query = "SELECT * FROM chat_sessions WHERE consultation_id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_id]);
    $existing_chat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_chat) {
        // Redirect to existing chat
        redirect(SITE_URL . '/dashboard/counselor/chat.php?chat_id=' . $existing_chat['id']);
        exit;
    }
    
    // Start new chat session if requested
    if (isset($_GET['start']) && $_GET['start'] == 1) {
        $subject = "Consultation #" . $consultation_id;
        $new_chat_id = $chat->createSession(
            $consultation['student_id'],
            $user_id,
            $subject,
            $consultation_id
        );
        
        if ($new_chat_id) {
            // Send system message
            $welcome_message = "Chat session started by counselor. This conversation is private and confidential.";
            $chat->sendMessage($new_chat_id, null, $welcome_message, 'system');
            
            // Redirect to new chat
            redirect(SITE_URL . '/dashboard/counselor/chat.php?chat_id=' . $new_chat_id);
            exit;
        } else {
            setMessage('Failed to create chat session.', 'danger');
            redirect(SITE_URL . '/dashboard/counselor/view_consultation.php?id=' . $consultation_id);
            exit;
        }
    }
    
    // Get student info
    $query = "SELECT u.*, sp.* 
              FROM users u
              LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
              WHERE u.user_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // No chat_id or consultation_id provided
    // Instead of showing an error message, redirect to consultations page
    redirect(SITE_URL . '/dashboard/counselor/consultations.php');
    exit;
}

// Process form submissions for closing chat
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
        $chat->sendMessage($chat_id, null, "Chat session closed by counselor.", 'system');
        
        // Update consultation status to completed if this chat is linked to a consultation
        if (!empty($chat_data['consultation_id'])) {
            require_once $base_path . '/classes/Consultation.php';
            $consultation = new Consultation($db);
            $consultation->completeRequest($chat_data['consultation_id']);
        }
        
        // Set success message in session
        $_SESSION['message'] = 'Chat session closed successfully.';
        $_SESSION['message_type'] = 'success';
        
        // Direct redirect to consultations page
        header('Location: ' . SITE_URL . '/dashboard/counselor/consultations.php');
        exit;
    } else {
        setMessage('Failed to close chat session.', 'danger');
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<!-- Include Modern Chat CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/chat-modern.css">

<style>
/* Capstone Project Livechat Design - Counselor */
.chat-container { 
    max-width: 800px; 
    margin: 20px auto; 
    background: #fff; 
    border-radius: 10px; 
    box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
    padding: 0; 
}

.chat-header { 
    background: #1a3a5f; 
    color: #fff; 
    padding: 16px; 
    border-radius: 10px 10px 0 0; 
    font-size: 1.2rem; 
    font-weight: bold; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}

.chat-info { 
    font-size: 0.9rem; 
    opacity: 0.9; 
    display: flex; 
    align-items: center; 
}

.chat-actions { 
    min-width: 120px; 
    text-align: right; 
}

.chat-box { 
    height: 400px; 
    overflow-y: auto; 
    padding: 16px; 
    background: #f8f9fa; 
}

.chat { 
    margin-bottom: 12px; 
}

.chat.outgoing { 
    text-align: right; 
}

.chat.incoming { 
    text-align: left; 
}

.chat .details { 
    display: inline-block; 
    max-width: 70%; 
    padding: 10px 16px; 
    border-radius: 18px; 
    position: relative; 
}

.chat.outgoing .details { 
    background: #1a3a5f; 
    color: #fff; 
    border-bottom-right-radius: 4px;
}

.chat.incoming .details { 
    background: #e9ecef; 
    color: #333; 
    border-bottom-left-radius: 4px;
}

.chat-form { 
    display: flex; 
    border-top: 1px solid #eee; 
}

.chat-form input { 
    flex: 1; 
    border: none; 
    padding: 16px; 
    font-size: 1rem; 
    border-radius: 0 0 0 10px; 
}

.chat-form button { 
    background: #1a3a5f; 
    color: #fff; 
    border: none; 
    padding: 0 24px; 
    font-size: 1.1rem; 
    border-radius: 0 0 10px 0; 
    cursor: pointer; 
}

.chat-form button:disabled { 
    background: #ccc; 
    cursor: not-allowed; 
}

.system-message { 
    text-align: center; 
    margin: 10px 0; 
    font-size: 0.9rem; 
    color: #777; 
}

.timestamp { 
    font-size: 0.7rem; 
    margin-top: 3px; 
    opacity: 0.7; 
}

.back-btn { 
    margin-bottom: 10px; 
    display: inline-block; 
}

.consultation-details { 
    background: #f8f9fa; 
    border-radius: 8px; 
    padding: 15px; 
    margin-bottom: 15px; 
    border-left: 4px solid #1a3a5f; 
}

.chat-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    background: #28a745;
    color: white;
}

.chat-status.closed {
    background: #dc3545;
}

#fileUploadBtn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 0 16px;
    cursor: pointer;
}

#fileUploadBtn:hover {
    background: #5a6268;
}

.file-upload-btn {
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-right: 0.5rem;
}

.file-upload-btn:hover {
    background: #5a6268;
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .chat-layout {
        flex-direction: column;
        height: auto;
    }
    
    .chat-sidebar {
        width: 100%;
        height: 200px;
        overflow-y: auto;
    }
    
    .sidebar-content {
        padding: 1rem;
        height: auto;
        display: flex;
        gap: 1rem;
        overflow-x: auto;
    }
    
    .info-card {
        min-width: 250px;
        margin-bottom: 0;
    }
}
</style>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <?php if ($chat_session): ?>
                Chat Session #<?php echo $chat_id; ?>
            <?php else: ?>
                Start Chat Session
            <?php endif; ?>
        </h1>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($chat_session): ?>
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $chat_session['consultation_id']; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Consultation
            </a>
        <?php elseif ($consultation): ?>
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Consultation
            </a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Consultations
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($chat_session): ?>
    <div class="row">
        <div class="col-12">
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php" class="btn btn-secondary back-btn">
                <i class="fa fa-arrow-left"></i> Back to Consultations
            </a>
            
            <div class="consultation-details">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Chat Session Details</strong>
                    <span class="chat-status <?php echo $chat_session['status'] == 'active' ? '' : 'closed'; ?>">
                        <?php echo ucfirst($chat_session['status']); ?>
                    </span>
                </div>
                <div><strong>Date & Time:</strong> <?php echo formatDate($chat_session['created_at'], 'F j, Y'); ?> at <?php echo formatTime($chat_session['created_at']); ?></div>
                <div><strong>Subject:</strong> Consultation #<?php echo $chat_session['consultation_id']; ?></div>
                <div><strong>Student:</strong> <?php echo $is_anonymous ? 'Anonymous Student' : htmlspecialchars($chat_session['first_name'] . ' ' . $chat_session['last_name']); ?></div>
            </div>
            
            <div class="chat-container">
                <div class="chat-header">
                    <div>
                        Chat with <?php echo $is_anonymous ? 'Anonymous Student' : htmlspecialchars($chat_session['first_name'] . ' ' . $chat_session['last_name']); ?>
                    </div>
                    <div class="chat-actions">
                        <?php if ($chat_session['status'] == 'active'): ?>
                            <form id="closeChatForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="close_chat">
                                <input type="hidden" name="chat_id" value="<?php echo $chat_id; ?>">
                                <button type="submit" id="closeChat" class="btn btn-sm btn-light">
                                    <i class="fa fa-check"></i> Close Chat
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge badge-secondary">Closed</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="chat-box" id="chatBox"></div>
                <?php if ($chat_session['status'] == 'active'): ?>
                <form class="chat-form" id="chatForm" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" name="chat_id" value="<?php echo $chat_id; ?>">
                    <input type="text" name="message" id="messageInput" placeholder="Type a message..." autocomplete="off" required />
                    <button type="button" id="fileUploadBtn" title="Attach File">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button type="submit" id="sendBtn">Send</button>
                    <input type="file" id="fileInput" name="file" style="display: none;" 
                           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt,.zip,.rar">
                </form>
                <?php else: ?>
                <div class="p-3 text-center text-muted">This chat is closed. No new messages can be sent.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php elseif ($consultation): ?>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Start Chat Session</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>Consultation Details</h6>
                        <p class="mb-1"><strong>ID:</strong> #<?php echo $consultation_id; ?></p>
                        <p class="mb-1"><strong>Issue:</strong> <?php echo !empty($consultation['issue_category']) ? $consultation['issue_category'] : 'General Consultation'; ?></p>
                        <p class="mb-1"><strong>Description:</strong> <?php echo $consultation['issue_description']; ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($consultation['preferred_date'], 'M d, Y'); ?> at <?php echo formatTime($consultation['preferred_time']); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Student</h6>
                        <?php if ($is_anonymous): ?>
                            <p class="text-muted mb-0">Anonymous Student</p>
                        <?php else: ?>
                            <p class="mb-0"><?php echo $consultation['first_name'] . ' ' . $consultation['last_name']; ?></p>
                            <p class="mb-0"><small><?php echo $consultation['email']; ?></small></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Starting a chat session will allow you to communicate with the student in real-time. The session will remain active until you close it.
                    </div>
                    
                    <div class="text-center">
                        <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?consultation_id=<?php echo $consultation_id; ?>&start=1" class="btn btn-primary">
                            <i class="fas fa-comments me-1"></i> Start Chat Session
                        </a>
                        <a href="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation_id; ?>" class="btn btn-outline-secondary ms-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($chat_session): ?>
<!-- Scripts -->
<script src="<?php echo SITE_URL; ?>/assets/js/jquery-3.5.1.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/bootstrap.min.js"></script>

<script>
const chatBox = document.getElementById('chatBox');
const chatForm = document.getElementById('chatForm');
const chatId = <?php echo $chat_id; ?>;

<?php if ($chat_session['status'] == 'active'): ?>
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');

chatForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!messageInput.value.trim()) return;
    
    console.log('Sending message...', {
        message: messageInput.value.trim(),
        formData: Object.fromEntries(new FormData(chatForm))
    });
    
    sendBtn.disabled = true;
    const formData = new FormData(chatForm);
    
    fetch('<?php echo SITE_URL; ?>/api/send_chat_message.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            messageInput.value = '';
            sendBtn.disabled = false;
            fetchChat();
            chatBox.scrollTop = chatBox.scrollHeight;
        } else {
            console.error('Error:', data.error);
            alert('Error sending message: ' + (data.error || 'Unknown error'));
            sendBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Network error:', err);
        alert('Network error. Please check your connection and try again.');
        sendBtn.disabled = false;
    });
});

// File upload functionality
const fileUploadBtn = document.getElementById('fileUploadBtn');
const fileInput = document.getElementById('fileInput');

if (fileUploadBtn && fileInput) {
    fileUploadBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Simple file size check (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                return;
            }
            
            // Create a simple message showing file is being sent
            const fileMessage = `📎 Sending file: ${file.name}`;
            messageInput.value = fileMessage;
            
            // Trigger form submission
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
}

function fetchChat() {
    const formData = new FormData();
    formData.append('chat_id', chatId);
    
    fetch('<?php echo SITE_URL; ?>/api/get_chat_messages.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                displayMessages(data.messages);
            } else {
                console.error('Error fetching messages:', data.error);
            }
        })
        .catch(err => console.error('Network error:', err));
}

function displayMessages(messages) {
    chatBox.innerHTML = '';
    messages.forEach(msg => {
        const messageDiv = document.createElement('div');
        messageDiv.className = msg.user_id == <?php echo $user_id; ?> ? 'chat outgoing' : 'chat incoming';
        
        const details = document.createElement('div');
        details.className = 'details';
        
        let messageContent = msg.message;
        if (msg.message_type === 'system') {
            messageDiv.className = 'system-message';
            details.textContent = messageContent;
        } else {
            // Use created_at instead of timestamp for the date
            const messageTime = msg.created_at ? new Date(msg.created_at).toLocaleTimeString() : '';
            
            // Check if this is a file message
            if (msg.file_path && msg.file_name) {
                const fileExtension = msg.file_name.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension);
                
                if (isImage) {
                    // Display image preview
                    messageContent = `
                        <div class="file-attachment">
                            <a href="<?php echo SITE_URL; ?>/${msg.file_path}" target="_blank">
                                <img src="<?php echo SITE_URL; ?>/${msg.file_path}" alt="${msg.file_name}" class="file-preview">
                                <div class="file-name">${msg.file_name}</div>
                            </a>
                        </div>
                        <p>${messageContent}</p>
                    `;
                } else {
                    // Display file link
                    messageContent = `
                        <div class="file-attachment">
                            <a href="<?php echo SITE_URL; ?>/${msg.file_path}" target="_blank" class="file-link">
                                <i class="fas fa-file"></i>
                                <div class="file-name">${msg.file_name}</div>
                            </a>
                        </div>
                        <p>${messageContent}</p>
                    `;
                }
            }
            
            details.innerHTML = messageContent + '<div class="timestamp">' + messageTime + '</div>';
        }
        
        messageDiv.appendChild(details);
        chatBox.appendChild(messageDiv);
    });
    
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Load messages immediately on page load
fetchChat();

// Refresh messages every 2 seconds
setInterval(fetchChat, 2000);

<?php endif; ?>

// Close chat button
const closeChat = document.getElementById('closeChat');
const closeChatForm = document.getElementById('closeChatForm');

if (closeChat && closeChatForm) {
    closeChat.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default button submission
        
        if (!confirm('Are you sure you want to close this chat session? This action cannot be undone.')) {
            return false;
        }
        
        // Show loading state
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Closing...';
        
        // Submit the form after a brief delay to show the loading state
        setTimeout(() => {
            closeChatForm.submit();
        }, 500);
    });
}
</script>
<?php endif; ?>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 