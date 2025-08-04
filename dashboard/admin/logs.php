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

// Handle clear all logs action
if (isset($_POST['clear_all_logs'])) {
    try {
        // Confirm with CSRF token for security
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
        
        $stmt = $db->prepare("DELETE FROM system_logs");
        $stmt->execute();
        
        $deleted_count = $stmt->rowCount();
        
        // Log this action
        logActivity($db, $_SESSION['user_id'], 'clear_logs', "Cleared all system logs ({$deleted_count} entries)", $_SERVER['REMOTE_ADDR']);
        
        setMessage("Successfully cleared {$deleted_count} log entries.", 'success');
        
        // Redirect to avoid resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        setMessage('Error clearing logs: ' . $e->getMessage(), 'danger');
    }
}

// Set page title
$page_title = 'System Logs';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get log type filter
$log_type = isset($_GET['type']) ? $_GET['type'] : '';

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days')); // Last 7 days by default
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

// Build query
$query = "SELECT sl.*, u.username, u.first_name, u.last_name 
          FROM system_logs sl
          LEFT JOIN users u ON sl.user_id = u.user_id
          WHERE sl.created_at BETWEEN :start_date AND :end_date";

// Add type filter if provided
if (!empty($log_type)) {
    $query .= " AND sl.action = :log_type";
}

$query .= " ORDER BY sl.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 50;
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$count_query = str_replace("SELECT sl.*, u.username, u.first_name, u.last_name", "SELECT COUNT(*) as total", $query);
$stmt = $db->prepare($count_query);

// Bind parameters
$end_date_adjusted = date('Y-m-d', strtotime($end_date . ' +1 day')); // Include the end date fully
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);

if (!empty($log_type)) {
    $stmt->bindParam(':log_type', $log_type);
}

$stmt->execute();
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to query
$query .= " LIMIT :offset, :records_per_page";

// Execute query with pagination
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date_adjusted);

if (!empty($log_type)) {
    $stmt->bindParam(':log_type', $log_type);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available log types for filter
$query = "SELECT DISTINCT action FROM system_logs ORDER BY action";
$stmt = $db->prepare($query);
$stmt->execute();
$log_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Include header
include_once $base_path . '/includes/header.php';

// Generate CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">System Logs</h1>
        <p class="lead">View and analyze system activity logs.</p>
    </div>
</div>

<!-- Filter Form -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Filter Logs</h5>
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Log Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($log_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $log_type === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-history me-1"></i>
                System Logs
                <span class="badge bg-primary ms-2"><?php echo $total_records; ?> entries</span>
            </div>
            <?php if ($total_records > 0): ?>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                    <i class="fas fa-trash me-1"></i>Clear All Logs
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <p class="text-center">No logs found for the selected criteria.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo formatDate($log['created_at'], 'M d, Y h:i:s A'); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'bg-secondary';
                                    switch ($log['action']) {
                                        case 'login':
                                            $badge_class = 'bg-success';
                                            break;
                                        case 'logout':
                                            $badge_class = 'bg-info';
                                            break;
                                        case 'error':
                                            $badge_class = 'bg-danger';
                                            break;
                                        case 'warning':
                                            $badge_class = 'bg-warning';
                                            break;
                                        case 'security':
                                            $badge_class = 'bg-dark';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($log['user_id']) {
                                        echo $log['username'] . ' (' . $log['first_name'] . ' ' . $log['last_name'] . ')';
                                    } else {
                                        echo '<span class="text-muted">System</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $log['ip_address']; ?></td>
                                <td><?php echo $log['details']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?page=' . ($page - 1) . (!empty($log_type) ? '&type=' . $log_type : '') . '&start_date=' . $start_date . '&end_date=' . $end_date; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        // Calculate range of page numbers to display
                        $range = 2; // Display 2 pages before and after current page
                        $start_page = max(1, $page - $range);
                        $end_page = min($total_pages, $page + $range);
                        
                        // Always show first page
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?page=1' . (!empty($log_type) ? '&type=' . $log_type : '') . '&start_date=' . $start_date . '&end_date=' . $end_date . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                        }
                        
                        // Display page numbers
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?page=' . $i . (!empty($log_type) ? '&type=' . $log_type : '') . '&start_date=' . $start_date . '&end_date=' . $end_date . '">' . $i . '</a></li>';
                        }
                        
                        // Always show last page
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?page=' . $total_pages . (!empty($log_type) ? '&type=' . $log_type : '') . '&start_date=' . $start_date . '&end_date=' . $end_date . '">' . $total_pages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?page=' . ($page + 1) . (!empty($log_type) ? '&type=' . $log_type : '') . '&start_date=' . $start_date . '&end_date=' . $end_date; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Log Summary -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-pie me-1"></i>
                Log Type Distribution
            </div>
            <div class="card-body">
                <?php
                // Get log type distribution
                $query = "SELECT action, COUNT(*) as count
                          FROM system_logs
                          WHERE created_at BETWEEN :start_date AND :end_date
                          GROUP BY action
                          ORDER BY count DESC";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':end_date', $end_date_adjusted);
                $stmt->execute();
                $log_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Prepare data for chart
                $log_labels = [];
                $log_counts = [];
                $log_colors = [];
                
                $color_map = [
                    'login' => '#198754', // Success
                    'logout' => '#0dcaf0', // Info
                    'error' => '#dc3545', // Danger
                    'warning' => '#ffc107', // Warning
                    'security' => '#212529', // Dark
                    'info' => '#0d6efd', // Primary
                    'system' => '#6c757d' // Secondary
                ];
                
                foreach ($log_distribution as $item) {
                    $log_labels[] = ucfirst($item['action']);
                    $log_counts[] = $item['count'];
                    $log_colors[] = $color_map[$item['action']] ?? '#6c757d'; // Default to secondary if not mapped
                }
                ?>
                
                <?php if (empty($log_distribution)): ?>
                    <p class="text-center">No data available for the selected period.</p>
                <?php else: ?>
                    <canvas id="logTypeChart" width="100%" height="50"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Daily Log Activity
            </div>
            <div class="card-body">
                <?php
                // Get daily log activity
                $query = "SELECT DATE(created_at) as date, COUNT(*) as count
                          FROM system_logs
                          WHERE created_at BETWEEN :start_date AND :end_date
                          GROUP BY DATE(created_at)
                          ORDER BY date";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':end_date', $end_date_adjusted);
                $stmt->execute();
                $daily_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Prepare data for chart
                $dates = [];
                $counts = [];
                
                foreach ($daily_activity as $item) {
                    $dates[] = date('M d', strtotime($item['date']));
                    $counts[] = $item['count'];
                }
                ?>
                
                <?php if (empty($daily_activity)): ?>
                    <p class="text-center">No data available for the selected period.</p>
                <?php else: ?>
                    <canvas id="dailyActivityChart" width="100%" height="50"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Button (Admin only) -->
<div class="row mb-4">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
            <i class="fas fa-trash me-2"></i> Clear Old Logs
        </button>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-labelledby="clearLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearLogsModalLabel">Clear Old Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="modal-body">
                    <p>Select how old logs should be to be deleted:</p>
                    
                    <div class="mb-3">
                        <select class="form-select" name="clear_older_than" required>
                            <option value="30">Older than 30 days</option>
                            <option value="60">Older than 60 days</option>
                            <option value="90">Older than 90 days</option>
                            <option value="180">Older than 6 months</option>
                            <option value="365">Older than 1 year</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Warning: This action cannot be undone. All logs older than the selected period will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="clear_logs" class="btn btn-danger">Clear Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear All Logs Confirmation Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-labelledby="clearLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearLogsModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirm Clear All Logs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>Warning!</strong> This action cannot be undone.
                    </div>
                </div>
                <p>Are you sure you want to clear all system logs? This will permanently delete:</p>
                <ul>
                    <li><strong><?php echo number_format($total_records); ?> log entries</strong></li>
                    <li>All login/logout records</li>
                    <li>All user activity history</li>
                    <li>All system event logs</li>
                </ul>
                <p class="text-muted mb-0">
                    <small><i class="fas fa-info-circle me-1"></i>
                    This action will be logged as a new entry after completion.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" name="clear_all_logs" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Clear All Logs
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($log_distribution)): ?>
    // Log Type Chart
    const logTypeCtx = document.getElementById('logTypeChart').getContext('2d');
    const logTypeChart = new Chart(logTypeCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($log_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($log_counts); ?>,
                backgroundColor: <?php echo json_encode($log_colors); ?>,
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
    <?php endif; ?>
    
    <?php if (!empty($daily_activity)): ?>
    // Daily Activity Chart
    const dailyActivityCtx = document.getElementById('dailyActivityChart').getContext('2d');
    const dailyActivityChart = new Chart(dailyActivityCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Log Entries',
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
    <?php endif; ?>
});
</script>

<?php
// Process clear logs request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $days = (int)$_POST['clear_older_than'];
    
    if ($days > 0) {
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $query = "DELETE FROM system_logs WHERE created_at < :threshold";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':threshold', $date_threshold);
        
        if ($stmt->execute()) {
            $deleted_count = $stmt->rowCount();
            
            // Log this action
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $description = "Cleared $deleted_count logs older than $days days";
            
            $log_query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$_SESSION['user_id'], 'system', $ip_address, $description]);
            
            setMessage("Successfully deleted $deleted_count logs older than $days days.", 'success');
        } else {
            setMessage('Failed to clear logs.', 'danger');
        }
        
        // Redirect to refresh the page and prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . (!empty($log_type) ? "?type=$log_type" : "") . "&start_date=$start_date&end_date=$end_date");
        exit;
    }
}

// Include footer
include_once $base_path . '/includes/footer.php';
?> 