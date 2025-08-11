<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/includes/utility.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';

// Check if user is logged in and has counselor role
requireRole('counselor');

// Set page title
$page_title = 'My Consultations';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Get status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'live', 'completed', 'cancelled'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'accept_consultation':
                $consultation_id = (int)$_POST['consultation_id'];
                if ($consultation->updateStatus($consultation_id, 'live', $user_id)) {
                    setMessage('Consultation accepted successfully.', 'success');
                } else {
                    setMessage('Failed to accept consultation.', 'danger');
                }
                break;
                
            case 'complete_consultation':
                $consultation_id = (int)$_POST['consultation_id'];
                if ($consultation->updateStatus($consultation_id, 'completed')) {
                    setMessage('Consultation marked as completed.', 'success');
                } else {
                    setMessage('Failed to update consultation status.', 'danger');
                }
                break;
                

        }
    }
}

// Build query based on status filter
$query = "SELECT cr.*, 
          u.first_name, u.last_name, u.email,
          (SELECT COUNT(*) FROM chat_sessions WHERE consultation_id = cr.id AND status = 'active') as has_active_chat
          FROM consultation_requests cr
          JOIN users u ON cr.student_id = u.user_id
          WHERE cr.counselor_id = ?";

if ($status_filter !== 'all') {
    $query .= " AND cr.status = ?";
}

$query .= " ORDER BY 
          CASE 
            WHEN cr.status = 'pending' THEN 1
            WHEN cr.status = 'live' THEN 2
            WHEN cr.status = 'completed' THEN 3
            WHEN cr.status = 'cancelled' THEN 4
          END,
          cr.preferred_date ASC, cr.preferred_time ASC";

// Prepare and execute query
$stmt = $db->prepare($query);
if ($status_filter !== 'all') {
    $stmt->execute([$user_id, $status_filter]);
} else {
    $stmt->execute([$user_id]);
}

$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>My Consultations</h1>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group" role="group">
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pending</a>
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=live" class="btn <?php echo $status_filter === 'live' ? 'btn-primary' : 'btn-outline-primary'; ?>">Active</a>
            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Completed</a>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <?php 
            if ($status_filter === 'all') echo 'All Consultations';
            elseif ($status_filter === 'pending') echo 'Pending Consultations';
            elseif ($status_filter === 'live') echo 'Active Consultations';
            elseif ($status_filter === 'completed') echo 'Completed Consultations';
            elseif ($status_filter === 'cancelled') echo 'Cancelled Consultations';
            ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($consultations)): ?>
            <div class="p-4 text-center">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <p class="lead">No <?php echo $status_filter !== 'all' ? $status_filter : ''; ?> consultations found.</p>
                <?php if ($status_filter !== 'all'): ?>
                    <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php" class="btn btn-outline-primary">View All Consultations</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Issue</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td>#<?php echo $consultation['id']; ?></td>
                                <td>
                                    <?php if ($consultation['is_anonymous']): ?>
                                        <span class="text-muted">Anonymous</span>
                                    <?php else: ?>
                                        <?php echo $consultation['first_name'] . ' ' . $consultation['last_name']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $issue = !empty($consultation['issue_category']) ? 
                                        $consultation['issue_category'] : 
                                        'General Consultation';
                                    echo $issue;
                                    ?>
                                </td>
                                <td>
                                    <?php echo formatDate($consultation['preferred_date'], 'M d, Y'); ?><br>
                                    <small><?php echo formatTime($consultation['preferred_time']); ?></small>
                                </td>
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
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        
                                        <?php if ($consultation['status'] === 'pending'): ?>
                                            
                                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="d-inline">
                                                <input type="hidden" name="action" value="accept_consultation">
                                                <input type="hidden" name="consultation_id" value="<?php echo $consultation['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Are you sure you want to accept this consultation?')">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                            </form>
                                        <?php elseif ($consultation['status'] === 'live'): ?>
                                            <?php if ($consultation['has_active_chat']): ?>
                                                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?consultation_id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-comments"></i> Chat
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?consultation_id=<?php echo $consultation['id']; ?>&start=1" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-comments"></i> Start Chat
                                                </a>
                                            <?php endif; ?>
                                            
                                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="d-inline">
                                                <input type="hidden" name="action" value="complete_consultation">
                                                <input type="hidden" name="consultation_id" value="<?php echo $consultation['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to mark this consultation as completed?')">
                                                    <i class="fas fa-check-circle"></i> Complete
                                                </button>
                                            </form>
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

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 