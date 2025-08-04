<?php
require_once __DIR__ . '/includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/includes/utility.php';
require_once $base_path . '/includes/auth.php';

// Redirect logged-in users to dashboard
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard/');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Progressive rate limiting for forgot password attempts
    $ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = 'forgot_pw_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'last_attempt' => time(), 'lockout_until' => 0];
    }
    
    $attempts_data = $_SESSION[$rate_limit_key];
    $current_time = time();
    
    // Check if currently locked out
    if ($current_time < $attempts_data['lockout_until']) {
        $remaining_time = $attempts_data['lockout_until'] - $current_time;
        $minutes = floor($remaining_time / 60);
        $seconds = $remaining_time % 60;
        $message = "Too many password reset attempts. Please wait {$minutes} minutes and {$seconds} seconds before trying again.";
    } else {
        // Reset attempts if lockout period is over
        if ($current_time >= $attempts_data['lockout_until']) {
            $_SESSION[$rate_limit_key]['count'] = 0;
            $_SESSION[$rate_limit_key]['lockout_until'] = 0;
        }
        
        // Reset attempts if more than 1 hour passed since last attempt
        if ($current_time - $attempts_data['last_attempt'] > 3600) {
            $_SESSION[$rate_limit_key]['count'] = 0;
        }
        
        $_SESSION[$rate_limit_key]['last_attempt'] = $current_time;
        $_SESSION[$rate_limit_key]['count']++;
        
        $failed_attempts = $_SESSION[$rate_limit_key]['count'];
        
        // Progressive lockout: 30s, 1m, 5m, 10m, 30m for attempts 3+
        $lockout_times = [0, 0, 30, 60, 300, 600, 1800]; // in seconds
        
        if ($failed_attempts >= 3) {
            $lockout_duration = $lockout_times[min($failed_attempts, count($lockout_times) - 1)];
            $_SESSION[$rate_limit_key]['lockout_until'] = $current_time + $lockout_duration;
            
            $minutes = floor($lockout_duration / 60);
            $seconds = $lockout_duration % 60;
            if ($minutes > 0) {
                $message = "Too many password reset attempts. Please wait {$minutes} minute(s) before trying again.";
            } else {
                $message = "Too many password reset attempts. Please wait {$seconds} seconds before trying again.";
            }
        } else {
        
    $email = sanitizeInput($_POST['email'] ?? '');
    if ($email) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT user_id, first_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Generate new random password
            $newPassPlain = bin2hex(random_bytes(4)); // 8-char random
            $hashed = password_hash($newPassPlain, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?')->execute([$hashed, $user['user_id']]);
            // Send e-mail
            $body = '<p>Hello '.$user['first_name'].',</p>';
            $body .= '<p>You requested a password reset. Here is your new temporary password:</p>';
            $body .= '<p style="font-size:18px;font-weight:bold;">'.$newPassPlain.'</p>';
            $body .= '<p>Please log in and change it immediately.</p>';
            sendEmail($email, 'Your new '.SITE_NAME.' password', buildEmailTemplate($body));
        }
        // Always show generic message
        $message = 'If the e-mail exists in our system, a new password has been sent.';
    } else {
        $message = 'Please enter your e-mail address.';
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
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    
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
            background: var(--bg-heavenly, linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 50%, #fff3e0 100%)) !important;
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
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .login-logo h2 {
            color: #000000 !important;
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
            background: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: #000000 !important;
            background-clip: unset !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.2;
            margin: 1rem 0 0.5rem;
        }
        
        .login-logo p {
            color: #444444 !important;
            font-weight: 500;
            font-size: 1rem;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            letter-spacing: 0.3px;
            margin-bottom: 0;
        }
        
        .login-form {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #e0e0e0 !important;
        }
        
        .login-form h3,
        .login-form h4,
        .login-form h3 {
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
        
        @media (max-width: 576px) {
            .login-form {
                padding: 2rem;
            }
            
            .login-logo h2 {
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
            <p class="text-muted" style="color: #444444 !important; font-size: 1.1rem;">E-GABAY Account Recovery</p>
        </div>
        <div class="login-form">
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
                            <h4 class="text-center mb-4">Enter Your Email</h4>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            <div class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
<script>
// Countdown timer for lockout period
document.addEventListener('DOMContentLoaded', function() {
    const alertDiv = document.querySelector('.alert-info');
    if (alertDiv && alertDiv.textContent.includes('Please wait')) {
        const text = alertDiv.textContent;
        const minutesMatch = text.match(/(\d+) minutes?/);
        const secondsMatch = text.match(/(\d+) seconds?/);
        
        if (minutesMatch || secondsMatch) {
            let totalSeconds = 0;
            if (minutesMatch) totalSeconds += parseInt(minutesMatch[1]) * 60;
            if (secondsMatch) totalSeconds += parseInt(secondsMatch[1]);
            
            const countdown = setInterval(function() {
                totalSeconds--;
                
                if (totalSeconds <= 0) {
                    clearInterval(countdown);
                    alertDiv.innerHTML = 'Lockout period has ended. You may try resetting your password again.';
                    alertDiv.className = 'alert alert-success';
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;
                    const timeStr = minutes > 0 ? `${minutes} minutes and ${seconds} seconds` : `${seconds} seconds`;
                    alertDiv.innerHTML = `Too many password reset attempts. Please wait ${timeStr} before trying again.`;
                }
            }, 1000);
        }
    }
});
</script>
</body>
</html> 