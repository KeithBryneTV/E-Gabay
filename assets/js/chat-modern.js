/**
 * E-GABAY ASC - Modern Chat System
 * Enhanced with file attachments, better UX, and real-time features
 */

class ModernChat {
    constructor() {
        this.chatId = null;
        this.currentUserId = null;
        this.siteUrl = '';
        this.lastTimestamp = null;
        this.refreshInterval = null;
        this.refreshRate = 2000;
        this.userHasScrolledUp = false;
        this.isTyping = false;
        this.typingTimeout = null;
        
        this.init();
    }

    init() {
        // Get configuration
        this.getConfiguration();
        
        // Get DOM elements
        this.chatContainer = document.getElementById('chatContainer');
        this.messageForm = document.getElementById('messageForm');
        this.messageInput = document.getElementById('messageInput');
        this.fileInput = document.getElementById('fileInput');
        this.fileUploadBtn = document.getElementById('fileUploadBtn');
        this.sendBtn = document.getElementById('sendBtn');
        this.scrollBottomBtn = document.getElementById('scrollBottomBtn');
        
        if (!this.validateElements()) {
            console.warn('Chat initialization failed: missing essential elements');
            return;
        }

        // Set up event listeners
        this.setupEventListeners();
        
        // Initialize chat
        this.startChat();
        
        console.log('Modern chat initialized successfully');
    }

    getConfiguration() {
        // Get from global variables or data attributes
        this.currentUserId = window.currentUserId || document.body.dataset.userId;
        this.siteUrl = window.siteUrl || document.body.dataset.siteUrl || '';
        this.refreshRate = window.CHAT_REFRESH_INTERVAL || 2000;
        
        const chatIdInput = this.messageForm?.querySelector('input[name="chat_id"]');
        this.chatId = chatIdInput?.value;
    }

    validateElements() {
        return this.chatContainer && this.chatId && this.currentUserId && this.messageForm;
    }

    setupEventListeners() {
        // Message form submission
        if (this.messageForm) {
            this.messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendTextMessage();
            });
        }

        // Message input events
        if (this.messageInput) {
            this.messageInput.addEventListener('input', () => {
                this.adjustTextareaHeight();
                this.handleTyping();
            });
            
            this.messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendTextMessage();
                }
            });
            
            this.messageInput.addEventListener('focus', () => {
                this.adjustTextareaHeight();
            });
        }

        // File upload events
        if (this.fileUploadBtn) {
            this.fileUploadBtn.addEventListener('click', () => {
                this.fileInput?.click();
            });
        }

        if (this.fileInput) {
            this.fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.uploadFile(e.target.files[0]);
                }
            });
        }

        // Scroll events
        if (this.chatContainer) {
            this.chatContainer.addEventListener('scroll', () => {
                this.handleScroll();
            });
        }

        // Scroll to bottom button
        if (this.scrollBottomBtn) {
            this.scrollBottomBtn.addEventListener('click', () => {
                this.scrollToBottom(true);
            });
        }

        // Window focus for marking messages as read
        window.addEventListener('focus', () => {
            this.markMessagesAsRead();
        });
    }

    adjustTextareaHeight() {
        if (!this.messageInput) return;
        
        this.messageInput.style.height = 'auto';
        const newHeight = Math.min(120, Math.max(20, this.messageInput.scrollHeight));
        this.messageInput.style.height = newHeight + 'px';
    }

    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            // Here you could emit typing indicator to other users
        }
        
        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            this.isTyping = false;
            // Here you could stop typing indicator
        }, 1000);
    }

    handleScroll() {
        if (!this.chatContainer) return;
        
        const { scrollTop, scrollHeight, clientHeight } = this.chatContainer;
        const isAtBottom = Math.abs(scrollHeight - scrollTop - clientHeight) < 10;
        
        this.userHasScrolledUp = !isAtBottom;
        
        // Show/hide scroll to bottom button
        if (this.scrollBottomBtn) {
            this.scrollBottomBtn.classList.toggle('show', this.userHasScrolledUp);
        }
    }

    scrollToBottom(smooth = true) {
        if (!this.chatContainer) return;
        
        const scrollOptions = {
            top: this.chatContainer.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto'
        };
        
        this.chatContainer.scrollTo(scrollOptions);
        this.userHasScrolledUp = false;
        
        if (this.scrollBottomBtn) {
            this.scrollBottomBtn.classList.remove('show');
        }
    }

    async sendTextMessage() {
        if (!this.messageInput?.value.trim()) return;
        
        const message = this.messageInput.value.trim();
        
        // Add preview message
        this.addPreviewMessage(message);
        
        // Clear input
        this.messageInput.value = '';
        this.adjustTextareaHeight();
        
        // Disable send button
        this.toggleSendButton(false);
        
        try {
            const formData = new FormData();
            formData.append('chat_id', this.chatId);
            formData.append('message', message);
            
            const response = await fetch(`${this.siteUrl}/api/send_chat_message.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.lastTimestamp = data.timestamp;
                this.removePreviewMessages();
                this.fetchMessages();
            } else {
                throw new Error(data.error || 'Failed to send message');
            }
            
        } catch (error) {
            console.error('Error sending message:', error);
            this.showError('Failed to send message: ' + error.message);
            this.removePreviewMessages();
        } finally {
            this.toggleSendButton(true);
            this.messageInput?.focus();
        }
    }

    async uploadFile(file) {
        // Validate file
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            this.showError('File size exceeds 10MB limit');
            return;
        }

        const allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        if (!allowedTypes.includes(file.type)) {
            this.showError('File type not supported');
            return;
        }

        // Show upload progress
        this.showUploadProgress(file.name);
        
        try {
            const formData = new FormData();
            formData.append('chat_id', this.chatId);
            formData.append('file', file);
            
            const response = await fetch(`${this.siteUrl}/api/upload_chat_file.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.lastTimestamp = data.timestamp;
                this.hideUploadProgress();
                this.fetchMessages();
                this.scrollToBottom();
            } else {
                throw new Error(data.error || 'Failed to upload file');
            }
            
        } catch (error) {
            console.error('Error uploading file:', error);
            this.showError('Failed to upload file: ' + error.message);
            this.hideUploadProgress();
        }
        
        // Reset file input
        if (this.fileInput) {
            this.fileInput.value = '';
        }
    }

    async fetchMessages() {
        if (!this.chatContainer || !this.chatId) return;
        
        try {
            const formData = new FormData();
            formData.append('chat_id', this.chatId);
            if (this.lastTimestamp) {
                formData.append('last_timestamp', this.lastTimestamp);
            }
            
            const response = await fetch(`${this.siteUrl}/api/get_chat_messages.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.lastTimestamp = data.current_timestamp;
                
                // Hide empty state if messages exist
                if (data.messages && data.messages.length > 0) {
                    this.hideEmptyState();
                    this.renderMessages(data.messages);
                }
            }
            
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    }

    renderMessages(messages) {
        messages.forEach(message => {
            this.addMessage(message);
        });
    }

    addMessage(message, isPreview = false) {
        if (!this.chatContainer) return;
        
        // Check if message already exists
        if (!isPreview && document.getElementById(`message-${message.id}`)) {
            return;
        }
        
        const messageEl = this.createMessageElement(message, isPreview);
        
        // Add date separator if needed
        this.addDateSeparatorIfNeeded(message.created_at || new Date());
        
        this.chatContainer.appendChild(messageEl);
        
        // Auto-scroll if user is at bottom or it's their message
        if (!this.userHasScrolledUp || message.user_id == this.currentUserId) {
            this.scrollToBottom();
        }
    }

    addPreviewMessage(text) {
        const previewMessage = {
            id: 'preview-' + Date.now(),
            user_id: this.currentUserId,
            message: text,
            created_at: new Date(),
            message_type: 'user'
        };
        
        this.addMessage(previewMessage, true);
    }

    removePreviewMessages() {
        const previews = this.chatContainer?.querySelectorAll('.message-wrapper.preview');
        previews?.forEach(preview => preview.remove());
    }

    createMessageElement(message, isPreview = false) {
        const isOwn = message.user_id == this.currentUserId;
        const isSystem = message.message_type === 'system';
        const isFile = message.message_type === 'file';
        
        const wrapper = document.createElement('div');
        wrapper.className = `message-wrapper ${isOwn ? 'sent' : 'received'} ${isPreview ? 'preview loading-message' : ''}`;
        if (!isPreview) wrapper.id = `message-${message.id}`;
        
        if (isSystem) {
            wrapper.className = 'message-wrapper system';
            wrapper.innerHTML = `
                <div class="message-bubble system">
                    ${message.message}
                    <div class="message-time">${this.formatTime(message.created_at)}</div>
                </div>
            `;
        } else {
            const bubble = document.createElement('div');
            bubble.className = `message-bubble ${isOwn ? 'sent' : 'received'}`;
            
            let content = `
                ${!isOwn ? `<div class="message-sender">${this.getSenderName(message)}</div>` : ''}
                <div class="message-text">${message.message.replace(/\n/g, '<br>')}</div>
            `;
            
            if (isFile && message.file_path) {
                content += this.createFileAttachment(message);
            }
            
            content += `
                <div class="message-time">
                    ${isPreview ? '<i class="fas fa-clock"></i> Sending...' : this.formatTime(message.created_at)}
                </div>
            `;
            
            bubble.innerHTML = content;
            wrapper.appendChild(bubble);
        }
        
        return wrapper;
    }

    createFileAttachment(message) {
        const extension = message.file_name?.split('.').pop()?.toLowerCase() || '';
        let iconClass = 'fas fa-file';
        let iconType = 'document';
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            iconClass = 'fas fa-image';
            iconType = 'image';
        } else if (extension === 'pdf') {
            iconClass = 'fas fa-file-pdf';
            iconType = 'pdf';
        } else if (['zip', 'rar'].includes(extension)) {
            iconClass = 'fas fa-file-archive';
            iconType = 'archive';
        }
        
        return `
            <div class="file-message">
                <div class="file-icon ${iconType}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${message.file_name}</div>
                    <div class="file-size">${this.formatFileSize(message.file_size)}</div>
                </div>
                <button class="file-download" onclick="window.open('${this.siteUrl}/${message.file_path}', '_blank')">
                    <i class="fas fa-download"></i>
                </button>
            </div>
        `;
    }

    addDateSeparatorIfNeeded(messageDate) {
        const dateStr = this.formatDate(messageDate);
        const lastSeparator = this.chatContainer?.querySelector('.date-separator:last-of-type span');
        
        if (!lastSeparator || lastSeparator.textContent !== dateStr) {
            const separator = document.createElement('div');
            separator.className = 'date-separator';
            separator.innerHTML = `<span>${dateStr}</span>`;
            this.chatContainer?.appendChild(separator);
        }
    }

    toggleSendButton(enabled) {
        if (!this.sendBtn) return;
        
        this.sendBtn.disabled = !enabled;
        
        if (enabled) {
            this.sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        } else {
            this.sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
    }

    showUploadProgress(fileName) {
        const progressEl = document.createElement('div');
        progressEl.className = 'upload-progress';
        progressEl.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            Uploading ${fileName}...
        `;
        
        const inputContainer = document.querySelector('.input-container');
        if (inputContainer) {
            inputContainer.appendChild(progressEl);
        }
    }

    hideUploadProgress() {
        const progressEl = document.querySelector('.upload-progress');
        progressEl?.remove();
    }

    hideEmptyState() {
        const emptyState = this.chatContainer?.querySelector('.chat-empty');
        if (emptyState) {
            emptyState.style.display = 'none';
        }
    }

    showError(message) {
        // Simple notification - you can replace with your preferred notification system
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            this.showToast(message, 'error');
        } else {
            alert('Error: ' + message);
        }
    }

    showToast(message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'primary'}`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    markMessagesAsRead() {
        // This would typically make an API call to mark messages as read
        // Implementation depends on your backend setup
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }

    getSenderName(message) {
        // Only anonymize student messages in anonymous chats
        // Check if this is an anonymous chat AND the message is from a student (role_id 3)
        if (window.isAnonymousChat && message.role_id == 3) {
            return 'Anonymous Student';
        }
        // Always show the real name for counselors and in non-anonymous chats
        return message.first_name || 'User';
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    startChat() {
        // Initial message load
        this.fetchMessages();
        
        // Set up refresh interval
        this.refreshInterval = setInterval(() => {
            this.fetchMessages();
        }, this.refreshRate);
        
        // Focus on input
        setTimeout(() => {
            this.messageInput?.focus();
        }, 500);
    }

    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }
    }
}

// Initialize chat when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a chat page
    if (document.getElementById('chatContainer')) {
        window.modernChat = new ModernChat();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (window.modernChat) {
        window.modernChat.destroy();
    }
}); 