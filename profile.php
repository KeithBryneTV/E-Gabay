<?php
// Include path fix helper
require_once __DIR__ . '/includes/path_fix.php';

// Include required files
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';
require_once $base_path . '/classes/Utility.php';

// Check if user is logged in
requireLogin();

// Set page title
$page_title = 'My Profile';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$role = getUserRole();

// Get user details from the database
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize additional profile data
$profile_data = null;

// Get role-specific profile information
if ($role == 'student') {
    $query = "SELECT * FROM student_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($role == 'counselor') {
    $query = "SELECT * FROM counselor_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update basic user information
    if (isset($_POST['update_basic_info'])) {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        
        // Check if email is already taken by another user
        $query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            setMessage('Email address is already in use by another account.', 'danger');
        } else {
            // Update user information
            $query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$first_name, $last_name, $email, $user_id])) {
                // Update session variables
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                
                setMessage('Basic information updated successfully.', 'success');
                
                // Log the action
                logAction('profile_update', 'User updated their basic profile information');
            } else {
                setMessage('Error updating basic information.', 'danger');
            }
        }
    }
    
    // Update student profile information
    elseif (isset($_POST['update_student_profile'])) {
        $student_id = sanitizeInput($_POST['student_id'] ?? '');
        $course = sanitizeInput($_POST['course'] ?? '');
        $year_level = sanitizeInput($_POST['year_level'] ?? '');
        $section = sanitizeInput($_POST['section'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        
        // Check if bio column exists in student_profiles table
        $check_bio_query = "SHOW COLUMNS FROM student_profiles LIKE 'bio'";
        $check_stmt = $db->prepare($check_bio_query);
        $check_stmt->execute();
        $bio_exists = $check_stmt->fetch();
        
        // Check if profile exists
        $query = "SELECT profile_id FROM student_profiles WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing profile
            if ($bio_exists) {
                $query = "UPDATE student_profiles SET student_id = ?, course = ?, year_level = ?, section = ?, bio = ? WHERE user_id = ?";
                $params = [$student_id, $course, $year_level, $section, $bio, $user_id];
            } else {
                $query = "UPDATE student_profiles SET student_id = ?, course = ?, year_level = ?, section = ? WHERE user_id = ?";
                $params = [$student_id, $course, $year_level, $section, $user_id];
            }
        } else {
            // Create new profile
            if ($bio_exists) {
                $query = "INSERT INTO student_profiles (user_id, student_id, course, year_level, section, bio) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$user_id, $student_id, $course, $year_level, $section, $bio];
            } else {
                $query = "INSERT INTO student_profiles (user_id, student_id, course, year_level, section) VALUES (?, ?, ?, ?, ?)";
                $params = [$user_id, $student_id, $course, $year_level, $section];
            }
        }
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            setMessage('Student profile updated successfully.', 'success');
            
            // Update profile data for display
            $query = "SELECT * FROM student_profiles WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log the action
            logAction('profile_update', 'Student updated their profile information');
        } else {
            setMessage('Error updating student profile.', 'danger');
        }
    }
    
    // Update counselor profile information
    elseif (isset($_POST['update_counselor_profile'])) {
        $specialization = sanitizeInput($_POST['specialization'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        
        // Check if bio column exists in counselor_profiles table
        $check_bio_query = "SHOW COLUMNS FROM counselor_profiles LIKE 'bio'";
        $check_stmt = $db->prepare($check_bio_query);
        $check_stmt->execute();
        $bio_exists = $check_stmt->fetch();
        
        // Availability is stored as JSON
        $availability = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $availability[$day] = [
                'available' => isset($_POST[$day.'_available']) ? 1 : 0,
                'start_time' => sanitizeInput($_POST[$day.'_start'] ?? ''),
                'end_time' => sanitizeInput($_POST[$day.'_end'] ?? '')
            ];
        }
        
        $availability_json = json_encode($availability);
        
        // Check if profile exists
        $query = "SELECT profile_id FROM counselor_profiles WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing profile
            if ($bio_exists) {
                $query = "UPDATE counselor_profiles SET specialization = ?, availability = ?, bio = ? WHERE user_id = ?";
                $params = [$specialization, $availability_json, $bio, $user_id];
            } else {
                $query = "UPDATE counselor_profiles SET specialization = ?, availability = ? WHERE user_id = ?";
                $params = [$specialization, $availability_json, $user_id];
            }
        } else {
            // Create new profile
            if ($bio_exists) {
                $query = "INSERT INTO counselor_profiles (user_id, specialization, availability, bio) VALUES (?, ?, ?, ?)";
                $params = [$user_id, $specialization, $availability_json, $bio];
            } else {
                $query = "INSERT INTO counselor_profiles (user_id, specialization, availability) VALUES (?, ?, ?)";
                $params = [$user_id, $specialization, $availability_json];
            }
        }
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            setMessage('Counselor profile updated successfully.', 'success');
            
            // Update profile data for display
            $query = "SELECT * FROM counselor_profiles WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log the action
            logAction('profile_update', 'Counselor updated their profile information');
        } else {
            setMessage('Error updating counselor profile.', 'danger');
        }
    }
    
    // Change profile picture
    elseif (isset($_POST['update_profile_picture'])) {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                setMessage('Invalid file type. Only JPG, PNG, and GIF files are allowed.', 'danger');
            } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                setMessage('File size is too large. Maximum file size is 10MB.', 'danger');
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = $base_path . '/uploads/profile_pictures/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate a unique filename
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . uniqid() . '.' . $file_extension;
                
                // Move the uploaded file
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                    // Delete old profile picture if exists
                    if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
                        unlink($upload_dir . $user['profile_picture']);
                    }
                    
                    // Update database with new profile picture filename
                    $query = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$new_filename, $user_id])) {
                        setMessage('Profile picture updated successfully.', 'success');
                        
                        // Update user data for display
                        $user['profile_picture'] = $new_filename;
                        
                        // Log the action
                        logAction('profile_update', 'User updated their profile picture');
                    } else {
                        setMessage('Error updating profile picture in database.', 'danger');
                    }
                } else {
                    setMessage('Error uploading profile picture.', 'danger');
                }
            }
        } else {
            setMessage('No file uploaded or error occurred.', 'warning');
        }
    }
    
    // Change password
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_data || !password_verify($current_password, $user_data['password'])) {
            setMessage('Current password is incorrect.', 'danger');
        } elseif (strlen($new_password) < 8) {
            setMessage('New password must be at least 8 characters long.', 'danger');
        } elseif ($new_password !== $confirm_password) {
            setMessage('New passwords do not match.', 'danger');
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$hashed_password, $user_id])) {
                setMessage('Password changed successfully.', 'success');
                
                // Log the action
                logAction('password_change', 'User changed their password');
            } else {
                setMessage('Error changing password.', 'danger');
            }
        }
    }
    
    // Redirect to refresh page and show message
    header("Location: " . SITE_URL . "/profile.php");
    exit;
}

// Include header
include_once $base_path . '/includes/header.php';

// Parse availability data for counselors
$availability = [];
if ($role == 'counselor' && $profile_data && isset($profile_data['availability'])) {
    $availability = json_decode($profile_data['availability'], true);
    if (!$availability) {
        $availability = [
            'monday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00'],
            'tuesday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00'],
            'wednesday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00'],
            'thursday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00'],
            'friday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00'],
            'saturday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00'],
            'sunday' => ['available' => 0, 'start_time' => '08:00', 'end_time' => '17:00']
        ];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Profile</h1>
        </div>
    </div>
    
    <div class="row">
        <!-- Profile Picture and Basic Info Column -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="profile-picture-container mb-3">
                        <?php if (!empty($user['profile_picture']) && file_exists($base_path . '/uploads/profile_pictures/' . $user['profile_picture'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Profile Picture" class="rounded-circle" width="150" height="150">
                        <?php else: ?>
                            <div class="profile-placeholder rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; margin: 0 auto;">
                                <i class="fas fa-user fa-5x text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                    <p class="text-muted"><?php echo ucfirst($role); ?></p>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><i class="fas fa-envelope me-2"></i> <?php echo $user['email']; ?></p>
                        
                        <?php if (!empty($user['phone'])): ?>
                            <p><i class="fas fa-phone me-2"></i> <?php echo $user['phone']; ?></p>
                        <?php endif; ?>
                        
                        <p><i class="fas fa-calendar me-2"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        
                        <?php if ($role == 'student' && isset($profile_data)): ?>
                            <?php if (!empty($profile_data['student_id'])): ?>
                                <p><i class="fas fa-id-card me-2"></i> Student ID: <?php echo $profile_data['student_id']; ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile_data['course'])): ?>
                                <p><i class="fas fa-graduation-cap me-2"></i> Course: <?php echo $profile_data['course']; ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile_data['year_level'])): ?>
                                <p><i class="fas fa-level-up-alt me-2"></i> Year Level: <?php echo $profile_data['year_level']; ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile_data['section'])): ?>
                                <p><i class="fas fa-users me-2"></i> Section: <?php echo $profile_data['section']; ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($role == 'counselor' && isset($profile_data)): ?>
                            <?php if (!empty($profile_data['specialization'])): ?>
                                <p><i class="fas fa-star me-2"></i> Specialization: <?php echo $profile_data['specialization']; ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadProfilePictureModal">
                        <i class="fas fa-camera me-2"></i> Change Picture
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Profile Tabs Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">
                                Basic Info
                            </button>
                        </li>
                        
                        <?php if ($role == 'student'): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="student-tab" data-bs-toggle="tab" data-bs-target="#student" type="button" role="tab" aria-controls="student" aria-selected="false">
                                    Student Details
                                </button>
                            </li>
                        <?php elseif ($role == 'counselor'): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="counselor-tab" data-bs-toggle="tab" data-bs-target="#counselor" type="button" role="tab" aria-controls="counselor" aria-selected="false">
                                    Counselor Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab" aria-controls="availability" aria-selected="false">
                                    Availability
                                </button>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                Security
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-4" id="profileTabsContent">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                            <h4>Basic Information</h4>
                            <p class="text-muted">Update your account's basic information</p>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <input type="hidden" name="update_basic_info" value="1">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($role == 'student'): ?>
                            <!-- Student Details Tab -->
                            <div class="tab-pane fade" id="student" role="tabpanel" aria-labelledby="student-tab">
                                <h4>Student Information</h4>
                                <p class="text-muted">Update your student details</p>
                                
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <input type="hidden" name="update_student_profile" value="1">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="student_id" class="form-label">Student ID</label>
                                            <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo $profile_data['student_id'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="course" class="form-label">Course/Program</label>
                                            <input type="text" class="form-control" id="course" name="course" value="<?php echo $profile_data['course'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="year_level" class="form-label">Year Level</label>
                                            <select class="form-select" id="year_level" name="year_level">
                                                <option value="">Select Year Level</option>
                                                <option value="1st Year" <?php echo (isset($profile_data['year_level']) && $profile_data['year_level'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                                <option value="2nd Year" <?php echo (isset($profile_data['year_level']) && $profile_data['year_level'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                                <option value="3rd Year" <?php echo (isset($profile_data['year_level']) && $profile_data['year_level'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                                <option value="4th Year" <?php echo (isset($profile_data['year_level']) && $profile_data['year_level'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                                <option value="5th Year" <?php echo (isset($profile_data['year_level']) && $profile_data['year_level'] == '5th Year') ? 'selected' : ''; ?>>5th Year</option>
                                                <option value="Graduate" <?php echo (isset($profile_data['year_level']) && $profile_data['year_level'] == 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="section" class="form-label">Section</label>
                                            <input type="text" class="form-control" id="section" name="section" value="<?php echo $profile_data['section'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">About Me (Bio)</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $profile_data['bio'] ?? ''; ?></textarea>
                                        <div class="form-text">Share a brief description about yourself</div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        <?php elseif ($role == 'counselor'): ?>
                            <!-- Counselor Details Tab -->
                            <div class="tab-pane fade" id="counselor" role="tabpanel" aria-labelledby="counselor-tab">
                                <h4>Counselor Information</h4>
                                <p class="text-muted">Update your counselor profile</p>
                                
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <input type="hidden" name="update_counselor_profile" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="specialization" class="form-label">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo $profile_data['specialization'] ?? ''; ?>">
                                        <div class="form-text">E.g., Academic Counseling, Career Guidance, Mental Health Support</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Professional Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="6"><?php echo $profile_data['bio'] ?? ''; ?></textarea>
                                        <div class="form-text">Share your background, experience, and approach to counseling</div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Availability Tab -->
                            <div class="tab-pane fade" id="availability" role="tabpanel" aria-labelledby="availability-tab">
                                <h4>Schedule & Availability</h4>
                                <p class="text-muted">Set your weekly availability for consultations</p>
                                
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <input type="hidden" name="update_counselor_profile" value="1">
                                    
                                    <?php
                                    $days = [
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday'
                                    ];
                                    
                                    foreach ($days as $day_key => $day_name):
                                        $is_available = isset($availability[$day_key]['available']) ? $availability[$day_key]['available'] : 0;
                                        $start_time = isset($availability[$day_key]['start_time']) ? $availability[$day_key]['start_time'] : '08:00';
                                        $end_time = isset($availability[$day_key]['end_time']) ? $availability[$day_key]['end_time'] : '17:00';
                                    ?>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="mb-0"><?php echo $day_name; ?></h5>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input availability-toggle" type="checkbox" id="<?php echo $day_key; ?>_available" name="<?php echo $day_key; ?>_available" <?php echo $is_available ? 'checked' : ''; ?> data-day="<?php echo $day_key; ?>">
                                                    <label class="form-check-label" for="<?php echo $day_key; ?>_available">Available</label>
                                                </div>
                                            </div>
                                            
                                            <div class="row time-slots <?php echo $is_available ? '' : 'd-none'; ?>" id="<?php echo $day_key; ?>_times">
                                                <div class="col-md-6">
                                                    <label for="<?php echo $day_key; ?>_start" class="form-label">Start Time</label>
                                                    <input type="time" class="form-control" id="<?php echo $day_key; ?>_start" name="<?php echo $day_key; ?>_start" value="<?php echo $start_time; ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="<?php echo $day_key; ?>_end" class="form-label">End Time</label>
                                                    <input type="time" class="form-control" id="<?php echo $day_key; ?>_end" name="<?php echo $day_key; ?>_end" value="<?php echo $end_time; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Save Availability</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h4>Security</h4>
                            <p class="text-muted">Update your password</p>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <input type="hidden" name="change_password" value="1">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                    <div class="form-text">Password must be at least 8 characters long</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Profile Picture Modal -->
<div class="modal fade" id="uploadProfilePictureModal" tabindex="-1" aria-labelledby="uploadProfilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="update_profile_picture" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadProfilePictureModalLabel">Change Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <?php if (!empty($user['profile_picture']) && file_exists($base_path . '/uploads/profile_pictures/' . $user['profile_picture'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Current Profile Picture" class="rounded-circle mb-3" width="150" height="150">
                            <p>Current Profile Picture</p>
                        <?php else: ?>
                            <div class="profile-placeholder rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px; margin: 0 auto;">
                                <i class="fas fa-user fa-5x text-white"></i>
                            </div>
                            <p>No Profile Picture Set</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Upload New Picture</label>
                        <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" required>
                        <div class="form-text">Max file size: 10MB. Allowed formats: JPG, PNG, GIF</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide time slots based on availability toggle
    const availabilityToggles = document.querySelectorAll('.availability-toggle');
    
    availabilityToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const timeSlots = document.getElementById(day + '_times');
            
            if (this.checked) {
                timeSlots.classList.remove('d-none');
            } else {
                timeSlots.classList.add('d-none');
            }
        });
    });
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 