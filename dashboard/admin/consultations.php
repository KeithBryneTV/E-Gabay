<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title
$page_title = 'All Consultations';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Process status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get consultation statistics
$consultation_stats = $consultation->getStatistics();

// Get all consultations
$query = "SELECT cr.*, 
          u1.first_name as student_first_name, u1.last_name as student_last_name,
          u2.first_name as counselor_first_name, u2.last_name as counselor_last_name
          FROM consultation_requests cr
          JOIN users u1 ON cr.student_id = u1.user_id
          LEFT JOIN users u2 ON cr.counselor_id = u2.user_id";

// Add status filter if provided
if (!empty($status_filter)) {
    $query .= " WHERE cr.status = :status";
}

$query .= " ORDER BY cr.created_at DESC";

$stmt = $db->prepare($query);

// Bind status parameter if needed
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}

$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all counselors for assignment
$query = "SELECT u.user_id, u.first_name, u.last_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.role_id 
          WHERE r.role_name = 'counselor' AND u.is_active = 1
          ORDER BY u.last_name, u.first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle consultation actions (assign, update, delete)
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign':
                // Assign counselor to consultation
                $consultation_id = (int)$_POST['consultation_id'];
                $counselor_id = (int)$_POST['counselor_id'];
                
                if ($consultation->updateStatus($consultation_id, 'pending', $counselor_id)) {
                    setMessage('Counselor assigned successfully.', 'success');
                } else {
                    setMessage('Failed to assign counselor.', 'danger');
                }
                break;
                
            case 'update':
                // Update consultation status
                $consultation_id = (int)$_POST['consultation_id'];
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
                $consultation_id = (int)$_POST['consultation_id'];
                
                // Check if consultation exists
                $consultation_data = $consultation->getRequestById($consultation_id);
                
                if ($consultation_data) {
                    // Use the Consultation class method to safely delete the consultation
                    if ($consultation->deleteConsultation($consultation_id)) {
                        setMessage('Consultation and all related data deleted successfully.', 'success');
                    } else {
                        setMessage('Failed to delete consultation. Please try again or contact the system administrator.', 'danger');
                    }
                } else {
                    setMessage('Consultation not found.', 'danger');
                }
                break;
        }
        
        // Redirect to refresh the page and prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . (!empty($status_filter) ? "?status=$status_filter" : ""));
        exit;
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">All Consultations</h1>
        <p class="lead">Manage consultation requests across the system.</p>
    </div>
</div>

<!-- Dashboard Stats -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Total Consultations</h6>
                        <h2 class="mb-0"><?php echo $consultation_stats['total_consultations']; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Active</h6>
                        <h2 class="mb-0"><?php echo $consultation_stats['active_consultations']; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-comments fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-success bg-opacity-75">
                <a class="small text-white stretched-link" href="?status=live">View Active</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Pending</h6>
                        <h2 class="mb-0"><?php echo $consultation_stats['pending_consultations']; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-warning bg-opacity-75">
                <a class="small text-white stretched-link" href="?status=pending">View Pending</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Completed</h6>
                        <h2 class="mb-0"><?php echo $consultation_stats['completed_consultations']; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-info bg-opacity-75">
                <a class="small text-white stretched-link" href="?status=completed">View Completed</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Status Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter by Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fas fa-th-list me-2"></i>All
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        <i class="fas fa-clock me-2"></i>Pending
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=live" class="btn <?php echo $status_filter === 'live' ? 'btn-success' : 'btn-outline-success'; ?>">
                        <i class="fas fa-comments me-2"></i>Active
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-info' : 'btn-outline-info'; ?>">
                        <i class="fas fa-check-circle me-2"></i>Completed
                    </a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        <i class="fas fa-times-circle me-2"></i>Cancelled
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consultations Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-clipboard-list me-2"></i>Consultation Requests</h5>
        <?php if (!empty($consultations)): ?>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="exportBtn">
                    <i class="fas fa-file-export me-1"></i> Export
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="printBtn">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($consultations)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                <p class="lead text-muted">No consultations found<?php echo !empty($status_filter) ? ' with status "' . ucfirst($status_filter) . '"' : ''; ?>.</p>
                <?php if (!empty($status_filter)): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">
                        <i class="fas fa-th-list me-2"></i>View All Consultations
                    </a>
                <?php else: ?>
                    <p class="text-muted">Add new consultations through the student portal.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="consultationsTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Counselor</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td><?php echo $consultation['id']; ?></td>
                                <td><?php echo formatDate($consultation['preferred_date']) . ' ' . formatTime($consultation['preferred_time']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($consultation['is_anonymous']) && $consultation['is_anonymous'] == 1) {
                                        echo '<span class="text-muted"><i class="fas fa-user-secret me-1"></i>Anonymous</span>';
                                    } else {
                                        echo $consultation['student_first_name'] . ' ' . $consultation['student_last_name'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo !empty($consultation['issue_category']) ? $consultation['issue_category'] : '<span class="text-muted">Uncategorized</span>'; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($consultation['counselor_id']) {
                                        echo $consultation['counselor_first_name'] . ' ' . $consultation['counselor_last_name'];
                                    } else {
                                        echo '<span class="text-muted"><i class="fas fa-user-plus me-1"></i>Not Assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $consultation['communication_method'])); ?></td>
                                <td>
                                    <?php
                                    switch ($consultation['status']) {
                                        case 'pending':
                                            echo '<span class="badge bg-warning">Pending</span>';
                                            break;
                                        case 'live':
                                            echo '<span class="badge bg-success">Active</span>';
                                            break;
                                        case 'completed':
                                            echo '<span class="badge bg-info">Completed</span>';
                                            break;
                                        case 'cancelled':
                                            echo '<span class="badge bg-danger">Cancelled</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatDate($consultation['created_at'], 'M d, Y'); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/dashboard/admin/view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (!$consultation['counselor_id'] && $consultation['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success assign-counselor-btn"
                                                    data-bs-toggle="modal" data-bs-target="#assignCounselorModal"
                                                    data-consultation-id="<?php echo $consultation['id']; ?>"
                                                    data-student-name="<?php echo $consultation['student_first_name'] . ' ' . $consultation['student_last_name']; ?>">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-warning update-status-btn"
                                                data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                                                data-consultation-id="<?php echo $consultation['id']; ?>"
                                                data-current-status="<?php echo $consultation['status']; ?>"
                                                data-notes="<?php echo htmlspecialchars($consultation['counselor_notes'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-consultation-btn"
                                                data-bs-toggle="modal" data-bs-target="#deleteConsultationModal"
                                                data-consultation-id="<?php echo $consultation['id']; ?>"
                                                data-student-name="<?php echo $consultation['student_first_name'] . ' ' . $consultation['student_last_name']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Assign Counselor Modal -->
<div class="modal fade" id="assignCounselorModal" tabindex="-1" aria-labelledby="assignCounselorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="assignCounselorModalLabel"><i class="fas fa-user-plus me-2"></i>Assign Counselor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (!empty($status_filter) ? "?status=$status_filter" : ""); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="consultation_id" id="assign_consultation_id">
                    
                    <p>Assign a counselor to the consultation for <strong id="assign_student_name"></strong>.</p>
                    
                    <div class="mb-3">
                        <label for="counselor_id" class="form-label">Select Counselor</label>
                        <select class="form-select" id="counselor_id" name="counselor_id" required>
                            <option value="">Select Counselor</option>
                            <?php foreach ($counselors as $counselor): ?>
                                <option value="<?php echo $counselor['user_id']; ?>">
                                    <?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Counselor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="updateStatusModalLabel"><i class="fas fa-edit me-2"></i>Update Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (!empty($status_filter) ? "?status=$status_filter" : ""); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="consultation_id" id="update_consultation_id">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="live">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="counselor_notes" class="form-label">Counselor Notes</label>
                        <textarea class="form-control" id="counselor_notes" name="counselor_notes" rows="5" placeholder="Add notes about this consultation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Consultation Modal -->
<div class="modal fade" id="deleteConsultationModal" tabindex="-1" aria-labelledby="deleteConsultationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConsultationModalLabel"><i class="fas fa-trash me-2"></i>Delete Consultation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (!empty($status_filter) ? "?status=$status_filter" : ""); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="consultation_id" id="delete_consultation_id">
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> You are about to delete the consultation for <strong id="delete_student_name"></strong>.
                    </div>
                    
                    <p>This action will permanently remove all consultation data from the system. This cannot be undone.</p>
                    
                    <p class="fw-bold">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable with advanced features
    $('#consultationsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-sm btn-success me-1',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger me-1',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-sm btn-info',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            }
        ]
    });
    
    // Populate assign counselor modal
    const assignCounselorBtns = document.querySelectorAll('.assign-counselor-btn');
    assignCounselorBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const consultationId = this.getAttribute('data-consultation-id');
            const studentName = this.getAttribute('data-student-name');
            
            document.getElementById('assign_consultation_id').value = consultationId;
            document.getElementById('assign_student_name').textContent = studentName || 'Student';
        });
    });
    
    // Populate update status modal
    const updateStatusBtns = document.querySelectorAll('.update-status-btn');
    updateStatusBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const consultationId = this.getAttribute('data-consultation-id');
            const currentStatus = this.getAttribute('data-current-status');
            const notes = this.getAttribute('data-notes');
            
            document.getElementById('update_consultation_id').value = consultationId;
            document.getElementById('status').value = currentStatus || 'pending';
            document.getElementById('counselor_notes').value = notes || '';
        });
    });
    
    // Populate delete consultation modal
    const deleteConsultationBtns = document.querySelectorAll('.delete-consultation-btn');
    deleteConsultationBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const consultationId = this.getAttribute('data-consultation-id');
            const studentName = this.getAttribute('data-student-name');
            
            document.getElementById('delete_consultation_id').value = consultationId;
            document.getElementById('delete_student_name').textContent = studentName || 'Student';
        });
    });
    
    // Export button functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        const table = $('#consultationsTable').DataTable();
        table.button('.buttons-excel').trigger();
    });
    
    // Print button functionality
    document.getElementById('printBtn').addEventListener('click', function() {
        const table = $('#consultationsTable').DataTable();
        table.button('.buttons-print').trigger();
    });
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 