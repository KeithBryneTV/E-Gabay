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
$page_title = 'Reports';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

// Handle quick date filters
if (isset($_GET['quick_filter'])) {
    switch ($_GET['quick_filter']) {
        case '7days':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $end_date = date('Y-m-d');
            break;
        case '30days':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
            break;
        case '90days':
            $start_date = date('Y-m-d', strtotime('-90 days'));
            $end_date = date('Y-m-d');
            break;
        case '1year':
            $start_date = date('Y-m-d', strtotime('-1 year'));
            $end_date = date('Y-m-d');
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('last month'));
            $end_date = date('Y-m-t', strtotime('last month'));
            break;
    }
}

// Get consultation statistics
$stats = $consultation->getStatistics();

// Get consultation data by status for the chart
$query = "SELECT 
          COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
          COUNT(CASE WHEN status = 'live' THEN 1 END) as active,
          COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
          COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
          FROM consultation_requests
          WHERE created_at BETWEEN :start_date AND :end_date";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$end_date_adjusted = date('Y-m-d', strtotime($end_date . ' +1 day')); // Include the end date fully
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$chart_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Get consultations by date for the period
$query = "SELECT DATE(created_at) as date, COUNT(*) as count
          FROM consultation_requests
          WHERE created_at BETWEEN :start_date AND :end_date
          GROUP BY DATE(created_at)
          ORDER BY date";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$consultations_by_date = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data for the line chart
$dates = [];
$counts = [];
foreach ($consultations_by_date as $item) {
    $dates[] = date('M d', strtotime($item['date']));
    $counts[] = $item['count'];
}

// Get top problems/categories
$query = "SELECT IFNULL(issue_category, 'Uncategorized') as issue_category, COUNT(*) as count
          FROM consultation_requests
          WHERE created_at BETWEEN :start_date AND :end_date
          GROUP BY issue_category
          ORDER BY count DESC
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$top_problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counselor performance
$query = "SELECT 
          u.first_name, u.last_name,
          COUNT(cr.id) as total_consultations,
          AVG(f.rating) as avg_rating,
          COUNT(CASE WHEN cr.status = 'completed' THEN 1 END) as completed,
          COUNT(CASE WHEN cr.status = 'cancelled' THEN 1 END) as cancelled
          FROM users u
          JOIN roles r ON u.role_id = r.role_id
          LEFT JOIN consultation_requests cr ON u.user_id = cr.counselor_id
          LEFT JOIN feedback f ON cr.id = f.consultation_id
          WHERE r.role_name = 'counselor'
          AND (cr.created_at IS NULL OR cr.created_at BETWEEN :start_date AND :end_date)
          GROUP BY u.user_id
          ORDER BY total_consultations DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$counselor_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get messaging statistics - Top messaging users
$query = "SELECT 
          u.first_name, u.last_name, u.user_id,
          r.role_name,
          COUNT(cm.id) as total_messages,
          COUNT(DISTINCT cm.chat_id) as chat_sessions_count,
          MAX(cm.created_at) as last_message_date,
          AVG(LENGTH(cm.message)) as avg_message_length
          FROM users u
          JOIN roles r ON u.role_id = r.role_id
          LEFT JOIN chat_messages cm ON u.user_id = cm.user_id
          WHERE cm.created_at IS NULL OR cm.created_at BETWEEN :start_date AND :end_date
          GROUP BY u.user_id
          HAVING total_messages > 0
          ORDER BY total_messages DESC
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$top_messaging_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get chat session statistics
$query = "SELECT 
          COUNT(DISTINCT cs.id) as total_chat_sessions,
          COUNT(DISTINCT CASE WHEN cs.status = 'active' THEN cs.id END) as active_sessions,
          COUNT(DISTINCT CASE WHEN cs.status = 'closed' THEN cs.id END) as closed_sessions,
          AVG(TIMESTAMPDIFF(MINUTE, cs.created_at, cs.updated_at)) as avg_session_duration
          FROM chat_sessions cs
          WHERE cs.created_at BETWEEN :start_date AND :end_date";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$chat_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get most active chat sessions
$query = "SELECT 
          cs.id, cs.consultation_id,
          u1.first_name as student_name, u1.last_name as student_lastname,
          u2.first_name as counselor_name, u2.last_name as counselor_lastname,
          COUNT(cm.id) as message_count,
          cs.created_at as session_start,
          cs.updated_at as last_activity
          FROM chat_sessions cs
          LEFT JOIN consultation_requests cr ON cs.consultation_id = cr.id
          LEFT JOIN users u1 ON cr.student_id = u1.user_id
          LEFT JOIN users u2 ON cr.counselor_id = u2.user_id
          LEFT JOIN chat_messages cm ON cs.id = cm.chat_id
          WHERE cs.created_at BETWEEN :start_date AND :end_date
          GROUP BY cs.id
          ORDER BY message_count DESC
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$most_active_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user registration trends
$query = "SELECT 
          DATE(u.created_at) as registration_date,
          COUNT(*) as new_registrations,
          COUNT(CASE WHEN r.role_name = 'student' THEN 1 END) as new_students,
          COUNT(CASE WHEN r.role_name = 'counselor' THEN 1 END) as new_counselors
          FROM users u
          JOIN roles r ON u.role_id = r.role_id
          WHERE u.created_at BETWEEN :start_date AND :end_date
          GROUP BY DATE(u.created_at)
          ORDER BY registration_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);
$stmt->execute();
$registration_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Reports</h1>
        <p class="lead">View system statistics and generate reports.</p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="row mb-4">
    <div class="col-md-12">
        <!-- Quick Filter Buttons -->
        <div class="mb-3">
            <h6>Quick Filters:</h6>
            <div class="btn-group" role="group">
                <a href="?quick_filter=7days" class="btn btn-outline-primary btn-sm <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == '7days') ? 'active' : ''; ?>">Last 7 Days</a>
                <a href="?quick_filter=30days" class="btn btn-outline-primary btn-sm <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == '30days') ? 'active' : ''; ?>">Last 30 Days</a>
                <a href="?quick_filter=90days" class="btn btn-outline-primary btn-sm <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == '90days') ? 'active' : ''; ?>">Last 90 Days</a>
                <a href="?quick_filter=this_month" class="btn btn-outline-primary btn-sm <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'this_month') ? 'active' : ''; ?>">This Month</a>
                <a href="?quick_filter=last_month" class="btn btn-outline-primary btn-sm <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'last_month') ? 'active' : ''; ?>">Last Month</a>
                <a href="?quick_filter=1year" class="btn btn-outline-primary btn-sm <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] == '1year') ? 'active' : ''; ?>">Last Year</a>
            </div>
        </div>
        
        <!-- Custom Date Range -->
        <form action="" method="get" class="form-inline filter-form">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="start_date" class="col-form-label">Custom Range - From:</label>
                </div>
                <div class="col-auto">
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-auto">
                    <label for="end_date" class="col-form-label">To:</label>
                </div>
                <div class="col-auto">
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary btn-print" data-report-title="Admin Reports">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="date-range-text print-only">
    Report Period: <?php echo date('F d, Y', strtotime($start_date)); ?> - <?php echo date('F d, Y', strtotime($end_date)); ?>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Total Consultations</div>
                        <div class="fs-4"><?php echo $stats['total_consultations']; ?></div>
                    </div>
                    <i class="fas fa-clipboard-list fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Pending Consultations</div>
                        <div class="fs-4"><?php echo $stats['pending_consultations']; ?></div>
                    </div>
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Active Consultations</div>
                        <div class="fs-4"><?php echo $stats['active_consultations']; ?></div>
                    </div>
                    <i class="fas fa-comments fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Completed Consultations</div>
                        <div class="fs-4"><?php echo $stats['completed_consultations']; ?></div>
                    </div>
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Consultation Status Chart -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-pie me-1"></i>
                Consultation Status Distribution
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="100%" height="50"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Consultations Over Time Chart -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Consultations Over Time
            </div>
            <div class="card-body">
                <canvas id="timelineChart" width="100%" height="50"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Issues and Counselor Performance -->
<div class="row">
    <!-- Top Issues -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-list-ol me-1"></i>
                Top Problems/Categories
            </div>
            <div class="card-body">
                <?php if (empty($top_problems)): ?>
                    <p class="text-center">No data available for the selected period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped professional-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 8%;">Rank</th>
                                    <th style="width: 35%;">Problem Category</th>
                                    <th style="width: 15%;">Count</th>
                                    <th style="width: 15%;">Percentage</th>
                                    <th style="width: 12%;">Completed</th>
                                    <th style="width: 15%;">Avg. Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_problems = array_sum(array_column($top_problems, 'count'));
                                $rank = 1;
                                foreach ($top_problems as $problem): 
                                    // Get additional stats for each category
                                    $category_stats_query = "SELECT 
                                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                                        AVG(DATEDIFF(updated_at, created_at)) as avg_resolution_days
                                        FROM consultation_requests 
                                        WHERE issue_category = ? AND created_at BETWEEN ? AND ?";
                                    $stats_stmt = $db->prepare($category_stats_query);
                                    $stats_stmt->execute([$problem['issue_category'], $start_date, $end_date_adjusted]);
                                    $category_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($rank <= 3): ?>
                                                <span class="badge bg-<?php echo $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'dark'); ?>">
                                                    #<?php echo $rank; ?>
                                                </span>
                                            <?php else: ?>
                                                <strong>#<?php echo $rank; ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo !empty($problem['issue_category']) ? $problem['issue_category'] : 'Uncategorized'; ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><strong><?php echo $problem['count']; ?></strong></span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $percentage = $total_problems > 0 ? round(($problem['count'] / $total_problems) * 100, 1) : 0;
                                            $color = $percentage >= 30 ? 'danger' : ($percentage >= 20 ? 'warning' : 'info');
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $percentage; ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $category_stats['completed_count'] ?? 0; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $avg_days = $category_stats['avg_resolution_days'] ?? 0;
                                            if ($avg_days > 0) {
                                                echo '<strong>' . round($avg_days, 1) . '</strong> days';
                                            } else {
                                                echo '<em>N/A</em>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php 
                                $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Counselor Performance -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-md me-1"></i>
                Counselor Performance
            </div>
            <div class="card-body">
                <?php if (empty($counselor_performance)): ?>
                    <p class="text-center">No data available for the selected period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped professional-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 25%;">Counselor Name</th>
                                    <th style="width: 12%;">Total Consult.</th>
                                    <th style="width: 12%;">Completed</th>
                                    <th style="width: 12%;">Cancelled</th>
                                    <th style="width: 15%;">Success Rate</th>
                                    <th style="width: 12%;">Avg. Rating</th>
                                    <th style="width: 12%;">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($counselor_performance as $counselor): ?>
                                    <tr>
                                        <td><strong><?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?></strong></td>
                                        <td class="text-center"><strong><?php echo $counselor['total_consultations']; ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $counselor['completed']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?php echo $counselor['cancelled']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $success_rate = $counselor['total_consultations'] > 0 ? 
                                                round(($counselor['completed'] / $counselor['total_consultations']) * 100, 1) : 0;
                                            $rate_color = $success_rate >= 80 ? 'success' : ($success_rate >= 60 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $rate_color; ?>"><?php echo $success_rate; ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            if ($counselor['avg_rating']) {
                                                $rating = round($counselor['avg_rating'], 1);
                                                echo '<strong>' . $rating . ' / 5.0</strong>';
                                                
                                                // Display stars
                                                echo '<br><small>';
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '★';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '⭐';
                                                    } else {
                                                        echo '☆';
                                                    }
                                                }
                                                echo '</small>';
                                            } else {
                                                echo '<em>N/A</em>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $performance_score = 0;
                                            if ($counselor['total_consultations'] > 0) {
                                                $completion_rate = ($counselor['completed'] / $counselor['total_consultations']) * 100;
                                                $rating_score = $counselor['avg_rating'] ? ($counselor['avg_rating'] / 5) * 100 : 50;
                                                $performance_score = ($completion_rate * 0.6) + ($rating_score * 0.4);
                                            }
                                            
                                            if ($performance_score >= 85) {
                                                echo '<span class="badge bg-success">Excellent</span>';
                                            } elseif ($performance_score >= 70) {
                                                echo '<span class="badge bg-info">Good</span>';
                                            } elseif ($performance_score >= 50) {
                                                echo '<span class="badge bg-warning">Average</span>';
                                            } else {
                                                echo '<span class="badge bg-danger">Needs Improvement</span>';
                                            }
                                            ?>
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

<!-- Additional Statistics Row -->
<div class="row mb-4">
    <!-- Chat Statistics -->
    <div class="col-md-3">
        <div class="card bg-dark text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Total Chat Sessions</div>
                        <div class="fs-4"><?php echo $chat_stats['total_chat_sessions'] ?? 0; ?></div>
                    </div>
                    <i class="fas fa-comments fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Active Chat Sessions</div>
                        <div class="fs-4"><?php echo $chat_stats['active_sessions'] ?? 0; ?></div>
                    </div>
                    <i class="fas fa-comment-dots fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Closed Sessions</div>
                        <div class="fs-4"><?php echo $chat_stats['closed_sessions'] ?? 0; ?></div>
                    </div>
                    <i class="fas fa-comment-slash fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Avg. Session (mins)</div>
                        <div class="fs-4"><?php echo round($chat_stats['avg_session_duration'] ?? 0); ?></div>
                    </div>
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Messaging and Activity Reports -->
<div class="row mb-4">
    <!-- Top Messaging Users -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-trophy me-1"></i>
                Top Messaging Users
            </div>
            <div class="card-body">
                <?php if (empty($top_messaging_users)): ?>
                    <p class="text-center">No messaging data available for the selected period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover professional-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 8%;">Rank</th>
                                    <th style="width: 25%;">User Name</th>
                                    <th style="width: 15%;">Role</th>
                                    <th style="width: 15%;">Messages</th>
                                    <th style="width: 15%;">Sessions</th>
                                    <th style="width: 22%;">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($top_messaging_users as $user): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($rank <= 3): ?>
                                                <span class="badge bg-<?php echo $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'dark'); ?>">
                                                    #<?php echo $rank; ?>
                                                </span>
                                            <?php else: ?>
                                                <strong>#<?php echo $rank; ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $user['role_name'] == 'student' ? 'primary' : ($user['role_name'] == 'counselor' ? 'success' : 'info'); ?>">
                                                <?php echo ucfirst($user['role_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><strong><?php echo $user['total_messages']; ?></strong></td>
                                        <td class="text-center"><?php echo $user['chat_sessions_count']; ?></td>
                                        <td class="text-center">
                                            <?php echo $user['last_message_date'] ? date('M d, Y', strtotime($user['last_message_date'])) : '<em>N/A</em>'; ?>
                                        </td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Most Active Chat Sessions -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-fire me-1"></i>
                Most Active Chat Sessions
            </div>
            <div class="card-body">
                <?php if (empty($most_active_chats)): ?>
                    <p class="text-center">No chat session data available for the selected period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover professional-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 12%;">Session ID</th>
                                    <th style="width: 40%;">Participants</th>
                                    <th style="width: 20%;">Messages</th>
                                    <th style="width: 28%;">Started Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($most_active_chats as $chat): ?>
                                    <tr>
                                        <td class="text-center"><strong>#<?php echo $chat['id']; ?></strong></td>
                                        <td>
                                            <div class="small">
                                                <strong>Student:</strong> <?php echo $chat['student_name'] . ' ' . $chat['student_lastname']; ?><br>
                                                <strong>Counselor:</strong> <span class="text-muted"><?php echo $chat['counselor_name'] . ' ' . $chat['counselor_lastname']; ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">
                                                <strong><?php echo $chat['message_count']; ?></strong> messages
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo date('M d, Y', strtotime($chat['session_start'])); ?></td>
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

<!-- User Registration Trends -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-plus me-1"></i>
                User Registration Trends
            </div>
            <div class="card-body">
                <?php if (empty($registration_trends)): ?>
                    <p class="text-center">No registration data available for the selected period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Registrations</th>
                                    <th>New Students</th>
                                    <th>New Counselors</th>
                                    <th>Growth Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $prev_count = 0;
                                foreach ($registration_trends as $trend): 
                                    $growth = $prev_count > 0 ? (($trend['new_registrations'] - $prev_count) / $prev_count) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($trend['registration_date'])); ?></td>
                                        <td><strong><?php echo $trend['new_registrations']; ?></strong></td>
                                        <td><?php echo $trend['new_students']; ?></td>
                                        <td><?php echo $trend['new_counselors']; ?></td>
                                        <td>
                                            <?php if ($growth > 0): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-arrow-up"></i> +<?php echo round($growth, 1); ?>%
                                                </span>
                                            <?php elseif ($growth < 0): ?>
                                                <span class="text-danger">
                                                    <i class="fas fa-arrow-down"></i> <?php echo round($growth, 1); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    $prev_count = $trend['new_registrations'];
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Report -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Detailed Report for Selected Period
            </div>
            <div class="card-body">
                <?php
                // Get detailed consultation data for the period
                $query = "SELECT cr.*, 
                          u1.first_name as student_first_name, u1.last_name as student_last_name,
                          u2.first_name as counselor_first_name, u2.last_name as counselor_last_name,
                          f.rating, f.comments,
                          cr.is_anonymous, cr.issue_description
                          FROM consultation_requests cr
                          JOIN users u1 ON cr.student_id = u1.user_id
                          LEFT JOIN users u2 ON cr.counselor_id = u2.user_id
                          LEFT JOIN feedback f ON cr.id = f.consultation_id
                          WHERE cr.created_at BETWEEN :start_date AND :end_date
                          ORDER BY cr.created_at DESC";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':end_date', $end_date_adjusted);
                $stmt->execute();
                $detailed_report = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (empty($detailed_report)): ?>
                    <p class="text-center">No data available for the selected period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover professional-table" id="detailedReportTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 4%;">#</th>
                                    <th style="width: 10%;">Date Created</th>
                                    <th style="width: 15%;">Student Name</th>
                                    <th style="width: 12%;">Counselor</th>
                                    <th style="width: 10%;">Issue Category</th>
                                    <th style="width: 8%;">Method</th>
                                    <th style="width: 8%;">Status</th>
                                    <th style="width: 6%;">Rating</th>
                                    <th style="width: 8%;">Anonymous</th>
                                    <th style="width: 19%;">Issue Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailed_report as $report): ?>
                                    <tr>
                                        <td class="text-center"><strong><?php echo $report['id']; ?></strong></td>
                                        <td><?php echo formatDate($report['created_at'], 'M d, Y'); ?><br><small class="text-muted"><?php echo date('H:i', strtotime($report['created_at'])); ?></small></td>
                                        <td>
                                            <strong><?php echo $report['student_first_name'] . ' ' . $report['student_last_name']; ?></strong>
                                            <?php if ($report['is_anonymous'] == 1): ?>
                                                <br><small class="text-warning"><i class="fas fa-user-secret"></i> Requested Anonymity</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($report['counselor_id']) {
                                                echo '<strong>' . $report['counselor_first_name'] . ' ' . $report['counselor_last_name'] . '</strong>';
                                            } else {
                                                echo '<span class="text-muted"><em>Not Assigned</em></span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center"><?php echo !empty($report['issue_category']) ? '<span class="badge bg-info">' . $report['issue_category'] . '</span>' : '<em>Uncategorized</em>'; ?></td>
                                        <td class="text-center">
                                            <?php 
                                            $method = ucfirst(str_replace('_', ' ', $report['communication_method']));
                                            $icon = '';
                                            switch(strtolower($report['communication_method'])) {
                                                case 'chat': $icon = 'fas fa-comments'; break;
                                                case 'video': $icon = 'fas fa-video'; break;
                                                case 'phone': $icon = 'fas fa-phone'; break;
                                                case 'email': $icon = 'fas fa-envelope'; break;
                                                default: $icon = 'fas fa-handshake';
                                            }
                                            echo '<i class="' . $icon . '"></i> ' . $method;
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            switch ($report['status']) {
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
                                        <td class="text-center">
                                            <?php 
                                            if ($report['rating']) {
                                                echo '<strong>' . $report['rating'] . ' / 5</strong>';
                                                // Add star display
                                                echo '<br><small>';
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $report['rating']) {
                                                        echo '★';
                                                    } else {
                                                        echo '☆';
                                                    }
                                                }
                                                echo '</small>';
                                            } else {
                                                echo '<em>N/A</em>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($report['is_anonymous'] == 1): ?>
                                                <span class="badge bg-warning"><i class="fas fa-user-secret"></i> Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php 
                                                $description = $report['issue_description'];
                                                echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                                                ?>
                                            </small>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* OVERRIDE ALL TABLE HEADER STYLES WITH HIGHEST PRIORITY */
body .table thead th,
body .table thead td,
body table.dataTable thead th,
body table.dataTable thead td,
body .dataTables_wrapper table.dataTable thead th,
body .dataTables_wrapper table.dataTable thead td,
body .professional-table thead th,
body .professional-table thead td,
#detailedReportTable thead th,
#detailedReportTable thead td {
    background: #343a40 !important;
    background-image: none !important;
    color: #ffffff !important;
    border: 1px solid #495057 !important;
    border-bottom: 2px solid #495057 !important;
    font-weight: bold !important;
    text-align: center !important;
    padding: 0.75rem !important;
    text-transform: none !important;
    font-size: 0.9rem !important;
    letter-spacing: normal !important;
    vertical-align: middle !important;
}

/* Ensure DataTables headers are visible */
.dataTables_scrollHead .table thead th {
    background: #343a40 !important;
    background-image: none !important;
    color: #ffffff !important;
}

@media print {
    .btn, .filter-form, .no-print {
        display: none !important;
    }
    
    body {
        font-size: 12px !important;
        font-family: 'Times New Roman', serif !important;
        line-height: 1.4;
        color: #000 !important;
    }
    
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        break-inside: avoid;
        margin-bottom: 1rem !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #000 !important;
        font-weight: bold !important;
        font-size: 14px !important;
        padding: 8px 12px !important;
        color: #000 !important;
        text-align: center;
    }
    
    .badge {
        border: 1px solid #000 !important;
        background-color: transparent !important;
        color: #000 !important;
        font-weight: bold !important;
        padding: 2px 6px !important;
    }
    
    .table {
        font-size: 10px !important;
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 20px !important;
    }
    
    .table th, .table td {
        border: 1px solid #000 !important;
        padding: 4px 6px !important;
        text-align: left !important;
        vertical-align: top !important;
    }
    
    .table th {
        background-color: #f0f0f0 !important;
        font-weight: bold !important;
        text-align: center !important;
        font-size: 11px !important;
    }
    
    .table tbody tr:nth-child(even) {
        background-color: #f9f9f9 !important;
    }
    
    h1, h2, h3, h4, h5, h6 {
        color: #000 !important;
        font-weight: bold !important;
        margin-bottom: 10px !important;
    }
    
    h1 {
        font-size: 18px !important;
        text-align: center !important;
        margin-bottom: 20px !important;
        text-transform: uppercase;
        border-bottom: 2px solid #000 !important;
        padding-bottom: 10px !important;
    }
    
    .print-header {
        text-align: center;
        margin-bottom: 2rem;
        border-bottom: 2px solid #000;
        padding-bottom: 15px;
    }
    
    .print-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 8px;
        color: #666;
        border-top: 1px solid #ccc;
        padding-top: 5px;
    }
    
    .row {
        margin: 0 !important;
    }
    
    .col-md-3, .col-md-6, .col-md-12 {
        padding: 0 5px !important;
    }
    
    .text-muted {
        color: #666 !important;
    }
    
    .fs-4 {
        font-size: 16px !important;
        font-weight: bold !important;
    }
    
    .small {
        font-size: 9px !important;
    }
    
    /* Hide charts for print */
    canvas {
        display: none !important;
    }
}

/* Screen styles */
@media screen {
    .table {
        font-size: 0.9rem;
        border: 1px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
        padding: 0.5rem;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,.05);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.075);
        cursor: pointer;
    }
    
    .card {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
        padding: 0.75rem 1.25rem;
    }
    
    /* DataTables specific styling */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
        color: #495057;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        margin-left: 2px;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        background: #fff;
        color: #495057;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #e9ecef;
        border-color: #adb5bd;
        color: #495057;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #007bff;
        border-color: #007bff;
        color: #fff;
    }
}

.quick-filter-active {
    background-color: #0d6efd !important;
    color: white !important;
}

.stat-highlight {
    font-size: 1.2rem;
    font-weight: bold;
    color: #0d6efd;
}

.professional-table {
    border: 2px solid #000;
    border-collapse: collapse;
}

.professional-table tbody td {
    border: 1px solid #dee2e6 !important;
    padding: 0.5rem !important;
    vertical-align: middle !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#detailedReportTable').DataTable({
        order: [[1, 'desc']]
    });
    
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Active', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $chart_data['pending'] ?? 0; ?>,
                    <?php echo $chart_data['active'] ?? 0; ?>,
                    <?php echo $chart_data['completed'] ?? 0; ?>,
                    <?php echo $chart_data['cancelled'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#0d6efd', // Primary (Pending)
                    '#198754', // Success (Active)
                    '#ffc107', // Warning (Completed)
                    '#dc3545'  // Danger (Cancelled)
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Timeline Chart
    const timelineCtx = document.getElementById('timelineChart').getContext('2d');
    const timelineChart = new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Consultations',
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 