<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/auth.php';

// Add security headers to prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate, private");
header("Pragma: no-cache");
header("Expires: 0");

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';
require_once $base_path . '/classes/Chat.php';

// Require counselor login with strict validation
requireRole(['counselor']);

// Add page-specific security script
$page_security_script = '
<script>
    // Removed pageshow auto reload to prevent unexpected refresh on tab switch
    
    // Session check is handled by auth.php - no need for duplicate checking
</script>';

// Set page title
$page_title = 'Counselor Dashboard';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Get counselor's consultations
$query = "SELECT 
          COUNT(*) as total_consultations,
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_consultations,
          SUM(CASE WHEN status = 'live' THEN 1 ELSE 0 END) as active_consultations,
          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_consultations
          FROM consultation_requests
          WHERE counselor_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$consultation_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure all keys exist with default values
$defaults = [
    'total_consultations' => 0,
    'pending_consultations' => 0,
    'active_consultations' => 0,
    'completed_consultations' => 0
];
$consultation_stats = array_merge($defaults, $consultation_stats ? $consultation_stats : []);

// Get counselor's average rating
$query = "SELECT AVG(f.rating) as average_rating, COUNT(f.id) as rating_count
          FROM feedback f
          JOIN consultation_requests cr ON f.consultation_id = cr.id
          WHERE cr.counselor_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
$average_rating = $rating_data['average_rating'] ? round($rating_data['average_rating'], 1) : 0;
$rating_count = $rating_data['rating_count'] ? $rating_data['rating_count'] : 0;

// Get pending consultations
$query = "SELECT cr.*, 
          u.first_name, u.last_name, u.email
          FROM consultation_requests cr
          JOIN users u ON cr.student_id = u.user_id
          WHERE cr.counselor_id = ? AND cr.status = 'pending'
          ORDER BY cr.preferred_date ASC, cr.preferred_time ASC
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$pending_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active consultations
$query = "SELECT cr.*, 
          u.first_name, u.last_name, u.email
          FROM consultation_requests cr
          JOIN users u ON cr.student_id = u.user_id
          WHERE cr.counselor_id = ? AND cr.status = 'live'
          ORDER BY cr.updated_at DESC
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$active_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread messages count
$chat = new Chat($db);
$unread_count = $chat->getUnreadCount($user_id, 'counselor');

// Include header
include_once $base_path . '/includes/header.php';
?>

<style>
/* Modern Dashboard Card Styles - Same as Admin */
.modern-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 45px rgba(0,0,0,0.15);
}

.modern-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient);
    border-radius: 16px 16px 0 0;
}

.card-primary::before { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.card-success::before { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.card-warning::before { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.card-info::before { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

.modern-card .card-body {
    padding: 1rem;
    position: relative;
    z-index: 2;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.3rem;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.modern-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 0.75rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    color: #667eea;
}

.modern-icon.success {
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
    color: #4facfe;
}

.modern-icon.warning {
    background: linear-gradient(135deg, rgba(67, 233, 123, 0.1) 0%, rgba(56, 249, 215, 0.1) 100%);
    color: #43e97b;
}

.modern-icon.info {
    background: linear-gradient(135deg, rgba(250, 112, 154, 0.1) 0%, rgba(254, 225, 64, 0.1) 100%);
    color: #fa709a;
}

.modern-footer {
    padding: 0.75rem 1rem;
    background: rgba(248, 249, 252, 0.5);
    border-top: 1px solid rgba(255,255,255,0.2);
}

.modern-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.75rem;
    transition: all 0.3s ease;
}

.modern-footer a:hover {
    color: #764ba2;
    transform: translateX(5px);
}

/* Enhanced Quick Actions */
.quick-actions-container {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.quick-actions-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border: none;
    margin: 0;
}

.quick-actions-body {
    padding: 1.5rem;
}

.action-btn {
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    min-height: 50px;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    color: white;
}

.action-btn i {
    margin-right: 0.5rem;
    font-size: 1rem;
}

.action-btn-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.action-btn-success {
    background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
}

.action-btn-info {
    background: linear-gradient(135deg, #36b9cc 0%, #2c9faf 100%);
}

/* Chart Cards */
.chart-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.8) 100%);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chart-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 0.75rem 1rem;
    color: white;
    font-size: 0.85rem;
}

.chart-card .card-body {
    padding: 1rem;
}
</style>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Counselor Dashboard</h1>
        <p class="lead">Welcome back, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!</p>
    </div>
</div>

<!-- ===================== Enhanced Top Section ===================== -->
<!-- Quick Actions Section -->
<div class="quick-actions-container">
    <div class="quick-actions-header">
        <h6 class="m-0 font-weight-bold"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
    </div>
    <div class="quick-actions-body">
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php" class="action-btn action-btn-primary w-100">
                    <i class="fas fa-clipboard-list"></i>
                    All Consultations
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/messages.php" class="action-btn action-btn-success w-100">
                    <i class="fas fa-envelope"></i>
                    Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/schedule.php" class="action-btn action-btn-info w-100">
                    <i class="fas fa-calendar-alt"></i>
                    Schedule
                </a>
            </div>
        </div>
    </div>
</div>
<!-- ================================================================ -->

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="modern-card card-primary h-100">
            <div class="card-body">
                <div class="modern-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-label">Total Consultations</div>
                <div class="stat-number"><?php echo $consultation_stats['total_consultations']; ?></div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="modern-card card-success h-100">
            <div class="card-body">
                <div class="modern-icon success">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-label">Active Consultations</div>
                <div class="stat-number"><?php echo $consultation_stats['active_consultations']; ?></div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=live">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="modern-card card-warning h-100">
            <div class="card-body">
                <div class="modern-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-label">Pending Consultations</div>
                <div class="stat-number"><?php echo $consultation_stats['pending_consultations']; ?></div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=pending">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="modern-card card-info h-100">
            <div class="card-body">
                <div class="modern-icon info">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-label">Unread Messages</div>
                <div class="stat-number"><?php echo $unread_count; ?></div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/messages.php">
                    View Messages <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Counselor Rating -->
<div class="row mb-4">
    <div class="col-md-6">
        <?php include_once $base_path . '/includes/notification_component.php'; ?>
    </div>

    <div class="col-md-6">
        <div class="chart-card">
            <div class="card-header">
                <i class="fas fa-star me-1"></i>
                Your Rating
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-4">
                        <h1 class="display-4 fw-bold text-primary"><?php echo $average_rating; ?></h1>
                        <p class="text-muted">out of 5.0</p>
                    </div>
                    <div class="flex-grow-1">
                        <div class="mb-2">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $average_rating) {
                                    echo '<i class="fas fa-star text-warning fa-lg me-1"></i>';
                                } elseif ($i - 0.5 <= $average_rating) {
                                    echo '<i class="fas fa-star-half-alt text-warning fa-lg me-1"></i>';
                                } else {
                                    echo '<i class="far fa-star text-warning fa-lg me-1"></i>';
                                }
                            }
                            ?>
                        </div>
                        <p class="mb-0">Based on <?php echo $rating_count; ?> student feedback ratings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="chart-card">
            <div class="card-header">
                <i class="fas fa-calendar-alt me-1"></i>
                Today's Schedule
            </div>
            <div class="card-body">
                <?php
                $today = date('Y-m-d');
                $query = "SELECT cr.*, 
                          u.first_name, u.last_name
                          FROM consultation_requests cr
                          JOIN users u ON cr.student_id = u.user_id
                          WHERE cr.counselor_id = ? AND DATE(cr.preferred_date) = ? AND cr.status IN ('pending', 'live')
                          ORDER BY cr.preferred_time ASC";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id, $today]);
                $today_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (empty($today_consultations)): ?>
                    <p class="text-center">No consultations scheduled for today.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($today_consultations as $consultation): ?>
                            <a href="view_consultation.php?id=<?php echo $consultation['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?php if ($consultation['is_anonymous']): ?>
                                            <span class="text-muted">Anonymous Student</span>
                                        <?php else: ?>
                                            <?php echo $consultation['first_name'] . ' ' . $consultation['last_name']; ?>
                                        <?php endif; ?>
                                    </h6>
                                    <small><?php echo formatTime($consultation['preferred_time']); ?></small>
                                </div>
                                <p class="mb-1">
                                    <?php echo !empty($consultation['issue_category']) ? $consultation['issue_category'] : 'Consultation'; ?>
                                </p>
                                <small>
                                    <?php
                                    switch ($consultation['status']) {
                                        case 'pending':
                                            echo '<span class="badge bg-primary">Pending</span>';
                                            break;
                                        case 'live':
                                            echo '<span class="badge bg-success">Active</span>';
                                            break;
                                    }
                                    ?>
                                    <span class="ms-2"><?php echo ucfirst(str_replace('_', ' ', $consultation['communication_method'])); ?></span>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pending and Active Consultations -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="chart-card">
            <div class="card-header">
                <i class="fas fa-clock me-1"></i>
                Pending Consultations
            </div>
            <div class="card-body">
                <?php if (empty($pending_consultations)): ?>
                    <p class="text-center">No pending consultations.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Method</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_consultations as $consultation): ?>
                                    <tr>
                                        <td>
                                            <?php echo formatDate($consultation['preferred_date']); ?><br>
                                            <small><?php echo formatTime($consultation['preferred_time']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($consultation['is_anonymous']): ?>
                                                <span class="text-muted">Anonymous Student</span>
                                            <?php else: ?>
                                                <?php echo $consultation['first_name'] . ' ' . $consultation['last_name']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $consultation['communication_method'])); ?></td>
                                        <td>
                                            <a href="view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=pending" class="btn btn-primary">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="chart-card">
            <div class="card-header">
                <i class="fas fa-comments me-1"></i>
                Active Consultations
            </div>
            <div class="card-body">
                <?php if (empty($active_consultations)): ?>
                    <p class="text-center">No active consultations.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Method</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_consultations as $consultation): ?>
                                    <tr>
                                        <td>
                                            <?php echo formatDate($consultation['preferred_date']); ?><br>
                                            <small><?php echo formatTime($consultation['preferred_time']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($consultation['is_anonymous']): ?>
                                                <span class="text-muted">Anonymous Student</span>
                                            <?php else: ?>
                                                <?php echo $consultation['first_name'] . ' ' . $consultation['last_name']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $consultation['communication_method'])); ?></td>
                                        <td>
                                            <a href="view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-comments"></i> Chat
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo SITE_URL; ?>/dashboard/counselor/consultations.php?status=live" class="btn btn-primary">View All</a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
echo $page_security_script;
include_once $base_path . '/includes/footer.php';
?> 