<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Utility.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get POST data
$recipient = isset($_POST['recipient']) ? sanitizeInput($_POST['recipient']) : '';
$subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : 'E-GABAY ASC Test Email';
$message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : 'This is a test email from the E-GABAY ASC system.';

// Validate input
if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid recipient email address']);
    exit;
}

// Get email settings from database
$smtp_host = Utility::getSetting('smtp_host', '');
$smtp_port = (int)Utility::getSetting('smtp_port', 587);
$smtp_username = Utility::getSetting('smtp_username', '');
$smtp_password = Utility::getSetting('smtp_password', '');
$smtp_encryption = Utility::getSetting('smtp_encryption', 'tls');
$email_from_name = Utility::getSetting('email_from_name', SITE_NAME);
$email_from_address = Utility::getSetting('email_from_address', '');

// Check if email settings are configured
if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password) || empty($email_from_address)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Email settings are not fully configured']);
    exit;
}

// Try to send email using PHPMailer
try {
    // Include PHPMailer if available
    if (file_exists($base_path . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        require_once $base_path . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once $base_path . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once $base_path . '/vendor/phpmailer/phpmailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = 0; // 0 = no output, 2 = verbose output
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        
        // Set encryption type
        if ($smtp_encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp_encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Port = $smtp_port;
        
        // Recipients
        $mail->setFrom($email_from_address, $email_from_name);
        $mail->addAddress($recipient);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($message);
        $mail->AltBody = strip_tags($message);
        
        // Send the email
        $mail->send();
        
        // Log the action
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $description = "Sent test email to {$recipient}";
        
        $database = new Database();
        $db = $database->getConnection();
        
        $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->execute([$user_id, 'system', $ip_address, $description]);
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        // Fallback to PHP mail() function if PHPMailer is not available
        $headers = "From: {$email_from_name} <{$email_from_address}>\r\n";
        $headers .= "Reply-To: {$email_from_address}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $result = mail($recipient, $subject, nl2br($message), $headers);
        
        if ($result) {
            // Log the action
            $user_id = $_SESSION['user_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $description = "Sent test email to {$recipient} using PHP mail()";
            
            $database = new Database();
            $db = $database->getConnection();
            
            $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$user_id, 'system', $ip_address, $description]);
            
            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to send email using PHP mail() function']);
        }
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 