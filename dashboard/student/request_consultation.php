<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Consultation.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$page_title = 'Request Consultation';

// Get user data
$user_id = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create consultation object
$consultation = new Consultation($db);

// Get counselors for selection
$query = "SELECT user_id, first_name, last_name FROM users WHERE role_id = ? AND is_verified = 1";
$stmt = $db->prepare($query);
$stmt->execute([ROLE_COUNSELOR]);
$counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_description = sanitizeInput($_POST['issue_description']);
    $issue_category = sanitizeInput($_POST['issue_category']);
    $preferred_date = sanitizeInput($_POST['preferred_date']);
    $preferred_time = sanitizeInput($_POST['preferred_time']);
    $communication_method = sanitizeInput($_POST['communication_method']);
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $counselor_id = !empty($_POST['counselor_id']) ? (int)$_POST['counselor_id'] : null;
    
    // Validate input
    if (empty($issue_description) || empty($preferred_date) || empty($preferred_time) || empty($communication_method)) {
        setMessage('All required fields must be filled out.', 'danger');
    } else {
        // Create consultation request
        $result = $consultation->createRequest(
            $user_id,
            $issue_description,
            $preferred_date,
            $preferred_time,
            $communication_method,
            $is_anonymous,
            $issue_category,
            $counselor_id
        );
        
        if ($result) {
            setMessage('Consultation request submitted successfully.', 'success');
            
            // Redirect to prevent form resubmission
            redirect(SITE_URL . '/dashboard/student/consultations.php');
            exit;
        } else {
            setMessage('Failed to submit consultation request.', 'danger');
        }
    }
}

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-2 text-gradient">Request Consultation</h1>
            <p class="text-muted">Submit a request for academic support or counseling guidance</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/student/">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Request Consultation</li>
            </ol>
        </nav>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Request Form Card -->
            <div class="card shadow-lg border-0">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-primary text-white me-3">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">New Consultation Request</h5>
                            <small class="text-light opacity-75">Fill out the form below to request support</small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php displayMessage(); ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <!-- Issue Category -->
                            <div class="col-md-6 mb-4">
                                <label for="issue_category" class="form-label fw-medium">
                                    <i class="fas fa-tags text-primary me-2"></i>Issue Category
                                </label>
                                <select class="form-select form-select-lg" id="issue_category" name="issue_category">
                                    <option value="">Select a category</option>
                                    <option value="Academic">Academic Performance</option>
                                    <option value="Career">Career Guidance</option>
                                    <option value="Relationships">Personal Issues</option>
                                    <option value="Social">Social Issues</option>
                                    <option value="Financial">Financial Concerns</option>
                                    <option value="Mental Health">Mental Health</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Preferred Counselor -->
                            <div class="col-md-6 mb-4">
                                <label for="counselor_id" class="form-label fw-medium">
                                    <i class="fas fa-user-tie text-primary me-2"></i>Preferred Counselor
                                    <small class="text-muted">(Optional)</small>
                                </label>
                                <select class="form-select form-select-lg" id="counselor_id" name="counselor_id">
                                    <option value="">Any available counselor</option>
                                    <?php foreach ($counselors as $counselor): ?>
                                        <option value="<?php echo $counselor['user_id']; ?>">
                                            <?php echo $counselor['first_name'] . ' ' . $counselor['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Issue Description -->
                        <div class="mb-4">
                            <label for="issue_description" class="form-label fw-medium">
                                <i class="fas fa-comment-alt text-primary me-2"></i>Describe Your Concern
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control form-control-lg" 
                                    id="issue_description" 
                                    name="issue_description" 
                                    rows="5" 
                                    placeholder="Please describe what you'd like to discuss or the support you need..."
                                    required></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Provide as much detail as possible to help us understand your needs better.
                            </div>
                        </div>

                        <div class="row">
                            <!-- Preferred Date -->
                            <div class="col-md-6 mb-4">
                                <label for="preferred_date" class="form-label fw-medium">
                                    <i class="fas fa-calendar text-primary me-2"></i>Preferred Date
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg" 
                                       id="preferred_date" 
                                       name="preferred_date" 
                                       required
                                       disabled>
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Select a counselor first to see available dates
                                </div>
                            </div>

                            <!-- Preferred Time -->
                            <div class="col-md-6 mb-4">
                                <label for="preferred_time" class="form-label fw-medium">
                                    <i class="fas fa-clock text-primary me-2"></i>Preferred Time
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg" id="preferred_time" name="preferred_time" required disabled>
                                    <option value="">Select a date first</option>
                                </select>
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Available times will appear after selecting a date
                                </div>
                            </div>
                        </div>

                        <!-- Communication Method -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-comments text-primary me-2"></i>Preferred Communication Method
                                <span class="text-danger">*</span>
                            </label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="communication_method" id="face_to_face" value="Face-to-face" required>
                                        <label class="form-check-label fw-medium" for="face_to_face">
                                            <i class="fas fa-handshake text-success me-2"></i>Face-to-face
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="communication_method" id="online" value="Online" required>
                                        <label class="form-check-label fw-medium" for="online">
                                            <i class="fas fa-video text-info me-2"></i>Online/Online Chat
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="communication_method" id="phone" value="Phone" required>
                                        <label class="form-check-label fw-medium" for="phone">
                                            <i class="fas fa-phone text-warning me-2"></i>Phone
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Anonymous Option -->
                        <div class="mb-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous">
                                        <label class="form-check-label fw-medium" for="is_anonymous">
                                            <i class="fas fa-user-secret text-secondary me-2"></i>Request Anonymous Consultation
                                        </label>
                                    </div>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Your identity will be kept confidential during the consultation session.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?php echo SITE_URL; ?>/dashboard/student/" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-warning btn-lg me-2">
                                    <i class="fas fa-redo me-2"></i>Reset Form
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Request
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-4 border-0 bg-light">
                <div class="card-body text-center">
                    <div class="icon-circle bg-info text-white mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-question-circle" style="font-size: 1.5rem;"></i>
                    </div>
                    <h5 class="card-title">Need Help?</h5>
                    <p class="card-text text-muted">
                        If you have any questions about the consultation process or need immediate assistance, 
                        please contact our support team.
                    </p>
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-phone text-primary mb-2"></i>
                            <p class="small mb-0"><strong>Phone</strong><br>(555) 123-4567</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-envelope text-primary mb-2"></i>
                            <p class="small mb-0"><strong>Email</strong><br>support@egabay.edu</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-clock text-primary mb-2"></i>
                            <p class="small mb-0"><strong>Hours</strong><br>Mon-Fri 8AM-5PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.form-check-lg .form-check-input {
    transform: scale(1.2);
}

.form-check-lg .form-check-label {
    padding-left: 0.5rem;
}

.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.needs-validation .form-control:invalid {
    border-color: #dc3545;
}

.needs-validation .form-control:valid {
    border-color: #28a745;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for preferred date input to today
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('preferred_date');
    const timeSelect = document.getElementById('preferred_time');
    const counselorSelect = document.getElementById('counselor_id');
    
    dateInput.setAttribute('min', today);
    
    // Handle counselor selection change
    counselorSelect.addEventListener('change', function() {
        const counselorId = this.value;
        const dateInput = document.getElementById('preferred_date');
        const timeSelect = document.getElementById('preferred_time');
        
        if (counselorId) {
            // Enable date input and get counselor availability
            dateInput.disabled = false;
            dateInput.value = '';
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
            
            // Update date input help text
            const dateHelp = dateInput.nextElementSibling;
            dateHelp.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading available dates...';
            
            // Fetch counselor availability
            fetch(`<?php echo SITE_URL; ?>/api/get_counselor_availability.php?counselor_id=${counselorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Set up date restrictions based on counselor availability
                        setupDateRestrictions(data.available_days);
                        dateHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Select from available dates based on counselor schedule';
                    } else {
                        dateHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error loading availability: ' + data.error;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    dateHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error loading counselor availability';
                });
        } else {
            // For "Any available counselor" option, enable date input without restrictions
            dateInput.disabled = false;
            dateInput.value = '';
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
            
            const dateHelp = dateInput.nextElementSibling;
            dateHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Select any date - we will find an available counselor for you';
            
            // Clear any previous date restrictions
            dateInput.onchange = null;
            setupDateRestrictions([]);
        }
    });
    
    // Handle date selection change
    dateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        const counselorId = counselorSelect.value;
        
        if (selectedDate) {
            if (counselorId) {
                // Specific counselor selected
                timeSelect.disabled = false;
                timeSelect.innerHTML = '<option value="">Loading available times...</option>';
                
                fetch(`<?php echo SITE_URL; ?>/api/get_counselor_availability.php?counselor_id=${counselorId}&date=${selectedDate}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            timeSelect.innerHTML = '<option value="">Select a time</option>';
                            
                            if (data.time_slots && data.time_slots.length > 0) {
                                data.time_slots.forEach(slot => {
                                    const option = document.createElement('option');
                                    option.value = slot.value;
                                    option.textContent = slot.label;
                                    timeSelect.appendChild(option);
                                });
                                
                                const timeHelp = timeSelect.nextElementSibling;
                                timeHelp.innerHTML = `<i class="fas fa-info-circle me-1"></i>${data.time_slots.length} available time slots`;
                            } else {
                                timeSelect.innerHTML = '<option value="">No available times</option>';
                                const timeHelp = timeSelect.nextElementSibling;
                                timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + (data.message || 'No available times for this date');
                            }
                        } else {
                            timeSelect.innerHTML = '<option value="">Error loading times</option>';
                            const timeHelp = timeSelect.nextElementSibling;
                            timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error: ' + data.error;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        timeSelect.innerHTML = '<option value="">Error loading times</option>';
                        const timeHelp = timeSelect.nextElementSibling;
                        timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error loading available times';
                    });
            } else {
                // "Any available counselor" selected - show standard business hours
                timeSelect.disabled = false;
                timeSelect.innerHTML = '<option value="">Select a time</option>';
                
                // Standard business hours
                const standardTimes = [
                    {value: '08:00', label: '8:00 AM'},
                    {value: '09:00', label: '9:00 AM'},
                    {value: '10:00', label: '10:00 AM'},
                    {value: '11:00', label: '11:00 AM'},
                    {value: '13:00', label: '1:00 PM'},
                    {value: '14:00', label: '2:00 PM'},
                    {value: '15:00', label: '3:00 PM'},
                    {value: '16:00', label: '4:00 PM'}
                ];
                
                standardTimes.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.value;
                    option.textContent = slot.label;
                    timeSelect.appendChild(option);
                });
                
                const timeHelp = timeSelect.nextElementSibling;
                timeHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>We will assign an available counselor for your preferred time';
            }
        } else {
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
        }
    });
    
    // Function to set up date restrictions based on counselor availability
    function setupDateRestrictions(availableDays) {
        if (!availableDays || availableDays.length === 0) {
            return;
        }
        
        // Convert available days to day numbers (0 = Sunday, 1 = Monday, etc.)
        const availableDayNumbers = availableDays.map(day => {
            const dayMap = {
                'sunday': 0, 'monday': 1, 'tuesday': 2, 'wednesday': 3,
                'thursday': 4, 'friday': 5, 'saturday': 6
            };
            return dayMap[day.day.toLowerCase()];
        });
        
        // Add custom validation to prevent selection of unavailable days
        dateInput.addEventListener('input', function() {
            const selectedDate = new Date(this.value);
            const dayOfWeek = selectedDate.getDay();
            
            if (!availableDayNumbers.includes(dayOfWeek)) {
                this.setCustomValidity('Counselor is not available on this day. Please select another date.');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Character counter for textarea
    const textarea = document.getElementById('issue_description');
    const maxLength = 1000;
    
    // Create character counter element
    const counter = document.createElement('div');
    counter.className = 'form-text text-end';
    counter.innerHTML = `<span id="char-count">0</span>/${maxLength} characters`;
    textarea.parentNode.appendChild(counter);
    
    // Update character count
    textarea.addEventListener('input', function() {
        const count = this.value.length;
        document.getElementById('char-count').textContent = count;
        
        if (count > maxLength * 0.9) {
            counter.classList.add('text-warning');
        } else {
            counter.classList.remove('text-warning');
        }
        
        if (count > maxLength) {
            counter.classList.add('text-danger');
            counter.classList.remove('text-warning');
        } else {
            counter.classList.remove('text-danger');
        }
    });
    
    // Set max length attribute
    textarea.setAttribute('maxlength', maxLength);
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 