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

// Require admin login with strict validation
requireRole(['admin']);

// Add page-specific security script
$page_security_script = '
<script>
    // Only reload on actual browser back navigation, not tab switching
    // Removed pageshow auto reload to prevent unexpected refresh on tab switch
    
    // Session check is handled by auth.php - no need for duplicate checking
</script>';

// Set page title
$page_title = 'Admin Dashboard';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get consultation statistics
$consultation = new Consultation($db);
$consultation_stats = $consultation->getStatistics();

// Get user statistics
$user_stats = getUserStats();

// ================= Additional analytics for enhanced dashboard =================
// User growth (registrations) for the last 30 days
$user_growth_stmt = $db->prepare("SELECT DATE(created_at) as reg_date, COUNT(*) as cnt FROM users GROUP BY reg_date ORDER BY reg_date DESC LIMIT 30");
$user_growth_stmt->execute();
$user_growth_rows = array_reverse($user_growth_stmt->fetchAll(PDO::FETCH_ASSOC)); // chronological order
$growth_labels  = array_column($user_growth_rows, 'reg_date');
$growth_counts  = array_column($user_growth_rows, 'cnt');

// SLA breach – open consultations older than 3 days
$sla_days = 3;
$sla_stmt = $db->prepare("SELECT
    SUM(CASE WHEN status IN ('pending','live') THEN 1 ELSE 0 END)  AS open_total,
    SUM(CASE WHEN status IN ('pending','live') AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY) THEN 1 ELSE 0 END) AS open_breached
    FROM consultation_requests");
$sla_stmt->bindValue(':days', $sla_days, PDO::PARAM_INT);
$sla_stmt->execute();
$sla_row = $sla_stmt->fetch(PDO::FETCH_ASSOC);
$sla_percentage = ($sla_row && $sla_row['open_total'] > 0)
    ? round(($sla_row['open_breached'] / $sla_row['open_total']) * 100, 1)
    : 0;

// Counselor workload (active & pending per counselor)
$work_stmt = $db->prepare("SELECT CONCAT(u.first_name,' ',u.last_name) AS name,
    SUM(CASE WHEN c.status='live' THEN 1 ELSE 0 END)    AS active_cases,
    SUM(CASE WHEN c.status='pending' THEN 1 ELSE 0 END) AS pending_cases
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN consultation_requests c ON c.counselor_id = u.user_id
    WHERE r.role_name = 'counselor'
    GROUP BY u.user_id
    ORDER BY name");
$work_stmt->execute();
$work_rows   = $work_stmt->fetchAll(PDO::FETCH_ASSOC);
$work_labels = array_column($work_rows, 'name');
$work_active = array_column($work_rows, 'active_cases');
$work_pending= array_column($work_rows, 'pending_cases');
// ================================================================================

// Get recent consultations
$recent_consultations = getRecentConsultations(5);

// ================= Additional comprehensive analytics =================
// System Health data
$last_backup = '';
$backup_files = glob($base_path . '/backups/*.sql');
if (!empty($backup_files)) {
    $last_backup_file = max($backup_files);
    $last_backup = date('M d, Y H:i', filemtime($last_backup_file));
}

// Active users (logged in last 24 hours)
$active_stmt = $db->prepare("SELECT COUNT(*) as active_count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$active_stmt->execute();
$active_users = $active_stmt->fetch(PDO::FETCH_ASSOC)['active_count'] ?? 0;

// Pending approvals (unverified users)
$pending_stmt = $db->prepare("SELECT COUNT(*) as pending_count FROM users WHERE is_verified = 0");
$pending_stmt->execute();
$pending_approvals = $pending_stmt->fetch(PDO::FETCH_ASSOC)['pending_count'] ?? 0;

// Notification statistics
$notif_stmt = $db->prepare("SELECT 
    COUNT(*) as total_notifications,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications
    FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$notif_stmt->execute();
$notif_stats = $notif_stmt->fetch(PDO::FETCH_ASSOC);

// Monthly consultation trends (last 6 months)
$trend_stmt = $db->prepare("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count
    FROM consultation_requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month");
$trend_stmt->execute();
$trend_data = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);
$trend_labels = array_column($trend_data, 'month');
$trend_counts = array_column($trend_data, 'count');

// Top consultation categories
$category_stmt = $db->prepare("SELECT 
    COALESCE(issue_category, 'Uncategorized') as category,
    COUNT(*) as count 
    FROM consultation_requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY category ORDER BY count DESC LIMIT 5");
$category_stmt->execute();
$category_data = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
$category_labels = array_column($category_data, 'category');
$category_counts = array_column($category_data, 'count');

// Recent activity log (safe query with table check)
try {
    $activity_stmt = $db->prepare("SELECT 
        al.activity_type, al.description, al.created_at,
        u.first_name, u.last_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC LIMIT 5");
    $activity_stmt->execute();
    $recent_activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, fallback to empty array
    $recent_activities = [];
}
// =====================================================================

// Include header
include_once $base_path . '/includes/header.php';
?>

<style>
/* Modern Dashboard Card Styles */
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
.card-danger::before { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.card-info::before { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
.card-secondary::before { background: linear-gradient(135deg, #c3cfe2 0%, #c3cfe2 100%); }

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

.stat-details {
    font-size: 0.7rem;
    color: #8e9aaf;
    margin-top: 0.3rem;
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

.modern-icon.danger {
    background: linear-gradient(135deg, rgba(250, 112, 154, 0.1) 0%, rgba(254, 225, 64, 0.1) 100%);
    color: #fa709a;
}

.modern-icon.info {
    background: linear-gradient(135deg, rgba(168, 237, 234, 0.1) 0%, rgba(254, 214, 227, 0.1) 100%);
    color: #a8edea;
}

.modern-icon.secondary {
    background: linear-gradient(135deg, rgba(195, 207, 226, 0.1) 0%, rgba(195, 207, 226, 0.1) 100%);
    color: #c3cfe2;
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

.progress-modern {
    height: 4px;
    border-radius: 10px;
    background: rgba(0,0,0,0.05);
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-modern .progress-bar {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    border-radius: 10px;
    transition: width 0.6s ease;
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

/* Enhanced Quick Actions */
.quick-actions-container {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 25px;
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

.action-btn-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
}

.action-btn-success {
    background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
}

.action-btn-info {
    background: linear-gradient(135deg, #36b9cc 0%, #2c9faf 100%);
}

/* Enhanced Recent Consultations */
.consultations-container {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    overflow: hidden;
}

.consultations-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.consultations-body {
    padding: 0;
}

.consultations-table {
    margin: 0;
    border: none;
}

.consultations-table th {
    background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f6 100%);
    border: none;
    padding: 0.75rem 1rem;
    font-weight: 600;
    color: #5a5c69;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.consultations-table td {
    border: none;
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    font-size: 0.85rem;
}

.consultations-table tr:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.status-badge {
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-completed {
    background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
    color: #2d3436;
}

.status-active {
    background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
    color: white;
}

.status-pending {
    background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    color: white;
}

.action-view-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 0.4rem 0.8rem;
    color: white;
    transition: all 0.3s ease;
    font-size: 0.8rem;
}

.action-view-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* Enhanced Notifications */
.notifications-container {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 25px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    overflow: hidden;
    height: fit-content;
}

.notifications-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    border: none;
}

.notifications-body {
    padding: 2rem;
    text-align: center;
}

.no-notifications-icon {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
}
</style>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Admin Dashboard</h1>
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
            <div class="col-lg-3 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php?action=add" class="action-btn action-btn-primary w-100">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php?status=pending" class="action-btn action-btn-warning w-100">
                    <i class="fas fa-tasks"></i>
                    Manage Pending Requests
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/reports.php" class="action-btn action-btn-success w-100">
                    <i class="fas fa-chart-bar"></i>
                    View Reports
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/settings.php" class="action-btn action-btn-info w-100">
                    <i class="fas fa-cog"></i>
                    System Settings
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="consultations-container">
            <div class="consultations-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-clipboard-list me-2"></i>Recent Consultations</h6>
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php" class="btn btn-light btn-sm">
                    View All
                </a>
            </div>
            <div class="consultations-body">
                <?php if (empty($recent_consultations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">No recent consultations found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table consultations-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_consultations as $consultation): ?>
                                    <tr>
                                        <td><strong>#<?php echo $consultation['id']; ?></strong></td>
                                        <td>
                                            <?php 
                                            if ($consultation['is_anonymous']) {
                                                echo '<span class="text-muted"><i class="fas fa-user-secret me-1"></i>Anonymous</span>';
                                            } else {
                                                echo '<strong>' . $consultation['student_first_name'] . ' ' . $consultation['student_last_name'] . '</strong>'; 
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($consultation['issue_category']) ? '<span class="badge bg-light text-dark">' . $consultation['issue_category'] . '</span>' : '<span class="text-muted">Uncategorized</span>'; ?>
                                        </td>
                                        <td><?php echo formatDate($consultation['created_at'], 'M d, Y'); ?></td>
                                        <td>
                                            <?php
                                            switch ($consultation['status']) {
                                                case 'pending':
                                                    echo '<span class="status-badge status-pending">Pending</span>';
                                                    break;
                                                case 'live':
                                                    echo '<span class="status-badge status-active">Active</span>';
                                                    break;
                                                case 'completed':
                                                    echo '<span class="status-badge status-completed">Completed</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="badge bg-danger">Cancelled</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/admin/view_consultation.php?id=<?php echo $consultation['id']; ?>" class="btn action-view-btn btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
<!-- ================================================================ -->

<!-- ===================== Main KPI Stats Row ===================== -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-primary h-100">
            <div class="card-body">
                <div class="modern-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-number"><?php echo $user_stats['total_users']; ?></div>
                <div class="stat-details">
                    <span class="me-2"><i class="fas fa-user-graduate"></i> <?php echo $user_stats['student_count']; ?> Students</span>
                    <span><i class="fas fa-user-tie"></i> <?php echo $user_stats['counselor_count']; ?> Counselors</span>
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-success h-100">
            <div class="card-body">
                <div class="modern-icon success">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-label">Active Consultations</div>
                <div class="stat-number"><?php echo $consultation_stats['active_consultations']; ?></div>
                <div class="stat-details">
                    Out of <?php echo $consultation_stats['total_consultations']; ?> total consultations
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php?status=live">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-warning h-100">
            <div class="card-body">
                <div class="modern-icon warning">
                    <i class="fas fa-circle"></i>
                </div>
                <div class="stat-label">Active Users (24h)</div>
                <div class="stat-number"><?php echo $active_users; ?></div>
                <div class="stat-details">
                    Currently active users
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-danger h-100">
            <div class="card-body">
                <div class="modern-icon danger">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-number"><?php echo $pending_approvals; ?></div>
                <div class="stat-details">
                    Unverified accounts
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/users.php?filter=unverified">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===================== Secondary Stats Row ===================== -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-info h-100">
            <div class="card-body">
                <div class="modern-icon info">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-label">Completed Consultations</div>
                <div class="stat-number"><?php echo $consultation_stats['completed_consultations']; ?></div>
                <div class="stat-details">
                    <?php echo $consultation_stats['total_consultations'] > 0 ? round(($consultation_stats['completed_consultations'] / $consultation_stats['total_consultations'] * 100), 1) : 0; ?>% Completion Rate
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php?status=completed">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-warning h-100">
            <div class="card-body">
                <div class="modern-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-label">Pending Consultations</div>
                <div class="stat-number"><?php echo $consultation_stats['pending_consultations']; ?></div>
                <div class="progress-modern">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $consultation_stats['total_consultations'] > 0 ? ($consultation_stats['pending_consultations'] / $consultation_stats['total_consultations'] * 100) : 0; ?>%"></div>
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/consultations.php?status=pending">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-success h-100">
            <div class="card-body">
                <div class="modern-icon success">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-label">Notifications (7d)</div>
                <div class="stat-number"><?php echo $notif_stats['total_notifications'] ?? 0; ?></div>
                <div class="stat-details">
                    <?php echo $notif_stats['unread_notifications'] ?? 0; ?> unread
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/notifications.php">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="modern-card card-secondary h-100">
            <div class="card-body">
                <div class="modern-icon secondary">
                    <i class="fas fa-<?php echo $last_backup ? 'shield-alt' : 'exclamation-triangle'; ?>"></i>
                </div>
                <div class="stat-label">System Health</div>
                <div class="stat-number"><?php echo $last_backup ? 'Healthy' : 'Warning'; ?></div>
                <div class="stat-details">
                    Last backup: <?php echo $last_backup ?: 'Never'; ?>
                </div>
            </div>
            <div class="modern-footer">
                <a href="<?php echo SITE_URL; ?>/dashboard/admin/backup.php">
                    View Details <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===================== Enhanced Analytics Widgets ===================== -->
<div class="row mb-4">
    <!-- User Growth Sparkline -->
    <div class="col-md-4 mb-4">
        <div class="chart-card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-user-plus me-2"></i>User Growth (30&nbsp;days)</h6>
            </div>
            <div class="card-body py-3">
                <canvas id="userGrowthChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- SLA Breach Gauge -->
    <div class="col-md-4 mb-4">
        <div class="chart-card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-stopwatch me-2"></i>SLA Breach (&gt;3&nbsp;days)</h6>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center position-relative py-3" style="height:120px;">
                <canvas id="slaGauge" width="100" height="100"></canvas>
                <div id="slaGaugeLabel" class="position-absolute fw-bold" style="font-size:1.2rem;"></div>
            </div>
        </div>
    </div>

    <!-- Top Categories -->
    <div class="col-md-4 mb-4">
        <div class="chart-card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-pie me-2"></i>Top Categories (30d)</h6>
            </div>
            <div class="card-body py-3">
                <canvas id="categoriesChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ===================== Monthly Trends & Workload ===================== -->
<div class="row mb-4">
    <!-- Monthly Consultation Trends -->
    <div class="col-md-8 mb-4">
        <div class="chart-card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-line me-2"></i>Monthly Trends (6 months)</h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyTrendsChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-md-4 mb-4">
        <div class="chart-card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Recent Activity</h6>
            </div>
            <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($recent_activities)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted small">No recent activity</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="d-flex align-items-start mb-3">
                            <div class="rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-<?php echo $activity['activity_type'] == 'login' ? 'sign-in-alt' : 'cog'; ?> text-white small"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold"><?php echo htmlspecialchars($activity['description']); ?></div>
                                <div class="text-muted small">
                                    <?php echo isset($activity['first_name']) ? $activity['first_name'] . ' ' . $activity['last_name'] : 'System'; ?>
                                    • <?php echo timeAgo($activity['created_at']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Counselor Workload Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-briefcase me-2"></i>Counselor Workload</h6>
            </div>
            <div class="card-body">
                <canvas id="counselorWorkloadChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>
<!-- ====================================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ==================== Additional Charts ====================
    // 1. User Growth sparkline
    const growthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($growth_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($growth_counts); ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78,115,223,0.15)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false } },
            elements: { line: { borderWidth: 2 } }
        }
    });

    // 2. SLA Breach Gauge
    const slaCtx = document.getElementById('slaGauge').getContext('2d');
    new Chart(slaCtx, {
        type: 'doughnut',
        data: {
            labels: ['Breached', 'Within SLA'],
            datasets: [{
                data: [<?php echo $sla_percentage; ?>, <?php echo 100 - $sla_percentage; ?>],
                backgroundColor: ['#e74a3b', '#1cc88a'],
                hoverBackgroundColor: ['#d33c2b', '#17a673'],
                borderWidth: 0,
            }]
        },
        options: {
            cutout: '80%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
            },
        }
    });
    document.getElementById('slaGaugeLabel').innerText = '<?php echo $sla_percentage; ?>%';

    // 3. Top Categories Doughnut
    const catCtx = document.getElementById('categoriesChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($category_counts); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#c92a2a'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        boxWidth: 10, 
                        font: { size: 10 },
                        padding: 10
                    } 
                }
            },
            cutout: '60%',
        }
    });

    // 4. Monthly Trends Line Chart
    const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trend_labels); ?>,
            datasets: [{
                label: 'Consultations',
                data: <?php echo json_encode($trend_counts); ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78,115,223,0.1)',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 5. Counselor Workload (stacked bar)
    const cwCtx = document.getElementById('counselorWorkloadChart').getContext('2d');
    new Chart(cwCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($work_labels); ?>,
            datasets: [
                {
                    label: 'Active',
                    data: <?php echo json_encode($work_active); ?>,
                    backgroundColor: '#4e73df',
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode($work_pending); ?>,
                    backgroundColor: '#f6c23e',
                }
            ],
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true },
            },
        }
    });
    // ============================================================
});
</script>

<?php
// Include footer
echo $page_security_script;
include_once $base_path . '/includes/footer.php';
?> 