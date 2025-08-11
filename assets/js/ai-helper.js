/**
 * EGABAY Enhanced AI Helper - Real AI Integration
 * Fixed version with working replies and dynamic suggestions
 */

class EgabayAIHelper {
    constructor() {
        this.isOpen = false;
        this.conversationHistory = [];
        this.initializeHelper();
        this.isFirstOpen = true;
    }

    initializeHelper() {
        this.createHelperWidget();
        this.attachEventListeners();
    }

    createHelperWidget() {
        const helperHTML = `
            <div id="egabay-ai-helper" class="egabay-ai-helper">
                <!-- Toggle Button -->
                <div id="ai-helper-toggle" class="ai-helper-toggle">
                    <i class="fas fa-comments"></i>
                    <span class="helper-badge" id="helper-badge">?</span>
                </div>

                <!-- Chat Container -->
                <div id="ai-chat-container" class="ai-chat-container">
                    <div class="ai-chat-header">
                        <div class="header-content">
                            <div class="helper-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="helper-info">
                                <h6>EGABAY System Helper</h6>
                                <span class="status-text">Here to help with system questions</span>
                            </div>
                        </div>
                        <button id="ai-chat-close" class="chat-close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="ai-chat-messages" id="ai-chat-messages">
                        <!-- Messages will be inserted here -->
                    </div>
                    
                    <div class="ai-chat-suggestions" id="ai-chat-suggestions">
                        <!-- Dynamic suggestions will appear here -->
                    </div>
                    
                    <div class="ai-chat-input">
                        <div class="input-container">
                            <input type="text" 
                                   id="ai-message-input" 
                                   placeholder="Ask about system features, navigation, or technical issues..."
                                   maxlength="500">
                            <button id="ai-send-btn" class="send-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', helperHTML);
    }

    attachEventListeners() {
        // Toggle chat
        document.getElementById('ai-helper-toggle').addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleChat();
        });

        // Close chat
        document.getElementById('ai-chat-close').addEventListener('click', (e) => {
            e.preventDefault();
            this.closeChat();
        });

        // Send message on button click
        document.getElementById('ai-send-btn').addEventListener('click', (e) => {
            e.preventDefault();
            this.handleSendMessage();
        });

        // Send message on Enter key
        document.getElementById('ai-message-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleSendMessage();
            }
        });

        // Handle suggestion clicks
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('suggestion-btn')) {
                e.preventDefault();
                const suggestionText = e.target.textContent;
                this.sendMessage(suggestionText);
            }
        });
    }

    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        const container = document.getElementById('ai-chat-container');
        const toggle = document.getElementById('ai-helper-toggle');
        
        container.classList.add('active');
        toggle.classList.add('active');
        this.isOpen = true;

        // Show privacy warning on first open
        if (this.isFirstOpen) {
            this.showOpeningMessage();
            this.isFirstOpen = false;
        }

        // Focus input
        setTimeout(() => {
            document.getElementById('ai-message-input').focus();
        }, 300);
    }

    closeChat() {
        const container = document.getElementById('ai-chat-container');
        const toggle = document.getElementById('ai-helper-toggle');
        
        container.classList.remove('active');
        toggle.classList.remove('active');
        this.isOpen = false;
        this.hideSuggestions();
    }

    showOpeningMessage() {
        const welcomeMessage = `
            <div class="ai-message system-message">
                <div class="message-avatar">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">
                        <strong>⚠️ Privacy Notice:</strong><br>
                        I'm a system helper only. Please don't send sensitive information like passwords, personal details, or private data. I can help with system navigation, features, and technical issues.
                        <br><br>
                        <strong>How can I assist you today?</strong>
                    </div>
                    <div class="message-time">${new Date().toLocaleTimeString()}</div>
                </div>
            </div>
        `;
        
        document.getElementById('ai-chat-messages').innerHTML = welcomeMessage;
        
        // Show initial suggestions
        this.showSuggestions([
            'How to request consultation?',
            'Where is my dashboard?',
            'Login problems?',
            'System features?'
        ]);
    }

    handleSendMessage() {
        const input = document.getElementById('ai-message-input');
        const message = input.value.trim();
        
        if (message) {
            this.sendMessage(message);
            input.value = '';
        }
    }

    async sendMessage(message) {
        // Add user message to chat
        this.addUserMessage(message);
        this.hideSuggestions();
        
        // Show typing indicator
        this.showTyping();
        
        try {
            const response = await fetch(this.getBaseUrl() + '/api/ai_helper_enhanced.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    user_role: this.getUserRole()
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            this.hideTyping();
            this.addAIMessage(data.response || 'Sorry, I couldn\'t process that request.');
            
            // Show suggestions if provided
            if (data.suggestions && data.suggestions.length > 0) {
                setTimeout(() => {
                    this.showSuggestions(data.suggestions);
                }, 500);
            }
            
        } catch (error) {
            console.error('AI Helper Error:', error);
            this.hideTyping();
            this.addAIMessage('Sorry, I\'m having technical difficulties. Please try refreshing the page or contact support.');
        }
    }

    addUserMessage(message) {
        const messageHTML = `
            <div class="ai-message user-message">
                <div class="message-content">
                    <div class="message-text">${this.escapeHtml(message)}</div>
                    <div class="message-time">${new Date().toLocaleTimeString()}</div>
                </div>
                <div class="message-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        `;
        
        document.getElementById('ai-chat-messages').insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    addAIMessage(message) {
        const formattedMessage = this.formatMessage(message);
        const messageHTML = `
            <div class="ai-message ai-response">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">${formattedMessage}</div>
                    <div class="message-time">${new Date().toLocaleTimeString()}</div>
                </div>
            </div>
        `;
        
        document.getElementById('ai-chat-messages').insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    showTyping() {
        const typingHTML = `
            <div class="ai-message ai-response typing-message" id="typing-indicator">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('ai-chat-messages').insertAdjacentHTML('beforeend', typingHTML);
        this.scrollToBottom();
    }

    hideTyping() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    showSuggestions(suggestions) {
        const suggestionsContainer = document.getElementById('ai-chat-suggestions');
        let suggestionsHTML = `
            <div class="suggestions-header">
                <div class="suggestions-label">Suggested questions:</div>
                <button class="suggestions-close-btn" id="suggestions-close-btn" title="Hide suggestions">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="suggestions-content">
        `;
        
        suggestions.forEach(suggestion => {
            suggestionsHTML += `
                <button class="suggestion-btn">${this.escapeHtml(suggestion)}</button>
            `;
        });
        
        suggestionsHTML += '</div>';
        
        suggestionsContainer.innerHTML = suggestionsHTML;
        suggestionsContainer.classList.add('active');
        
        // Add event listener for close button
        document.getElementById('suggestions-close-btn').addEventListener('click', (e) => {
            e.preventDefault();
            this.hideSuggestions();
        });
    }

    hideSuggestions() {
        const suggestionsContainer = document.getElementById('ai-chat-suggestions');
        suggestionsContainer.classList.remove('active');
        setTimeout(() => {
            suggestionsContainer.innerHTML = '';
        }, 300);
    }

    formatMessage(message) {
        return message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/• /g, '• ');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    scrollToBottom() {
        const messagesContainer = document.getElementById('ai-chat-messages');
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }

    getUserRole() {
        // Try to get user role from meta tag or session
        const roleElement = document.querySelector('meta[name="user-role"]');
        if (roleElement) {
            return roleElement.getAttribute('content');
        }
        
        // Fallback: detect from URL or page context
        if (window.location.href.includes('/admin/')) return 'admin';
        if (window.location.href.includes('/counselor/')) return 'counselor';
        if (window.location.href.includes('/student/')) return 'student';
        
        return 'guest';
    }

    getBaseUrl() {
        // Try to get base URL from meta tag
        const baseElement = document.querySelector('meta[name="base-url"]');
        if (baseElement) {
            return baseElement.getAttribute('content');
        }
        
        // Fallback: construct from current location
        const pathParts = window.location.pathname.split('/');
        const egabayIndex = pathParts.findIndex(part => part.toLowerCase() === 'egabay');
        
        if (egabayIndex !== -1) {
            const basePath = pathParts.slice(0, egabayIndex + 1).join('/');
            return window.location.origin + basePath;
        }
        
        return window.location.origin;
    }

    // Public methods for external use
    openChatExternal() {
        this.openChat();
    }

    sendMessageExternal(message) {
        if (!this.isOpen) {
            this.openChat();
            setTimeout(() => {
                this.sendMessage(message);
            }, 500);
        } else {
            this.sendMessage(message);
        }
    }
}

// Enhanced CSS styles for the improved helper
const aiHelperStyles = `
<style>
.egabay-ai-helper {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.ai-helper-toggle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    position: relative;
    border: none;
}

.ai-helper-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(0, 123, 255, 0.4);
}

.ai-helper-toggle i {
    color: white;
    font-size: 24px;
    transition: transform 0.3s ease;
}

.ai-helper-toggle.active i {
    transform: rotate(180deg);
}

.helper-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.ai-chat-container {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 400px;
    height: 600px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    transform: translateY(20px) scale(0.95);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    border: 1px solid #e0e6ed;
}

.ai-chat-container.active {
    transform: translateY(0) scale(1);
    opacity: 1;
    visibility: visible;
}

.ai-chat-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 16px 20px;
    border-radius: 16px 16px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.helper-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.helper-avatar i {
    font-size: 20px;
}

.helper-info h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.status-text {
    font-size: 12px;
    opacity: 0.9;
    margin-top: 2px;
}

.chat-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.chat-close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.ai-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #f8f9fa;
}

.ai-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    animation: slideIn 0.3s ease;
}

.user-message {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
}

.ai-response .message-avatar {
    background: #007bff;
    color: white;
}

.user-message .message-avatar {
    background: #28a745;
    color: white;
}

.system-message .message-avatar {
    background: #ffc107;
    color: #212529;
}

.message-content {
    max-width: 75%;
    display: flex;
    flex-direction: column;
}

.user-message .message-content {
    align-items: flex-end;
}

.message-text {
    background: white;
    padding: 12px 16px;
    border-radius: 16px;
    border: 1px solid #e0e6ed;
    line-height: 1.5;
    word-wrap: break-word;
}

.user-message .message-text {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.system-message .message-text {
    background: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.message-time {
    font-size: 11px;
    color: #6c757d;
    margin-top: 4px;
    padding: 0 4px;
}

.typing-message .message-content {
    background: white;
    padding: 12px 16px;
    border-radius: 16px;
    border: 1px solid #e0e6ed;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    align-items: center;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #007bff;
    border-radius: 50%;
    animation: typing 1.4s ease-in-out infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

.ai-chat-suggestions {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.ai-chat-suggestions.active {
    max-height: 200px;
    padding: 16px 20px;
}

.suggestions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.suggestions-label {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
    margin: 0;
}

.suggestions-close-btn {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    font-size: 12px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
}

.suggestions-close-btn:hover {
    background: #f1f3f4;
    color: #495057;
}

.suggestions-content {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.suggestion-btn {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 6px 12px;
    font-size: 12px;
    color: #495057;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-block;
    margin: 0;
}

.suggestion-btn:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.ai-chat-input {
    padding: 16px 20px;
    border-top: 1px solid #e0e6ed;
    background: white;
    border-radius: 0 0 16px 16px;
}

.input-container {
    display: flex;
    gap: 8px;
    align-items: center;
}

#ai-message-input {
    flex: 1;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease;
}

#ai-message-input:focus {
    border-color: #007bff;
}

.send-btn {
    width: 36px;
    height: 36px;
    background: #007bff;
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.send-btn:hover {
    background: #0056b3;
}

.send-btn:active {
    transform: scale(0.95);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

/* Mobile responsiveness */
@media (max-width: 480px) {
    .ai-chat-container {
        width: 350px;
        height: 500px;
        bottom: 80px;
        right: -10px;
    }
    
    .message-content {
        max-width: 85%;
    }
}

@media (max-width: 380px) {
    .ai-chat-container {
        width: 320px;
        right: -20px;
    }
}
</style>
`;

// Add styles to head
document.head.insertAdjacentHTML('beforeend', aiHelperStyles);

// Initialize helper when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.egabayAI = new EgabayAIHelper();
    });
} else {
    window.egabayAI = new EgabayAIHelper();
} 