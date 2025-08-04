<?php
class Auth {
    private $conn;
    private $table = "users";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Login user
    public function login($username, $password) {
        // Prepare query
        $query = "SELECT u.*, r.role_name FROM " . $this->table . " u
                  JOIN roles r ON u.role_id = r.role_id
                  WHERE u.username = ? 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(1, $username);
        
        // Execute query
        $stmt->execute();
        
        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user exists
        if($row) {
            // Verify password
            if(password_verify($password, $row['password'])) {
                
                // Update last login time
                $this->updateLastLogin($row['user_id']);
                
                // Return user data
                return $row;
            }
        }
        
        return false;
    }
    
    // Update last login time
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table . " 
                  SET last_login = NOW() 
                  WHERE user_id = ?";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
    }
    
    // Log user activity
    public function logActivity($user_id, $action, $details = "", $ip_address = "") {
        if(empty($ip_address)) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        $query = "INSERT INTO system_logs 
                 (user_id, action, details, ip_address)
                 VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $action);
        $stmt->bindParam(3, $details);
        $stmt->bindParam(4, $ip_address);
        
        return $stmt->execute();
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check if user has permission
    public static function hasPermission($required_role) {
        if(!self::isLoggedIn()) {
            return false;
        }
        
        $allowed_roles = [];
        
        switch($required_role) {
            case 'student':
                $allowed_roles = ['student', 'counselor', 'admin', 'staff'];
                break;
            case 'counselor':
                $allowed_roles = ['counselor', 'admin', 'staff'];
                break;
            case 'staff':
                $allowed_roles = ['staff', 'admin'];
                break;
            case 'admin':
                $allowed_roles = ['admin'];
                break;
            default:
                return false;
        }
        
        return in_array($_SESSION['role_name'], $allowed_roles);
    }
    
    // Register new user
    public function register($username, $password, $first_name, $last_name, $email, $role_id) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert query
        $query = "INSERT INTO " . $this->table . " 
                 (username, password, first_name, last_name, email, role_id) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(1, $username);
        $stmt->bindParam(2, $hashed_password);
        $stmt->bindParam(3, $first_name);
        $stmt->bindParam(4, $last_name);
        $stmt->bindParam(5, $email);
        $stmt->bindParam(6, $role_id);
        
        // Execute query
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // Change password
    public function changePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " 
                  SET password = ? 
                  WHERE user_id = ?";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $hashed_password);
        $stmt->bindParam(2, $user_id);
        
        return $stmt->execute();
    }
    
    // Get user by ID
    public function getUserById($user_id) {
        $query = "SELECT u.*, r.role_name FROM " . $this->table . " u
                  JOIN roles r ON u.role_id = r.role_id
                  WHERE u.user_id = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get user by username
    public function getUserByUsername($username) {
        $query = "SELECT u.*, r.role_name FROM " . $this->table . " u
                  JOIN roles r ON u.role_id = r.role_id
                  WHERE u.username = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        $query = "SELECT u.*, r.role_name FROM " . $this->table . " u
                  JOIN roles r ON u.role_id = r.role_id
                  WHERE u.email = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 