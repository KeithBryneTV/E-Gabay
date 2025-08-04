<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';
require_once $base_path . '/classes/Chat.php';
// Include counselor availability helper
require_once $base_path . '/includes/counselor_availability_helper.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title
$page_title = 'View Consultation';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Check if consultation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('Invalid consultation ID.', 'danger');
    redirect(SITE_URL . '/dashboard/admin/consultations.php');
    exit;
}

$consultation_id = (int)$_GET['id'];

// Get consultation details
$consultation_data = $consultation->getRequestById($consultation_id);

// Check if consultation exists
if (!$consultation_data) {
    setMessage('Consultation not found.', 'danger');
    redirect(SITE_URL . '/dashboard/admin/consultations.php');
    exit;
}

// Get student details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_data['student_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get student profile
$query = "SELECT * FROM student_profiles WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_data['student_id']]);
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get counselor details if assigned
$counselor = null;
$counselor_profile = null;
if ($consultation_data['counselor_id']) {
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_data['counselor_id']]);
    $counselor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get counselor profile
    $query = "SELECT * FROM counselor_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_data['counselor_id']]);
    $counselor_profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all counselors for assignment
$query = "SELECT u.user_id, u.first_name, u.last_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.role_id 
          WHERE r.role_name = 'counselor' AND u.is_active = 1
          ORDER BY u.last_name, u.first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get feedback if available
$query = "SELECT * FROM feedback WHERE consultation_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

// Get chat sessions related to this consultation
$query = "SELECT * FROM chat_sessions WHERE consultation_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id]);
$chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle consultation actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_counselor':
                // Assign counselor to consultation
                $counselor_id = (int)$_POST['counselor_id'];
                
                if ($consultation->updateStatus($consultation_id, 'pending', $counselor_id)) {
                    setMessage('Counselor assigned successfully.', 'success');
                } else {
                    setMessage('Failed to assign counselor.', 'danger');
                }
                break;
                
            case 'update_status':
                // Update consultation status
                $status = $_POST['status'];
                $counselor_notes = sanitizeInput($_POST['counselor_notes'] ?? '');
                
                if ($consultation->updateStatus($consultation_id, $status, null, $counselor_notes)) {
                    setMessage('Consultation status updated successfully.', 'success');
                } else {
                    setMessage('Failed to update consultation status.', 'danger');
                }
                break;
                
            case 'delete':
                // Delete consultation
                if ($consultation_data) {
                    try {
                        // Start transaction
                        $db->beginTransaction();
                        
                        // First get all chat sessions related to this consultation
                        $query = "SELECT id FROM chat_sessions WHERE consultation_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$consultation_id]);
                        $chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Delete chat messages for each chat session
                        foreach ($chat_sessions as $session) {
                            $query = "DELETE FROM chat_messages WHERE chat_id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$session['id']]);
                        }
                        
                        // Now delete related chat sessions
                        $query = "DELETE FROM chat_sessions WHERE consultation_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$consultation_id]);
                        
                        // Now delete any related feedback
                        $query = "DELETE FROM feedback WHERE consultation_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$consultation_id]);
                        
                        // Then delete consultation
                        $query = "DELETE FROM consultation_requests WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$consultation_id]);
                        
                        // Commit transaction
                        $db->commit();
                        
                        setMessage('Consultation deleted successfully.', 'success');
                        redirect(SITE_URL . '/dashboard/admin/consultations.php');
                        exit;
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $db->rollBack();
                        error_log("Delete consultation error: " . $e->getMessage());
                        setMessage('Failed to delete consultation: ' . $e->getMessage(), 'danger');
                    }
                } else {
                    setMessage('Consultation not found.', 'danger');
                }
                break;
        }
        
        // Redirect to refresh the page and prevent form resubmission
        redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div class="mb-3 mb-md-0">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/admin/">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php">Consultations</a></li>
                        <li class="breadcrumb-item active">Consultation #<?php echo $consultation_data['id']; ?></li>
                    </ol>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        Consultation #<?php echo $consultation_data['id']; ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php
                        switch ($consultation_data['status']) {
                            case 'pending':
                                echo '<span class="badge bg-warning rounded-pill me-2">Pending Review</span>';
                                break;
                            case 'live':
                                echo '<span class="badge bg-success rounded-pill me-2">Active Session</span>';
                                break;
                            case 'completed':
                                echo '<span class="badge bg-info rounded-pill me-2">Completed</span>';
                                break;
                            case 'cancelled':
                                echo '<span class="badge bg-danger rounded-pill me-2">Cancelled</span>';
                                break;
                        }
                        ?>
                        Created <?php echo formatDate($consultation_data['created_at'], 'M d, Y h:i A'); ?>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    
                    <?php if ($consultation_data['status'] === 'pending' || $consultation_data['status'] === 'live'): ?>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            <i class="fas fa-edit me-1"></i>Edit Status
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConsultationModal">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Info Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Student Info Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <?php if ($consultation_data['is_anonymous']): ?>
                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-user-secret fa-lg text-white"></i>
                            </div>
                            <h6 class="mt-2 mb-1">Anonymous Student</h6>
                            <small class="text-muted">Privacy Protected</small>
                        <?php else: ?>
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width: 60px; height: 60px;">
                                <i class="fas fa-user-graduate fa-lg"></i>
                            </div>
                            <h6 class="mt-2 mb-1"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                            <small class="text-muted"><?php echo $student_profile['student_id'] ?? 'N/A'; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Communication Method Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <?php 
                        $method = $consultation_data['communication_method'];
                        $method_info = [
                            'chat' => ['icon' => 'comments', 'color' => 'info', 'label' => 'Live Chat'],
                            'video_call' => ['icon' => 'video', 'color' => 'success', 'label' => 'Video Call'],
                            'in_person' => ['icon' => 'user-friends', 'color' => 'warning', 'label' => 'In Person'],
                            'phone_call' => ['icon' => 'phone', 'color' => 'primary', 'label' => 'Phone Call']
                        ];
                        $info = $method_info[$method] ?? ['icon' => 'phone', 'color' => 'secondary', 'label' => ucfirst($method)];
                        ?>
                        <div class="bg-<?php echo $info['color']; ?> rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width: 60px; height: 60px;">
                            <i class="fas fa-<?php echo $info['icon']; ?> fa-lg"></i>
                        </div>
                        <h6 class="mt-2 mb-1"><?php echo $info['label']; ?></h6>
                        <small class="text-muted">Communication Method</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schedule Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width: 60px; height: 60px;">
                            <i class="fas fa-calendar-alt fa-lg"></i>
                        </div>
                        <h6 class="mt-2 mb-1"><?php echo formatDate($consultation_data['preferred_date'], 'M d, Y'); ?></h6>
                        <small class="text-muted"><?php echo formatTime($consultation_data['preferred_time']); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Counselor Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <?php if ($counselor): ?>
                            <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width: 60px; height: 60px;">
                                <i class="fas fa-user-tie fa-lg"></i>
                            </div>
                            <h6 class="mt-2 mb-1"><?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?></h6>
                            <small class="text-muted">Assigned Counselor</small>
                        <?php else: ?>
                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width: 60px; height: 60px;">
                                <i class="fas fa-user-plus fa-lg"></i>
                            </div>
                            <h6 class="mt-2 mb-1">Unassigned</h6>
                            <small class="text-muted">No Counselor</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="row g-4">
        <!-- Left Column - Main Details -->
        <div class="col-lg-8">
            <!-- Issue Description -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comment-alt me-2 text-primary"></i>
                        Issue Description
                    </h5>
                </div>
                <div class="card-body">
                    <div class="p-4 bg-light rounded-3">
                        <?php echo nl2br(htmlspecialchars($consultation_data['issue_description'])); ?>
                    </div>
                    
                    <?php if (!empty($consultation_data['issue_category'])): ?>
                    <div class="mt-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                            <i class="fas fa-tag me-1"></i>
                            <?php echo $consultation_data['issue_category']; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Counselor Notes -->
            <?php if (!empty($consultation_data['counselor_notes'])): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard me-2 text-success"></i>
                        Counselor Notes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="p-4 bg-light rounded-3">
                        <?php echo nl2br(htmlspecialchars($consultation_data['counselor_notes'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Chat Sessions -->
            <?php if (!empty($chat_sessions)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments me-2 text-info"></i>
                        Chat Sessions (<?php echo count($chat_sessions); ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($chat_sessions as $chat): ?>
                            <a href="<?php echo SITE_URL; ?>/dashboard/admin/view_chat.php?id=<?php echo $chat['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-comments text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($chat['subject']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo formatDate($chat['created_at'], 'M d, Y h:i A'); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <?php if ($chat['status'] === 'active'): ?>
                                        <span class="badge bg-success rounded-pill me-2">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill me-2">Closed</span>
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Student Feedback -->
            <?php if ($feedback): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2 text-warning"></i>
                        Student Feedback
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
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
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?php echo formatDate($feedback['created_at'], 'M d, Y h:i A'); ?>
                        </small>
                    </div>
                    
                    <div class="p-4 bg-light rounded-3">
                        <?php echo nl2br(htmlspecialchars($feedback['comments'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Sidebar -->
        <div class="col-lg-4">
            <!-- Detailed Student Info -->
            <?php if (!$consultation_data['is_anonymous'] && $student_profile): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-graduate me-2 text-primary"></i>
                        Student Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center text-white mb-2" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-graduate fa-2x"></i>
                        </div>
                        <h6 class="mb-1"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                        <small class="text-muted"><?php echo $student['email']; ?></small>
                    </div>
                    
                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted mb-1">Student ID</div>
                                <div class="fw-bold"><?php echo $student_profile['student_id']; ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted mb-1">Year Level</div>
                                <div class="fw-bold"><?php echo $student_profile['year_level']; ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted mb-1">Course</div>
                                <div class="fw-bold"><?php echo $student_profile['course']; ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 bg-light rounded">
                                <div class="text-muted mb-1">Section</div>
                                <div class="fw-bold"><?php echo $student_profile['section']; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php?action=view&id=<?php echo $student['user_id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-external-link-alt me-1"></i>View Full Profile
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Detailed Counselor Info -->
            <?php if ($counselor): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-tie me-2 text-info"></i>
                        Counselor Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center text-white mb-2" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-tie fa-2x"></i>
                        </div>
                        <h6 class="mb-1"><?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?></h6>
                        <small class="text-muted"><?php echo $counselor['email']; ?></small>
                    </div>
                    
                    <?php if ($counselor_profile): ?>
                    <div class="p-3 bg-light rounded mb-3">
                        <div class="text-muted mb-1 small">Specialization</div>
                        <div class="fw-bold"><?php echo $counselor_profile['specialization']; ?></div>
                    </div>
                    
                    <div class="p-3 bg-light rounded mb-3">
                        <div class="text-muted mb-1 small">Availability</div>
                        <?php if (!empty($counselor_profile['availability'])): ?>
                            <div class="small"><?php echo formatAvailabilityDisplay($counselor_profile['availability']); ?></div>
                        <?php else: ?>
                            <div class="text-muted small">No availability set</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php?action=view&id=<?php echo $counselor['user_id']; ?>" class="btn btn-outline-info btn-sm w-100">
                            <i class="fas fa-external-link-alt me-1"></i>View Full Profile
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Assign Counselor Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2 text-warning"></i>
                        Assign Counselor
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center text-warning mb-2" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-plus fa-lg"></i>
                        </div>
                        <p class="text-muted mb-0">No counselor assigned yet</p>
                    </div>
                    
                    <?php if ($consultation_data['status'] === 'pending'): ?>
                    <button class="btn btn-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#assignCounselorModal">
                        <i class="fas fa-user-plus me-1"></i>Assign Counselor
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Timeline Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2 text-secondary"></i>
                        Timeline
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <div class="fw-bold small">Request Created</div>
                                <div class="text-muted small"><?php echo formatDate($consultation_data['created_at'], 'M d, Y h:i A'); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($counselor): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <div class="fw-bold small">Counselor Assigned</div>
                                <div class="text-muted small"><?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($consultation_data['status'] === 'live'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="fw-bold small">Session Started</div>
                                <div class="text-muted small">Consultation is now active</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($consultation_data['status'] === 'completed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <div class="fw-bold small">Session Completed</div>
                                <div class="text-muted small"><?php echo formatDate($consultation_data['updated_at'], 'M d, Y h:i A'); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($feedback): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <div class="fw-bold small">Feedback Received</div>
                                <div class="text-muted small"><?php echo formatDate($feedback['created_at'], 'M d, Y h:i A'); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    padding-left: 15px;
}

.card {
    transition: transform 0.1s ease, box-shadow 0.1s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

@media (max-width: 768px) {
    .timeline {
        padding-left: 20px;
    }
    
    .timeline-marker {
        left: -13px;
        width: 12px;
        height: 12px;
    }
}</style>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 