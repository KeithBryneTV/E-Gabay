<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$page_title = 'My Consultations';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Process status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get consultations
$query = "SELECT cr.*, 
          u.first_name as counselor_first_name, u.last_name as counselor_last_name
          FROM consultation_requests cr
          LEFT JOIN users u ON cr.counselor_id = u.user_id
          WHERE cr.student_id = ?";

// Add status filter if provided
if (!empty($status_filter)) {
    $query .= " AND cr.status = ?";
}

$query .= " ORDER BY cr.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($status_filter)) {
    $stmt->execute([$user_id, $status_filter]);
} else {
    $stmt->execute([$user_id]);
}

$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        // Cancel consultation
        $consultation_id = (int)$_POST['consultation_id'];
        
        // Check if consultation belongs to the student and is in pending status
        $query = "SELECT * FROM consultation_requests 
                  WHERE id = ? AND student_id = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute([$consultation_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update status to cancelled
            $query = "UPDATE consultation_requests SET status = 'cancelled' WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$consultation_id])) {
                setMessage('Consultation request cancelled successfully.', 'success');
            } else {
                setMessage('Failed to cancel consultation request.', 'danger');
            }
        } else {
            setMessage('You can only cancel pending consultation requests.', 'danger');
        }
        
        // Redirect to refresh the page and prevent form resubmission
        redirect($_SERVER['PHP_SELF'] . (!empty($status_filter) ? "?status=$status_filter" : ""));
        exit;
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">My Consultations</h1>
        <p class="lead">View and manage your consultation requests.</p>
    </div>
</div>

<!-- Status Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Filter by Status</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        All
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Pending
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=live" class="btn <?php echo $status_filter === 'live' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Active
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Completed
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Cancelled
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Consultation Button -->
<div class="row mb-4">
    <div class="col-12">
        <a href="<?php echo SITE_URL; ?>/dashboard/student/request_consultation.php" class="btn btn-success">
            <i class="fas fa-plus-circle me-2"></i> Request New Consultation
        </a>
    </div>
</div>

<!-- Consultations Table -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-clipboard-list me-1"></i>
        Consultation Requests
    </div>
    <div class="card-body">
        <?php if (empty($consultations)): ?>
            <p class="text-center">No consultation requests found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="consultationsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Counselor</th>
                            <th>Method</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td><?php echo formatDate($consultation['preferred_date']); ?></td>
                                <td><?php echo formatTime($consultation['preferred_time']); ?></td>
                                <td>
                                    <?php 
                                    if ($consultation['counselor_id']) {
                                        echo $consultation['counselor_first_name'] . ' ' . $consultation['counselor_last_name'];
                                    } else {
                                        echo '<span class="text-muted">Not Assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $consultation['communication_method'])); ?></td>
                                <td><?php echo !empty($consultation['issue_category']) ? $consultation['issue_category'] : 'Uncategorized'; ?></td>
                                <td>
                                    <?php
                                    switch ($consultation['status']) {
                                        case 'pending':
                                            echo '<span class="badge bg-primary">Pending</span>';
                                            break;
                                        case 'live':
                                            echo '<span class="badge bg-success">Active</span>';
                                            break;
                                        case 'completed':
                                            echo '<span class="badge bg-warning">Completed</span>';
                                            break;
                                        case 'cancelled':
                                            echo '<span class="badge bg-danger">Cancelled</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/dashboard/student/view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($consultation['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-danger cancel-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#cancelModal"
                                                    data-consultation-id="<?php echo $consultation['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($consultation['status'] === 'live'): ?>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/student/chat.php?consultation_id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($consultation['status'] === 'completed' && !hasFeedback($consultation['id'], $user_id)): ?>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/student/feedback.php?id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-star"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancel Consultation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (!empty($status_filter) ? "?status=$status_filter" : ""); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="consultation_id" id="cancel_consultation_id">
                    
                    <p>Are you sure you want to cancel this consultation request?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                    <button type="submit" class="btn btn-danger">Yes, Cancel It</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#consultationsTable').DataTable({
        order: [[0, 'desc'], [1, 'desc']]
    });
    
    // Populate cancel modal
    const cancelBtns = document.querySelectorAll('.cancel-btn');
    cancelBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('cancel_consultation_id').value = this.dataset.consultationId;
        });
    });
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 