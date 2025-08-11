<?php
// Include path fix helper
require_once __DIR__ . '/includes/path_fix.php';

// Include configuration
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/includes/utility.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection and objects
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Redirect if already logged in
if (isLoggedIn()) {
    $userRole = strtolower($_SESSION['role_name'] ?? 'student');
            redirect(rtrim(SITE_URL, '/') . '/dashboard/' . $userRole);
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // Get user IP safely
    $userIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Attempt login directly without SecurityManager blocking
    $loginResult = $auth->login($username, $password);
    
    if ($loginResult && is_array($loginResult)) {
        // Login successful - $loginResult contains user data
        
        // Check if user is verified
        if (isset($loginResult['is_verified']) && $loginResult['is_verified'] != 1) {
            setMessage('Please verify your email first before logging in.', 'warning');
        } else {
            // Set session variables
            $_SESSION['user_id'] = $loginResult['user_id'];
            $_SESSION['username'] = $loginResult['username'];
            $_SESSION['first_name'] = $loginResult['first_name'];
            $_SESSION['last_name'] = $loginResult['last_name'];
            $_SESSION['email'] = $loginResult['email'];
            $_SESSION['role_id'] = $loginResult['role_id'];
            $_SESSION['role_name'] = $loginResult['role_name'];
            
            // Generate session token for additional security
            $_SESSION['session_token'] = bin2hex(random_bytes(32));
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['user_ip'] = $userIP;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Set secure session parameters
            $sessionLifetime = $remember_me ? (86400 * 30) : 0; // 30 days or session-only
            session_set_cookie_params([
                'lifetime' => $sessionLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // Log successful login
            try {
                if ($auth && method_exists($auth, 'logActivity')) {
                    $auth->logActivity($_SESSION['user_id'], 'login', 'User logged in successfully', $userIP);
                }
            } catch (Exception $e) {
                error_log("Failed to log login activity: " . $e->getMessage());
            }
            
            // Redirect based on role with cache busting
                            $redirectUrl = rtrim(SITE_URL, '/') . '/dashboard/' . strtolower($_SESSION['role_name']) . '?t=' . time();
            header("Location: " . $redirectUrl);
            exit();
        }
    } else {
        // Failed login - simple error message, no blocking
        setMessage('Invalid username or password.', 'danger');
        
        // Optional: Log failed login attempt for monitoring (but no blocking)
        try {
            if (method_exists($auth, 'logActivity')) {
                $auth->logActivity(null, 'failed_login', 'Failed login attempt for username: ' . $username, $userIP);
            }
        } catch (Exception $e) {
            error_log("Failed to log failed login: " . $e->getMessage());
        }
    }
}

// Add security script to prevent back button issues
if (isset($_SESSION['user_id'])) {
    echo '<script>
        // Clear history after successful login
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Redirect immediately after login
                                window.location.replace("' . rtrim(SITE_URL, '/') . '/dashboard/' . strtolower($_SESSION['role_name'] ?? 'student') . '");
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
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
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --primary-light: #4a7bc8;
            --primary-dark: #1e3d6f;
            --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
        }
        
        body {
            background: var(--bg-heavenly, linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 50%, #fff3e0 100%));
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .login-container {
            max-width: 480px;
            width: 100%;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .login-logo img {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }
        
        .login-logo h1, .login-logo h2 {
            color: #000000 !important;
            font-size: 2.2rem;
            font-weight: 700;
            margin: 1rem 0 0.5rem;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
            line-height: 1.2;
            background: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: #000000 !important;
            background-clip: unset !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .login-logo p {
            color: #444444 !important;
            font-size: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            letter-spacing: 0.3px;
        }
        
        .login-form {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        

        
        .form-control {
            border: 2px solid #e3e6f0;
            border-radius: var(--border-radius-lg);
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: border-color 0.1s ease, box-shadow 0.1s ease;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            outline: none;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e3e6f0;
            border-right: none;
            color: var(--primary-color);
            font-weight: 500;
            border-radius: var(--border-radius-lg) 0 0 var(--border-radius-lg);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: var(--border-radius-lg);
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
        
        .alert {
            border: none;
            border-radius: var(--border-radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .back-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link:hover {
            color: white;
            transform: translateX(-5px);
        }
        
        .form-floating .form-control {
            border-radius: var(--border-radius-lg);
        }
        
        .captcha-field {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
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
        
        @media (max-width: 576px) {
            .login-form {
                padding: 2rem;
            }
            
            .login-logo h1 {
                font-size: 2rem;
            }
            
            .login-container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="<?php echo SITE_URL; ?>/assets/images/egabay-logo.png" alt="EGABAY Logo" height="200" style="max-width: 100%; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));">
            <p class="text-muted" style="color: #444444 !important; font-size: 1.1rem;">Academic Support and Counseling System</p>
        </div>
        <div class="login-form">
                <?php displayMessage(); ?>
                               
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <?php 
                    // Show CAPTCHA if required
                    if (isset($captcha)): 
                    ?>
                    <div class="mb-3">
                        <label for="captcha_answer" class="form-label">Security Question</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                            <input type="text" class="form-control" id="captcha_answer" name="captcha_answer" 
                                   placeholder="<?php echo htmlspecialchars($captcha['question']); ?>" required>
                        </div>
                        <small class="form-text text-muted">Please solve the math problem above.</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-end mb-2">
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <?php
                // Determine if registrations allowed
                $allow_reg = (int)Utility::getSetting('allow_registrations', 1);
                ?>
                <?php if($allow_reg): ?>
                <div class="text-center mt-4">
                    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?php echo SITE_URL; ?>/" class="back-link">
                    <i class="fas fa-arrow-left"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Enhanced security countdown timer
document.addEventListener('DOMContentLoaded', function() {
    const alertDiv = document.querySelector('.alert-danger');
    if (alertDiv && (alertDiv.textContent.includes('temporarily blocked') || alertDiv.textContent.includes('Please wait'))) {
        const text = alertDiv.textContent;
        const minutesMatch = text.match(/(\d+) minute\(s\)/);
        const secondsMatch = text.match(/(\d+) second\(s\)/);
        
        if (minutesMatch || secondsMatch) {
            let totalSeconds = 0;
            if (minutesMatch) totalSeconds += parseInt(minutesMatch[1]) * 60;
            if (secondsMatch) totalSeconds += parseInt(secondsMatch[1]);
            
            const originalMessage = alertDiv.innerHTML;
            const blockType = text.includes('IP address') ? 'IP address' : 'account';
            
            const countdown = setInterval(function() {
                totalSeconds--;
                
                if (totalSeconds <= 0) {
                    clearInterval(countdown);
                    alertDiv.innerHTML = `Lockout period has ended. You may try logging in again.`;
                    alertDiv.className = 'alert alert-success';
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;
                    
                    let timeStr;
                    if (minutes > 0) {
                        timeStr = `${minutes} minute(s) and ${seconds} second(s)`;
                    } else {
                        timeStr = `${seconds} second(s)`;
                    }
                    
                    alertDiv.innerHTML = `Too many failed attempts. Your ${blockType} is temporarily blocked for ${timeStr}.`;
                }
            }, 1000);
        }
    }
    
    // Auto-focus on CAPTCHA field if present
    const captchaField = document.getElementById('captcha_answer');
    if (captchaField) {
        captchaField.focus();
    }
});
</script>

<script>
// Prevent caching of login page
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};

// Clear any existing sessions when login page loads
document.addEventListener('DOMContentLoaded', function() {
    // Clear any browser storage
    if (typeof(Storage) !== "undefined") {
        sessionStorage.clear();
        localStorage.removeItem('user_session');
    }
});
</script>

</body>
</html> 