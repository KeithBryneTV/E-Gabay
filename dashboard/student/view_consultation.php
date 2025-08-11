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

// Check if user is logged in and has student role
requireRole('student');

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
    setMessage('Invalid consultation ID.', 'danger');
    redirect(SITE_URL . '/dashboard/student/consultations.php');
    exit;
}

$consultation_id = (int)$_GET['id'];

// Get consultation details
$query = "SELECT cr.*, 
          u.first_name as counselor_first_name, u.last_name as counselor_last_name,
          u.email as counselor_email
          FROM consultation_requests cr
          LEFT JOIN users u ON cr.counselor_id = u.user_id
          WHERE cr.id = ? AND cr.student_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id, $user_id]);
$consultation_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if consultation exists and belongs to the student
if (!$consultation_data) {
    setMessage('Consultation not found or you do not have permission to view it.', 'danger');
    redirect(SITE_URL . '/dashboard/student/consultations.php');
    exit;
}

// Get counselor profile if assigned
$counselor_profile = null;
if ($consultation_data['counselor_id']) {
    $query = "SELECT * FROM counselor_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$consultation_data['counselor_id']]);
    $counselor_profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get chat sessions related to this consultation
$query = "SELECT * FROM chat_sessions WHERE consultation_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id]);
$chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get feedback if available
$query = "SELECT * FROM feedback WHERE consultation_id = ? AND student_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$consultation_id, $user_id]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
        // Submit feedback
        $rating = (int)$_POST['rating'];
        $comments = sanitizeInput($_POST['comments']);
        
        // Validate input
        if ($rating < 1 || $rating > 5) {
            setMessage('Please provide a valid rating (1-5).', 'danger');
        } else {
            // Check if feedback already exists
            if ($feedback) {
                // Update existing feedback
                $query = "UPDATE feedback 
                          SET rating = ?, comments = ? 
                          WHERE consultation_id = ? AND student_id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$rating, $comments, $consultation_id, $user_id])) {
                    setMessage('Feedback updated successfully.', 'success');
                } else {
                    setMessage('Failed to update feedback.', 'danger');
                }
            } else {
                // Insert new feedback
                $query = "INSERT INTO feedback 
                          (consultation_id, student_id, rating, comments) 
                          VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$consultation_id, $user_id, $rating, $comments])) {
                    setMessage('Feedback submitted successfully.', 'success');
                } else {
                    setMessage('Failed to submit feedback.', 'danger');
                }
            }
            
            // Redirect to refresh the page and prevent form resubmission
            redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        // Cancel consultation
        if ($consultation_data['status'] === 'pending') {
            $query = "UPDATE consultation_requests SET status = 'cancelled' WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$consultation_id])) {
                setMessage('Consultation request cancelled successfully.', 'success');
                
                // Redirect to consultations page
                redirect(SITE_URL . '/dashboard/student/consultations.php');
                exit;
            } else {
                setMessage('Failed to cancel consultation request.', 'danger');
            }
        } else {
            setMessage('You can only cancel pending consultation requests.', 'danger');
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
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/student/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/student/consultations.php">Consultations</a></li>
                <li class="breadcrumb-item active">Consultation #<?php echo $consultation_data['id']; ?></li>
            </ol>
            <h1 class="mt-2">
                <i class="fas fa-clipboard-list me-2"></i>
                Consultation Details
            </h1>
            <p class="lead text-muted">View your consultation information and progress</p>
        </div>
        <div class="col-md-6 text-md-end d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
            <a href="<?php echo SITE_URL; ?>/dashboard/student/consultations.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
            
            <?php if ($consultation_data['status'] === 'completed' && !$feedback): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                    <i class="fas fa-star me-2"></i>Provide Feedback
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($consultation_data['status'] === 'completed' && !$feedback): ?>
        <!-- Feedback Reminder Alert -->
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-star-half-alt fa-2x me-3"></i>
                </div>
                <div>
                    <h5 class="alert-heading">Your Feedback is Important!</h5>
                    <p class="mb-0">This consultation has been marked as completed. Please take a moment to provide your feedback about your experience with the counselor.</p>
                    <button class="btn btn-info mt-2" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        <i class="fas fa-star me-1"></i> Provide Feedback Now
                    </button>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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
                                Requested: <?php echo formatDate($consultation_data['created_at'], 'M d, Y h:i A'); ?>
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
                                        <p class="mb-1 text-muted small">Your Preferred Date</p>
                                        <p class="fw-bold"><?php echo formatDate($consultation_data['preferred_date']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Your Preferred Time</p>
                                        <p class="fw-bold"><?php echo formatTime($consultation_data['preferred_time']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($consultation_data['scheduled_date']) && !empty($consultation_data['scheduled_time'])): ?>
                                <div class="row mt-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Confirmed Date</p>
                                        <p class="fw-bold text-success"><?php echo formatDate($consultation_data['scheduled_date']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted small">Confirmed Time</p>
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
                                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-tag me-2"></i>Details</h6>
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
                            Your Issue Description
                        </h6>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($consultation_data['issue_description'])); ?>
                        </div>
                    </div>
                    
                    <!-- Scheduled Meeting -->
                    <?php if (!empty($consultation_data['scheduled_date']) && !empty($consultation_data['scheduled_time'])): ?>
                    <div class="border rounded p-3 mt-3">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="fas fa-calendar-check me-2"></i>
                            Scheduled Meeting
                        </h6>
                        <div class="alert alert-info mb-0">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Date</p>
                                    <p class="fw-bold"><?php echo formatDate($consultation_data['scheduled_date']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Time</p>
                                    <p class="fw-bold"><?php echo formatTime($consultation_data['scheduled_time']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Method</p>
                                    <p class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $consultation_data['communication_method'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($consultation_data['scheduled_link'])): ?>
                            <div class="mt-2 pt-2 border-top">
                                <p class="mb-1 text-muted small">Meeting Link</p>
                                <a href="<?php echo $consultation_data['scheduled_link']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> Join Meeting
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultation_data['scheduled_notes'])): ?>
                            <div class="mt-2 pt-2 border-top">
                                <p class="mb-1 text-muted small">Notes from Counselor</p>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($consultation_data['scheduled_notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Sessions -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments me-2 text-info"></i>
                        Communication
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($chat_sessions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No chat sessions available yet.</p>
                            <p class="small text-muted">Your counselor will start a chat session when they're ready.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($chat_sessions as $chat): ?>
                                <a href="<?php echo SITE_URL; ?>/dashboard/student/chat.php?session_id=<?php echo $chat['id']; ?>" 
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
            
            <!-- Feedback Section -->
            <?php if ($consultation_data['status'] === 'completed'): ?>
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2 text-warning"></i>
                        Feedback
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($feedback): ?>
                        <div class="border rounded p-3">
                            <div class="row align-items-center mb-3">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-2">Your Rating</h6>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $feedback['rating']) {
                                                echo '<i class="fas fa-star text-warning me-1"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning me-1"></i>';
                                            }
                                        }
                                        ?>
                                        <span class="ms-2 fw-bold"><?php echo $feedback['rating']; ?>/5</span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <span class="text-muted">
                                        Submitted: <?php echo formatDate($feedback['created_at'], 'M d, Y'); ?>
                                    </span>
                                    <br>
                                    <button class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                        <i class="fas fa-edit me-1"></i> Edit Feedback
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-2 pt-2 border-top">
                                <h6 class="fw-bold mb-2">Your Comments</h6>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($feedback['comments'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="far fa-star fa-3x text-muted mb-3"></i>
                            <p class="mb-3">Please provide feedback on your consultation experience.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                <i class="fas fa-star me-2"></i> Provide Feedback
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <?php if ($consultation_data['status'] === 'pending' && !$consultation_data['counselor_id']): ?>
            <!-- Awaiting Assignment -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-hourglass-half me-2 text-warning"></i>
                        Awaiting Assignment
                    </h5>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-user-clock fa-4x text-warning opacity-50 mb-3"></i>
                        <h5>Your request is being processed</h5>
                        <p class="text-muted">A counselor will be assigned to your case soon.</p>
                    </div>
                    
                    <div class="alert alert-light border">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            You'll receive a notification once a counselor has been assigned to your consultation request.
                        </small>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Counselor Information -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-tie me-2 text-primary"></i>
                        Assigned Counselor
                    </h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($consultation_data['counselor_id']): ?>
                        <div class="mb-4">
                            <img src="<?php echo getUserAvatarUrl($consultation_data['counselor_id']); ?>" alt="Counselor Avatar" class="rounded-circle img-thumbnail mx-auto" style="width: 100px; height: 100px;">
                            <h5 class="mt-3"><?php echo $consultation_data['counselor_first_name'] . ' ' . $consultation_data['counselor_last_name']; ?></h5>
                            <?php if (!$consultation_data['is_anonymous']): ?>
                                <p class="text-muted"><?php echo $consultation_data['counselor_email']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($counselor_profile): ?>
                        <div class="border rounded p-3 text-start">
                            <?php if (!empty($counselor_profile['specialization'])): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p class="mb-1 text-muted small">Specialization</p>
                                    <p class="fw-bold"><?php echo $counselor_profile['specialization']; ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($counselor_profile['availability'])): ?>
                            <div class="mt-2 pt-2 border-top">
                                <p class="mb-1 text-muted small">Availability Schedule</p>
                                <?php echo formatAvailabilityDisplay($counselor_profile['availability']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($counselor_profile['bio'])): ?>
                            <div class="mt-2 pt-2 border-top">
                                <p class="mb-1 text-muted small">About</p>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($counselor_profile['bio'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="py-4">
                            <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                            <h5>No Counselor Assigned Yet</h5>
                            <p class="text-muted">Your request is still being processed.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Help & Resources -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-life-ring me-2 text-info"></i>
                        Help & Resources
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-question-circle text-primary me-3"></i>
                            <div>
                                <strong>Consultation FAQs</strong>
                                <small class="d-block text-muted">Common questions about the process</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-book text-success me-3"></i>
                            <div>
                                <strong>Student Resources</strong>
                                <small class="d-block text-muted">Helpful information and materials</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-phone text-info me-3"></i>
                            <div>
                                <strong>Emergency Contacts</strong>
                                <small class="d-block text-muted">Important contacts for urgent situations</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="fas fa-star me-2"></i><?php echo $feedback ? 'Edit Feedback' : 'Provide Feedback'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
                <input type="hidden" name="action" value="submit_feedback">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rate your consultation experience</label>
                        <div class="rating-stars mb-2">
                            <div class="d-flex">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="form-check form-check-inline me-3">
                                    <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($feedback && $feedback['rating'] == $i) ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="rating<?php echo $i; ?>">
                                        <i class="fas fa-star text-warning"></i> <?php echo $i; ?>
                                    </label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Share your experience with this consultation..."><?php echo $feedback ? htmlspecialchars($feedback['comments']) : ''; ?></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your feedback helps us improve our counseling services.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Feedback
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
// Include footer
include_once $base_path . '/includes/footer.php';
?> 

<!-- End of page script -->
<script>
// Check if we should show the feedback modal automatically (when redirected from chat)
document.addEventListener('DOMContentLoaded', function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // If show_feedback is set and consultation is completed, open the feedback modal
    if (urlParams.get('show_feedback') === '1' && 
        <?php echo ($consultation_data['status'] === 'completed' ? 'true' : 'false'); ?>) {
        
        // Add a notification banner
        if (!<?php echo $feedback ? 'true' : 'false'; ?>) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show';
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5>Your chat session has been ended</h5>
                        <p class="mb-0">Please take a moment to provide feedback on your consultation experience.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Insert at the top of the content
            const container = document.querySelector('.container-fluid');
            if (container) {
                container.insertBefore(notification, container.firstChild);
            }
            
            // Show the feedback modal with slight delay
            setTimeout(() => {
                const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
                feedbackModal.show();
            }, 500);
        }
    }
});
</script> 