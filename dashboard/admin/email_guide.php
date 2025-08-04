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
$page_title = 'Email Templates Guide';

// Include header
include_once $base_path . '/includes/header.php';
?>

<style>
.guide-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    text-align: center;
}

.step-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    margin-bottom: 20px;
    overflow: hidden;
}

.step-card:hover {
    transform: translateY(-5px);
}

.step-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.step-number {
    width: 50px;
    height: 50px;
    background: white;
    color: #4facfe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin: 0 auto 15px;
}

.feature-box {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.feature-box:hover {
    border-color: #4facfe;
    transform: scale(1.02);
}

.feature-icon {
    font-size: 48px;
    color: #4facfe;
    margin-bottom: 15px;
}

.btn-giant {
    padding: 20px 40px;
    font-size: 18px;
    font-weight: bold;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.btn-giant:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.video-placeholder {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    margin: 20px 0;
}

.example-email {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.email-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
}
</style>

<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="guide-card">
        <h1><i class="fas fa-graduation-cap me-3"></i>Email Templates Made Easy!</h1>
        <p class="lead mb-4">Don't worry - you don't need to be a programmer! This simple guide will show you how to customize your email messages in just a few clicks.</p>
        <a href="settings.php#email-templates" class="btn btn-light btn-giant">
            <i class="fas fa-rocket me-2"></i>Start Customizing Emails
        </a>
    </div>

    <div class="row">
        <!-- Why Use Email Templates -->
        <div class="col-lg-4">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h4>Make Users Happy</h4>
                <p>Send beautiful, personalized emails that make your users feel valued and welcomed to your platform.</p>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h4>Easy to Customize</h4>
                <p>No coding needed! Just click, type, and save. Change colors, text, and messages with simple clicks.</p>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fas fa-magic"></i>
                </div>
                <h4>Automatic Personal Touch</h4>
                <p>Use "magic variables" to automatically include user names, dates, and other personal information.</p>
            </div>
        </div>
    </div>

    <!-- Simple Steps -->
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="text-center mb-4">🎯 How to Customize Your Emails (Super Easy!)</h2>
        </div>
        
        <div class="col-lg-3">
            <div class="step-card">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h5>Choose Email Type</h5>
                </div>
                <div class="p-3">
                    <p>Pick which email you want to customize:</p>
                    <ul class="text-start">
                        <li>Welcome emails</li>
                        <li>Password reset</li>
                        <li>Notifications</li>
                        <li>And more!</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="step-card">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h5>Click "Edit"</h5>
                </div>
                <div class="p-3">
                    <p>Simply click the "Edit Template" button to open the easy editor.</p>
                    <p><strong>No technical knowledge required!</strong></p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="step-card">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h5>Type Your Message</h5>
                </div>
                <div class="p-3">
                    <p>Write your message just like writing an email. Use the magic variables for personal touches!</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="step-card">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <h5>Test & Save</h5>
                </div>
                <div class="p-3">
                    <p>Send yourself a test email to see how it looks, then save your changes!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Magic Variables Explained -->
    <div class="row mt-5">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-magic me-2"></i>Magic Variables Explained</h4>
                </div>
                <div class="card-body">
                    <p><strong>What are these?</strong> Magic variables automatically replace themselves with real user information.</p>
                    
                    <div class="alert alert-info">
                        <strong>Example:</strong><br>
                        You type: "Hello {{first_name}}, welcome to {{site_name}}!"<br>
                        User sees: "Hello John, welcome to EGABAY ASC!"
                    </div>
                    
                    <h6>Common Magic Variables:</h6>
                    <ul>
                        <li><code>{{first_name}}</code> - User's first name</li>
                        <li><code>{{site_name}}</code> - Your website name</li>
                        <li><code>{{email}}</code> - User's email address</li>
                        <li><code>{{logo}}</code> - Your website logo</li>
                    </ul>
                    
                    <p class="text-muted">💡 Just click on any variable to copy it, then paste it in your email!</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4><i class="fas fa-envelope me-2"></i>Email Preview Example</h4>
                </div>
                <div class="card-body">
                    <div class="example-email">
                        <div class="email-header">
                            <h5>Welcome to EGABAY ASC!</h5>
                        </div>
                        <p><strong>Hello John,</strong></p>
                        <p>Thank you for registering with <strong>EGABAY ASC</strong>. We're excited to have you join our community!</p>
                        <p>To complete your registration, please click the button below:</p>
                        <div class="text-center">
                            <button class="btn btn-success">✓ Verify My Account</button>
                        </div>
                        <hr>
                        <small class="text-muted">If you didn't create an account, please ignore this email.</small>
                    </div>
                    <p class="text-center text-muted mt-3">👆 This is how your users will see the email!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips for Success -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h4><i class="fas fa-lightbulb me-2"></i>Pro Tips for Great Emails</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>✅ Do This:</h6>
                            <ul>
                                <li>Keep messages friendly and welcoming</li>
                                <li>Use the user's name with {{first_name}}</li>
                                <li>Test emails before saving</li>
                                <li>Write clear, simple messages</li>
                                <li>Include your website name with {{site_name}}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>❌ Avoid This:</h6>
                            <ul>
                                <li>Making emails too long</li>
                                <li>Using complicated language</li>
                                <li>Forgetting to test your changes</li>
                                <li>Removing important magic variables</li>
                                <li>Making emails too formal or scary</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="text-center mt-5 mb-5">
        <div class="guide-card">
            <h3>Ready to Create Amazing Emails? 🚀</h3>
            <p class="lead">Don't worry - it's easier than you think! Start with one email template and see how simple it is.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="settings.php#email-templates" class="btn btn-light btn-giant">
                    <i class="fas fa-magic me-2"></i>Advanced Email Editor
                </a>
                <a href="settings.php" class="btn btn-outline-light btn-giant">
                    <i class="fas fa-cog me-2"></i>Email Settings
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 