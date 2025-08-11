<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/utility.php';
require_once $base_path . '/includes/auth.php';

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
    try {
        // Use trim instead of sanitizeInput to avoid HTML entity encoding
        $issue_description = trim($_POST['issue_description'] ?? '');
        $issue_category = trim($_POST['issue_category'] ?? '');
        $preferred_date = trim($_POST['preferred_date'] ?? '');
        $preferred_time = trim($_POST['preferred_time'] ?? '');
        $communication_method = trim($_POST['communication_method'] ?? '');
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        $counselor_id = !empty($_POST['counselor_id']) ? (int)$_POST['counselor_id'] : null;
        
        // Enhanced validation
        $errors = [];
        
        if (empty($issue_description)) {
            $errors[] = 'Please describe your concern or issue.';
        } elseif (strlen($issue_description) < 10) {
            $errors[] = 'Issue description must be at least 10 characters long.';
        }
        
        if (empty($preferred_date)) {
            $errors[] = 'Please select a preferred date.';
        } else {
            $selected_date = strtotime($preferred_date);
            $today = strtotime(date('Y-m-d'));
            if ($selected_date < $today) {
                $errors[] = 'Preferred date cannot be in the past.';
            }
        }
        
        if (empty($preferred_time)) {
            $errors[] = 'Please select a preferred time.';
        }
        
        if (empty($communication_method)) {
            $errors[] = 'Please select a communication method.';
        }
        
        // Check if user exists and is active
        $user_check = $db->prepare("SELECT user_id, is_active FROM users WHERE user_id = ?");
        $user_check->execute([$user_id]);
        $user_data = $user_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_data || !$user_data['is_active']) {
            $errors[] = 'User account is not active. Please contact support.';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                setMessage($error, 'danger');
            }
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
                setMessage('Consultation request submitted successfully! You will be notified when a counselor is assigned.', 'success');
                
                // Redirect to prevent form resubmission
                redirect(SITE_URL . '/dashboard/student/consultations.php');
                exit;
            } else {
                setMessage('Failed to submit consultation request. Please try again or contact support if the problem persists.', 'danger');
            }
        }
        
    } catch (Exception $e) {
        error_log("Consultation request error: " . $e->getMessage());
        setMessage('An unexpected error occurred. Please try again later.', 'danger');
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
                                    <i class="fas fa-calendar text-primary me-2"></i> <-- Preferred Date
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg" 
                                       id="preferred_date" 
                                       name="preferred_date" 
                                       required
                                       disabled
                                       placeholder="Select counselor first">
                                <div class="form-text text-muted" id="date-help">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Please select a counselor first to see available dates
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
                        If you have any questions about the consultation process, need technical assistance, 
                        or have issues with the system, please contact the developer directly.
                    </p>
                    <div class="row text-center">
                        <div class="col-md-6">
                            <i class="fab fa-facebook text-primary mb-2" style="font-size: 1.5rem;"></i>
                            <p class="small mb-0">
                                <strong>Facebook</strong><br>
                                <a href="https://www.facebook.com/Keithtordaofficial1/" target="_blank" class="text-decoration-none">
                                    Keith Torda
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <i class="fas fa-envelope text-primary mb-2" style="font-size: 1.5rem;"></i>
                            <p class="small mb-0">
                                <strong>Email</strong><br>
                                <a href="mailto:keithorario@gmail.com" class="text-decoration-none">
                                    keithorario@gmail.com
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            For immediate system support, consultation help, or technical issues
                        </small>
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

/* Disabled field styling for better UX */
.form-control:disabled, .form-select:disabled {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #6c757d;
    opacity: 0.8;
}

.form-control:disabled::placeholder {
    color: #adb5bd;
    font-style: italic;
}

/* Mobile responsive improvements */
@media (max-width: 768px) {
    .form-control-lg, .form-select-lg {
        font-size: 1rem;
        padding: 0.75rem;
    }
    
    .form-text {
        font-size: 0.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for preferred date input to today
    const today = new Date().toISOString().split('T')[0];
    let dateInput = document.getElementById('preferred_date');
    const timeSelect = document.getElementById('preferred_time');
    const counselorSelect = document.getElementById('counselor_id');
    const siteUrl = '<?php echo rtrim(SITE_URL, '/'); ?>';
    
    dateInput.setAttribute('min', today);
    
    // Initialize form state
    console.log('Form initialized');
    console.log('Counselor select:', counselorSelect);
    console.log('Date input:', dateInput);
    console.log('Time select:', timeSelect);
    
    // Handle counselor selection change
    counselorSelect.addEventListener('change', function() {
        const counselorId = this.value;
        
        if (counselorId) {
            // Enable date input and get counselor availability
            dateInput.disabled = false;
            dateInput.value = '';
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
            
            // Update date input help text
            const dateHelp = document.getElementById('date-help') || dateInput.nextElementSibling;
            dateHelp.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading available dates...';
            
            // Fetch counselor availability
            const apiUrl = `${siteUrl}/api/get_counselor_availability_simple.php?counselor_id=${counselorId}`;
            console.log('Fetching counselor availability from:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    console.log('API Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw API response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        
                        if (data.success) {
                            // Set up date restrictions based on counselor availability
                            setupDateRestrictions(data.available_days);
                            dateHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Select from available dates based on counselor schedule';
                        } else {
                            console.error('API returned error:', data.error);
                            dateHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error loading availability: ' + (data.error || 'Unknown error');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e, 'Raw text:', text);
                        dateHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Invalid response from server';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    dateHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error loading counselor availability';
                });
        } else {
            // For "Any available counselor" option, enable date input without restrictions
            dateInput.disabled = false;
            dateInput.value = '';
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
            
            const dateHelp = document.getElementById('date-help') || dateInput.nextElementSibling;
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
        
        console.log('Date changed:', selectedDate, 'Counselor:', counselorId);
        
        if (selectedDate) {
            if (counselorId) {
                // Specific counselor selected
                console.log('Loading time slots for counselor', counselorId, 'on date', selectedDate);
                timeSelect.disabled = false;
                timeSelect.innerHTML = '<option value="">Loading available times...</option>';
                
                const timeApiUrl = `${siteUrl}/api/get_counselor_availability_simple.php?counselor_id=${counselorId}&date=${selectedDate}`;
                console.log('Fetching time slots from:', timeApiUrl);
                
                fetch(timeApiUrl)
                    .then(response => {
                        console.log('Time API Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(text => {
                        console.log('Raw time API response:', text);
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed time data:', data);
                            
                            if (data.success) {
                                timeSelect.innerHTML = '<option value="">Select a time</option>';
                                timeSelect.disabled = false; // Make sure it's enabled
                                
                                if (data.time_slots && data.time_slots.length > 0) {
                                    console.log('Found time slots:', data.time_slots.length);
                                    data.time_slots.forEach(slot => {
                                        const option = document.createElement('option');
                                        option.value = slot.value;
                                        option.textContent = slot.label;
                                        timeSelect.appendChild(option);
                                    });
                                    
                                    console.log('Time select populated with', timeSelect.options.length - 1, 'time slots');
                                    console.log('Time select disabled?', timeSelect.disabled);
                                    
                                    const timeHelp = timeSelect.nextElementSibling;
                                    if (timeHelp) {
                                        timeHelp.innerHTML = `<i class="fas fa-info-circle me-1"></i>${data.time_slots.length} available time slots`;
                                    }
                                } else {
                                    console.log('No time slots found:', data);
                                    timeSelect.innerHTML = '<option value="">No available times</option>';
                                    timeSelect.disabled = true;
                                    const timeHelp = timeSelect.nextElementSibling;
                                    if (timeHelp) {
                                        timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + (data.message || 'No available times for this date');
                                    }
                                }
                            } else {
                                console.error('Time API returned error:', data.error);
                                timeSelect.innerHTML = '<option value="">Error loading times</option>';
                                timeSelect.disabled = true;
                                const timeHelp = timeSelect.nextElementSibling;
                                if (timeHelp) {
                                    timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error: ' + (data.error || 'Unknown error');
                                }
                            }
                        } catch (e) {
                            console.error('Time JSON parse error:', e, 'Raw text:', text);
                            timeSelect.innerHTML = '<option value="">Invalid response</option>';
                            timeSelect.disabled = true;
                            const timeHelp = timeSelect.nextElementSibling;
                            if (timeHelp) {
                                timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Invalid response from server';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Time fetch error:', error);
                        timeSelect.innerHTML = '<option value="">Error loading times</option>';
                        timeSelect.disabled = true;
                        const timeHelp = timeSelect.nextElementSibling;
                        if (timeHelp) {
                            timeHelp.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error loading available times';
                        }
                    });
            } else {
                // "Any available counselor" selected - show standard business hours
                timeSelect.disabled = false;
                timeSelect.innerHTML = '<option value="">Select a time</option>';
                
                // Standard business hours (matching typical counselor availability)
                const standardTimes = [
                    {value: '08:00', label: '8:00 AM'},
                    {value: '09:00', label: '9:00 AM'},
                    {value: '10:00', label: '10:00 AM'},
                    {value: '11:00', label: '11:00 AM'},
                    {value: '12:00', label: '12:00 PM'}
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
        // Clear any previous validation
        dateInput.setCustomValidity('');
        
        // Remove any existing validation listener by removing the attribute
        dateInput.removeAttribute('data-validation-added');
        
        if (!availableDays || availableDays.length === 0) {
            console.log('No available days provided, allowing all dates');
            return;
        }
        
        console.log('Setting up date restrictions for:', availableDays);
        
        // Convert available days to day numbers (0 = Sunday, 1 = Monday, etc.)
        const availableDayNumbers = availableDays.map(day => {
            const dayMap = {
                'sunday': 0, 'monday': 1, 'tuesday': 2, 'wednesday': 3,
                'thursday': 4, 'friday': 5, 'saturday': 6
            };
            const dayName = day.day ? day.day.toLowerCase() : day.toLowerCase();
            return dayMap[dayName];
        }).filter(num => num !== undefined);
        
        console.log('Available day numbers:', availableDayNumbers);
        
        // Add validation function without destroying the element
        if (!dateInput.hasAttribute('data-validation-added')) {
            dateInput.setAttribute('data-validation-added', 'true');
            
            // Store validation function globally so we can reference it
            window.currentDateValidation = function(inputElement) {
                const selectedDate = new Date(inputElement.value);
                const dayOfWeek = selectedDate.getDay();
                
                console.log('Date selected:', inputElement.value, 'Day of week:', dayOfWeek);
                
                if (availableDayNumbers.length > 0 && !availableDayNumbers.includes(dayOfWeek)) {
                    inputElement.setCustomValidity('Counselor is not available on this day. Please select another date.');
                    console.log('Date validation failed');
                } else {
                    inputElement.setCustomValidity('');
                    console.log('Date validation passed');
                }
            };
            
            // Add input event listener for validation
            dateInput.addEventListener('input', function() {
                if (window.currentDateValidation) {
                    window.currentDateValidation(this);
                }
            });
        } else {
            // Update the existing validation function
            window.currentDateValidation = function(inputElement) {
                const selectedDate = new Date(inputElement.value);
                const dayOfWeek = selectedDate.getDay();
                
                console.log('Date selected:', inputElement.value, 'Day of week:', dayOfWeek);
                
                if (availableDayNumbers.length > 0 && !availableDayNumbers.includes(dayOfWeek)) {
                    inputElement.setCustomValidity('Counselor is not available on this day. Please select another date.');
                    console.log('Date validation failed');
                } else {
                    inputElement.setCustomValidity('');
                    console.log('Date validation passed');
                }
            };
        }
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