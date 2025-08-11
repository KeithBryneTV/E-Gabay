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

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$page_title = 'Chat';

// Get user data - handle both session systems
$user_id = $_SESSION['user_id'] ?? $_SESSION['ID'] ?? null;

// Debug logging
error_log("Student Chat Access - Session data: " . json_encode([
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'ID' => $_SESSION['ID'] ?? 'not set',
    'detected_user_id' => $user_id,
    'role_name' => $_SESSION['role_name'] ?? 'not set',
    'AccountType' => $_SESSION['AccountType'] ?? 'not set'
]));

if (!$user_id) {
    setMessage('User session not found. Please login again.', 'danger');
    redirect(rtrim(SITE_URL, '/') . '/login');
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create chat object
$chat = new Chat($db);

// Check if chat ID is provided
$chat_id = null;
$chat_session = null;
$consultation_id = null;
$consultation_data = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $chat_id = (int)$_GET['id'];
    
    // Get chat session details
    $query = "SELECT cs.*, 
              u1.first_name as student_first_name, u1.last_name as student_last_name,
              u2.first_name as counselor_first_name, u2.last_name as counselor_last_name,
              IFNULL(cr.is_anonymous, 0) as is_anonymous
              FROM chat_sessions cs
              LEFT JOIN users u1 ON cs.student_id = u1.user_id
              LEFT JOIN users u2 ON cs.counselor_id = u2.user_id
              LEFT JOIN consultation_requests cr ON cs.consultation_id = cr.id
              WHERE cs.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$chat_id]);
    $chat_session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug logging for chat session
    error_log("Student Chat Access - Chat ID: $chat_id, User ID: $user_id, Chat Session: " . json_encode($chat_session));
    
    // Check if chat session exists and student has access
    if (!$chat_session) {
        setMessage('Chat session not found. Please check your consultations page.', 'danger');
        redirect(SITE_URL . '/dashboard/student/consultations.php');
        exit;
    }
    
    if ($chat_session['student_id'] != $user_id) {
        setMessage('You do not have permission to access this chat session.', 'danger');
        redirect(SITE_URL . '/dashboard/student/consultations.php');
        exit;
    }
    
    // Get consultation ID if available
    $consultation_id = $chat_session['consultation_id'];
    
    // Check consultation status to sync with chat status
    if ($consultation_id) {
        $query = "SELECT status FROM consultation_requests WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$consultation_id]);
        $consultation_status = $stmt->fetchColumn();
        
        // If consultation is completed but chat shows active, update chat status
        if ($consultation_status === 'completed' && $chat_session['status'] === 'active') {
            $update_query = "UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$chat_id]);
            $chat_session['status'] = 'closed'; // Update local variable
        }
    }
    
    // Mark messages as read
    $chat->markMessagesAsRead($chat_id, $user_id);
} elseif (isset($_GET['consultation_id']) && !empty($_GET['consultation_id'])) {
    // Coming from a consultation
    $consultation_id = (int)$_GET['consultation_id'];
    
    // Get consultation details
    $consultation = new Consultation($db);
    $consultation_data = $consultation->getRequestById($consultation_id);
    
    // Check if consultation exists and student has access
    if (!$consultation_data || $consultation_data['student_id'] != $user_id) {
        setMessage('Consultation not found or you do not have permission to access it.', 'danger');
        redirect(SITE_URL . '/dashboard/student/consultations.php');
        exit;
    }
    
    // Check if consultation has a counselor assigned and is active
    if (!$consultation_data['counselor_id']) {
        setMessage('No counselor has been assigned to this consultation yet.', 'warning');
        redirect(SITE_URL . '/dashboard/student/view_consultation.php?id=' . $consultation_id);
        exit;
    }
    
    if ($consultation_data['status'] !== 'live') {
        setMessage('You can only chat for active consultations.', 'warning');
        redirect(SITE_URL . '/dashboard/student/view_consultation.php?id=' . $consultation_id);
        exit;
    }
    
    // Check if a chat session already exists for this consultation
    $query = "SELECT * FROM chat_sessions WHERE consultation_id = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_id]);
    $existing_chat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_chat) {
        // Use existing chat session
        $chat_id = $existing_chat['id'];
        $chat_session = $chat->getSessionById($chat_id);
        
        // Mark messages as read
        $chat->markMessagesAsRead($chat_id, $user_id);
    } else {
        // Create new chat session
        $subject = "Consultation #" . $consultation_id;
        $chat_id = $chat->createSession(
            $user_id, 
            $consultation_data['counselor_id'], 
            $subject,
            $consultation_id
        );
        
        if ($chat_id) {
            $chat_session = $chat->getSessionById($chat_id);
            
            // Add system welcome message
            $chat->sendMessage($chat_id, null, "Chat session started. This conversation is private and confidential.", "system");
        } else {
            setMessage('Failed to create chat session.', 'danger');
            redirect(SITE_URL . '/dashboard/student/view_consultation.php?id=' . $consultation_id);
            exit;
        }
    }
} else {
    // No chat_id or consultation_id provided
    // Instead of showing an error message, redirect to consultations page
    redirect(SITE_URL . '/dashboard/student/consultations.php');
    exit;
}

// Get counselor details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$chat_session['counselor_id']]);
$counselor = $stmt->fetch(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<!-- Include Modern Chat CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/chat-modern.css">

<style>
/* Capstone Project Livechat Design */
.chat-container { 
    max-width: 800px; 
    margin: 20px auto; 
    background: #fff; 
    border-radius: 10px; 
    box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
    padding: 0; 
}

.chat-header { 
    background: #6a1b9a; 
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
    background: #6a1b9a; 
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
    background: #6a1b9a; 
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
    border-left: 4px solid #6a1b9a; 
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

/* Chat Main Area */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f8f9fa;
}

/* File Upload Button */
.chat-input {
    padding: 1rem;
    background: white;
    border-top: 1px solid #e1e8ed;
}

.chat-input .input-group {
    align-items: flex-end;
}

.chat-input textarea {
    resize: none;
    border-radius: 20px 0 0 20px;
    border-right: none;
}

.chat-input .btn {
    border-radius: 0;
}

.chat-input .btn:last-child {
    border-radius: 0 20px 20px 0;
}

#fileUploadBtn {
    border-left: none;
    border-right: none;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .chat-layout {
        flex-direction: column;
        height: auto;
    }
    
    .chat-sidebar {
        width: 100%;
        height: 180px;
        overflow-y: auto;
    }
    
    .sidebar-content {
        padding: 0.75rem;
        height: auto;
        display: flex;
        gap: 1rem;
        overflow-x: auto;
    }
    
    .info-card {
        min-width: 200px;
        margin-bottom: 0;
    }
}
</style>

<div class="container-fluid">
    <?php if ($chat_session): ?>
    <div class="row">
        <div class="col-12">
            <a href="<?php echo SITE_URL; ?>/dashboard/student/consultations.php" class="btn btn-secondary back-btn">
                <i class="fa fa-arrow-left"></i> Back to Consultations
            </a>
            
            <?php if ($consultation_id): ?>
            <div class="consultation-details">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Consultation Details</strong>
                    <span class="chat-status <?php echo $chat_session['status'] == 'active' ? '' : 'closed'; ?>">
                        <?php echo ucfirst($chat_session['status']); ?>
                    </span>
                </div>
                <div><strong>Date & Time:</strong> <?php echo formatDate($chat_session['created_at'], 'F j, Y'); ?> at <?php echo formatTime($chat_session['created_at']); ?></div>
                <div><strong>Subject:</strong> Consultation #<?php echo $consultation_id; ?></div>
                <div><strong>Chatting with:</strong> <?php echo htmlspecialchars($chat_session['counselor_first_name'] . ' ' . $chat_session['counselor_last_name']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="chat-container">
                <div class="chat-header">
                    <div>
                        Chat with <?php echo htmlspecialchars($chat_session['counselor_first_name'] . ' ' . $chat_session['counselor_last_name']); ?>
                    </div>
                    <div class="chat-actions">
                        <?php if ($chat_session['status'] == 'active'): ?>
                            <span class="badge badge-success">Active</span>
                            <button class="btn btn-sm btn-danger ms-2" id="endChatBtn" data-bs-toggle="modal" data-bs-target="#endChatModal">
                                <i class="fas fa-times me-1"></i> End Chat
                            </button>
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
    <?php else: ?>
    <!-- No chat session message -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center">
                    <h3>No Active Chat Session</h3>
                    <p class="text-muted">You don't have an active chat session. Please start a consultation first.</p>
                    <a href="<?php echo SITE_URL; ?>/dashboard/student/consultations.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Consultations
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- End Chat Modal -->
<div class="modal fade" id="endChatModal" tabindex="-1" aria-labelledby="endChatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="endChatModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>End Chat Session
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to end this chat session? This action cannot be undone.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>What happens next?</strong>
                    <ul class="mb-0 mt-2">
                        <li>This chat session will be closed permanently</li>
                        <li>You'll be redirected to provide feedback</li>
                        <li>Your consultation status will be updated</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmEndChat">
                    <i class="fas fa-check me-2"></i>Yes, End Chat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add these JavaScript variables for real-time chat -->
<script>
    // Chat configuration
    const CHAT_REFRESH_INTERVAL = <?php echo defined('CHAT_REFRESH_INTERVAL') ? CHAT_REFRESH_INTERVAL : 2000; ?>;
    const CHAT_MAX_MESSAGES = <?php echo defined('CHAT_MAX_MESSAGES') ? CHAT_MAX_MESSAGES : 100; ?>;
    const CHAT_ENABLE_TYPING_INDICATOR = <?php echo defined('CHAT_ENABLE_TYPING_INDICATOR') && CHAT_ENABLE_TYPING_INDICATOR ? 'true' : 'false'; ?>;
    
    // Set global variables for chat system
    window.currentUserId = <?php echo $user_id; ?>;
    window.siteUrl = '<?php echo SITE_URL; ?>';
    window.isAnonymousChat = <?php echo isset($chat_session['is_anonymous']) && $chat_session['is_anonymous'] ? 'true' : 'false'; ?>;
</script>

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
        chatId: chatId,
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
            // Use created_at instead of timestamp for the date (Philippine timezone)
            const messageTime = msg.created_at ? new Date(msg.created_at).toLocaleTimeString('en-PH', { 
                timeZone: 'Asia/Manila',
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            }) : '';
            
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
</script>

<script>
// Add end chat functionality
document.getElementById('confirmEndChat')?.addEventListener('click', function() {
    // Disable the button to prevent multiple submissions
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    
    // Create form data for the request
    const formData = new FormData();
    formData.append('action', 'end_chat');
    formData.append('chat_id', <?php echo $chat_id; ?>);
    formData.append('consultation_id', <?php echo $consultation_id ?? 'null'; ?>);
    
    // Send request to end the chat
    fetch('<?php echo SITE_URL; ?>/api/end_chat_session.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Add system message about chat ending
            const systemMessage = document.createElement('div');
            systemMessage.className = 'system-message';
            systemMessage.textContent = 'You ended this chat session. You will now be redirected to provide feedback.';
            chatBox.appendChild(systemMessage);
            chatBox.scrollTop = chatBox.scrollHeight;
            
            // Wait 2 seconds, then redirect to feedback
            setTimeout(() => {
                if (data.consultation_id) {
                    window.location.href = '<?php echo SITE_URL; ?>/dashboard/student/view_consultation.php?id=' + data.consultation_id + '&show_feedback=1';
                } else {
                    window.location.href = '<?php echo SITE_URL; ?>/dashboard/student/consultations.php';
                }
            }, 2000);
        } else {
            alert('Error ending chat: ' + (data.error || 'Unknown error'));
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check me-2"></i>Yes, End Chat';
            $('#endChatModal').modal('hide');
        }
    })
    .catch(err => {
        console.error('Error ending chat:', err);
        alert('Network error. Please try again.');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check me-2"></i>Yes, End Chat';
        $('#endChatModal').modal('hide');
    });
});
</script>
<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 