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
requireRole('admin');

// Set page title
$page_title = 'System Settings';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Ensure email logos directory exists
$email_logos_dir = $base_path . '/uploads/email_logos';
if (!file_exists($email_logos_dir)) {
    mkdir($email_logos_dir, 0755, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        // Update site settings
        $site_name = sanitizeInput($_POST['site_name']);
        $site_description = sanitizeInput($_POST['site_description']);
        $admin_email = sanitizeInput($_POST['admin_email']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $maintenance_message = sanitizeInput($_POST['maintenance_message'] ?? '');
        $maintenance_end_time = sanitizeInput($_POST['maintenance_end_time'] ?? '');
        $allow_registrations = isset($_POST['allow_registrations']) ? 1 : 0;
        $default_role = (int)$_POST['default_role'];
        $session_timeout = (int)$_POST['session_timeout'];
        $max_login_attempts = (int)$_POST['max_login_attempts'];
        
        // Update settings in database
        $settings = [
            'site_name' => $site_name,
            'site_description' => $site_description,
            'admin_email' => $admin_email,
            'maintenance_mode' => $maintenance_mode,
            'maintenance_message' => $maintenance_message,
            'maintenance_end_time' => $maintenance_end_time,
            'allow_registrations' => $allow_registrations,
            'default_role' => $default_role,
            'session_timeout' => $session_timeout,
            'max_login_attempts' => $max_login_attempts
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            if (!Utility::updateSetting($key, $value)) {
                $success = false;
            }
        }
        
        if ($success) {
            setMessage('Settings updated successfully.', 'success');
        } else {
            setMessage('Failed to update some settings.', 'danger');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_appearance') {
        // Update appearance settings
        $primary_color = sanitizeInput($_POST['primary_color']);
        $secondary_color = sanitizeInput($_POST['secondary_color']);
        $logo_url = sanitizeInput($_POST['logo_url']);
        $favicon_url = sanitizeInput($_POST['favicon_url']);
        $footer_text = sanitizeInput($_POST['footer_text']);
        
        // Update settings in database
        $settings = [
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'logo_url' => $logo_url,
            'favicon_url' => $favicon_url,
            'footer_text' => $footer_text
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            if (!Utility::updateSetting($key, $value)) {
                $success = false;
            }
        }
        
        if ($success) {
            setMessage('Appearance settings updated successfully.', 'success');
        } else {
            setMessage('Failed to update some appearance settings.', 'danger');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_email') {
        // Update email settings
        $smtp_host = sanitizeInput($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = sanitizeInput($_POST['smtp_username']);
        $smtp_password = $_POST['smtp_password']; // Don't sanitize password
        $smtp_encryption = sanitizeInput($_POST['smtp_encryption']);
        $email_from_name = sanitizeInput($_POST['email_from_name']);
        $email_from_address = sanitizeInput($_POST['email_from_address']);
        
        // Update settings in database
        $settings = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_encryption' => $smtp_encryption,
            'email_from_name' => $email_from_name,
            'email_from_address' => $email_from_address
        ];
        
        // Only update password if provided
        if (!empty($smtp_password)) {
            $settings['smtp_password'] = $smtp_password;
        }
        
        $success = true;
        foreach ($settings as $key => $value) {
            if (!Utility::updateSetting($key, $value)) {
                $success = false;
            }
        }
        
        if ($success) {
            setMessage('Email settings updated successfully.', 'success');
        } else {
            setMessage('Failed to update some email settings.', 'danger');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_structured_template') {
        // Update advanced email template
        $template_id = (int)$_POST['template_id'];
        $template_subject = sanitizeInput($_POST['template_subject']);
        $header_title = sanitizeInput($_POST['header_title']);
        $greeting_text = sanitizeInput($_POST['greeting_text']);
        $main_message = $_POST['main_message']; // Don't sanitize - may contain HTML
        $button_text = sanitizeInput($_POST['button_text']);
        $fallback_message = $_POST['fallback_message']; // Don't sanitize - may contain HTML
        $footer_note = $_POST['footer_note']; // Don't sanitize - may contain HTML
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $use_structured_editor = 1;
        
        // Handle logo upload
        $custom_logo = null;
        if (isset($_FILES['custom_logo']) && $_FILES['custom_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['custom_logo']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $file_extension = pathinfo($_FILES['custom_logo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'email_logo_' . $template_id . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $email_logos_dir . '/' . $new_filename;
                
                if (move_uploaded_file($_FILES['custom_logo']['tmp_name'], $upload_path)) {
                    // Delete old logo if exists
                    $old_logo_query = "SELECT custom_logo FROM email_templates WHERE template_id = ?";
                    $old_logo_stmt = $db->prepare($old_logo_query);
                    $old_logo_stmt->execute([$template_id]);
                    $old_logo = $old_logo_stmt->fetchColumn();
                    
                    if ($old_logo && file_exists($email_logos_dir . '/' . $old_logo)) {
                        unlink($email_logos_dir . '/' . $old_logo);
                    }
                    
                    $custom_logo = $new_filename;
                } else {
                    setMessage('❌ Failed to upload logo image.', 'danger');
                }
            } else {
                setMessage('❌ Please upload a valid image file (JPG, PNG, or GIF).', 'danger');
            }
        } else {
            // Keep existing logo
            try {
                // First check if the columns exist
                $check_columns = "SHOW COLUMNS FROM email_templates LIKE 'custom_logo'";
                $check_stmt = $db->prepare($check_columns);
                $check_stmt->execute();
                $column_exists = $check_stmt->fetch();
                
                if ($column_exists) {
                    $existing_logo_query = "SELECT custom_logo FROM email_templates WHERE template_id = ?";
                    $existing_logo_stmt = $db->prepare($existing_logo_query);
                    $existing_logo_stmt->execute([$template_id]);
                    $custom_logo = $existing_logo_stmt->fetchColumn();
                } else {
                    // Column doesn't exist, set to null
                    $custom_logo = null;
                    error_log("custom_logo column does not exist in email_templates table");
                }
            } catch (Exception $e) {
                // Handle any database errors gracefully
                $custom_logo = null;
                error_log("Error accessing custom_logo column: " . $e->getMessage());
            }
        }
        
        // Build template_body from structured fields for backward compatibility
        $template_body = buildEmailFromStructuredFieldsSettings([
            'header_title' => $header_title,
            'greeting_text' => $greeting_text,
            'main_message' => $main_message,
            'button_text' => $button_text,
            'button_link' => '{{verification_link}}{{reset_link}}{{dashboard_link}}{{action_link}}',
            'fallback_message' => $fallback_message,
            'footer_note' => $footer_note
        ]);
        
        // Try to build the query based on available columns
        try {
            // Check which columns are available
            $available_columns = [];
            $check_query = "SHOW COLUMNS FROM email_templates";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute();
            while ($row = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
                $available_columns[] = $row['Field'];
            }
            
            // Build query dynamically based on available columns
            $update_fields = ['template_subject = ?', 'template_body = ?', 'is_active = ?'];
            $params = [$template_subject, $template_body, $is_active];
            
            if (in_array('header_title', $available_columns)) {
                $update_fields[] = 'header_title = ?';
                $params[] = $header_title;
            }
            if (in_array('greeting_text', $available_columns)) {
                $update_fields[] = 'greeting_text = ?';
                $params[] = $greeting_text;
            }
            if (in_array('main_message', $available_columns)) {
                $update_fields[] = 'main_message = ?';
                $params[] = $main_message;
            }
            if (in_array('button_text', $available_columns)) {
                $update_fields[] = 'button_text = ?';
                $params[] = $button_text;
            }
            if (in_array('fallback_message', $available_columns)) {
                $update_fields[] = 'fallback_message = ?';
                $params[] = $fallback_message;
            }
            if (in_array('footer_note', $available_columns)) {
                $update_fields[] = 'footer_note = ?';
                $params[] = $footer_note;
            }
            if (in_array('custom_logo', $available_columns)) {
                $update_fields[] = 'custom_logo = ?';
                $params[] = $custom_logo;
            }
            if (in_array('use_structured_editor', $available_columns)) {
                $update_fields[] = 'use_structured_editor = ?';
                $params[] = $use_structured_editor;
            }
            
            // Add timestamp and template_id
            $update_fields[] = 'updated_at = NOW()';
            $params[] = $template_id;
            
            $query = "UPDATE email_templates SET " . implode(', ', $update_fields) . " WHERE template_id = ?";
            
        } catch (Exception $e) {
            // Fallback to basic query if column checking fails
            $query = "UPDATE email_templates SET 
                     template_subject = ?, 
                     template_body = ?, 
                     is_active = ?,
                     updated_at = NOW()
                     WHERE template_id = ?";
            $params = [$template_subject, $template_body, $is_active, $template_id];
        }
        
        try {
            $stmt = $db->prepare($query);
            $success = $stmt->execute($params);
            
            if ($success) {
                setMessage('✅ Email template updated successfully!', 'success');
                // Log successful update for debugging
                error_log("Email template {$template_id} updated successfully with " . count($params) . " parameters");
            } else {
                setMessage('❌ Failed to update email template.', 'danger');
                error_log("Email template update failed for template_id: {$template_id}");
            }
        } catch (Exception $e) {
            // Enhanced error handling with detailed logging
            error_log("Email template update error: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Parameters: " . print_r($params, true));
            
            setMessage('❌ Error updating template: ' . $e->getMessage(), 'danger');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'test_email_template') {
        // Test email template
        $template_id = (int)$_POST['test_template_id'];
        $test_email = sanitizeInput($_POST['test_email']);
        
        if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            setMessage('Please enter a valid email address for testing.', 'danger');
        } else {
            // Get template
            $query = "SELECT * FROM email_templates WHERE template_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$template_id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                // Use structured fields if available, otherwise fall back to template_body
                if (isset($template['use_structured_editor']) && $template['use_structured_editor'] && !empty($template['header_title'])) {
                    $body = buildEmailFromStructuredFieldsSettings([
                        'header_title' => $template['header_title'],
                        'greeting_text' => $template['greeting_text'],
                        'main_message' => $template['main_message'],
                        'button_text' => $template['button_text'],
                        'button_link' => SITE_URL . '/test-action',
                        'fallback_message' => $template['fallback_message'],
                        'footer_note' => $template['footer_note']
                    ], $template['custom_logo'] ?? null);
                } else {
                    $body = $template['template_body'];
                }
                
                // Replace template variables with test data
                $subject = str_replace([
                    '{{site_name}}',
                    '{{first_name}}',
                    '{{admin_email}}'
                ], [
                    $settings['site_name'] ?? SITE_NAME,
                    'Test User',
                    $settings['admin_email']
                ], $template['template_subject']);
                
                $body = str_replace([
                    '{{site_name}}',
                    '{{logo}}',
                    '{{first_name}}',
                    '{{last_name}}',
                    '{{username}}',
                    '{{email}}',
                    '{{admin_email}}',
                    '{{verification_link}}',
                    '{{reset_link}}',
                    '{{dashboard_link}}',
                    '{{consultation_date}}',
                    '{{consultation_time}}',
                    '{{counselor_name}}',
                    '{{consultation_status}}',
                    '{{consultation_link}}',
                    '{{subject}}',
                    '{{message_content}}',
                    '{{action_text}}',
                    '{{action_link}}',
                    '{{notification_message}}'
                ], [
                    $settings['site_name'] ?? SITE_NAME,
                    '<img src="' . SITE_URL . '/assets/images/egabay-logo.png" alt="Logo" style="height:60px;">',
                    'Test User',
                    'Sample',
                    'testuser',
                    $test_email,
                    $settings['admin_email'],
                    SITE_URL . '/verify.php?token=sample123',
                    SITE_URL . '/reset_password.php?token=sample123',
                    SITE_URL . '/dashboard/',
                    'January 15, 2025',
                    '2:00 PM - 3:00 PM',
                    'Dr. Sample Counselor',
                    'Approved',
                    SITE_URL . '/consultation/123',
                    'Test Email Subject',
                    'This is a test message content to preview how emails will look.',
                    'View Details',
                    SITE_URL . '/action',
                    'This is a test notification message.'
                ], $body);
                
                // Send test email
                if (sendEmail($test_email, '[TEST] ' . $subject, $body)) {
                    setMessage('Test email sent successfully to ' . $test_email, 'success');
                } else {
                    setMessage('Failed to send test email. Please check your SMTP settings.', 'danger');
                }
            } else {
                setMessage('Email template not found.', 'danger');
            }
        }
    }
}

// Function to build email HTML from structured fields for settings page
function buildEmailFromStructuredFieldsSettings($fields, $custom_logo = null) {
    // Handle logo
    if ($custom_logo && file_exists($GLOBALS['base_path'] . '/uploads/email_logos/' . $custom_logo)) {
        $logo_html = '<img src="' . SITE_URL . '/uploads/email_logos/' . $custom_logo . '" alt="{{site_name}} Logo" style="height:60px; max-width:200px;">';
    } else {
        $logo_html = '<img src="' . SITE_URL . '/assets/images/egabay-logo.png" alt="{{site_name}} Logo" style="height:60px;">';
    }
    
    // Build button HTML if button text is provided
    $button_html = '';
    if (!empty($fields['button_text']) && !empty($fields['button_link'])) {
        $button_html = '
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $fields['button_link'] . '" 
               style="background-color: #007bff; color: white; padding: 12px 30px; 
                      text-decoration: none; border-radius: 5px; font-weight: bold; 
                      display: inline-block; text-decoration: none;">' . $fields['button_text'] . '</a>
        </div>';
    }
    
    // Build complete email HTML
    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{site_name}}</title>
    </head>
    <body style="font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 0; margin: 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="text-align: center; padding: 20px 0; background: #0d6efd;">
                    ' . $logo_html . '
                </td>
            </tr>
            <tr>
                <td style="padding: 30px 25px; color: #212529;">
                    <h2 style="color: #0d6efd; margin-top: 0; margin-bottom: 20px; font-size: 24px;">' . $fields['header_title'] . '</h2>
                    <p style="font-size: 16px; margin-bottom: 20px; color: #495057;">' . $fields['greeting_text'] . '</p>
                    <div style="margin: 25px 0; font-size: 15px; line-height: 1.6; color: #495057;">' . $fields['main_message'] . '</div>
                    ' . $button_html . '
                    ' . (!empty($fields['fallback_message']) ? '<div style="margin: 25px 0; font-size: 14px; color: #6c757d; line-height: 1.5;">' . $fields['fallback_message'] . '</div>' : '') . '
                </td>
            </tr>
            <tr>
                <td style="background: #f1f3f5; color: #6c757d; font-size: 12px; text-align: center; padding: 20px 15px;">
                    ' . (!empty($fields['footer_note']) ? $fields['footer_note'] . '<br><br>' : '') . '
                    {{site_name}} • This is an automated message, please do not reply.
                </td>
            </tr>
        </table>
    </body>
    </html>';
    
    return $html;
}

// Get current settings
$settings = [
    'site_name' => Utility::getSetting('site_name', SITE_NAME),
    'site_description' => Utility::getSetting('site_description', SITE_DESC),
    'admin_email' => Utility::getSetting('admin_email', ''),
    'maintenance_mode' => (int)Utility::getSetting('maintenance_mode', 0),
    'maintenance_message' => Utility::getSetting('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.'),
    'maintenance_end_time' => Utility::getSetting('maintenance_end_time', 'soon'),
    'allow_registrations' => (int)Utility::getSetting('allow_registrations', 1),
    'default_role' => (int)Utility::getSetting('default_role', ROLE_STUDENT),
    'session_timeout' => (int)Utility::getSetting('session_timeout', 30),
    'max_login_attempts' => (int)Utility::getSetting('max_login_attempts', 5),
    'primary_color' => Utility::getSetting('primary_color', '#0d6efd'),
    'secondary_color' => Utility::getSetting('secondary_color', '#6c757d'),
    'logo_url' => Utility::getSetting('logo_url', ''),
    'favicon_url' => Utility::getSetting('favicon_url', ''),
    'footer_text' => Utility::getSetting('footer_text', '© ' . date('Y') . ' ' . SITE_NAME . '. All Rights Reserved.'),
    'smtp_host' => Utility::getSetting('smtp_host', ''),
    'smtp_port' => (int)Utility::getSetting('smtp_port', 587),
    'smtp_username' => Utility::getSetting('smtp_username', ''),
    'smtp_password' => Utility::getSetting('smtp_password', ''),
    'smtp_encryption' => Utility::getSetting('smtp_encryption', 'tls'),
    'email_from_name' => Utility::getSetting('email_from_name', SITE_NAME),
    'email_from_address' => Utility::getSetting('email_from_address', ''),
    'maintenance_message' => Utility::getSetting('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.'),
    'maintenance_end_time' => Utility::getSetting('maintenance_end_time', 'soon')
];

// Get roles for dropdown
$query = "SELECT * FROM roles ORDER BY role_name";
$stmt = $db->prepare($query);
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get email templates
$query = "SELECT * FROM email_templates ORDER BY template_name";
$stmt = $db->prepare($query);
$stmt->execute();
$email_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get template variables for reference
$query = "SELECT * FROM email_template_variables ORDER BY is_global DESC, variable_name";
$stmt = $db->prepare($query);
$stmt->execute();
$template_variables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<style>
/* Advanced Email Template Editor Styles */
.email-template-card {
    border: 1px solid #e3e6f0;
    border-radius: 12px;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.email-template-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.template-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
}

.template-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 15px;
}

.btn-modern {
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.current-logo {
    max-width: 200px;
    max-height: 80px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 5px;
}

.form-section {
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-section h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.upload-area {
    border: 2px dashed #007bff;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    background: #f8f9ff;
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-area:hover {
    border-color: #0056b3;
    background: #e6f2ff;
}

.upload-area.dragover {
    border-color: #28a745;
    background: #e8f5e8;
}

.variable-chip {
    background: #e3f2fd;
    color: #1565c0;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 11px;
    margin: 2px;
    display: inline-block;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.variable-chip:hover {
    background: #1565c0;
    color: white;
    transform: scale(1.05);
}

.preview-panel {
    border: 2px solid #28a745;
    border-radius: 12px;
    background: white;
    max-height: 600px;
    overflow-y: auto;
    position: sticky;
    top: 20px;
}

.preview-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
    margin: 0;
}

#emailPreview iframe {
    width: 100%;
    height: 400px;
    border: none;
    border-radius: 0 0 8px 8px;
}
</style>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">System Settings</h1>
        <p class="lead">Configure and customize the E-GABAY ASC system.</p>
    </div>
</div>

<!-- Settings Tabs -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                            <i class="fas fa-cog me-2"></i> General
                        </button>
                    </li>
                    <!-- Appearance tab temporarily disabled -->
                    <!--
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">
                            <i class="fas fa-palette me-2"></i> Appearance
                        </button>
                    </li>
                    -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">
                            <i class="fas fa-envelope me-2"></i> Email SMTP
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="email-templates-tab" data-bs-toggle="tab" data-bs-target="#email-templates" type="button" role="tab" aria-controls="email-templates" aria-selected="false">
                            <i class="fas fa-magic me-2"></i> Advanced Email Editor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="settings.php#email-templates" class="nav-link text-decoration-none">
                            <i class="fas fa-external-link-alt me-2"></i> <span class="badge bg-success">User-Friendly</span>
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="settingsTabContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="site_name" class="form-label">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo $settings['site_name']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="site_description" class="form-label">Site Description</label>
                                    <input type="text" class="form-control" id="site_description" name="site_description" value="<?php echo $settings['site_description']; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="admin_email" class="form-label">Admin Email</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo $settings['admin_email']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="default_role" class="form-label">Default User Role</label>
                                    <select class="form-select" id="default_role" name="default_role">
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['role_id']; ?>" <?php echo $settings['default_role'] == $role['role_id'] ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($role['role_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="<?php echo $settings['session_timeout']; ?>" min="5" max="180">
                                </div>
                                <div class="col-md-6 d-none">
                                    <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" value="<?php echo $settings['max_login_attempts']; ?>" min="3" max="10">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                    </div>
                                    <small class="text-muted">When enabled, only administrators and staff can access the site.</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="allow_registrations" name="allow_registrations" <?php echo $settings['allow_registrations'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allow_registrations">Allow User Registrations</label>
                                    </div>
                                    <small class="text-muted">When disabled, only administrators can create new user accounts.</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3 maintenance-options" style="<?php echo $settings['maintenance_mode'] ? '' : 'display: none;'; ?>">
                                <div class="col-md-6">
                                    <label for="maintenance_message" class="form-label">Maintenance Message</label>
                                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?php echo $settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance. Please check back soon.'; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="maintenance_end_time" class="form-label">Estimated End Time</label>
                                    <input type="text" class="form-control" id="maintenance_end_time" name="maintenance_end_time" value="<?php echo $settings['maintenance_end_time'] ?? 'soon'; ?>" placeholder="e.g., 'in 2 hours', 'tomorrow at 9 AM'">
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Appearance Settings pane temporarily disabled -->
                    <!--
                    <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="action" value="update_appearance">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="primary_color" class="form-label">Primary Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="primary_color_picker" value="<?php echo $settings['primary_color']; ?>">
                                        <input type="text" class="form-control" id="primary_color" name="primary_color" value="<?php echo $settings['primary_color']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="secondary_color" class="form-label">Secondary Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="secondary_color_picker" value="<?php echo $settings['secondary_color']; ?>">
                                        <input type="text" class="form-control" id="secondary_color" name="secondary_color" value="<?php echo $settings['secondary_color']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="logo_url" class="form-label">Logo URL</label>
                                    <input type="text" class="form-control" id="logo_url" name="logo_url" value="<?php echo $settings['logo_url']; ?>">
                                    <small class="text-muted">Enter the full URL to your logo image.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="favicon_url" class="form-label">Favicon URL</label>
                                    <input type="text" class="form-control" id="favicon_url" name="favicon_url" value="<?php echo $settings['favicon_url']; ?>">
                                    <small class="text-muted">Enter the full URL to your favicon image.</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="footer_text" class="form-label">Footer Text</label>
                                <textarea class="form-control" id="footer_text" name="footer_text" rows="2"><?php echo $settings['footer_text']; ?></textarea>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Appearance Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    -->
                    
                    <!-- Email Settings -->
                    <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="action" value="update_email">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo $settings['smtp_host']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo $settings['smtp_port']; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo $settings['smtp_username']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Enter new password to change">
                                    <small class="text-muted">Leave blank to keep current password.</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="email_from_name" class="form-label">From Name</label>
                                    <input type="text" class="form-control" id="email_from_name" name="email_from_name" value="<?php echo $settings['email_from_name']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email_from_address" class="form-label">From Email Address</label>
                                <input type="email" class="form-control" id="email_from_address" name="email_from_address" value="<?php echo $settings['email_from_address']; ?>">
                            </div>
                            
                            <div class="text-end">
                                <button type="button" class="btn btn-info me-2" id="testEmailBtn">
                                    <i class="fas fa-paper-plane me-2"></i> Test Email
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Email Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Advanced Email Templates -->
                    <div class="tab-pane fade" id="email-templates" role="tabpanel" aria-labelledby="email-templates-tab">
                        <h5 class="mb-3">✨ Advanced Email Template Editor</h5>
                        <p class="text-muted mb-4">Design beautiful emails with an easy-to-use form interface - no coding required!</p>
                        
                        <!-- Email Templates Grid -->
                        <div class="row">
                            <?php foreach ($email_templates as $template): 
                                $icons = [
                                    'user_verification' => 'fas fa-user-check',
                                    'password_reset' => 'fas fa-lock',
                                    'welcome_message' => 'fas fa-heart',
                                    'consultation_notification' => 'fas fa-calendar-check',
                                    'general_notification' => 'fas fa-bell'
                                ];
                                $colors = [
                                    'user_verification' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                    'password_reset' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                                    'welcome_message' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                                    'consultation_notification' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                                    'general_notification' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
                                ];
                                $icon = $icons[$template['template_name']] ?? 'fas fa-envelope';
                                $gradient = $colors[$template['template_name']] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                            ?>
                            <div class="col-lg-6 mb-4">
                                <div class="email-template-card">
                                    <div class="template-header" style="background: <?php echo $gradient; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="template-icon">
                                                <i class="<?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1"><?php echo ucwords(str_replace('_', ' ', $template['template_name'])); ?></h5>
                                                <p class="mb-0 opacity-75"><?php echo $template['template_description']; ?></p>
                                            </div>
                                            <div>
                                                <span class="status-badge <?php echo $template['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $template['is_active'] ? '✅ Active' : '❌ Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3">
                                        <div class="mb-3">
                                            <strong>📧 Email Subject:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($template['template_subject']); ?></span>
                                        </div>
                                        
                                        <?php if (isset($template['custom_logo']) && !empty($template['custom_logo'])): ?>
                                        <div class="mb-3">
                                            <strong>🎨 Custom Logo:</strong><br>
                                            <img src="<?php echo SITE_URL . '/uploads/email_logos/' . $template['custom_logo']; ?>" 
                                                 alt="Custom Logo" class="current-logo">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-primary btn-modern flex-fill" 
                                                    onclick="editAdvancedTemplate(<?php echo $template['template_id']; ?>)">
                                                <i class="fas fa-magic me-2"></i>Advanced Editor
                                            </button>
                                            <button class="btn btn-info btn-modern" 
                                                    onclick="testTemplate(<?php echo $template['template_id']; ?>)">
                                                <i class="fas fa-paper-plane me-2"></i>Test
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testEmailModalLabel">Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="test_email" class="form-label">Recipient Email</label>
                    <input type="email" class="form-control" id="test_email" required>
                </div>
                <div class="mb-3">
                    <label for="test_subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="test_subject" value="E-GABAY ASC Test Email">
                </div>
                <div class="mb-3">
                    <label for="test_message" class="form-label">Message</label>
                    <textarea class="form-control" id="test_message" rows="3">This is a test email from the E-GABAY ASC system.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendTestEmailBtn">Send Test Email</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide maintenance options based on checkbox state
    const maintenanceMode = document.getElementById('maintenance_mode');
    const maintenanceOptions = document.querySelector('.maintenance-options');
    
    if (maintenanceMode) {
        maintenanceMode.addEventListener('change', function() {
            if (this.checked) {
                maintenanceOptions.style.display = 'flex';
            } else {
                maintenanceOptions.style.display = 'none';
            }
        });
    }
    
    // Update color inputs when color picker changes
    const primaryColorPicker = document.getElementById('primary_color_picker');
    const primaryColor = document.getElementById('primary_color');
    if (primaryColorPicker && primaryColor) {
        primaryColorPicker.addEventListener('input', function() {
            primaryColor.value = this.value;
        });
        primaryColor.addEventListener('input', function() {
            primaryColorPicker.value = this.value;
        });
    }
    
    const secondaryColorPicker = document.getElementById('secondary_color_picker');
    const secondaryColor = document.getElementById('secondary_color');
    if (secondaryColorPicker && secondaryColor) {
        secondaryColorPicker.addEventListener('input', function() {
            secondaryColor.value = this.value;
        });
        secondaryColor.addEventListener('input', function() {
            secondaryColorPicker.value = this.value;
        });
    }
    
    // Handle test email button
    document.getElementById('testEmailBtn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
        modal.show();
    });
    
    // Handle send test email button
    document.getElementById('sendTestEmailBtn').addEventListener('click', function() {
        const recipient = document.getElementById('test_email').value;
        const subject = document.getElementById('test_subject').value;
        const message = document.getElementById('test_message').value;
        
        if (!recipient) {
            alert('Please enter a recipient email address.');
            return;
        }
        
        // Show loading state
        const button = this;
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        button.disabled = true;
        
        // Send test email via AJAX
        fetch('send_test_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                recipient: recipient,
                subject: subject,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test email sent successfully!');
                bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
            } else {
                alert('Failed to send test email: ' + data.error);
            }
        })
        .catch(error => {
            alert('An error occurred: ' + error);
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
    
    // Email Template Functions
    window.testEmailTemplate = function(templateId) {
        const email = prompt('Enter email address to send test template:');
        if (email && email.includes('@')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'test_email_template';
            
            const templateInput = document.createElement('input');
            templateInput.type = 'hidden';
            templateInput.name = 'test_template_id';
            templateInput.value = templateId;
            
            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'test_email';
            emailInput.value = email;
            
            form.appendChild(actionInput);
            form.appendChild(templateInput);
            form.appendChild(emailInput);
            
            document.body.appendChild(form);
            form.submit();
        } else if (email !== null) {
            alert('Please enter a valid email address.');
        }
    }
    
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-success border-0 position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        Copied: ${text}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: 2000 });
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toast);
            });
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Copied: ' + text);
        });
    }
});
</script>

<!-- Advanced Template Editor Modal -->
<div class="modal fade" id="advancedTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-magic me-2"></i>Advanced Email Template Editor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="container-fluid h-100">
                    <div class="row h-100">
                        <!-- Editor Panel -->
                        <div class="col-lg-8 p-4" style="max-height: 90vh; overflow-y: auto;">
                            <form method="post" enctype="multipart/form-data" id="advancedTemplateForm">
                                <input type="hidden" name="action" value="update_structured_template">
                                <input type="hidden" name="template_id" id="advanced_template_id">
                                
                                <!-- Email Subject -->
                                <div class="form-section">
                                    <h6><i class="fas fa-envelope me-2"></i>Email Subject Line</h6>
                                    <input type="text" class="form-control form-control-lg" name="template_subject" 
                                           id="advanced_template_subject" placeholder="Enter email subject..." required>
                                    <small class="text-muted">This appears in the user's inbox</small>
                                </div>

                                <!-- Logo Upload -->
                                <div class="form-section">
                                    <h6><i class="fas fa-image me-2"></i>Email Logo</h6>
                                    <div class="upload-area" onclick="document.getElementById('logoUpload').click()">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color: #007bff;"></i>
                                        <p class="mb-0">Click to upload a custom logo</p>
                                        <small class="text-muted">JPG, PNG, or GIF (max 2MB)</small>
                                    </div>
                                    <input type="file" id="logoUpload" name="custom_logo" accept="image/*" style="display: none;">
                                    <div id="currentLogoPreview" class="mt-3" style="display: none;">
                                        <p class="mb-2"><strong>Current Logo:</strong></p>
                                        <img id="currentLogoImg" src="" alt="Current Logo" class="current-logo">
                                    </div>
                                </div>

                                <!-- Header Title -->
                                <div class="form-section">
                                    <h6><i class="fas fa-heading me-2"></i>Header Title</h6>
                                    <input type="text" class="form-control" name="header_title" 
                                           id="advanced_header_title" placeholder="e.g., Welcome to {{site_name}}!">
                                    <small class="text-muted">Large title shown at the top of the email</small>
                                </div>

                                <!-- Greeting Text -->
                                <div class="form-section">
                                    <h6><i class="fas fa-hand-wave me-2"></i>Greeting Text</h6>
                                    <input type="text" class="form-control" name="greeting_text" 
                                           id="advanced_greeting_text" placeholder="e.g., Hi {{first_name}},">
                                    <small class="text-muted">Personal greeting to the user</small>
                                </div>

                                <!-- Main Message -->
                                <div class="form-section">
                                    <h6><i class="fas fa-comment-alt me-2"></i>Main Message</h6>
                                    <textarea class="form-control" name="main_message" id="advanced_main_message" 
                                             rows="4" placeholder="Enter your main message here..."></textarea>
                                    <small class="text-muted">The main content of your email. You can use HTML for formatting.</small>
                                </div>

                                <!-- Button Text -->
                                <div class="form-section">
                                    <h6><i class="fas fa-mouse-pointer me-2"></i>Action Button</h6>
                                    <input type="text" class="form-control" name="button_text" 
                                           id="advanced_button_text" placeholder="e.g., Verify My Account">
                                    <small class="text-muted">Text for the main action button (leave empty to hide button)</small>
                                </div>

                                <!-- Fallback Message -->
                                <div class="form-section">
                                    <h6><i class="fas fa-info-circle me-2"></i>Fallback Message</h6>
                                    <textarea class="form-control" name="fallback_message" id="advanced_fallback_message" 
                                             rows="2" placeholder="e.g., If the button doesn't work, copy this link..."></textarea>
                                    <small class="text-muted">Alternative instructions if the button doesn't work</small>
                                </div>

                                <!-- Footer Note -->
                                <div class="form-section">
                                    <h6><i class="fas fa-sticky-note me-2"></i>Footer Note</h6>
                                    <textarea class="form-control" name="footer_note" id="advanced_footer_note" 
                                             rows="2" placeholder="e.g., If you didn't request this, please ignore this email."></textarea>
                                    <small class="text-muted">Additional information or disclaimer</small>
                                </div>

                                <!-- Template Status -->
                                <div class="form-section">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="advanced_is_active">
                                        <label class="form-check-label" for="advanced_is_active">
                                            <strong>✅ Template Active</strong><br>
                                            <small class="text-muted">Turn off to disable this email</small>
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Live Preview Panel -->
                        <div class="col-lg-4 p-0" style="background: #f8f9fa; border-left: 1px solid #dee2e6;">
                            <div class="preview-panel h-100">
                                <div class="preview-header">
                                    <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h6>
                                </div>
                                <div class="p-3">
                                    <!-- Variables Quick Insert -->
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Quick Variables:</h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="variable-chip" onclick="insertVariableAdvanced('{{first_name}}')">{{first_name}}</span>
                                            <span class="variable-chip" onclick="insertVariableAdvanced('{{site_name}}')">{{site_name}}</span>
                                            <span class="variable-chip" onclick="insertVariableAdvanced('{{verification_link}}')">{{verification_link}}</span>
                                            <span class="variable-chip" onclick="insertVariableAdvanced('{{admin_email}}')">{{admin_email}}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Live Preview -->
                                    <div id="emailPreview">
                                        <iframe id="previewFrame" srcdoc="<div style='padding: 20px; text-align: center; color: #666;'>Start editing to see preview...</div>"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="submit" form="advancedTemplateForm" class="btn btn-success btn-modern">
                    <i class="fas fa-save me-2"></i>Save Template
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Send Test Email</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="test_email_template">
                <input type="hidden" name="test_template_id" id="test_template_id">
                
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-envelope fa-3x" style="color: #4facfe;"></i>
                        <h6 class="mt-2">Test Your Email Template</h6>
                        <p class="text-muted">We'll send a preview email with sample data</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>📧 Send Test Email To:</strong></label>
                        <input type="email" class="form-control form-control-lg" name="test_email" 
                               placeholder="your.email@example.com" required>
                        <small class="text-muted">Enter your email address to receive the test</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>What happens:</strong> We'll send you the email with sample data like "Test User" 
                        and example dates so you can see exactly how it will look to your users.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="fas fa-rocket me-2"></i>Send Test Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Global variables for email templates
let currentTemplates = <?php echo json_encode($email_templates); ?>;
let lastFocusedField = null;

// Advanced template editor function
function editAdvancedTemplate(templateId) {
    const template = currentTemplates.find(t => t.template_id == templateId);
    if (template) {
        document.getElementById('advanced_template_id').value = template.template_id;
        document.getElementById('advanced_template_subject').value = template.template_subject || '';
        document.getElementById('advanced_header_title').value = template.header_title || '';
        document.getElementById('advanced_greeting_text').value = template.greeting_text || '';
        document.getElementById('advanced_main_message').value = template.main_message || '';
        document.getElementById('advanced_button_text').value = template.button_text || '';
        document.getElementById('advanced_fallback_message').value = template.fallback_message || '';
        document.getElementById('advanced_footer_note').value = template.footer_note || '';
        document.getElementById('advanced_is_active').checked = template.is_active == 1;
        
        // Show current logo if exists
        if (template.custom_logo && template.custom_logo !== null) {
            document.getElementById('currentLogoPreview').style.display = 'block';
            document.getElementById('currentLogoImg').src = '<?php echo SITE_URL; ?>/uploads/email_logos/' + template.custom_logo;
        } else {
            document.getElementById('currentLogoPreview').style.display = 'none';
        }
        
        // Add event listeners for live preview
        addLivePreviewListeners();
        
        // Update initial preview
        updateLivePreview();
        
        new bootstrap.Modal(document.getElementById('advancedTemplateModal')).show();
    }
}

// Add live preview listeners
function addLivePreviewListeners() {
    const fields = [
        'advanced_template_subject',
        'advanced_header_title', 
        'advanced_greeting_text',
        'advanced_main_message',
        'advanced_button_text',
        'advanced_fallback_message',
        'advanced_footer_note'
    ];
    
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.removeEventListener('input', updateLivePreview); // Remove existing listeners
            field.removeEventListener('focus', () => lastFocusedField = field);
            field.addEventListener('input', updateLivePreview);
            field.addEventListener('focus', () => lastFocusedField = field);
        }
    });
    
    // Logo upload preview
    const logoUpload = document.getElementById('logoUpload');
    if (logoUpload) {
        logoUpload.removeEventListener('change', handleLogoUpload); // Remove existing listener
        logoUpload.addEventListener('change', handleLogoUpload);
    }
}

function handleLogoUpload(e) {
    if (e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('currentLogoPreview').style.display = 'block';
            document.getElementById('currentLogoImg').src = e.target.result;
            updateLivePreview();
        };
        reader.readAsDataURL(e.target.files[0]);
    }
}

// Update live preview
function updateLivePreview() {
    const subject = document.getElementById('advanced_template_subject').value || 'Email Subject';
    const headerTitle = document.getElementById('advanced_header_title').value || 'Header Title';
    const greetingText = document.getElementById('advanced_greeting_text').value || 'Hi there,';
    const mainMessage = document.getElementById('advanced_main_message').value || 'Your main message goes here...';
    const buttonText = document.getElementById('advanced_button_text').value || '';
    const fallbackMessage = document.getElementById('advanced_fallback_message').value || '';
    const footerNote = document.getElementById('advanced_footer_note').value || '';
    
    // Get logo source
    let logoSrc = '<?php echo SITE_URL; ?>/assets/images/egabay-logo.png';
    const currentLogoImg = document.getElementById('currentLogoImg');
    if (currentLogoImg && currentLogoImg.src && currentLogoImg.src !== window.location.href) {
        logoSrc = currentLogoImg.src;
    }
    
    // Build button HTML
    let buttonHtml = '';
    if (buttonText.trim()) {
        buttonHtml = `
        <div style="text-align: center; margin: 30px 0;">
            <a href="#" style="background-color: #007bff; color: white; padding: 12px 30px; 
                              text-decoration: none; border-radius: 5px; font-weight: bold; 
                              display: inline-block;">${buttonText}</a>
        </div>`;
    }
    
    // Build preview HTML
    const previewHtml = `
    <div style="font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 20px; margin: 0;">
        <div style="max-width: 500px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px;">
            <div style="text-align: center; padding: 15px 0; background: #0d6efd; border-radius: 8px 8px 0 0;">
                <img src="${logoSrc}" alt="Logo" style="height: 40px; max-width: 150px;">
            </div>
            <div style="padding: 20px; color: #212529;">
                <h2 style="color: #0d6efd; margin-top: 0; font-size: 18px;">${headerTitle}</h2>
                <p style="font-size: 14px; margin-bottom: 15px;">${greetingText}</p>
                <div style="margin: 15px 0; font-size: 13px;">${mainMessage}</div>
                ${buttonHtml}
                ${fallbackMessage ? `<div style="margin: 15px 0; font-size: 12px; color: #666;">${fallbackMessage}</div>` : ''}
            </div>
            <div style="background: #f1f3f5; color: #6c757d; font-size: 10px; text-align: center; padding: 10px; border-radius: 0 0 8px 8px;">
                ${footerNote}<br><br>
                <?php echo SITE_NAME; ?> • This is an automated message
            </div>
        </div>
    </div>`;
    
    // Update iframe
    const previewFrame = document.getElementById('previewFrame');
    if (previewFrame) {
        previewFrame.srcdoc = previewHtml;
    }
}

// Insert variable into currently focused field
function insertVariableAdvanced(variable) {
    if (lastFocusedField) {
        const start = lastFocusedField.selectionStart;
        const end = lastFocusedField.selectionEnd;
        const text = lastFocusedField.value;
        
        lastFocusedField.value = text.substring(0, start) + variable + text.substring(end);
        lastFocusedField.selectionStart = lastFocusedField.selectionEnd = start + variable.length;
        lastFocusedField.focus();
        
        updateLivePreview();
        showToast('Inserted: ' + variable, 'success');
    } else {
        showToast('Click on a text field first, then click the variable', 'warning');
    }
}

// Test template function
function testTemplate(templateId) {
    document.getElementById('test_template_id').value = templateId;
    new bootstrap.Modal(document.getElementById('testTemplateModal')).show();
}

// Show toast notification
function showToast(message, type = 'info') {
    const colors = {
        'success': '#28a745',
        'info': '#17a2b8',
        'warning': '#ffc107',
        'danger': '#dc3545'
    };
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 9999;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.style.transform = 'translateX(0)', 100);
    
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('advancedTemplateForm');
    if (form) {
        form.addEventListener('submit', function() {
            const submitBtn = document.querySelector('#advancedTemplateModal button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Auto-switch to email templates tab if hash is present
    if (window.location.hash === '#email-templates') {
        const emailTemplatesTab = document.getElementById('email-templates-tab');
        if (emailTemplatesTab) {
            const tab = new bootstrap.Tab(emailTemplatesTab);
            tab.show();
        }
    }
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 