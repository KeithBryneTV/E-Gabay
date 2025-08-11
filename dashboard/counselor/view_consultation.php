<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/auth.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';
require_once $base_path . '/classes/Chat.php';
require_once $base_path . '/includes/utility.php';
// Include counselor availability helper
require_once $base_path . '/includes/counselor_availability_helper.php';

// Check if user is logged in and has counselor role
requireRole('counselor');

// Set page title
$page_title = 'View Consultation';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Check if consultation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('Invalid consultation ID. Please select a consultation from the list.', 'danger');
    header('Location: ' . SITE_URL . '/dashboard/counselor/consultations.php');
    exit;
}

$consultation_id = (int)$_GET['id'];

// Get consultation details
$query = "SELECT cr.*, 
          u.first_name as student_first_name, u.last_name as student_last_name,
          u.email as student_email
          FROM consultation_requests cr
          JOIN users u ON cr.student_id = u.user_id
          WHERE cr.id = ? AND cr.counselor_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id, $user_id]);
$consultation_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if consultation exists and belongs to the counselor
if (!$consultation_data) {
    setMessage('Consultation not found or you do not have permission to view it.', 'danger');
    header('Location: ' . SITE_URL . '/dashboard/counselor/consultations.php');
    exit;
}

// Get student profile
$query = "SELECT sp.* FROM student_profiles sp
          JOIN users u ON sp.user_id = u.user_id
          WHERE u.user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_data['student_id']]);
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get chat sessions related to this consultation
$query = "SELECT * FROM chat_sessions WHERE consultation_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id]);
$chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get feedback if available
$query = "SELECT f.*, u.first_name, u.last_name
          FROM feedback f
          JOIN users u ON f.student_id = u.user_id
          WHERE f.consultation_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

// After a consultation is marked as completed, check if student has provided feedback
if ($consultation_data['status'] === 'completed') {
    // Check if feedback exists
    $query = "SELECT COUNT(*) as count FROM feedback WHERE consultation_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_id]);
    $feedback_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // If no feedback, send notification to student
    if ($feedback_count == 0) {
        // Check if notification has already been sent
        $query = "SELECT COUNT(*) as count FROM notifications 
                  WHERE reference_id = ? AND type = 'feedback_request'";
        $stmt = $db->prepare($query);
        $stmt->execute([$consultation_id]);
        $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($notification_count == 0) {
            // Add notification for student
            $message = "Please provide feedback for your completed consultation session.";
            
            $query = "INSERT INTO notifications (user_id, message, type, reference_id) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_data['student_id'], $message, 'feedback_request', $consultation_id]);
        }
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $status = sanitizeInput($_POST['status']);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Update consultation status
                if ($consultation->updateStatus($consultation_id, $status, $user_id, $notes)) {
                    setMessage('Consultation status updated successfully.', 'success');
                    
                    // Redirect using full URL to avoid form resubmission
                    header('Location: ' . SITE_URL . '/dashboard/counselor/view_consultation.php?id=' . $consultation_id);
                    exit;
                } else {
                    setMessage('Failed to update consultation status.', 'danger');
                }
                break;
                
            case 'schedule_consultation':
                $scheduled_date = $_POST['scheduled_date'];
                $scheduled_time = $_POST['scheduled_time'];
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Update consultation with scheduled date and time (separate from student's preferred)
                $query = "UPDATE consultation_requests 
                          SET scheduled_date = ?, scheduled_time = ?, counselor_notes = ? 
                          WHERE id = ? AND counselor_id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$scheduled_date, $scheduled_time, $notes, $consultation_id, $user_id])) {
                    setMessage('Consultation scheduled successfully.', 'success');
                    
                    // Redirect to avoid form resubmission
                    redirect(rtrim(SITE_URL, '/') . '/dashboard/counselor/view_consultation.php?id=' . $consultation_id);
                    exit;
                } else {
                    setMessage('Failed to schedule consultation.', 'danger');
                }
                break;
                
            case 'add_notes':
                $notes = sanitizeInput($_POST['counselor_notes']);
                
                // Update consultation notes
                $query = "UPDATE consultation_requests SET counselor_notes = ? WHERE id = ? AND counselor_id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$notes, $consultation_id, $user_id])) {
                    setMessage('Notes updated successfully.', 'success');
                    
                    // Redirect to avoid form resubmission
                    header('Location: ' . SITE_URL . '/dashboard/counselor/view_consultation.php?id=' . $consultation_id);
                    exit;
                } else {
                    setMessage('Failed to update notes.', 'danger');
                }
                break;
        }
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb & Action Buttons -->
<div class="row mb-4">
        <div class="col-md-6">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/consultations.php">Consultations</a></li>
                <li class="breadcrumb-item active">Consultation #<?php echo $consultation_data['id']; ?></li>
            </ol>
            <h1 class="mt-2">
                <i class="fas fa-clipboard-list me-2"></i>
                Consultation Details
            </h1>
            <p class="lead text-muted">View and manage consultation information</p>
    </div>
        <div class="col-md-6 text-md-end d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
            <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/consultations.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
            
            <?php if ($consultation_data['status'] === 'pending' || $consultation_data['status'] === 'live'): ?>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                    <i class="fas fa-edit me-2"></i>Update Status
                </button>
            <?php endif; ?>
        </div>
</div>

    <!-- Status Badge -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-0 text-muted">Status:</h6>
                    <?php
                    switch ($consultation_data['status']) {
                        case 'pending':
                                    echo '<span class="badge bg-warning fs-6">Pending</span>';
                            break;
                        case 'live':
                                    echo '<span class="badge bg-success fs-6">Active</span>';
                            break;
                        case 'completed':
                                    echo '<span class="badge bg-info fs-6">Completed</span>';
                            break;
                        case 'cancelled':
                                    echo '<span class="badge bg-danger fs-6">Cancelled</span>';
                            break;
                    }
                    ?>
                        </div>
                        <div class="ms-auto">
                            <span class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Created: <?php echo formatDate($consultation_data['created_at'], 'M d, Y h:i A'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Main Content Column -->
        <div class="col-lg-8">
            <!-- Consultation Information -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Consultation Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-calendar me-2"></i>Schedule</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Student's Preferred Date</p>
                                        <p class="fw-bold"><?php echo formatDate($consultation_data['preferred_date']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Student's Preferred Time</p>
                                        <p class="fw-bold"><?php echo formatTime($consultation_data['preferred_time']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($consultation_data['scheduled_date']) && !empty($consultation_data['scheduled_time'])): ?>
                                <div class="row mt-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Scheduled Date</p>
                                        <p class="fw-bold text-success"><?php echo formatDate($consultation_data['scheduled_date']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Scheduled Time</p>
                                        <p class="fw-bold text-success"><?php echo formatTime($consultation_data['scheduled_time']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <p class="mb-1 text-muted small">Communication Method</p>
                                        <p class="fw-bold">
                    <?php
                                            $method = ucfirst(str_replace('_', ' ', $consultation_data['communication_method']));
                                            $icon = '';
                                            switch($consultation_data['communication_method']) {
                                                case 'chat':
                                                    $icon = 'comments';
                            break;
                                                case 'video_call':
                                                    $icon = 'video';
                            break;
                                                case 'in_person':
                                                    $icon = 'user-friends';
                            break;
                        default:
                                                    $icon = 'phone';
                                            }
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> me-1"></i> <?php echo $method; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-tag me-2"></i>Category Details</h6>
                                <div class="row">
                                    <div class="col-12">
                                        <p class="mb-1 text-muted small">Issue Category</p>
                                        <p class="fw-bold">
                                            <?php if (!empty($consultation_data['issue_category'])): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo $consultation_data['issue_category']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Uncategorized</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Anonymous Request</p>
                                        <p class="fw-bold">
                                            <?php if ($consultation_data['is_anonymous']): ?>
                                                <i class="fas fa-user-secret me-1 text-warning"></i> Yes
                                            <?php else: ?>
                                                <i class="fas fa-user me-1"></i> No
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Last Updated</p>
                                        <p class="fw-bold"><?php echo formatDate($consultation_data['updated_at'], 'M d, Y'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Issue Description -->
                    <div class="border rounded p-3 mt-3">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="fas fa-comment-alt me-2"></i>
                            Issue Description
                        </h6>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($consultation_data['issue_description'])); ?>
                        </div>
                    </div>
                    
                    <!-- Counselor Notes -->
                    <?php if (!empty($consultation_data['counselor_notes'])): ?>
                    <div class="border rounded p-3 mt-3">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="fas fa-clipboard me-2"></i>
                            Your Notes
                        </h6>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($consultation_data['counselor_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Actions -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments me-2 text-info"></i>
                        Communication
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($consultation_data['status'] === 'pending' || $consultation_data['status'] === 'live'): ?>
                        <div class="d-flex gap-2">
                            <?php if ($consultation_data['communication_method'] === 'chat'): ?>
                                <?php if (empty($chat_sessions)): ?>
                                    <button type="button" class="btn btn-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#startChatModal">
                                        <i class="fas fa-comments me-2"></i> Start Chat Session
                                    </button>
                                <?php else: ?>
                            <?php
                                    $active_chat = null;
                                    foreach ($chat_sessions as $chat) {
                                        if ($chat['status'] === 'active') {
                                            $active_chat = $chat;
                                    break;
                                }
                            }
                            ?>
                            
                                    <?php if ($active_chat): ?>
                                        <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?session_id=<?php echo $active_chat['id']; ?>" class="btn btn-success flex-grow-1">
                                            <i class="fas fa-comments me-2"></i> Continue Chat Session
                                </a>
                            <?php else: ?>
                                        <button type="button" class="btn btn-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#startChatModal">
                                            <i class="fas fa-comments me-2"></i> Start New Chat Session
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                <i class="fas fa-calendar-alt me-2"></i> Schedule Meeting
                            </button>
                        </div>

                        <?php if (!empty($consultation_data['scheduled_date']) && !empty($consultation_data['scheduled_time'])): ?>
                            <div class="alert alert-info mt-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="alert-heading">Scheduled Meeting</h5>
                                        <p class="mb-0">
                                            Date: <strong><?php echo formatDate($consultation_data['scheduled_date']); ?></strong> at
                                            <strong><?php echo formatTime($consultation_data['scheduled_time']); ?></strong>
                                        </p>
                                        <?php if (!empty($consultation_data['scheduled_link'])): ?>
                                            <p class="mb-0 mt-2">
                                                Link: <a href="<?php echo $consultation_data['scheduled_link']; ?>" target="_blank" class="alert-link">
                                                    <?php echo $consultation_data['scheduled_link']; ?>
                                                </a>
                                            </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Chat Sessions List -->
                    <?php if (!empty($chat_sessions)): ?>
                        <h6 class="fw-bold mt-4 mb-3">Chat History</h6>
                        <div class="list-group">
                            <?php foreach ($chat_sessions as $chat): ?>
                                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?session_id=<?php echo $chat['id']; ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-comments me-2 text-info"></i>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($chat['subject']); ?></h6>
                                        </div>
                                        <small class="text-muted">Created: <?php echo formatDate($chat['created_at'], 'M d, Y h:i A'); ?></small>
                                    </div>
                                    <div>
                                        <?php if ($chat['status'] === 'active'): ?>
                                            <span class="badge bg-success rounded-pill px-3">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3">Closed</span>
                                        <?php endif; ?>
                                        <i class="fas fa-chevron-right ms-2"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                    </div>
                <?php endif; ?>
        </div>
    </div>
    
            <!-- Feedback Information (if available) -->
            <?php if ($feedback): ?>
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2 text-warning"></i>
                        Student Feedback
                    </h5>
            </div>
            <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2">Rating</h6>
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $feedback['rating']) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="fw-bold"><?php echo $feedback['rating']; ?>/5</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Submitted: <?php echo formatDate($feedback['created_at'], 'M d, Y h:i A'); ?>
                            </span>
            </div>
        </div>
        
                    <div class="border rounded p-3">
                        <h6 class="fw-bold mb-2">Comments</h6>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($feedback['comments'])); ?>
                        </div>
                    </div>
                </div>
            </div>
                <?php endif; ?>
        </div>
        
        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <!-- Student Information -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-graduate me-2 text-success"></i>
                        Student Information
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if ($consultation_data['is_anonymous']): ?>
                            <div class="avatar-circle bg-warning mx-auto">
                                <i class="fas fa-user-secret fa-2x text-white"></i>
            </div>
                            <h5 class="mt-3">Anonymous</h5>
                            <p class="text-muted">This student requested anonymity</p>
                        <?php else: ?>
                            <img src="<?php echo getUserAvatarUrl($consultation_data['student_id']); ?>" alt="Student Avatar" class="rounded-circle img-thumbnail mx-auto" style="width: 100px; height: 100px;">
                            <h5 class="mt-3"><?php echo $consultation_data['student_first_name'] . ' ' . $consultation_data['student_last_name']; ?></h5>
                            <p class="text-muted"><?php echo $consultation_data['student_email']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($student_profile && !$consultation_data['is_anonymous']): ?>
                    <div class="border rounded p-3 text-start">
                        <div class="row g-2">
                            <div class="col-6">
                                <p class="mb-1 text-muted small">Student ID</p>
                                <p class="fw-bold"><?php echo $student_profile['student_id']; ?></p>
                            </div>
                            <div class="col-6">
                                <p class="mb-1 text-muted small">Year Level</p>
                                <p class="fw-bold"><?php echo $student_profile['year_level']; ?></p>
                            </div>
                            <div class="col-12">
                                <p class="mb-1 text-muted small">Course</p>
                                <p class="fw-bold"><?php echo $student_profile['course']; ?></p>
                            </div>
                            <div class="col-12">
                                <p class="mb-1 text-muted small">Section</p>
                                <p class="fw-bold"><?php echo $student_profile['section']; ?></p>
                            </div>
                                </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
            <!-- Update Status Form -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2 text-warning"></i>
                        Update Status
                    </h5>
            </div>
            <div class="card-body">
                    <form action="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation_id; ?>" method="post">
                        <input type="hidden" name="action" value="update_status">
                        
                    <div class="mb-3">
                            <label for="status" class="form-label">Consultation Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $consultation_data['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="live" <?php echo $consultation_data['status'] === 'live' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $consultation_data['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $consultation_data['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Counselor Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Add notes about this consultation..."><?php echo $consultation_data['counselor_notes']; ?></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i> Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    <i class="fas fa-edit me-2"></i>Update Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation_id; ?>" method="post">
                <input type="hidden" name="action" value="update_status">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_status" class="form-label">Consultation Status</label>
                        <select class="form-select" id="modal_status" name="status" required>
                            <option value="pending" <?php echo $consultation_data['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="live" <?php echo $consultation_data['status'] === 'live' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $consultation_data['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $consultation_data['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_notes" class="form-label">Counselor Notes</label>
                        <textarea class="form-control" id="modal_notes" name="notes" rows="4" placeholder="Add notes about this consultation..."><?php echo $consultation_data['counselor_notes']; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Start Chat Modal -->
<div class="modal fade" id="startChatModal" tabindex="-1" aria-labelledby="startChatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="startChatModalLabel">
                    <i class="fas fa-comments me-2"></i>Start Chat Session
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation_id; ?>" method="post">
                <input type="hidden" name="action" value="start_chat">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="chat_subject" class="form-label">Chat Subject</label>
                        <input type="text" class="form-control" id="chat_subject" name="subject" required placeholder="Enter a brief subject for this chat session">
                    </div>
                    
                    <div class="mb-3">
                        <label for="chat_message" class="form-label">Initial Message (Optional)</label>
                        <textarea class="form-control" id="chat_message" name="message" rows="3" placeholder="Type your initial message to the student..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-comments me-2"></i>Start Chat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Meeting Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="scheduleModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Schedule Meeting
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation_id; ?>" method="post">
                <input type="hidden" name="action" value="schedule_consultation">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required 
                                   value="<?php echo $consultation_data['scheduled_date'] ?? $consultation_data['preferred_date']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" required
                                   value="<?php echo $consultation_data['scheduled_time'] ?? $consultation_data['preferred_time']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="scheduled_link" class="form-label">Meeting Link (Optional)</label>
                        <input type="url" class="form-control" id="scheduled_link" name="scheduled_link" placeholder="Enter video call link"
                               value="<?php echo $consultation_data['scheduled_link'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="scheduled_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="scheduled_notes" name="scheduled_notes" rows="3" 
                                  placeholder="Add any additional details about the meeting"><?php echo $consultation_data['scheduled_notes'] ?? ''; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-calendar-check me-2"></i>Schedule Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php
// Include existing modals and footer
include_once $base_path . '/includes/footer.php';
?> 