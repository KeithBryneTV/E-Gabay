<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Chat.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$page_title = 'My Messages';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create chat object
$chat = new Chat($db);

// Get chat sessions
$query = "SELECT cs.*, 
          u.first_name as counselor_first_name, u.last_name as counselor_last_name,
          (SELECT COUNT(*) FROM chat_messages WHERE chat_id = cs.id AND user_id != ? AND is_read = 0) as unread_count
          FROM chat_sessions cs
          JOIN users u ON cs.counselor_id = u.user_id
          WHERE cs.student_id = ?
          ORDER BY cs.updated_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $user_id]);
$chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">My Messages</h1>
        <p class="lead">View and manage your chat sessions with counselors.</p>
    </div>
</div>

<!-- Back Button -->
<div class="row mb-4">
    <div class="col-12">
        <a href="<?php echo SITE_URL; ?>/dashboard/student/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Chat Sessions -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-comments me-1"></i>
                Chat Sessions
            </div>
            <div class="card-body">
                <?php if (empty($chat_sessions)): ?>
                    <p class="text-center">You don't have any chat sessions yet.</p>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>/dashboard/student/request_consultation.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i> Request a Consultation
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="chatSessionsTable">
                            <thead>
                                <tr>
                                    <th>Counselor</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chat_sessions as $session): ?>
                                    <tr>
                                        <td><?php echo $session['counselor_first_name'] . ' ' . $session['counselor_last_name']; ?></td>
                                        <td>
                                            <?php echo $session['subject']; ?>
                                            <?php if ($session['unread_count'] > 0): ?>
                                                <span class="badge bg-danger ms-2"><?php echo $session['unread_count']; ?> new</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($session['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Closed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($session['updated_at'], 'M d, Y h:i A'); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/student/chat.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-comments me-1"></i> View Chat
                                            </a>
                                            
                                            <?php if ($session['consultation_id']): ?>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/student/view_consultation.php?id=<?php echo $session['consultation_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-clipboard-list me-1"></i> View Consultation
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#chatSessionsTable').DataTable({
        order: [[3, 'desc']]
    });
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 