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
$page_title = 'My Schedule';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_availability') {
        // Get form data
        $availability = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if (isset($_POST[$day . '_available']) && $_POST[$day . '_available'] === '1') {
                $availability[$day] = [
                    'available' => true,
                    'start_time' => $_POST[$day . '_start_time'],
                    'end_time' => $_POST[$day . '_end_time']
                ];
            } else {
                $availability[$day] = [
                    'available' => false,
                    'start_time' => '',
                    'end_time' => ''
                ];
            }
        }
        
        // Update counselor profile
        $query = "UPDATE counselor_profiles SET availability = ? WHERE user_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([json_encode($availability), $user_id])) {
            setMessage('Schedule updated successfully.', 'success');
        } else {
            setMessage('Failed to update schedule.', 'danger');
        }
    }
}

// Get counselor profile and availability
$query = "SELECT * FROM counselor_profiles WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Parse availability
$availability = [];
if ($profile && isset($profile['availability'])) {
    $availability = json_decode($profile['availability'], true);
}

// Default availability if not set
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
foreach ($days as $day) {
    if (!isset($availability[$day])) {
        $availability[$day] = [
            'available' => false,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ];
    }
}

// Get upcoming consultations
$query = "SELECT cr.*, 
          u.first_name, u.last_name, u.email
          FROM consultation_requests cr
          JOIN users u ON cr.student_id = u.user_id
          WHERE cr.counselor_id = ? AND cr.status IN ('pending', 'live')
          AND cr.preferred_date >= CURDATE()
          ORDER BY cr.preferred_date ASC, cr.preferred_time ASC
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$upcoming_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>My Schedule</h1>
        <p class="text-muted">Manage your availability and view upcoming consultations</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Set Your Availability</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <input type="hidden" name="action" value="update_availability">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Day</strong>
                        </div>
                        <div class="col-md-3">
                            <strong>Available</strong>
                        </div>
                        <div class="col-md-3">
                            <strong>Start Time</strong>
                        </div>
                        <div class="col-md-3">
                            <strong>End Time</strong>
                        </div>
                    </div>
                    
                    <?php foreach ($days as $day): ?>
                        <div class="row mb-3 align-items-center">
                            <div class="col-md-3">
                                <label for="<?php echo $day; ?>_available" class="form-label mb-0 text-capitalize"><?php echo $day; ?></label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input availability-toggle" type="checkbox" id="<?php echo $day; ?>_available" name="<?php echo $day; ?>_available" value="1" <?php echo isset($availability[$day]) && $availability[$day]['available'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="<?php echo $day; ?>_available">Available</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control time-input" id="<?php echo $day; ?>_start_time" name="<?php echo $day; ?>_start_time" value="<?php echo isset($availability[$day]) ? $availability[$day]['start_time'] : '09:00'; ?>" <?php echo isset($availability[$day]) && !$availability[$day]['available'] ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control time-input" id="<?php echo $day; ?>_end_time" name="<?php echo $day; ?>_end_time" value="<?php echo isset($availability[$day]) ? $availability[$day]['end_time'] : '17:00'; ?>" <?php echo isset($availability[$day]) && !$availability[$day]['available'] ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Upcoming Consultations</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcoming_consultations)): ?>
                    <div class="p-4 text-center">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <p>No upcoming consultations scheduled.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcoming_consultations as $consultation): ?>
                            <a href="<?php echo SITE_URL; ?>/dashboard/counselor/view_consultation.php?id=<?php echo $consultation['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?php if ($consultation['is_anonymous']): ?>
                                            <span class="text-muted">Anonymous Student</span>
                                        <?php else: ?>
                                            <?php echo $consultation['first_name'] . ' ' . $consultation['last_name']; ?>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-<?php echo $consultation['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                        <?php echo ucfirst($consultation['status']); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <?php 
                                    echo !empty($consultation['issue_category']) ? 
                                        $consultation['issue_category'] : 
                                        'General Consultation'; 
                                    ?>
                                </p>
                                <small>
                                    <i class="fas fa-calendar-alt me-1"></i> 
                                    <?php echo formatDate($consultation['preferred_date'], 'M d, Y'); ?> at 
                                    <?php echo formatTime($consultation['preferred_time']); ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle time inputs based on availability checkbox
    const availabilityToggles = document.querySelectorAll('.availability-toggle');
    
    availabilityToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.id.replace('_available', '');
            const startTimeInput = document.getElementById(day + '_start_time');
            const endTimeInput = document.getElementById(day + '_end_time');
            
            if (this.checked) {
                startTimeInput.disabled = false;
                endTimeInput.disabled = false;
            } else {
                startTimeInput.disabled = true;
                endTimeInput.disabled = true;
            }
        });
    });
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 