<?php
class Consultation {
    private $conn;
    private $table = "consultation_requests";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create a new consultation request
    public function createRequest($student_id, $issue_description, $preferred_date, $preferred_time, $communication_method, $is_anonymous = 0, $issue_category = null, $counselor_id = null) {
        $query = "INSERT INTO " . $this->table . " 
                 (student_id, issue_description, issue_category, preferred_date, preferred_time, communication_method, is_anonymous, counselor_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $student_id);
        $stmt->bindParam(2, $issue_description);
        $stmt->bindParam(3, $issue_category);
        $stmt->bindParam(4, $preferred_date);
        $stmt->bindParam(5, $preferred_time);
        $stmt->bindParam(6, $communication_method);
        $stmt->bindParam(7, $is_anonymous);
        $stmt->bindParam(8, $counselor_id);
        
        if($stmt->execute()) {
            $newId = $this->conn->lastInsertId();

            // Send system notifications
            if (!function_exists('addSystemNotification')) {
                require_once __DIR__ . '/../includes/utility.php';
            }

            // Use global email template builder

            // Notify admins
            $adminLink = SITE_URL . '/dashboard/admin/view_consultation.php?id=' . $newId;
            addRoleBroadcastNotification('A new consultation request has been submitted.', 'admin', 'info', 'consultation', $adminLink);

            // Notify counselor(s)
            if ($counselor_id) {
                // Specific counselor assigned on creation
                $counselorLink = SITE_URL . '/dashboard/counselor/view_consultation.php?id=' . $newId;
                addSystemNotification($counselor_id, 'A new consultation has been assigned to you.', 'info', 'consultation', $counselorLink);
            } else {
                // Broadcast to all counselors if none assigned yet
                addRoleBroadcastNotification('A new consultation request is awaiting assignment.', 'counselor', 'info', 'consultation', SITE_URL . '/dashboard/counselor/consultations.php');
            }

            return $newId;
        }
        
        return false;
    }
    
    // Get all consultation requests
    public function getAllRequests($limit = 0, $offset = 0) {
        $query = "SELECT cr.*, 
                  u1.first_name as student_first_name, u1.last_name as student_last_name,
                  u2.first_name as counselor_first_name, u2.last_name as counselor_last_name
                  FROM " . $this->table . " cr
                  JOIN users u1 ON cr.student_id = u1.user_id
                  LEFT JOIN users u2 ON cr.counselor_id = u2.user_id
                  ORDER BY cr.created_at DESC";
        
        if($limit > 0) {
            $query .= " LIMIT " . $offset . ", " . $limit;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get consultation requests by student ID
    public function getRequestsByStudent($student_id, $limit = 0, $offset = 0) {
        $query = "SELECT cr.*, 
                  u2.first_name as counselor_first_name, u2.last_name as counselor_last_name
                  FROM " . $this->table . " cr
                  LEFT JOIN users u2 ON cr.counselor_id = u2.user_id
                  WHERE cr.student_id = ?
                  ORDER BY cr.created_at DESC";
        
        if($limit > 0) {
            $query .= " LIMIT " . $offset . ", " . $limit;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get consultation requests by counselor ID
    public function getRequestsByCounselor($counselor_id, $limit = 0, $offset = 0) {
        $query = "SELECT cr.*, 
                  u1.first_name as student_first_name, u1.last_name as student_last_name
                  FROM " . $this->table . " cr
                  JOIN users u1 ON cr.student_id = u1.user_id
                  WHERE cr.counselor_id = ?
                  ORDER BY cr.created_at DESC";
        
        if($limit > 0) {
            $query .= " LIMIT " . $offset . ", " . $limit;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $counselor_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get consultation request by ID
    public function getRequestById($id) {
        $query = "SELECT cr.*, 
                  u1.first_name as student_first_name, u1.last_name as student_last_name, u1.email as student_email,
                  u2.first_name as counselor_first_name, u2.last_name as counselor_last_name, u2.email as counselor_email
                  FROM " . $this->table . " cr
                  JOIN users u1 ON cr.student_id = u1.user_id
                  LEFT JOIN users u2 ON cr.counselor_id = u2.user_id
                  WHERE cr.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update consultation request status
    public function updateStatus($id, $status, $counselor_id = null, $counselor_notes = null) {
        // Get current data first
        $current = $this->getRequestById($id);
        if (!$current) {
            return false;
        }

        $query = "UPDATE " . $this->table . " 
                  SET status = ?";
        
        $params = [$status];
        
        if($counselor_id !== null) {
            $query .= ", counselor_id = ?";
            $params[] = $counselor_id;
        }
        
        if($counselor_notes !== null) {
            $query .= ", counselor_notes = ?";
            $params[] = $counselor_notes;
        }
        
        $query .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->conn->prepare($query);
        
        for($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        
        $result = $stmt->execute();

        if ($result) {
            // Include utility functions for notifications
            if (!function_exists('addSystemNotification')) {
                require_once __DIR__ . '/../includes/utility.php';
            }

            $student_id = $current['student_id'];
            $assigned_counselor_id = $counselor_id ?? $current['counselor_id'];
            $consultationLinkStudent = SITE_URL . '/dashboard/student/view_consultation.php?id=' . $id;
            $consultationLinkCounselor = SITE_URL . '/dashboard/counselor/view_consultation.php?id=' . $id;
            $adminLink = SITE_URL . '/dashboard/admin/view_consultation.php?id=' . $id;

            switch ($status) {
                case 'live':
                    // Notify student about approval
                    addSystemNotification($student_id, 'Your consultation request has been approved.', 'success', 'consultation', $consultationLinkStudent);
                    // Notify counselor about assignment
                    if ($assigned_counselor_id) {
                        addSystemNotification($assigned_counselor_id, 'A new consultation has been assigned to you.', 'info', 'consultation', $consultationLinkCounselor);
                    }
                    // Email student
                    $chatLink = SITE_URL . '/dashboard/student/chat.php?id=' . $current['id'];
                    $when   = '';
                    if (!empty($current['preferred_date']) && !empty($current['preferred_time'])) {
                        $when = '<p><strong>Scheduled for:</strong> '.formatDate($current['preferred_date'],'M d, Y').' at '.formatTime($current['preferred_time'],'h:i A').'</p>';
                    }
                    $body  = '<p>Your consultation request has been <strong>approved</strong>.</p>'.$when;
                    $body .= '<p>You can now start chatting with your counselor by clicking the button below:</p>';
                    $body .= '<p style="text-align:center;margin:20px 0;"><a href="'.$chatLink.'" style="background:#0d6efd;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Open Chat</a></p>';
                    sendEmail($current['student_email'], 'Consultation Approved', buildEmailTemplate($body));
                    if ($assigned_counselor_id) {
                        sendEmail($current['counselor_email'], 'New Consultation Assigned', buildEmailTemplate('A new consultation has been assigned to you.'));
                    }
                    break;
                case 'pending':
                    if ($assigned_counselor_id) {
                        // New counselor assigned while still pending
                        addSystemNotification($assigned_counselor_id, 'A consultation has been assigned to you and awaits approval.', 'info', 'consultation', $consultationLinkCounselor);
                    }
                    addRoleBroadcastNotification('Consultation status updated to pending.', 'admin', 'info', 'consultation', $adminLink);
                    break;
                case 'completed':
                    // Notify student consultation completed
                    addSystemNotification($student_id, 'Your consultation has been marked completed.', 'info', 'consultation', $consultationLinkStudent);
                    // Notify counselor
                    if ($assigned_counselor_id) {
                        addSystemNotification($assigned_counselor_id, 'Consultation marked completed.', 'info', 'consultation', $consultationLinkCounselor);
                    }
                    sendEmail($current['student_email'], 'Consultation Completed', buildEmailTemplate('Your consultation has been marked completed.'));
                    if ($assigned_counselor_id) {
                        sendEmail($current['counselor_email'], 'Consultation Completed', buildEmailTemplate('You have marked the consultation completed.'));
                    }
                    addRoleBroadcastNotification('A consultation has been completed.', 'admin', 'success', 'consultation', $adminLink);
                    break;

                case 'cancelled':
                    // Notify student cancelled
                    addSystemNotification($student_id, 'Your consultation request has been cancelled.', 'danger', 'consultation', $consultationLinkStudent);
                    // Notify counselor if exists
                    if ($assigned_counselor_id) {
                        addSystemNotification($assigned_counselor_id, 'Consultation has been cancelled.', 'danger', 'consultation', $consultationLinkCounselor);
                    }
                    sendEmail($current['student_email'], 'Consultation Cancelled', buildEmailTemplate('Your consultation request has been cancelled.'));
                    if ($assigned_counselor_id) {
                        sendEmail($current['counselor_email'], 'Consultation Cancelled', buildEmailTemplate('A consultation assigned to you was cancelled.'));
                    }
                    addRoleBroadcastNotification('A consultation has been cancelled.', 'admin', 'danger', 'consultation', $adminLink);
                    break;
            }
        }

        return $result;
    }
    
    // Accept a consultation request
    public function acceptRequest($id, $counselor_id) {
        return $this->updateStatus($id, 'live', $counselor_id);
    }
    
    // Complete a consultation request
    public function completeRequest($id, $counselor_notes = null) {
        return $this->updateStatus($id, 'completed', null, $counselor_notes);
    }
    
    // Cancel a consultation request
    public function cancelRequest($id) {
        return $this->updateStatus($id, 'cancelled');
    }
    
    // Get consultation statistics
    public function getStatistics() {
        $query = "SELECT 
                  COUNT(*) as total_consultations,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_consultations,
                  SUM(CASE WHEN status = 'live' THEN 1 ELSE 0 END) as active_consultations,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_consultations,
                  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_consultations
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure all keys exist with default values
        $defaults = [
            'total_consultations' => 0,
            'pending_consultations' => 0,
            'active_consultations' => 0,
            'completed_consultations' => 0,
            'cancelled_consultations' => 0
        ];
        
        return array_merge($defaults, $result ? $result : []);
    }

    // Delete a consultation and all related data
    public function deleteConsultation($id) {
        try {
            // Start transaction for safe deletion
            $this->conn->beginTransaction();
            
            // 1. Delete related chat sessions and messages
            $query = "SELECT id FROM chat_sessions WHERE consultation_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($chat_sessions as $chat) {
                // Delete messages for this chat
                $delete_messages = "DELETE FROM chat_messages WHERE chat_id = ?";
                $stmt = $this->conn->prepare($delete_messages);
                $stmt->execute([$chat['id']]);
            }
            
            // Delete all chat sessions for this consultation
            $delete_chats = "DELETE FROM chat_sessions WHERE consultation_id = ?";
            $stmt = $this->conn->prepare($delete_chats);
            $stmt->execute([$id]);
            
            // 2. Delete related feedback if any
            $delete_feedback = "DELETE FROM feedback WHERE consultation_id = ?";
            $stmt = $this->conn->prepare($delete_feedback);
            $stmt->execute([$id]);
            
            // 3. Delete notifications related to this consultation
            $delete_notifications = "DELETE FROM notifications WHERE type LIKE 'consultation_%' AND reference_id = ?";
            $stmt = $this->conn->prepare($delete_notifications);
            $stmt->execute([$id]);
            
            // 4. Finally delete the consultation
            $delete_consultation = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($delete_consultation);
            $stmt->execute([$id]);
            
            // Commit the transaction
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            // Roll back the transaction if something failed
            $this->conn->rollBack();
            error_log("Error deleting consultation: " . $e->getMessage());
            return false;
        }
    }
}
?> 