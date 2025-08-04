<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';

// Check if user is logged in and has counselor role
requireRole('counselor');

// Set page title
$page_title = 'My Reports';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get date range filter
$default_start_date = date('Y-m-d', strtotime('-30 days'));
$default_end_date = date('Y-m-d');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end_date;

// Get consultation statistics
$query = "SELECT 
          COUNT(*) as total_consultations,
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_consultations,
          SUM(CASE WHEN status = 'live' THEN 1 ELSE 0 END) as active_consultations,
          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_consultations,
          SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_consultations
          FROM consultation_requests
          WHERE counselor_id = ?
          AND preferred_date BETWEEN ? AND ?";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $start_date, $end_date]);
$consultation_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure all keys exist with default values
$defaults = [
    'total_consultations' => 0,
    'pending_consultations' => 0,
    'active_consultations' => 0,
    'completed_consultations' => 0,
    'cancelled_consultations' => 0
];
$consultation_stats = array_merge($defaults, $consultation_stats ? $consultation_stats : []);

// Get average rating
$query = "SELECT AVG(f.rating) as average_rating, COUNT(f.id) as rating_count
          FROM feedback f
          JOIN consultation_requests cr ON f.consultation_id = cr.id
          WHERE cr.counselor_id = ?
          AND cr.preferred_date BETWEEN ? AND ?";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $start_date, $end_date]);
$rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
$average_rating = $rating_data['average_rating'] ? round($rating_data['average_rating'], 1) : 0;
$rating_count = $rating_data['rating_count'] ? $rating_data['rating_count'] : 0;

// Get consultation trends (by day)
$query = "SELECT DATE(preferred_date) as date, COUNT(*) as count
          FROM consultation_requests
          WHERE counselor_id = ?
          AND preferred_date BETWEEN ? AND ?
          GROUP BY DATE(preferred_date)
          ORDER BY DATE(preferred_date)";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $start_date, $end_date]);
$consultation_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get issue categories
$query = "SELECT issue_category, COUNT(*) as count
          FROM consultation_requests
          WHERE counselor_id = ?
          AND preferred_date BETWEEN ? AND ?
          AND issue_category IS NOT NULL AND issue_category != ''
          GROUP BY issue_category
          ORDER BY count DESC
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $start_date, $end_date]);
$issue_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent feedback
$query = "SELECT f.*, cr.issue_category, cr.is_anonymous,
          u.first_name, u.last_name
          FROM feedback f
          JOIN consultation_requests cr ON f.consultation_id = cr.id
          JOIN users u ON cr.student_id = u.user_id
          WHERE cr.counselor_id = ?
          AND cr.preferred_date BETWEEN ? AND ?
          ORDER BY f.created_at DESC
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute([$user_id, $start_date, $end_date]);
$recent_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <form action="" method="get" class="form-inline filter-form">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="start_date" class="col-form-label">From:</label>
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
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-secondary btn-print" data-report-title="Counselor Reports">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<div class="date-range-text print-only">
    Report Period: <?php echo date('F d, Y', strtotime($start_date)); ?> - <?php echo date('F d, Y', strtotime($end_date)); ?>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-left-primary shadow-sm">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Consultations</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $consultation_stats['total_consultations']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-left-success shadow-sm">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $consultation_stats['completed_consultations']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-left-warning shadow-sm">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $consultation_stats['pending_consultations']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-left-info shadow-sm">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Rating</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $average_rating; ?> / 5</div>
                        <div class="small text-muted"><?php echo $rating_count; ?> ratings</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-star fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Status Distribution Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Consultation Status Distribution</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Consultation Trends Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Consultation Trends</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Issue Categories -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Top Issue Categories</h5>
            </div>
            <div class="card-body">
                <?php if (empty($issue_categories)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <p>No issue categories found for the selected date range.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Feedback -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Feedback</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_feedback)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p>No feedback received for the selected date range.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_feedback as $feedback): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <?php if ($feedback['is_anonymous']): ?>
                                            <span class="text-muted">Anonymous Student</span>
                                        <?php else: ?>
                                            <strong><?php echo $feedback['first_name'] . ' ' . $feedback['last_name']; ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mb-1"><?php echo $feedback['comments']; ?></p>
                                <small class="text-muted">
                                    <?php echo !empty($feedback['issue_category']) ? $feedback['issue_category'] : 'General Consultation'; ?> - 
                                    <?php echo timeAgo($feedback['created_at']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Active', 'Pending', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $consultation_stats['completed_consultations']; ?>,
                    <?php echo $consultation_stats['active_consultations']; ?>,
                    <?php echo $consultation_stats['pending_consultations']; ?>,
                    <?php echo $consultation_stats['cancelled_consultations']; ?>
                ],
                backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#2c9faf', '#f4b619', '#e02d1b'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%',
        },
    });
    
    // Consultation Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    const trendsData = {
        labels: [
            <?php 
            $dates = [];
            $counts = [];
            foreach ($consultation_trends as $trend) {
                $dates[] = "'" . formatDate($trend['date'], 'M d') . "'";
                $counts[] = $trend['count'];
            }
            echo implode(', ', $dates);
            ?>
        ],
        datasets: [{
            label: 'Consultations',
            data: [<?php echo implode(', ', $counts); ?>],
            backgroundColor: 'rgba(78, 115, 223, 0.2)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
            tension: 0.3
        }]
    };
    
    new Chart(trendsCtx, {
        type: 'line',
        data: trendsData,
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    <?php if (!empty($issue_categories)): ?>
    // Categories Chart
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesData = {
        labels: [
            <?php 
            $categories = [];
            $categoryCounts = [];
            foreach ($issue_categories as $category) {
                $categories[] = "'" . $category['issue_category'] . "'";
                $categoryCounts[] = $category['count'];
            }
            echo implode(', ', $categories);
            ?>
        ],
        datasets: [{
            label: 'Consultations',
            data: [<?php echo implode(', ', $categoryCounts); ?>],
            backgroundColor: [
                'rgba(78, 115, 223, 0.8)',
                'rgba(28, 200, 138, 0.8)',
                'rgba(246, 194, 62, 0.8)',
                'rgba(54, 185, 204, 0.8)',
                'rgba(231, 74, 59, 0.8)'
            ],
            borderWidth: 1
        }]
    };
    
    new Chart(categoriesCtx, {
        type: 'bar',
        data: categoriesData,
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 