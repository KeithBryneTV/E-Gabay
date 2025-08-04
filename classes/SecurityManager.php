<?php
class SecurityManager {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Check if IP or username is currently blocked
     */
    public function isBlocked($ip_address, $username = null) {
        $current_time = date('Y-m-d H:i:s');
        
        // Check IP-based blocking
        $query = "SELECT blocked_until FROM login_attempts 
                  WHERE ip_address = ? AND blocked_until > ? 
                  ORDER BY blocked_until DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ip_address, $current_time]);
        $ip_block = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ip_block) {
            return [
                'blocked' => true,
                'type' => 'ip',
                'until' => $ip_block['blocked_until']
            ];
        }
        
        // Check username-based blocking if username provided
        if ($username) {
            $query = "SELECT blocked_until FROM login_attempts 
                      WHERE username = ? AND blocked_until > ? 
                      ORDER BY blocked_until DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username, $current_time]);
            $user_block = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_block) {
                return [
                    'blocked' => true,
                    'type' => 'account',
                    'until' => $user_block['blocked_until']
                ];
            }
        }
        
        return ['blocked' => false];
    }
    
    /**
     * Get recent failed login attempts count
     */
    public function getRecentFailedAttempts($ip_address, $username = null, $time_window = 3600) {
        $since_time = date('Y-m-d H:i:s', time() - $time_window);
        
        // Count IP-based attempts
        $query = "SELECT COUNT(*) as count FROM login_attempts 
                  WHERE ip_address = ? AND success = FALSE AND attempted_at >= ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ip_address, $since_time]);
        $ip_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count username-based attempts
        $user_attempts = 0;
        if ($username) {
            $query = "SELECT COUNT(*) as count FROM login_attempts 
                      WHERE username = ? AND success = FALSE AND attempted_at >= ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username, $since_time]);
            $user_attempts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
        return [
            'ip_attempts' => $ip_attempts,
            'user_attempts' => $user_attempts,
            'total' => max($ip_attempts, $user_attempts)
        ];
    }
    
    /**
     * Calculate lockout duration based on attempt count
     */
    public function calculateLockoutDuration($attempt_count) {
        // Progressive lockout: 30s, 2m, 5m, 15m, 30m, 1h, 2h, 4h, 8h, 24h
        $lockout_times = [
            1 => 30,      // 30 seconds
            2 => 120,     // 2 minutes  
            3 => 300,     // 5 minutes
            4 => 900,     // 15 minutes
            5 => 1800,    // 30 minutes
            6 => 3600,    // 1 hour
            7 => 7200,    // 2 hours
            8 => 14400,   // 4 hours
            9 => 28800,   // 8 hours
            10 => 86400   // 24 hours
        ];
        
        if ($attempt_count >= 10) {
            return 86400; // 24 hours max
        }
        
        return $lockout_times[$attempt_count] ?? 30;
    }
    
    /**
     * Record login attempt
     */
    public function recordLoginAttempt($ip_address, $username, $success, $user_agent = null) {
        // Clean old attempts (older than 24 hours)
        $this->cleanOldAttempts();
        
        $blocked_until = null;
        
        if (!$success) {
            $attempts = $this->getRecentFailedAttempts($ip_address, $username);
            $attempt_count = $attempts['total'] + 1;
            
            if ($attempt_count >= 3) {
                $lockout_duration = $this->calculateLockoutDuration($attempt_count);
                $blocked_until = date('Y-m-d H:i:s', time() + $lockout_duration);
            }
        }
        
        // Record the attempt
        $query = "INSERT INTO login_attempts (ip_address, username, success, user_agent, blocked_until) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ip_address, $username, $success, $user_agent, $blocked_until]);
        
        // Log security event
        $this->logSecurityEvent(
            null, 
            $ip_address, 
            $success ? 'login_success' : 'login_failed',
            "Username: $username, Attempts: " . ($attempts['total'] ?? 0 + 1),
            $success ? 'LOW' : ($attempt_count >= 5 ? 'HIGH' : 'MEDIUM')
        );
        
        return [
            'blocked_until' => $blocked_until,
            'attempt_count' => $attempts['total'] ?? 0 + 1
        ];
    }
    
    /**
     * Clear successful login attempts
     */
    public function clearAttempts($ip_address, $username) {
        $query = "DELETE FROM login_attempts 
                  WHERE (ip_address = ? OR username = ?) AND success = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ip_address, $username]);
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($user_id, $ip_address, $action, $details = '', $risk_level = 'LOW') {
        $query = "INSERT INTO security_logs (user_id, ip_address, action, details, risk_level) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $ip_address, $action, $details, $risk_level]);
    }
    
    /**
     * Clean old login attempts (older than 24 hours)
     */
    private function cleanOldAttempts() {
        $cleanup_time = date('Y-m-d H:i:s', time() - 86400); // 24 hours ago
        
        $query = "DELETE FROM login_attempts WHERE attempted_at < ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$cleanup_time]);
    }
    
    /**
     * Get time remaining for block
     */
    public function getBlockTimeRemaining($blocked_until) {
        $current_time = time();
        $block_time = strtotime($blocked_until);
        
        return max(0, $block_time - $current_time);
    }
    
    /**
     * Check if CAPTCHA should be required
     */
    public function requiresCaptcha($ip_address, $username = null) {
        $attempts = $this->getRecentFailedAttempts($ip_address, $username, 1800); // 30 minutes
        return $attempts['total'] >= 2; // Require CAPTCHA after 2 failed attempts
    }
    
    /**
     * Validate simple CAPTCHA
     */
    public function validateCaptcha($user_answer, $session_answer) {
        return isset($_SESSION['captcha_answer']) && 
               strtolower(trim($user_answer)) === strtolower(trim($session_answer));
    }
    
    /**
     * Generate simple math CAPTCHA
     */
    public function generateMathCaptcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operations = ['+', '-'];
        $operation = $operations[array_rand($operations)];
        
        switch($operation) {
            case '+':
                $answer = $num1 + $num2;
                break;
            case '-':
                $answer = $num1 - $num2;
                break;
        }
        
        $_SESSION['captcha_answer'] = $answer;
        
        return [
            'question' => "$num1 $operation $num2 = ?",
            'answer' => $answer
        ];
    }
}
?> 