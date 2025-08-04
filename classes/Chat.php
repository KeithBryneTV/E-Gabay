<?php
class Chat {
    private $conn;
    private $sessions_table = "chat_sessions";
    private $messages_table = "chat_messages";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create a new chat session
    public function createSession($student_id, $counselor_id, $subject, $consultation_id = null) {
        $query = "INSERT INTO " . $this->sessions_table . " 
                 (student_id, counselor_id, subject, consultation_id) 
                 VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $student_id);
        $stmt->bindParam(2, $counselor_id);
        $stmt->bindParam(3, $subject);
        $stmt->bindParam(4, $consultation_id);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // Send a message
    public function sendMessage($chat_id, $user_id, $message, $message_type = 'user', $file_path = null, $file_name = null, $file_size = null) {
        // First check if the chat session exists and is active
        if ($message_type !== 'system') {
            $checkQuery = "SELECT id FROM " . $this->sessions_table . " WHERE id = ? AND status = 'active'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$chat_id]);
            
            if ($checkStmt->rowCount() === 0) {
                return false; // Chat session not found or not active
            }
        }
        
        // Check if the message_type column exists
        $columnExists = false;
        try {
            $checkQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'message_type'";
            $checkStmt = $this->conn->query($checkQuery);
            $columnExists = $checkStmt && $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            // If there's an error, assume the column doesn't exist
            $columnExists = false;
        }
        
        // Check if file attachment columns exist
        $fileColumnsExist = false;
        try {
            $checkFileQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'file_path'";
            $checkFileStmt = $this->conn->query($checkFileQuery);
            $fileColumnsExist = $checkFileStmt && $checkFileStmt->rowCount() > 0;
        } catch (Exception $e) {
            $fileColumnsExist = false;
        }

        // For system messages, we need to make sure user_id can be null
        if ($message_type === 'system' && $columnExists) {
            // Check if user_id column allows NULL
            try {
                $checkNullQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'user_id'";
                $checkNullStmt = $this->conn->query($checkNullQuery);
                $columnInfo = $checkNullStmt->fetch(PDO::FETCH_ASSOC);
                $allowsNull = $columnInfo && strtoupper($columnInfo['Null']) === 'YES';
                
                if (!$allowsNull) {
                    // Alter the table to allow NULL values for user_id
                    $alterQuery = "ALTER TABLE " . $this->messages_table . " MODIFY user_id INT(11) NULL";
                    $this->conn->exec($alterQuery);
                }
            } catch (Exception $e) {
                // If there's an error, continue with the insert anyway
            }
            
            // Insert system message with file support
            if ($fileColumnsExist) {
                $query = "INSERT INTO " . $this->messages_table . " 
                         (chat_id, message, message_type, file_path, file_name, file_size) 
                         VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                $stmt->bindParam(1, $chat_id);
                $stmt->bindParam(2, $message);
                $stmt->bindParam(3, $message_type);
                $stmt->bindParam(4, $file_path);
                $stmt->bindParam(5, $file_name);
                $stmt->bindParam(6, $file_size);
            } else {
                $query = "INSERT INTO " . $this->messages_table . " 
                         (chat_id, message, message_type) 
                         VALUES (?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                $stmt->bindParam(1, $chat_id);
                $stmt->bindParam(2, $message);
                $stmt->bindParam(3, $message_type);
            }
        } else {
            // For user messages or if message_type column doesn't exist
            if ($columnExists && $fileColumnsExist) {
                $query = "INSERT INTO " . $this->messages_table . " 
                         (chat_id, user_id, message, message_type, file_path, file_name, file_size) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                $stmt->bindParam(1, $chat_id);
                $stmt->bindParam(2, $user_id);
                $stmt->bindParam(3, $message);
                $stmt->bindParam(4, $message_type);
                $stmt->bindParam(5, $file_path);
                $stmt->bindParam(6, $file_name);
                $stmt->bindParam(7, $file_size);
            } else if ($columnExists) {
                $query = "INSERT INTO " . $this->messages_table . " 
                         (chat_id, user_id, message, message_type) 
                         VALUES (?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                $stmt->bindParam(1, $chat_id);
                $stmt->bindParam(2, $user_id);
                $stmt->bindParam(3, $message);
                $stmt->bindParam(4, $message_type);
            } else {
                $query = "INSERT INTO " . $this->messages_table . " 
                         (chat_id, user_id, message) 
                         VALUES (?, ?, ?)";
                
                $stmt = $this->conn->prepare($query);
                
                $stmt->bindParam(1, $chat_id);
                $stmt->bindParam(2, $user_id);
                $stmt->bindParam(3, $message);
            }
        }
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // Get messages for a chat session
    public function getMessages($chat_id, $limit = 50, $offset = 0) {
        // First check if the message_type column exists
        $columnExists = false;
        try {
            $checkQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'message_type'";
            $checkStmt = $this->conn->query($checkQuery);
            $columnExists = $checkStmt && $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            // If there's an error, assume the column doesn't exist
            $columnExists = false;
        }
        
        // Check if file attachment columns exist
        $fileColumnsExist = false;
        try {
            $checkFileQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'file_path'";
            $checkFileStmt = $this->conn->query($checkFileQuery);
            $fileColumnsExist = $checkFileStmt && $checkFileStmt->rowCount() > 0;
        } catch (Exception $e) {
            $fileColumnsExist = false;
        }
        
        if ($columnExists && $fileColumnsExist) {
            // Use both message_type and file columns
            $query = "SELECT cm.*, 
                      CASE WHEN cm.message_type = 'system' THEN 'System' ELSE u.first_name END as first_name, 
                      CASE WHEN cm.message_type = 'system' THEN '' ELSE u.last_name END as last_name,
                      CASE WHEN cm.message_type = 'system' THEN 0 ELSE u.role_id END as role_id
                      FROM " . $this->messages_table . " cm
                      LEFT JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chat_id = ?
                      ORDER BY cm.created_at ASC
                      LIMIT ?, ?";
        } elseif ($columnExists) {
            // Use the message_type column in the query
            $query = "SELECT cm.*, 
                      CASE WHEN cm.message_type = 'system' THEN 'System' ELSE u.first_name END as first_name, 
                      CASE WHEN cm.message_type = 'system' THEN '' ELSE u.last_name END as last_name,
                      CASE WHEN cm.message_type = 'system' THEN 0 ELSE u.role_id END as role_id
                      FROM " . $this->messages_table . " cm
                      LEFT JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chat_id = ?
                      ORDER BY cm.created_at ASC
                      LIMIT ?, ?";
        } else {
            // Fallback query without message_type
            $query = "SELECT cm.*, 
                      u.first_name, u.last_name, u.role_id
                      FROM " . $this->messages_table . " cm
                      LEFT JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chat_id = ?
                      ORDER BY cm.created_at ASC
                      LIMIT ?, ?";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $chat_id);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->bindParam(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get new messages since a specific timestamp
    public function getNewMessages($chat_id, $timestamp) {
        // First check if the message_type column exists
        $columnExists = false;
        try {
            $checkQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'message_type'";
            $checkStmt = $this->conn->query($checkQuery);
            $columnExists = $checkStmt && $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            // If there's an error, assume the column doesn't exist
            $columnExists = false;
        }
        
        // Check if file attachment columns exist
        $fileColumnsExist = false;
        try {
            $checkFileQuery = "SHOW COLUMNS FROM " . $this->messages_table . " LIKE 'file_path'";
            $checkFileStmt = $this->conn->query($checkFileQuery);
            $fileColumnsExist = $checkFileStmt && $checkFileStmt->rowCount() > 0;
        } catch (Exception $e) {
            $fileColumnsExist = false;
        }
        
        if ($columnExists && $fileColumnsExist) {
            // Use both message_type and file columns
            $query = "SELECT cm.*, 
                      CASE WHEN cm.message_type = 'system' THEN 'System' ELSE u.first_name END as first_name, 
                      CASE WHEN cm.message_type = 'system' THEN '' ELSE u.last_name END as last_name,
                      CASE WHEN cm.message_type = 'system' THEN 0 ELSE u.role_id END as role_id
                      FROM " . $this->messages_table . " cm
                      LEFT JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chat_id = ? AND cm.created_at > ?
                      ORDER BY cm.created_at ASC";
        } elseif ($columnExists) {
            // Use the message_type column in the query
            $query = "SELECT cm.*, 
                      CASE WHEN cm.message_type = 'system' THEN 'System' ELSE u.first_name END as first_name, 
                      CASE WHEN cm.message_type = 'system' THEN '' ELSE u.last_name END as last_name,
                      CASE WHEN cm.message_type = 'system' THEN 0 ELSE u.role_id END as role_id
                      FROM " . $this->messages_table . " cm
                      LEFT JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chat_id = ? AND cm.created_at > ?
                      ORDER BY cm.created_at ASC";
        } else {
            // Fallback query without message_type
            $query = "SELECT cm.*, 
                      u.first_name, u.last_name, u.role_id
                      FROM " . $this->messages_table . " cm
                      LEFT JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chat_id = ? AND cm.created_at > ?
                      ORDER BY cm.created_at ASC";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $chat_id);
        $stmt->bindParam(2, $timestamp);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user's chat sessions
    public function getUserSessions($user_id, $role, $limit = 10, $offset = 0) {
        $field = ($role == 'student') ? 'student_id' : 'counselor_id';
        
        $query = "SELECT cs.*, 
                  u1.first_name as student_first_name, u1.last_name as student_last_name,
                  u2.first_name as counselor_first_name, u2.last_name as counselor_last_name,
                  (SELECT COUNT(*) FROM " . $this->messages_table . " WHERE chat_id = cs.id) as message_count,
                  (SELECT COUNT(*) FROM " . $this->messages_table . " WHERE chat_id = cs.id AND is_read = 0 AND user_id != ?) as unread_count
                  FROM " . $this->sessions_table . " cs
                  JOIN users u1 ON cs.student_id = u1.user_id
                  JOIN users u2 ON cs.counselor_id = u2.user_id
                  WHERE cs." . $field . " = ?
                  ORDER BY cs.updated_at DESC
                  LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $user_id);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->bindParam(4, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get a chat session by ID
    public function getSessionById($id) {
        $query = "SELECT cs.*, 
                  u1.first_name as student_first_name, u1.last_name as student_last_name,
                  u2.first_name as counselor_first_name, u2.last_name as counselor_last_name,
                  IFNULL(cr.is_anonymous, 0) as is_anonymous
                  FROM " . $this->sessions_table . " cs
                  JOIN users u1 ON cs.student_id = u1.user_id
                  JOIN users u2 ON cs.counselor_id = u2.user_id
                  LEFT JOIN consultation_requests cr ON cs.consultation_id = cr.id
                  WHERE cs.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Close a chat session
    public function closeSession($id) {
        $query = "UPDATE " . $this->sessions_table . " 
                  SET status = 'closed' 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        
        return $stmt->execute();
    }
    
    // Mark messages as read
    public function markMessagesAsRead($chat_id, $user_id) {
        $query = "UPDATE " . $this->messages_table . " 
                  SET is_read = 1 
                  WHERE chat_id = ? AND user_id != ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $chat_id);
        $stmt->bindParam(2, $user_id);
        
        return $stmt->execute();
    }
    
    // Get unread message count for a user
    public function getUnreadCount($user_id, $role) {
        $field = ($role == 'student') ? 'student_id' : 'counselor_id';
        
        $query = "SELECT COUNT(*) as unread_count
                  FROM " . $this->messages_table . " m
                  JOIN " . $this->sessions_table . " s ON m.chat_id = s.id
                  WHERE s." . $field . " = ? AND m.user_id != ? AND m.is_read = 0 AND s.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['unread_count'] : 0;
    }
    
    // Check if a user has access to a chat session
    public function userHasAccess($chat_id, $user_id) {
        $query = "SELECT COUNT(*) as count
                  FROM " . $this->sessions_table . "
                  WHERE id = ? AND (student_id = ? OR counselor_id = ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $chat_id);
        $stmt->bindParam(2, $user_id);
        $stmt->bindParam(3, $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['count'] > 0;
    }
}
?> 