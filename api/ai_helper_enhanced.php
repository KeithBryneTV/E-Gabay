<?php
// EGABAY AI Helper - English Only System Assistant
require_once __DIR__ . '/../includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$user_role = $input['user_role'] ?? 'guest';

if (empty($message)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

/**
 * EGABAY System Helper - English Only
 * 
 * Simple system assistant that helps with navigation and basic questions.
 * Privacy-focused with no personal data storage.
 */
class EgabaySystemHelper {
    
    private $user_role = '';
    private $db;
    private $restricted_keywords = [];
    private $single_word_responses = [];
    
    public function __construct($user_role = 'guest') {
        $this->user_role = $user_role;
        $this->initializeRestrictedKeywords();
        $this->initializeSingleWordResponses();
        $this->initializeDatabase();
    }
    
    /**
     * Initialize restricted keywords for privacy protection
     */
    private function initializeRestrictedKeywords() {
        $this->restricted_keywords = [
            'personal_info' => [
                'full name', 'real name', 'address', 'phone number', 'email address',
                'social security', 'id number', 'student id', 'employee id',
                'date of birth', 'birthday', 'age', 'gender', 'contact'
            ],
            'credentials' => [
                'password', 'login credentials', 'username', 'pin code',
                'security question', 'recovery code', 'access code'
            ],
            'sensitive_data' => [
                'grades', 'transcript', 'medical record', 'diagnosis',
                'financial', 'bank account', 'payment', 'salary', 'income'
            ],
            'unrelated_topics' => [
                'dating advice', 'relationship', 'personal problems',
                'family issues', 'money problems', 'health advice', 'legal advice'
            ]
        ];
    }
    
    /**
     * Initialize single-word response mapping for accuracy
     */
    private function initializeSingleWordResponses() {
        $this->single_word_responses = [
            // Navigation words
            'dashboard' => 'The Dashboard is your main page with an overview of all activities. Click "Dashboard" in the sidebar to access it.',
            'menu' => 'The main menu is located in the sidebar on the left. It contains all system features like Dashboard, Consultations, Messages, etc.',
            'sidebar' => 'The sidebar is the navigation menu on the left side of your screen. It contains links to all main features.',
            'navigation' => 'Use the sidebar menu on the left to navigate between different sections of EGABAY like Dashboard, Messages, Consultations, etc.',
            
            // Consultation words
            'consultation' => 'To request a consultation: Click "Request Consultation" in sidebar → Fill out the form → Choose your preferred method → Submit. You\'ll get assigned to a counselor within 24 hours.',
            'counseling' => 'EGABAY provides online counseling services. You can request sessions through "Request Consultation" and chat with professional counselors.',
            'appointment' => 'Appointments are called "Consultations" in EGABAY. Use "Request Consultation" to book a session with a counselor.',
            'session' => 'Consultation sessions can be done via Live Chat, Video Call, Phone Call, or In-Person. Choose your preference when requesting.',
            
            // Communication words
            'chat' => 'The chat system lets you communicate with counselors in real-time. Access it through "Messages" in the sidebar.',
            'message' => 'Messages are found in the sidebar. Click "Messages" to view conversations with counselors and send new messages.',
            'messages' => 'Access your messages by clicking "Messages" in the sidebar. Here you can chat with counselors and view conversation history.',
            'notification' => 'Notifications appear in the header (bell icon) and alert you about new messages, consultation updates, etc.',
            'notifications' => 'Check the bell icon in the header for notifications about messages, consultation status changes, and system updates.',
            
            // Account words  
            'login' => 'To login: Go to login page → Enter email → Enter password → Click "Sign In". Use "Forgot Password" if needed.',
            'password' => 'To reset password: Click "Forgot Password" on login page → Enter email → Check email for reset link → Follow instructions.',
            'profile' => 'Access your profile settings by clicking your name/profile icon in the header or "Profile" in sidebar.',
            'account' => 'Your account settings are in "Profile". You can update basic information but cannot change sensitive details.',
            
            // Technical words
            'error' => 'For errors: Try refreshing the page (F5) → Clear browser cache → Try different browser → Check internet connection. Contact support if persists.',
            'problem' => 'Common solutions: Refresh page → Clear cache (Ctrl+Shift+Delete) → Try incognito mode → Restart browser → Check internet.',
            'help' => 'I can help with system navigation, consultations, messages, login issues, and technical problems. What specific area do you need help with?',
            'support' => 'For system questions, I\'m here to help! For technical issues, try basic troubleshooting first. Contact admin for account problems.',
            
            // Feature words
            'features' => 'EGABAY features: Consultation Booking, Live Chat, Anonymous Options, File Sharing, Notifications, Session History, and Feedback System.',
            'anonymous' => 'Anonymous consultations hide your identity from counselors. Choose "Yes" for anonymous when requesting a consultation.',
            'feedback' => 'After completed sessions, you can rate and review in "My Consultations" or "Feedback" section.',
            
            // Status words
            'pending' => 'Pending consultations are waiting for counselor assignment. Check "My Consultations" for status updates.',
            'live' => 'Live consultations are active sessions. Access them through "Messages" or "My Consultations".',
            'completed' => 'Completed consultations can be found in "My Consultations" where you can also leave feedback.',
            
            // General system words
            'egabay' => 'EGABAY is an Academic Support & Counseling System that connects students with professional counselors online.',
            'system' => 'The EGABAY system provides online counseling services with features like consultation booking, live chat, and session management.'
        ];
    }
    
    /**
     * Check if message contains restricted content
     */
    private function checkPrivacyViolation($message) {
        $message_lower = strtolower($message);
        $violations = [];
        
        foreach ($this->restricted_keywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, strtolower($keyword)) !== false) {
                    $violations[] = [
                        'category' => $category,
                        'keyword' => $keyword,
                        'severity' => $this->getViolationSeverity($category)
                    ];
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Get violation severity level
     */
    private function getViolationSeverity($category) {
        $severity_levels = [
            'personal_info' => 'high',
            'credentials' => 'critical', 
            'sensitive_data' => 'high',
            'unrelated_topics' => 'low'
        ];
        
        return $severity_levels[$category] ?? 'medium';
    }
    
    /**
     * Initialize database connection
     */
    private function initializeDatabase() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("System Helper DB Error: " . $e->getMessage());
        }
    }
    
    /**
     * Handle privacy violations with warnings
     */
    private function handlePrivacyViolation($violations) {
        $high_severity = array_filter($violations, function($v) {
            return in_array($v['severity'], ['critical', 'high']);
        });
        
        if (!empty($high_severity)) {
            return [
                'response' => "⚠️ Please don't share personal information like names, passwords, IDs, or private details. I'm a system helper and can only assist with EGABAY features and navigation.\n\n💡 **I can help you with:**\n• How to use EGABAY features\n• System navigation\n• Technical problems\n• Consultation process\n\n📞 **Need personal assistance?**\nContact the developer:\n🔗 **Facebook:** [Keith Torda](https://www.facebook.com/Keithtordaofficial1/)\n📧 **Email:** keithorario@gmail.com\n\nWhat system-related question do you have?",
                'type' => 'privacy_violation',
                'severity' => 'high',
                'suggestions' => ['How to login?', 'Where is my dashboard?', 'How to request consultation?', 'Technical problems?'],
                'privacy_protected' => true
            ];
        } else {
            return [
                'response' => "🎯 I can only help with EGABAY system questions. Could you ask about system features, navigation, or technical issues instead?\n\n📞 **Need personal assistance beyond system help?**\nContact the developer:\n🔗 **Facebook:** [Keith Torda](https://www.facebook.com/Keithtordaofficial1/)\n📧 **Email:** keithorario@gmail.com",
                'type' => 'scope_violation',
                'severity' => 'medium',
                'suggestions' => ['System features?', 'Navigation help?', 'Technical issues?', 'How to use consultations?'],
                'privacy_protected' => true
            ];
        }
    }
    
    /**
     * Get system suggestions
     */
    private function getSystemSuggestions() {
        $suggestions = [
            'How to request consultation?',
            'Where can I find my messages?', 
            'Login problems?',
            'What features are available?',
            'Technical issues?',
            'How to use the chat system?'
        ];
        
        shuffle($suggestions);
        return array_slice($suggestions, 0, 4);
    }
    
    /**
     * Check for single word queries and provide accurate responses
     */
    private function handleSingleWord($word) {
        $word_lower = strtolower(trim($word));
        
        if (isset($this->single_word_responses[$word_lower])) {
            return [
                'response' => $this->single_word_responses[$word_lower],
                'type' => 'single_word_response',
                'word_detected' => $word_lower,
                'suggestions' => $this->getRelatedSuggestions($word_lower)
            ];
        }
        
        return null;
    }
    
    /**
     * Get related suggestions for single word queries
     */
    private function getRelatedSuggestions($word) {
        $related_suggestions = [
            'dashboard' => ['How to navigate dashboard?', 'What\'s on the dashboard?', 'Dashboard features?'],
            'consultation' => ['How to request consultation?', 'Consultation types?', 'Anonymous consultation?'],
            'chat' => ['How to use chat?', 'Where are my messages?', 'Chat with counselor?'],
            'login' => ['Login problems?', 'Forgot password?', 'Account verification?'],
            'error' => ['Common errors?', 'Technical problems?', 'Browser issues?'],
            'help' => ['System features?', 'Navigation help?', 'Technical support?']
        ];
        
        return $related_suggestions[$word] ?? $this->getSystemSuggestions();
    }
    
    /**
     * Identify response type from message content
     */
    private function identifyResponseType($message_lower) {
        $patterns = [
            'consultation' => ['consultation', 'counseling', 'session', 'appointment', 'book', 'request'],
            'navigation' => ['dashboard', 'find', 'where', 'locate', 'menu', 'sidebar', 'navigate'],
            'login' => ['login', 'sign in', 'log in', 'password', 'forgot', 'reset'],
            'technical' => ['error', 'not working', 'problem', 'issue', 'broken', 'fix'],
            'messaging' => ['message', 'chat', 'communication', 'talk', 'speak'],
            'features' => ['features', 'what can', 'what does', 'how does', 'explain'],
            'account' => ['profile', 'account', 'settings', 'update', 'change']
        ];
        
        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return 'general_help';
    }
    
    /**
     * Get role-specific features
     */
    private function getRoleFeatures() {
        $features = [
            'student' => "• **Dashboard** - View consultation status and overview\n• **Request Consultation** - Book counseling sessions\n• **My Consultations** - Track your requests and history\n• **Messages** - Chat with counselors\n• **Notifications** - Stay updated on activities\n• **Feedback** - Rate completed sessions\n• **Profile** - Update basic account information",
            'counselor' => "• **Dashboard** - Overview of assigned consultations\n• **Manage Consultations** - Handle student requests\n• **Schedule** - Set availability and preferences\n• **Messages** - Communicate with students\n• **Reports** - View performance analytics\n• **Notifications** - Stay updated on assignments",
            'admin' => "• **Dashboard** - System-wide monitoring and analytics\n• **User Management** - Manage all user accounts\n• **System Settings** - Configure system parameters\n• **Reports** - Generate comprehensive reports\n• **Backup & Maintenance** - Database management\n• **Notifications** - Send system-wide messages"
        ];
        
        return $features[$this->user_role] ?? $features['student'];
    }
    
    /**
     * Generate system-appropriate response
     */
    private function generateSystemResponse($message) {
        $message_lower = strtolower($message);
        $response_type = $this->identifyResponseType($message_lower);
        
        // Get appropriate system guide
        $response_content = $this->getSystemGuide($response_type);
        
        return [
            'response' => $response_content,
            'type' => 'system_guide',
            'category' => $response_type,
            'suggestions' => $this->getSystemSuggestions(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get system guide based on response type
     */
    private function getSystemGuide($response_type) {
        switch ($response_type) {
            case 'consultation':
                return "📅 **How to Request a Consultation:**\n\n1. Click **'Request Consultation'** in your sidebar\n2. Choose consultation type (Academic, Personal, Career, etc.)\n3. Select communication method:\n   • **Live Chat** - Real-time messaging\n   • **Video Call** - Face-to-face online\n   • **Phone Call** - Voice only\n   • **In-Person** - Physical meeting\n4. Pick your preferred date and time\n5. Describe your concern briefly\n6. Choose **Anonymous** option if you want privacy\n7. Click **Submit Request**\n\n⏰ **Response Time:** Usually within 24 hours\n🔔 **Updates:** Check notifications for counselor assignment";
            
            case 'navigation':
                return "🧭 **System Navigation Guide:**\n\n**Main Sidebar Menu:**\n• **Dashboard** - Main overview page\n• **Request Consultation** - Book new sessions\n• **My Consultations** - Track your requests\n• **Messages** - Chat with counselors\n• **Profile** - Account settings\n• **Notifications** - System updates\n\n**Navigation Tips:**\n• Sidebar is always on the left side\n• Current page is highlighted\n• Use breadcrumbs at top to go back\n• Mobile: Click ☰ (hamburger) for menu\n• Red badges show new notifications/messages";
            
            case 'login':
                return "🔑 **Login & Account Help:**\n\n**Login Steps:**\n1. Go to EGABAY login page\n2. Enter your registered email address\n3. Enter your password\n4. Click **'Sign In'**\n\n**Having Login Problems?**\n• **Forgot Password?** Click 'Forgot Password' → Enter email → Check inbox for reset link\n• **Account not verified?** Check email for verification link\n• **Still can't login?** Clear browser cache (Ctrl+Shift+Delete)\n• **First time user?** Click 'Register' to create account\n\n**Password Requirements:**\n• At least 8 characters\n• Mix of letters and numbers\n• Don't use personal information";
            
            case 'technical':
                return "🔧 **Technical Support & Troubleshooting:**\n\n**Quick Fixes (Try these first):**\n1. **Refresh the page** - Press F5 or Ctrl+R\n2. **Clear browser cache** - Ctrl+Shift+Delete\n3. **Try different browser** - Chrome, Firefox, Edge\n4. **Check internet connection** - Test other websites\n5. **Disable extensions** - Try incognito/private mode\n6. **Restart browser** - Close completely and reopen\n\n**Still Having Issues?**\n• Note the exact error message\n• Remember what you were doing when error occurred\n• Try on different device if possible\n• Contact support with specific details\n\n**Common Issues:**\n• Page not loading → Clear cache, check internet\n• Login errors → Reset password, verify account\n• Chat not working → Refresh page, check connection";
            
            case 'messaging':
                return "💬 **Messages & Chat System:**\n\n**How to Access Messages:**\n• Click **'Messages'** in sidebar\n• Or click notification bell for new messages\n\n**Chat Features:**\n• **Real-time messaging** - Instant delivery\n• **File sharing** - Click 📎 (paperclip) icon\n• **Message history** - All conversations saved\n• **Read receipts** - See when messages are read\n• **Typing indicators** - Know when counselor is typing\n\n**File Sharing:**\n• Supported: PDF, DOC, DOCX, images (JPG, PNG)\n• Max file size: 10MB per file\n• Multiple files can be shared\n\n**Best Practices:**\n• Be clear and specific in messages\n• Stay professional and respectful\n• Respond promptly for effective counseling\n• Don't share personal information like passwords";
            
            case 'features':
                return "✨ **EGABAY System Features:**\n\n" . $this->getRoleFeatures() . "\n\n**Key Capabilities:**\n• **Anonymous Consultations** - Your choice of privacy\n• **Multiple Communication Methods** - Chat, Video, Phone, In-Person\n• **File Sharing** - Share documents securely\n• **Session History** - Track all your consultations\n• **Real-time Notifications** - Stay updated\n• **Feedback System** - Rate your experience\n• **Mobile Friendly** - Works on all devices\n\n**Getting Started:**\n1. Complete your profile setup\n2. Explore the dashboard\n3. Request your first consultation\n4. Familiarize yourself with messaging\n\nNeed help with any specific feature?";
            
            case 'account':
                return "👤 **Profile & Account Management:**\n\n**Access Your Profile:**\n• Click your name/avatar in header\n• Or find 'Profile' in sidebar\n\n**What You Can Update:**\n• Basic personal information\n• Contact preferences\n• Password (use 'Change Password')\n• Profile picture\n• Communication preferences\n\n**What You Cannot Change:**\n• Email address (contact admin)\n• User role (student/counselor/admin)\n• Account verification status\n\n**Account Security:**\n• Use strong passwords\n• Log out from shared computers\n• Don't share your login credentials\n• Report suspicious activity\n\n**Need Help?**\n• Forgot password? Use reset link\n• Account issues? Contact administrator\n• Profile problems? Try refreshing page";
            
            default:
                return "👋 **Hello! I'm the EGABAY System Helper.**\n\nI can assist you with:\n\n" . $this->getRoleFeatures() . "\n\n**Common Questions I Can Answer:**\n• How to request consultations\n• System navigation and features\n• Login and account issues\n• Technical troubleshooting\n• Using the chat/messaging system\n• Understanding notifications\n\n**Important:** I'm a system helper only. Please don't share personal information like passwords, IDs, or private details.\n\n📞 **Need personal assistance or have questions beyond system help?**\nContact the developer:\n🔗 **Facebook:** [Keith Torda](https://www.facebook.com/Keithtordaofficial1/)\n📧 **Email:** keithorario@gmail.com\n\nWhat would you like help with?";
        }
    }
    
    /**
     * Main response generation
     */
    public function generateResponse($message) {
        // Step 1: Check for privacy violations
        $violations = $this->checkPrivacyViolation($message);
        if (!empty($violations)) {
            return $this->handlePrivacyViolation($violations);
        }
        
        // Step 2: Handle single word queries first (for accuracy)
        $words = explode(' ', trim($message));
        if (count($words) == 1) {
            $single_word_response = $this->handleSingleWord($words[0]);
            if ($single_word_response) {
                return $single_word_response;
            }
        }
        
        // Step 3: Generate comprehensive system response
        return $this->generateSystemResponse($message);
    }
}

// Process the request
try {
    $helper = new EgabaySystemHelper($user_role);
    $result = $helper->generateResponse($message);
    
    // Add metadata
    $result['timestamp'] = date('Y-m-d H:i:s');
    $result['user_role'] = $user_role;
    $result['privacy_compliant'] = true;
    $result['language'] = 'en';
    $result['version'] = '3.0-english-only';
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("System Helper Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'System helper temporarily unavailable',
        'response' => 'Sorry, there\'s a technical issue right now. Please try refreshing the page or contact support. 🔧',
        'type' => 'error',
        'suggestions' => ['Refresh page', 'Clear browser cache', 'Contact support'],
        'privacy_compliant' => true
    ]);
}
?> 