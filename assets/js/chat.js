/**
 * E-GABAY ASC - Chat Functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let chatRefreshInterval = null;
    let lastTimestamp = null;
    let userHasScrolledUp = false;
    
    // Elements
    const chatContainer = document.getElementById('chatContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const chatIdInput = messageForm ? messageForm.querySelector('input[name="chat_id"]') : null;
    const chatId = chatIdInput ? chatIdInput.value : null;
    
    // Get configuration from global variables or defaults
    const refreshRate = typeof CHAT_REFRESH_INTERVAL !== 'undefined' ? CHAT_REFRESH_INTERVAL : 2000;
    const maxMessages = typeof CHAT_MAX_MESSAGES !== 'undefined' ? CHAT_MAX_MESSAGES : 100;
    
    // Get user info from global variables or body data attributes
    const currentUserId = window.currentUserId || document.body.dataset.userId;
    const siteUrl = window.siteUrl || document.body.dataset.siteUrl || '';
    const isAnonymousChat = window.isAnonymousChat === true || document.body.dataset.anonymousChat === 'true';
    
    console.log('Chat initialized with:', {
        chatId: chatId,
        currentUserId: currentUserId,
        siteUrl: siteUrl,
        refreshRate: refreshRate
    });
    
    // Early return if essential elements are missing
    if (!chatContainer || !chatId || !currentUserId) {
        console.warn('Chat initialization failed: missing essential elements');
        return;
    }
    
    // Scroll to bottom of chat container
    const scrollToBottom = (smooth = true) => {
        if (chatContainer) {
            if (smooth) {
                chatContainer.scrollTo({
                    top: chatContainer.scrollHeight,
                    behavior: 'smooth'
                });
            } else {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }
    };
    
    // Initial scroll to bottom
    scrollToBottom(false);
    
    // Auto-resize textarea
    if (messageInput) {
        const adjustHeight = () => {
            messageInput.style.height = 'auto';
            const newHeight = Math.min(120, Math.max(46, messageInput.scrollHeight));
            messageInput.style.height = newHeight + 'px';
        };
        
        messageInput.addEventListener('input', adjustHeight);
        messageInput.addEventListener('focus', adjustHeight);
        
        // Enter to send, Shift+Enter for new line
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (messageForm && messageInput.value.trim()) {
                    sendMessage();
                }
            }
        });
        
        // Focus on input when page loads
        setTimeout(() => messageInput.focus(), 300);
    }
    
    // Check if user has scrolled up
    if (chatContainer) {
        chatContainer.addEventListener('scroll', function() {
            const isScrolledToBottom = Math.abs(chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight) < 10;
            userHasScrolledUp = !isScrolledToBottom;
        });
    }
    
    // Format date and time
    const formatTime = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };
    
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    };
    
    // Create a message element
    const createMessageElement = (message, isPreview = false) => {
        // Check if message already exists (to prevent duplicates)
        if (!isPreview && document.getElementById(`message-${message.id}`)) {
            return null;
        }
        
        const isOutgoing = message.user_id == currentUserId;
        const isSystem = message.message_type === 'system';
        
        if (isSystem) {
            const systemMsg = document.createElement('div');
            systemMsg.className = 'system-message';
            if (!isPreview) systemMsg.id = `message-${message.id}`;
            systemMsg.innerHTML = `
                <div class="system-message-content">
                    <span>${message.message}</span>
                    <small class="text-muted d-block">${formatTime(message.created_at)}</small>
                </div>
            `;
            return systemMsg;
        } else {
            const messageEl = document.createElement('div');
            messageEl.className = `chat-message ${isOutgoing ? 'outgoing' : 'incoming'} ${isPreview ? 'preview' : ''}`;
            if (!isPreview) messageEl.id = `message-${message.id}`;
            
            let senderHTML = '';
            if (!isOutgoing && !isPreview) {
                // Only anonymize student messages in anonymous chats
                // Check if this is an anonymous chat AND the message is from a student (role_id 3)
                if (window.isAnonymousChat && message.role_id == 3) {
                    senderHTML = `<div class="message-sender">Anonymous Student</div>`;
                } else {
                    // Always show the real name for counselors and in non-anonymous chats
                    senderHTML = `<div class="message-sender">${message.first_name || 'User'}</div>`;
                }
            }
            
            messageEl.innerHTML = `
                <div class="message-content">
                    ${senderHTML}
                    ${message.message.replace(/\n/g, '<br>')}
                    <div class="message-time">
                        <small class="${isOutgoing ? 'text-white-50' : 'text-muted'}">
                            ${isPreview ? '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...' : formatTime(message.created_at)}
                        </small>
                    </div>
                </div>
            `;
            
            return messageEl;
        }
    };
    
    // Add message to chat
    const addMessageToChat = (message, isPreview = false) => {
        if (!chatContainer) return;
        
        const messageEl = createMessageElement(message, isPreview);
        if (!messageEl) return; // Skip if message already exists
        
        // Check if we need to add a date separator
        const messageDate = formatDate(message.created_at || new Date());
        const lastDateSeparator = Array.from(chatContainer.querySelectorAll('.date-separator span')).pop();
        const lastDateText = lastDateSeparator ? lastDateSeparator.textContent : null;
        
        if (messageDate !== lastDateText && !isPreview) {
            const separator = document.createElement('div');
            separator.className = 'date-separator';
            separator.innerHTML = `<span>${messageDate}</span>`;
            chatContainer.appendChild(separator);
        }
        
        chatContainer.appendChild(messageEl);
        
        // Clean up preview messages once real message is displayed
        if (!isPreview) {
            const previews = chatContainer.querySelectorAll('.chat-message.preview');
            previews.forEach(preview => preview.remove());
        }
        
        // Scroll to bottom if user hasn't scrolled up or this is their message
        if (!userHasScrolledUp || (message.user_id == currentUserId)) {
            scrollToBottom();
        }
    };
    
    // Show error toast
    const showErrorToast = (message) => {
        console.error('Chat Error:', message);
        
        // Simple alert fallback if Bootstrap toast is not available
        if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
            alert('Chat Error: ' + message);
            return;
        }
        
        // Check if toast container exists, if not create it
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-danger';
        toast.id = toastId;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    };
    
    // Send message via AJAX
    const sendMessage = () => {
        if (!messageForm || !messageInput || !messageInput.value.trim()) return;
        
        const message = messageInput.value.trim();
        
        // Add loading state to button
        const submitBtn = messageForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
        
        // Add temporary message preview
        const previewMessage = {
            id: 'preview-' + Date.now(),
            user_id: currentUserId,
            message: message,
            created_at: new Date()
        };
        addMessageToChat(previewMessage, true);
        
        // Reset textarea height and clear input
        messageInput.style.height = '46px';
        messageInput.value = '';
        
        // Reset user scroll position
        userHasScrolledUp = false;
        
        // Create form data
        const formData = new FormData();
        formData.append('chat_id', chatId);
        formData.append('message', message);
        
        // Send with fetch API
        fetch(`${siteUrl}/api/send_chat_message.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Reset button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
            
            if (data.status === 'success') {
                // The message will be added by the refresh function
                lastTimestamp = data.timestamp;
                // Trigger immediate refresh to show the sent message
                fetchMessages();
            } else {
                showErrorToast(data.error || 'Failed to send message');
                
                // Remove preview
                const previews = chatContainer.querySelectorAll('.chat-message.preview');
                previews.forEach(preview => preview.remove());
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            
            // Reset button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
            
            showErrorToast('Network error. Please check your connection and try again.');
            
            // Remove preview
            const previews = chatContainer.querySelectorAll('.chat-message.preview');
            previews.forEach(preview => preview.remove());
        });
        
        return false;
    };
    
    // Handle message submission
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    
    // Fetch messages via AJAX
    const fetchMessages = () => {
        if (!chatContainer || !chatId) return;
        
        const formData = new FormData();
        formData.append('chat_id', chatId);
        if (lastTimestamp) {
            formData.append('last_timestamp', lastTimestamp);
        }
        
        fetch(`${siteUrl}/api/get_chat_messages.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update timestamp
                lastTimestamp = data.current_timestamp;
                
                // Remove loading message if it exists
                const loadingMessage = chatContainer.querySelector('.chat-empty-state');
                if (loadingMessage && data.messages && data.messages.length > 0) {
                    loadingMessage.style.display = 'none';
                }
                
                // Add messages
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        addMessageToChat(message);
                    });
                }
            } else {
                console.error('Failed to fetch messages:', data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
        });
    };
    
    // Handle close chat button
    const closeChat = document.getElementById('closeChat');
    if (closeChat) {
        closeChat.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to close this chat session? This cannot be undone.')) {
                // Show loading state
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Closing...';
                
                // Submit the form
                document.getElementById('closeChatForm')?.submit();
            }
        });
    }
    
    // Initialize chat
    const initChat = () => {
        console.log('Initializing chat...');
        
        // Get initial messages
        fetchMessages();
        
        // Set refresh interval
        chatRefreshInterval = setInterval(fetchMessages, refreshRate);
        
        console.log('Chat initialized successfully');
    };
    
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
                messageForm.dispatchEvent(new Event('submit'));
                
                // Clear file input
                fileInput.value = '';
            }
        });
    }
    
    // Initialize the chat
    initChat();
}); 