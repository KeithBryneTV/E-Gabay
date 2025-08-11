<?php
class Database {
    // Database credentials
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }
    
    // Get database connection
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
            
            // Set database timezone to Philippine time
            $this->conn->exec("SET time_zone = '+08:00'");
        } catch(PDOException $exception) {
            // Log error for debugging but don't expose database details to users
            error_log("Database connection error: " . $exception->getMessage());
            
            // In production, don't echo database errors directly
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                // Don't echo anything in production
            } else {
                echo "Connection error: " . $exception->getMessage();
            }
        }
        
        return $this->conn;
    }
    
    // Execute a query with parameters
    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("PDO query error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get a single record
    public function getRecord($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
    
    // Get multiple records
    public function getRecords($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
    
    // Insert a record and return the ID
    public function insert($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            return false;
        }
    }
    
    // Update a record
    public function update($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete a record
    public function delete($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }
}
?> 