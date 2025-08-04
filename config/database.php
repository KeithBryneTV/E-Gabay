<?php
// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'egabay_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create a mysqli connection for backward compatibility with some functions
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    error_log("MySQLi connection failed: " . mysqli_connect_error());
    die("Database connection error. Please contact the system administrator.");
}

/**
 * Execute a query safely with error handling
 * 
 * @param string $query SQL query to execute
 * @return mysqli_result|bool Query result or false on failure
 */
function executeQuery($query) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection not available");
        return false;
    }
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Query error: " . mysqli_error($conn));
    }
    
    return $result;
}

/**
 * Execute a prepared statement with PDO
 * 
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return PDOStatement|bool Statement object or false on failure
 */
function executeQueryPDO($query, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("PDO query error: " . $e->getMessage());
        return false;
    }
}
?> 