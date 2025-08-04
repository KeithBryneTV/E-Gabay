<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';

// Check if user is logged in and has admin role
requireRole('admin');

// Set page title
$page_title = 'Manage Consultations';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $consultation_id = (int)$_POST['consultation_id'];
    
    if ($_POST['action'] === 'delete') {
        try {
            $db->beginTransaction();
            
            // First, get consultation details for logging
            $query = "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as student_name
                     FROM consultation_requests cr
                     LEFT JOIN users u ON cr.student_id = u.user_id
                     WHERE cr.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_id]);
            $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$consultation) {
                throw new Exception('Consultation not found');
            }
            
            // Delete in proper order to handle foreign keys
            
            // 1. Delete chat messages first
            $query = "DELETE cm FROM chat_messages cm 
                     JOIN chat_sessions cs ON cm.chat_id = cs.id 
                     WHERE cs.consultation_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_id]);
            $deletedMessages = $stmt->rowCount();
            
            // 2. Delete chat sessions
            $query = "DELETE FROM chat_sessions WHERE consultation_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_id]);
            $deletedChats = $stmt->rowCount();
            
            // 3. Delete feedback
            $query = "DELETE FROM feedback WHERE consultation_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_id]);
            $deletedFeedback = $stmt->rowCount();
            
            // 4. Delete notifications related to this consultation
            $query = "DELETE FROM notifications WHERE reference_id = ? AND type = 'consultation'";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_id]);
            $deletedNotifications = $stmt->rowCount();
            
            // 5. Finally delete the consultation
            $query = "DELETE FROM consultation_requests WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$consultation_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to delete consultation');
            }
            
            $db->commit();
            
            // Log the deletion
            $logMessage = "Deleted consultation #{$consultation_id} for student {$consultation['student_name']}. ";
            $logMessage .= "Removed: {$deletedMessages} messages, {$deletedChats} chat sessions, {$deletedFeedback} feedback, {$deletedNotifications} notifications.";
            
            // Log to system logs if table exists
            try {
                $logQuery = "INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, 'delete_consultation', ?, ?)";
                $logStmt = $db->prepare($logQuery);
                $logStmt->execute([$_SESSION['user_id'], $logMessage, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            } catch (Exception $e) {
                // Ignore if system_logs table doesn't exist
            }
            
            setMessage("Consultation #{$consultation_id} and all related data have been successfully deleted.", 'success');
            
        } catch (Exception $e) {
            $db->rollBack();
            setMessage('Error deleting consultation: ' . $e->getMessage(), 'danger');
        }
        
        // Redirect to prevent resubmission
        redirect($_SERVER['PHP_SELF']);
        exit;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "cr.status = ?";
    $params[] = $status_filter;
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(cr.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "cr.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "cr.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'old':
            $where_conditions[] = "cr.created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
               FROM consultation_requests cr
               LEFT JOIN users s ON cr.student_id = s.user_id
               LEFT JOIN users c ON cr.counselor_id = c.user_id
               {$where_clause}";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Get consultations with related data
$query = "SELECT cr.*,
          CONCAT(s.first_name, ' ', s.last_name) as student_name,
          s.email as student_email,
          CONCAT(c.first_name, ' ', c.last_name) as counselor_name,
          cs.id as chat_id,
          cs.status as chat_status,
          (SELECT COUNT(*) FROM chat_messages cm JOIN chat_sessions css ON cm.chat_id = css.id WHERE css.consultation_id = cr.id) as message_count,
          (SELECT COUNT(*) FROM feedback f WHERE f.consultation_id = cr.id) as feedback_count
          FROM consultation_requests cr
          LEFT JOIN users s ON cr.student_id = s.user_id
          LEFT JOIN users c ON cr.counselor_id = c.user_id
          LEFT JOIN chat_sessions cs ON cr.id = cs.consultation_id
          {$where_clause}
          ORDER BY cr.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);

// Bind parameters for WHERE conditions
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index++, $param);
}

// Explicitly bind LIMIT and OFFSET as integers
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);

$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-cogs me-2"></i>Manage Consultations</h1>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php" class="btn btn-outline-primary">
            <i class="fas fa-list me-1"></i> View All Consultations
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="live" <?php echo $status_filter === 'live' ? 'selected' : ''; ?>>Live</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Date Range</label>
                <select name="date" id="date" class="form-select">
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="old" <?php echo $date_filter === 'old' ? 'selected' : ''; ?>>Older than 3 months</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-undo me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary"><?php echo $total_records; ?></h5>
                <p class="card-text">Total Consultations</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <?php
                $pending_count = $db->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'pending'")->fetch()['count'];
                ?>
                <h5 class="card-title text-warning"><?php echo $pending_count; ?></h5>
                <p class="card-text">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <?php
                $live_count = $db->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'live'")->fetch()['count'];
                ?>
                <h5 class="card-title text-success"><?php echo $live_count; ?></h5>
                <p class="card-text">Live</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <?php
                $completed_count = $db->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'completed'")->fetch()['count'];
                ?>
                <h5 class="card-title text-info"><?php echo $completed_count; ?></h5>
                <p class="card-text">Completed</p>
            </div>
        </div>
    </div>
</div>

<!-- Consultations Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Consultations
            <span class="badge bg-secondary ms-2"><?php echo $total_records; ?> total</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($consultations)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>No consultations found</h5>
                <p class="text-muted">Try adjusting your filters or check back later.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Counselor</th>
                            <th>Issue</th>
                            <th>Status</th>
                            <th>Chat</th>
                            <th>Messages</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td>#<?php echo $consultation['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($consultation['student_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($consultation['student_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($consultation['counselor_name']): ?>
                                        <?php echo htmlspecialchars($consultation['counselor_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($consultation['issue_category'] ?: 'General'); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($consultation['issue_description'], 0, 50)); ?>...</small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = [
                                        'pending' => 'bg-warning',
                                        'live' => 'bg-success',
                                        'completed' => 'bg-info',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $badge_class[$consultation['status']] ?? 'bg-secondary'; ?>">
                                        <?php echo ucfirst($consultation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($consultation['chat_id']): ?>
                                        <span class="badge <?php echo $consultation['chat_status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo ucfirst($consultation['chat_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No chat</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo $consultation['message_count']; ?></span>
                                    <?php if ($consultation['feedback_count'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $consultation['feedback_count']; ?> feedback</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatDate($consultation['created_at'], 'M d, Y'); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo SITE_URL; ?>/dashboard/admin/view_consultation.php?id=<?php echo $consultation['id']; ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($consultation['chat_id']): ?>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/chat.php?chat_id=<?php echo $consultation['chat_id']; ?>" 
                                               class="btn btn-outline-info" title="View Chat">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Delete Consultation"
                                                onclick="confirmDelete(<?php echo $consultation['id']; ?>, '<?php echo htmlspecialchars($consultation['student_name']); ?>', <?php echo $consultation['message_count']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Consultations pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                
                <p>Are you sure you want to delete this consultation?</p>
                
                <div class="mb-3">
                    <strong>Consultation ID:</strong> <span id="deleteConsultationId"></span><br>
                    <strong>Student:</strong> <span id="deleteStudentName"></span><br>
                    <strong>Chat Messages:</strong> <span id="deleteMessageCount"></span>
                </div>
                
                <p><strong>This will permanently delete:</strong></p>
                <ul>
                    <li>The consultation record</li>
                    <li>All chat sessions and messages</li>
                    <li>Any feedback provided</li>
                    <li>Related notifications</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="consultation_id" id="deleteConsultationIdInput">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(consultationId, studentName, messageCount) {
    document.getElementById('deleteConsultationId').textContent = '#' + consultationId;
    document.getElementById('deleteStudentName').textContent = studentName;
    document.getElementById('deleteMessageCount').textContent = messageCount + ' messages';
    document.getElementById('deleteConsultationIdInput').value = consultationId;
    
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<style>
.btn-group-sm .btn {
    font-size: 0.8rem;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75em;
}
</style>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 