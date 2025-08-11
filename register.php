<?php
// register.php – self-service student registration with email verification
require_once __DIR__ . '/includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/includes/utility.php';
require_once $base_path . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard/');
    exit;
}

// Disable self-registration if setting turned off
$allow_reg = (int)Utility::getSetting('allow_registrations', 1);
if ($allow_reg === 0) {
    setMessage('Registrations are currently disabled. Please contact the administrator.', 'warning');
    header('Location: login');
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Honeypot spam protection
    if (!empty($_POST['website'])) {
        $errors[] = 'Spam detected. Registration blocked.';
    }
    
    // Enhanced rate limiting with database storage instead of session
    $ip = $_SERVER['REMOTE_ADDR'];
    $db = (new Database())->getConnection();
    
    // Create rate limiting table if it doesn't exist
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempt_count INT DEFAULT 1,
            first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_ip (ip_address)
        )");
    } catch (PDOException $e) {
        // Table likely already exists
    }
    
    // Check and update rate limit
    $now = date('Y-m-d H:i:s');
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // Clean old entries and check current rate
    $db->prepare("DELETE FROM rate_limits WHERE last_attempt < ?")->execute([$oneHourAgo]);
    
    $stmt = $db->prepare("SELECT attempt_count FROM rate_limits WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $currentAttempts = $stmt->fetchColumn();
    
    if ($currentAttempts && $currentAttempts >= 10) {
        $errors[] = 'Too many registration attempts. Please try again later.';
    } else {
        // Update or insert rate limit record
        $stmt = $db->prepare("INSERT INTO rate_limits (ip_address, attempt_count) VALUES (?, 1) 
                              ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1");
        $stmt->execute([$ip]);
    }
    
    $first  = trim($_POST['first_name']);
    $last   = trim($_POST['last_name']);
    $user   = trim($_POST['username']);
    $email  = trim($_POST['email']);
    $pass   = $_POST['password'];
    $pass2  = $_POST['confirm_password'];

    if (empty($first) || empty($last) || empty($user) || empty($email) || empty($pass) || empty($pass2)) {
        $errors[] = 'All fields are required.';
    }
    
    // Enhanced validation with better error messages
    if (!empty($first) && strlen($first) < 2) {
        $errors[] = 'First name must be at least 2 characters long.';
    }
    if (!empty($last) && strlen($last) < 2) {
        $errors[] = 'Last name must be at least 2 characters long.';
    }
    if (!empty($user)) {
        if (strlen($user) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        } elseif (strlen($user) > 20) {
            $errors[] = 'Username must be no more than 20 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $user)) {
            $errors[] = 'Username can only contain letters, numbers, dots (.), hyphens (-), and underscores (_). No spaces or special characters allowed.';
        }
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $pass)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
    }
    
    // Ensure user agreed to legal terms
    if (!isset($_POST['agree'])) {
        $errors[] = 'You must agree to the Terms and Conditions, Privacy Policy, and Data Privacy Act before registering.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Use database transaction to prevent race conditions
        try {
            $db->beginTransaction();
            
            $token = bin2hex(random_bytes(20));
            $hash  = password_hash($pass, PASSWORD_BCRYPT);
            $roleId = ROLE_STUDENT; // 1
            
            // Sanitize data before database insertion
            $first = htmlspecialchars($first, ENT_QUOTES, 'UTF-8');
            $last = htmlspecialchars($last, ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            
            // Direct INSERT with proper error handling for duplicates
            $query = 'INSERT INTO users (first_name,last_name,username,email,password,role_id,is_verified,verification_token,is_active)
                      VALUES (?,?,?,?,?,?,0,?,1)';
            
            $stmt = $db->prepare($query);
            $success = $stmt->execute([$first,$last,$user,$email,$hash,$roleId,$token]);
            
            if ($success && $stmt->rowCount() > 0) {
                // Registration successful - commit transaction
                $db->commit();
                
                // Clear rate limit for successful registration
                $db->prepare("DELETE FROM rate_limits WHERE ip_address = ?")->execute([$ip]);
                
                // Send verification mail using custom template
                try {
                    sendVerificationEmail($email, $first, $token);
                } catch (Exception $e) {
                    // Log email error but don't fail registration
                    error_log("Email sending failed: " . $e->getMessage());
                }
                
                setMessage('Registration successful! Please check your e-mail to verify your account.','success');
                redirect('login');
                exit;
                
            } else {
                // Check if it's a duplicate key constraint violation
                $errorInfo = $stmt->errorInfo();
                if ($errorInfo[1] == 1062) { // MySQL duplicate entry error
                    if (strpos($errorInfo[2], 'username') !== false) {
                        $errors[] = 'The username "' . htmlspecialchars($user) . '" is already taken. Please choose a different username.';
                    } elseif (strpos($errorInfo[2], 'email') !== false) {
                        $errors[] = 'The email address "' . htmlspecialchars($email) . '" is already registered. Please use a different email address.';
                    } else {
                        $errors[] = 'Username or email already exists. Please try different values.';
                    }
                } else {
                    $errors[] = 'Registration failed. Error: ' . $errorInfo[2] . ' Please try again.';
                }
                $db->rollback();
            }
            
        } catch (PDOException $e) {
            $db->rollback();
            
            // Handle specific database errors
            if ($e->getCode() == 23000) { // Integrity constraint violation
                if (strpos($e->getMessage(), 'username') !== false) {
                    $errors[] = 'The username "' . htmlspecialchars($user) . '" is already taken. Please choose a different username.';
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $errors[] = 'The email address "' . htmlspecialchars($email) . '" is already registered. Please use a different email address.';
                } else {
                    $errors[] = 'Username or email already exists. Please try different values.';
                }
            } else {
                error_log("Registration database error: " . $e->getMessage());
                $errors[] = 'Database connection error. Please check if your database is running and try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <style>
        body {
            background: var(--bg-heavenly, linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 50%, #fff3e0 100%));
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .register-container {
            max-width: 480px;
            width: 100%;
        }
        
        .register-logo {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .register-logo img {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .register-logo h2 {
            color: #000000 !important;
            font-size: 2.2rem;
            font-weight: 700;
            margin: 1rem 0 0.5rem;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
            background: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: #000000 !important;
            background-clip: unset !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.2;
        }
        
        .register-logo p {
            color: #444444 !important;
            font-size: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            letter-spacing: 0.3px;
        }
        
        .register-form {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        
        .register-form h3,
        .register-form h4 {
            color: #2c3e50 !important;
            font-weight: 600;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 1.5rem;
            letter-spacing: 0.3px;
            margin-bottom: 2rem;
        }
        
        .form-control {
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: border-color 0.1s ease, box-shadow 0.1s ease;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            outline: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            border-radius: 8px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            transition: background 0.1s ease, box-shadow 0.1s ease;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            box-shadow: 0 7px 14px rgba(0,0,0,0.18);
        }
        
        a {
            color: #4e73df !important;
            text-decoration: none;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-weight: 500;
            transition: color 0.1s ease;
        }
        
        a:hover {
            color: #2653d3 !important;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">
            <img src="<?php echo SITE_URL; ?>/assets/images/egabay-logo.png" alt="EGABAY Logo" height="200" style="max-width: 100%; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));">
            <p class="text-muted" style="color: #444444 !important; font-size: 1.1rem;">Create Your Account</p>
        </div>
        <div class="register-form">
            <?php foreach ($errors as $e): echo '<div class="alert alert-danger">'.$e.'</div>'; endforeach; ?>
            <?php displayMessage(); ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <!-- CSRF Protection -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- Honeypot field (hidden from users, visible to bots) -->
                <input type="text" name="website" style="position:absolute;left:-9999px;opacity:0;" tabindex="-1" autocomplete="off">
                
                <div class="mb-2"><label class="form-label" for="first_name">Name</label></div>
                <div class="row g-2 mb-3">
                    <div class="col"><input type="text" name="first_name" class="form-control" placeholder="First name" required></div>
                    <div class="col"><input type="text" name="last_name" class="form-control" placeholder="Last name" required></div>
                </div>
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email address" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="form-text">
                        Password must be at least 8 characters with uppercase, lowercase, and number.
                    </div>
                    <div id="passwordStrength" class="mt-1"></div>
                </div>
                <div class="mb-4">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    <div id="passwordMatch" class="form-text"></div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="agree" name="agree" required>
                    <label class="form-check-label" for="agree">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#legalModal">Terms and Conditions</a>, <a href="#" data-bs-toggle="modal" data-bs-target="#legalModal">Privacy Policy</a>, and <a href="#" data-bs-toggle="modal" data-bs-target="#legalModal">Data Privacy Act</a>.
                    </label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Create Account</button>
            </form>
            <div class="text-center mt-3">
                                    <a href="login">Already have an account? Log in</a>
            </div>
        </div>
    </div>

<!-- Legal Modals -->
<!-- Combined Legal Documents Modal -->
<div class="modal fade" id="legalModal" tabindex="-1" aria-labelledby="legalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="legalModalLabel">LEGAL DOCUMENTS & PRIVACY NOTICE</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="legalTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms-pane" type="button" role="tab">Terms & Conditions</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy-pane" type="button" role="tab">Privacy Policy</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="data-privacy-tab" data-bs-toggle="tab" data-bs-target="#data-privacy-pane" type="button" role="tab">Data Privacy Act</button>
          </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="legalTabContent" style="max-height:60vh;overflow-y:auto;">
          <!-- Terms and Conditions Tab -->
          <div class="tab-pane fade show active" id="terms-pane" role="tabpanel">
            <ol class="ps-3 lh-lg">
              <li><strong>Acceptance.</strong> By creating an account or using the EGABAY ASC System ("System"), you agree to be bound by these Terms and Conditions.</li>
              <li><strong>Purpose of the System.</strong>
                  <ul class="mb-2 list-unstyled ps-2">
                      <li>Online booking and monitoring of guidance / consultation sessions.</li>
                      <li>Real-time chat and file sharing among students, counselors, and administrators.</li>
                      <li>Delivery of system notifications and e-mail updates.</li>
                  </ul>
              </li>
              <li><strong>Account Creation.</strong>
                  <ul class="mb-2 list-unstyled ps-2">
                      <li>Real first and last name, valid e-mail, and unique username are required.</li>
                      <li>Passwords are stored only in hashed form (bcrypt); you are responsible for keeping credentials confidential.</li>
                      <li>Only one personal account is allowed per individual.</li>
                  </ul>
              </li>
              <li><strong>Acceptable Use.</strong>
                  <ul class="mb-2 list-unstyled ps-2">
                      <li>No defamatory, pornographic, or illegal content.</li>
                      <li>File-upload limits: 10&nbsp;MB; allowed types – JPG, JPEG, PNG, GIF, WEBP, PDF, DOC/X, XLS/X, PPT/X, TXT/CSV, ZIP, RAR.</li>
                      <li>You must own—or have rights to—any material you upload.</li>
                  </ul>
              </li>
              <li><strong>Responsibility &amp; Limitation of Liability.</strong> The System is provided "as-is." Reasonable precautions are taken, but 100&nbsp;% uptime is not guaranteed. ASC is not liable for data loss, lost profits, or damages arising from force majeure, maintenance, or user misuse.</li>
              <li><strong>Termination.</strong> The administration may suspend or delete any account that violates these Terms or is used for unlawful purposes. You may request deletion at any time, provided you have no outstanding obligations (e.g., scheduled sessions).</li>
              <li><strong>Changes.</strong> Updates will be posted in the dashboard and take effect 30&nbsp;days after posting.</li>
              <li><strong>Governing Law &amp; Venue.</strong> Philippine law applies; exclusive venue—courts of Baguio City (or the school's official venue).</li>
            </ol>
          </div>
          
          <!-- Privacy Policy Tab -->
          <div class="tab-pane fade" id="privacy-pane" role="tabpanel">
             <div class="mb-4">
               <h6><strong>1. Personal Data Collected</strong></h6>
               <ul class="list-unstyled ps-3">
                 <li>• Account information: first name, last name, username, e-mail address, hashed password, user role</li>
                 <li>• Optional profile picture</li>
                 <li>• Consultation and chat records (text messages and file attachments)</li>
                 <li>• Technical metadata: IP address, browser/OS details, login and activity timestamps</li>
                 <li>• System logs and scheduled database backups</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>2. Purposes of Processing</strong></h6>
               <ul class="list-unstyled ps-3">
                 <li>a) User registration and authentication</li>
                 <li>b) Scheduling and documenting guidance consultations</li>
                 <li>c) Facilitating real-time chat, file transfers, and system notifications</li>
                 <li>d) Sending transactional e-mails via secure SMTP (PHPMailer)</li>
                 <li>e) Security auditing and disaster recovery through daily automated backups</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>3. Legal Bases for Processing</strong></h6>
               <ul class="list-unstyled ps-3">
                 <li>• Your explicit consent (registration checkbox)</li>
                 <li>• Contract performance (providing guidance services)</li>
                 <li>• Legal obligation (educational record-keeping requirements)</li>
                 <li>• Legitimate interest (system security and service analytics)</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>4. Data Sharing and Disclosure</strong></h6>
               <p class="ps-3 mb-1">Your personal data is shared only with:</p>
               <ul class="list-unstyled ps-3">
                 <li>• Authorized counselors and administrative staff</li>
                 <li>• Third-party service providers (e-mail delivery and web hosting)</li>
                 <li>• Government authorities or courts when legally required by law</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>5. Data Retention</strong></h6>
               <ul class="list-unstyled ps-3">
                 <li>• Active user accounts: retained while service is needed</li>
                 <li>• Chat and consultation logs: up to 5 years after the last session</li>
                 <li>• System backups: automatically rotated every 30 days</li>
                 <li>• Account deletion requests: archived for 1 year for audit purposes, then permanently erased</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>6. Security Measures</strong></h6>
               <ul class="list-unstyled ps-3">
                 <li>• TLS/HTTPS encryption for all data transmission</li>
                 <li>• Bcrypt password hashing and prepared SQL statements</li>
                 <li>• Restricted physical and logical access to file uploads and server</li>
                 <li>• File type and size validation for all uploads</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>7. Your Rights Under the Data Privacy Act</strong></h6>
               <ul class="list-unstyled ps-3">
                 <li>• Right to access, correct, and request portability of your data</li>
                 <li>• Right to withdraw consent at any time</li>
                 <li>• Right to request deletion of your personal data</li>
                 <li>• Right to file a complaint with the National Privacy Commission</li>
               </ul>
             </div>
             
             <div class="mb-4">
               <h6><strong>8. Data Protection Officer</strong></h6>
               <div class="ps-3">
                 <p class="mb-1"><strong>Name:</strong> Keith Bryan Torda</p>
                 <p class="mb-1"><strong>E-mail:</strong> keithniiyoow@gmail.com</p>
                 <p class="mb-1"><strong>Phone:</strong> 09290593625</p>
               </div>
             </div>
             
             <div class="mb-3">
               <h6><strong>9. Policy Updates</strong></h6>
               <p class="ps-3">Privacy policy revisions will be posted at least 30 days before taking effect. Continued use of the system constitutes acceptance of any changes.</p>
             </div>
          </div>
          
          <!-- Data Privacy Act Tab -->
          <div class="tab-pane fade" id="data-privacy-pane" role="tabpanel">
             <div class="mb-4">
               <p class="lead">By ticking the "I agree" checkbox on the registration form, you expressly consent to the following:</p>
             </div>
             
             <div class="mb-4">
               <h6><strong>Data Collection and Processing</strong></h6>
               <p class="ps-3">You acknowledge that EGABAY ASC will collect and process your personal data solely for the purposes stated in the Privacy Policy, in compliance with the Data Privacy Act of 2012 (RA 10173).</p>
             </div>
             
             <div class="mb-4">
               <h6><strong>Your Rights</strong></h6>
               <p class="ps-3">You understand your rights under the <strong>DATA PRIVACY ACT OF 2012 (RA 10173)</strong> and acknowledge that you may exercise these rights or withdraw consent at any time by contacting the Data Protection Officer listed in the Privacy Policy.</p>
             </div>
             
             <div class="mb-4">
               <h6><strong>Communication Consent</strong></h6>
               <p class="ps-3">You consent to the use of your e-mail address and mobile number for verification, scheduling reminders, password resets, security notifications, and other essential transactional messages related to your use of the system.</p>
             </div>
             
             <div class="mb-3">
               <h6><strong>Record Retention</strong></h6>
               <p class="ps-3">You accept that your chat and consultation history will be retained as part of the school's official guidance records for up to five (5) years after your last session, unless a longer period is legally required for educational or regulatory compliance.</p>
             </div>
          </div>
        </div>
        
        <!-- Footer with Agreement Button -->
        <div class="modal-footer">
          <div class="text-muted small">Please read all tabs above before agreeing</div>
          <button type="button" class="btn btn-primary" id="agreeToTerms">I Have Read and Agree to All Terms</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const agreeCheckbox = document.getElementById('agree');
    const legalModal = new bootstrap.Modal(document.getElementById('legalModal'));
    
    agreeCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Show legal modal when checkbox is clicked
            legalModal.show();
            // Uncheck the box until user reads all tabs
            this.checked = false;
        }
    });
    
    // Handle agreement button click
    document.getElementById('agreeToTerms').addEventListener('click', function() {
        document.getElementById('agree').checked = true;
        legalModal.hide();
    });
    
    // Real-time password strength checker
    const passwordField = document.querySelector('input[name="password"]');
    const confirmPasswordField = document.querySelector('input[name="confirm_password"]');
    const strengthDiv = document.getElementById('passwordStrength');
    const matchDiv = document.getElementById('passwordMatch');
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let feedback = '';
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z\d]/.test(password)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                feedback = '<span class="text-danger">Weak</span>';
                break;
            case 2:
            case 3:
                feedback = '<span class="text-warning">Medium</span>';
                break;
            case 4:
            case 5:
                feedback = '<span class="text-success">Strong</span>';
                break;
        }
        
        strengthDiv.innerHTML = password.length > 0 ? 'Strength: ' + feedback : '';
        checkPasswordMatch();
    });
    
    confirmPasswordField.addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (confirmPassword.length > 0) {
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="text-success">Passwords match</span>';
                matchDiv.className = 'form-text text-success';
            } else {
                matchDiv.innerHTML = '<span class="text-danger">Passwords do not match</span>';
                matchDiv.className = 'form-text text-danger';
            }
        } else {
            matchDiv.innerHTML = '';
        }
    }
    
    // Username validation with real-time feedback
    const usernameField = document.querySelector('input[name="username"]');
    const usernameHelp = document.createElement('div');
    usernameHelp.className = 'form-text';
    usernameHelp.id = 'usernameHelp';
    usernameField.parentNode.appendChild(usernameHelp);
    
    usernameField.addEventListener('input', function() {
        const username = this.value;
        const regex = /^[a-zA-Z0-9._-]+$/;
        let message = '';
        let isValid = true;
        
        if (username.length === 0) {
            message = '';
        } else if (username.length < 3) {
            message = '<span class="text-danger">Username too short (minimum 3 characters)</span>';
            isValid = false;
        } else if (username.length > 20) {
            message = '<span class="text-danger">Username too long (maximum 20 characters)</span>';
            isValid = false;
        } else if (!regex.test(username)) {
            message = '<span class="text-danger">Invalid characters! Only letters, numbers, dots (.), hyphens (-), and underscores (_) allowed</span>';
            isValid = false;
        } else {
            message = '<span class="text-success">Username looks good!</span>';
        }
        
        usernameHelp.innerHTML = message;
        
        if (isValid || username.length === 0) {
            this.setCustomValidity('');
        } else if (username.length < 3 || username.length > 20) {
            this.setCustomValidity('Username must be between 3 and 20 characters.');
        } else if (!regex.test(username)) {
            this.setCustomValidity('Username can only contain letters, numbers, dots (.), hyphens (-), and underscores (_).');
        }
    });
    
    // Enhanced form submission handling
    const form = document.querySelector('form');
    const submitBtn = form.querySelector('button[type="submit"]');
    let isSubmitting = false;
    
    form.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        isSubmitting = true;
        
        // Disable submit button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        
        // Re-enable after timeout in case of error
        setTimeout(() => {
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Create Account';
        }, 10000); // 10 seconds timeout
    });
});
</script>
</body>
</html> 