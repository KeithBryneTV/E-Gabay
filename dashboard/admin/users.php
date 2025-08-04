<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Check for form submissions and log them for debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submitted with data: " . print_r($_POST, true));
}

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';

// Check if user is logged in and has admin role
requireLogin();
requireRole('admin');

// Set page title
$page_title = 'User Management';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle user actions (add, edit, delete)
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new user
                $username = sanitizeInput($_POST['username']);
                $password = $_POST['password'];
                $first_name = sanitizeInput($_POST['first_name']);
                $last_name = sanitizeInput($_POST['last_name']);
                $email = sanitizeInput($_POST['email']);
                $role_id = (int)$_POST['role_id'];
                // Pre-fetch role-specific fields for validation
                $student_id = sanitizeInput($_POST['student_id'] ?? '');
                $course = sanitizeInput($_POST['course'] ?? '');
                $year_level = sanitizeInput($_POST['year_level'] ?? '');
                $section = sanitizeInput($_POST['section'] ?? '');
                $specialization = sanitizeInput($_POST['specialization'] ?? '');
                $availability = sanitizeInput($_POST['availability'] ?? '');
                // Additional validation for student role BEFORE inserting user
                if($role_id == ROLE_STUDENT){
                    if(empty($student_id)){
                        setMessage('Student ID is required.', 'danger');
                        break;
                    }
                    $check_stmt = $db->prepare("SELECT profile_id FROM student_profiles WHERE student_id = ?");
                    $check_stmt->execute([$student_id]);
                    if($check_stmt->rowCount() > 0){
                        setMessage('Student ID already exists. Please use a different ID.', 'danger');
                        break;
                    }
                }
                
                // Validate input
                if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
                    setMessage('All fields are required.', 'danger');
                } else {
                    $auth = new Auth($db);
                    
                    // Check if username or email already exists
                    if ($auth->getUserByUsername($username)) {
                        setMessage('Username already exists.', 'danger');
                    } elseif ($auth->getUserByEmail($email)) {
                        setMessage('Email already exists.', 'danger');
                    } else {
                        // Register new user
                        $user_id = $auth->register($username, $password, $first_name, $last_name, $email, $role_id);
                        
                        if ($user_id) {
                            // Add additional profile information if needed
                            if ($role_id == ROLE_STUDENT) {
                                // Add student profile
                                $student_id = sanitizeInput($_POST['student_id'] ?? '');
                                $course = sanitizeInput($_POST['course'] ?? '');
                                $year_level = sanitizeInput($_POST['year_level'] ?? '');
                                $section = sanitizeInput($_POST['section'] ?? '');
                                
                                // Check if student ID is provided and not already used
                                if (empty($student_id)) {
                                    setMessage('Student ID is required.', 'danger');
                                } else {
                                    // Check if the student ID already exists
                                    $check_query = "SELECT profile_id FROM student_profiles WHERE student_id = ?";
                                    $check_stmt = $db->prepare($check_query);
                                    $check_stmt->execute([$student_id]);
                                    
                                    if ($check_stmt->rowCount() > 0) {
                                        setMessage('Student ID already exists. Please use a different ID.', 'danger');
                                    } else {
                                        $query = "INSERT INTO student_profiles (user_id, student_id, course, year_level, section) 
                                                VALUES (?, ?, ?, ?, ?)";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute([$user_id, $student_id, $course, $year_level, $section]);
                                    }
                                }
                            } elseif ($role_id == ROLE_COUNSELOR) {
                                // Add counselor profile
                                $specialization = sanitizeInput($_POST['specialization'] ?? '');
                                $availability = sanitizeInput($_POST['availability'] ?? '');
                                
                                $query = "INSERT INTO counselor_profiles (user_id, specialization, availability) 
                                          VALUES (?, ?, ?)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$user_id, $specialization, $availability]);
                            }
                            
                            setMessage('User added successfully.', 'success');
                            $db->prepare("UPDATE users SET is_verified=1 WHERE user_id = ?")->execute([$user_id]);
                        } else {
                            setMessage('Failed to add user.', 'danger');
                        }
                    }
                }
                break;
                
            case 'edit':
                // Edit existing user
                $user_id = (int)$_POST['user_id'];
                $first_name = sanitizeInput($_POST['first_name']);
                $last_name = sanitizeInput($_POST['last_name']);
                $email = sanitizeInput($_POST['email']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $is_verified = isset($_POST['is_verified']) ? 1 : 0;
                $submitted_role_id = (int)($_POST['role_id'] ?? 0);
                $student_id = sanitizeInput($_POST['student_id'] ?? '');
                // fetch existing student id if any
                $existing_sid = '';
                if($submitted_role_id == ROLE_STUDENT){
                    $sid_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
                    $sid_stmt->execute([$user_id]);
                    $row_sid = $sid_stmt->fetch(PDO::FETCH_ASSOC);
                    $existing_sid = $row_sid['student_id'] ?? '';
                }
                
                // Pre-validate student fields for student role
                if($submitted_role_id == ROLE_STUDENT){
                    // If a new student ID has been supplied, validate its uniqueness.
                    if($student_id !== '' && $student_id !== $existing_sid){
                        $dup = $db->prepare("SELECT profile_id FROM student_profiles WHERE student_id = ? AND user_id <> ?");
                        $dup->execute([$student_id, $user_id]);
                        if($dup->rowCount() > 0){
                            setMessage('Student ID already exists. Please use a different ID.', 'danger');
                            break;
                        }
                    }
                }
                
                // Validate input
                if (empty($first_name) || empty($last_name) || empty($email)) {
                    setMessage('Name and email are required.', 'danger');
                } else {
                    // Update user
                    $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, is_active = ?, is_verified = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$first_name, $last_name, $email, $is_active, $is_verified, $user_id])) {
                        // Update password if provided
                        if (!empty($_POST['password'])) {
                            $auth = new Auth($db);
                            $auth->changePassword($user_id, $_POST['password']);
                        }
                        
                        // Get user role
                        $query = "SELECT role_id FROM users WHERE user_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$user_id]);
                        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        $role_id = $user_data ? (int)$user_data['role_id'] : 0;
                        
                        // Update profile information if needed
                        if ($role_id == ROLE_STUDENT) {
                            // Gather submitted profile details
                            $student_id = sanitizeInput($_POST['student_id'] ?? '');
                            $course = sanitizeInput($_POST['course'] ?? '');
                            $year_level = sanitizeInput($_POST['year_level'] ?? '');
                            $section = sanitizeInput($_POST['section'] ?? '');

                            // Only attempt to update / insert profile if at least one field is supplied
                            if ($student_id !== '' || $course !== '' || $year_level !== '' || $section !== '') {
                                // Check if profile exists
                                $query = "SELECT profile_id FROM student_profiles WHERE user_id = ?";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$user_id]);

                                if ($stmt->rowCount() > 0) {
                                    // Update existing profile
                                    $query = "UPDATE student_profiles SET student_id = ?, course = ?, year_level = ?, section = ? WHERE user_id = ?";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute([$student_id, $course, $year_level, $section, $user_id]);
                                } else {
                                    // Create new profile
                                    $query = "INSERT INTO student_profiles (user_id, student_id, course, year_level, section) VALUES (?, ?, ?, ?, ?)";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute([$user_id, $student_id, $course, $year_level, $section]);
                                }
                            }
                        } elseif ($role_id == ROLE_COUNSELOR) {
                            // Update counselor profile
                            $specialization = sanitizeInput($_POST['specialization'] ?? '');
                            $availability = sanitizeInput($_POST['availability'] ?? '');
                            
                            // Check if profile exists
                            $query = "SELECT profile_id FROM counselor_profiles WHERE user_id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$user_id]);
                            
                            if ($stmt->rowCount() > 0) {
                                // Update existing profile
                                $query = "UPDATE counselor_profiles SET specialization = ?, availability = ? WHERE user_id = ?";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$specialization, $availability, $user_id]);
                            } else {
                                // Create new profile
                                $query = "INSERT INTO counselor_profiles (user_id, specialization, availability) VALUES (?, ?, ?)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$user_id, $specialization, $availability]);
                            }
                        }
                        
                        setMessage('User updated successfully.', 'success');
                        
                        // Log the action
                        $log_details = "Updated user: {$first_name} {$last_name} (ID: {$user_id})";
                        logAction('update_user', $log_details);
                        
                    } else {
                        setMessage('Failed to update user.', 'danger');
                    }
                }
                break;
                
            case 'delete':
                // Delete user
                $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
                error_log("Attempting to delete user with ID: " . $user_id);
                
                // Validate user ID
                if ($user_id === '' || !is_numeric($user_id)) {
                    setMessage('Invalid user ID provided: "' . htmlspecialchars($user_id) . '"', 'danger');
                    error_log("Invalid user ID format: " . $user_id);
                    break;
                }
                
                $user_id = (int)$user_id; // Convert to integer
                
                if ($user_id <= 0) {
                    setMessage('Invalid user ID: Must be a positive number', 'danger');
                    error_log("User ID must be positive: " . $user_id);
                    break;
                }
                
                // Check if trying to delete self
                if ($user_id == $_SESSION['user_id']) {
                    setMessage('You cannot delete your own account', 'danger');
                    error_log("Attempted to delete own account: " . $user_id);
                    break;
                }
                
                // Check if user exists
                $query = "SELECT * FROM users WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user_data) {
                    error_log("User not found with ID: " . $user_id);
                    setMessage('User not found with ID: ' . $user_id, 'danger');
                    break;
                }
                
                try {
                    // Start transaction
                    $db->beginTransaction();
                    
                    error_log("User found: {$user_data['first_name']} {$user_data['last_name']} (ID: {$user_id}). Starting deletion process.");
                    
                    // Delete from student_profiles if exists
                    $query = "DELETE FROM student_profiles WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " student profile records");
                    
                    // Delete from counselor_profiles if exists
                    $query = "DELETE FROM counselor_profiles WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " counselor profile records");
                    
                    // Delete chat messages sent by this user
                    $query = "DELETE FROM chat_messages WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " chat messages");
                    
                    // Get chat sessions where this user is student or counselor
                    $query = "SELECT id FROM chat_sessions WHERE student_id = ? OR counselor_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id, $user_id]);
                    $chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Found " . count($chat_sessions) . " related chat sessions");
                    
                    // Delete chat messages for these sessions
                    foreach ($chat_sessions as $session) {
                        $query = "DELETE FROM chat_messages WHERE chat_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$session['id']]);
                        error_log("Deleted messages for session ID " . $session['id']);
                    }
                    
                    // Delete chat sessions
                    $query = "DELETE FROM chat_sessions WHERE student_id = ? OR counselor_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id, $user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " chat sessions");
                    
                    // Delete feedback
                    $query = "DELETE FROM feedback WHERE student_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " feedback records");
                    
                    // Delete notifications
                    $query = "DELETE FROM notifications WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " notification records");
                    
                    // Delete system logs
                    $query = "DELETE FROM system_logs WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " system log records");
                    
                    // Delete consultations where this user is student or counselor
                    $query = "DELETE FROM consultation_requests WHERE student_id = ? OR counselor_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id, $user_id]);
                    error_log("Deleted " . $stmt->rowCount() . " consultation requests");
                    
                    // Finally delete the user
                    $query = "DELETE FROM users WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        // User was deleted successfully
                        error_log("Successfully deleted user with ID " . $user_id);
                        $db->commit();
                        setMessage("User '{$user_data['username']}' deleted successfully.", 'success');
                    } else {
                        // User was not deleted for some reason
                        throw new Exception("Failed to delete the user record itself");
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $db->rollBack();
                    error_log("Error deleting user: " . $e->getMessage());
                    setMessage('Failed to delete user: ' . $e->getMessage(), 'danger');
                }
                break;
        }
    }
}

// Get all users
$query = "SELECT u.*, r.role_name 
          FROM users u
          JOIN roles r ON u.role_id = r.role_id
          WHERE r.role_name <> 'staff'
          ORDER BY u.last_name, u.first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all roles
$query = "SELECT * FROM roles WHERE role_name <> 'staff' ORDER BY role_name";
$stmt = $db->prepare($query);
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">User Management</h1>
        <p class="lead">Manage system users including students, counselors, staff, and administrators.</p>
    </div>
</div>

<!-- Add User Button -->
<div class="row mb-4">
    <div class="col-12">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i> Add New User
        </button>
    </div>
</div>

<!-- Users Table -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        All Users
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($user['role_name']); ?></span></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($user['is_verified']): ?>
                                    <span class="badge bg-success">Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['last_login'] ? formatDate($user['last_login'], 'M d, Y h:i A') : 'Never'; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-user-btn" 
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-user-id="<?php echo htmlspecialchars($user['user_id']); ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                        data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-role-id="<?php echo htmlspecialchars($user['role_id']); ?>"
                                        data-is-active="<?php echo htmlspecialchars($user['is_active']); ?>"
                                        data-is-verified="<?php echo htmlspecialchars($user['is_verified']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($user['user_id'] != $_SESSION['user_id']): // Prevent self-deletion ?>
                                    <!-- Delete Button triggers modal -->
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['user_id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="role_id" class="form-label">Role</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo ucfirst($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Student-specific fields -->
                    <div id="student-fields" class="d-none">
                        <hr>
                        <h5>Student Details</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id">
                            </div>
                            <div class="col-md-6">
                                <label for="course" class="form-label">Course</label>
                                <input type="text" class="form-control" id="course" name="course">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level">
                                    <option value="">Select Year Level</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                    <option value="5th Year">5th Year</option>
                                    <option value="Graduate">Graduate</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="section" class="form-label">Section</label>
                                <input type="text" class="form-control" id="section" name="section">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Counselor-specific fields -->
                    <div id="counselor-fields" class="d-none">
                        <hr>
                        <h5>Counselor Details</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="specialization" class="form-label">Specialization</label>
                                <input type="text" class="form-control" id="specialization" name="specialization">
                            </div>
                            <div class="col-md-6">
                                <label for="availability" class="form-label">Availability</label>
                                <textarea class="form-control" id="availability" name="availability" rows="3" placeholder="E.g., Monday-Friday, 9:00 AM - 5:00 PM"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    
                    <!-- User Information Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-id-card me-2"></i>User Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="edit_username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="edit_username" name="username" readonly>
                                    </div>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" id="edit_password" name="password" placeholder="Leave blank to keep current">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="edit_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="edit_first_name" class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_last_name" class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="edit_email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_role_display" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="edit_role_display" readonly>
                                </div>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    <span class="text-success active-label">Active Account</span>
                                    <span class="text-danger inactive-label d-none">Inactive Account</span>
                                </label>
                            </div>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="edit_is_verified" name="is_verified">
                                <label class="form-check-label" for="edit_is_verified">
                                    <span class="text-info verified-label">Verified Account</span>
                                    <span class="text-warning unverified-label d-none">Unverified</span>
                                </label>
                            </div>
                        </div>
                    </div>





                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<?php foreach ($users as $user): ?>
<?php if ($user['user_id'] != $_SESSION['user_id']): // Prevent self-deletion ?>
<div class="modal fade" id="deleteUserModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="deleteUserModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel<?php echo $user['user_id']; ?>">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    
                    <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                    <div class="alert alert-info">
                        <small><strong>User ID:</strong> <?php echo $user['user_id']; ?><br>
                        <strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?><br>
                        <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user['role_name'])); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize DataTable
    $('#usersTable').DataTable({
        order: [[0, 'asc']]
    });
    
    // Show/hide role-specific fields
    document.getElementById('role_id').addEventListener('change', function() {
        const roleId = parseInt(this.value);
        const studentFields = document.getElementById('student-fields');
        const counselorFields = document.getElementById('counselor-fields');
        
        // Hide all role-specific fields
        studentFields.classList.add('d-none');
        counselorFields.classList.add('d-none');
        
        // Show fields based on selected role
        if (roleId === <?php echo ROLE_STUDENT; ?>) {
            studentFields.classList.remove('d-none');
        } else if (roleId === <?php echo ROLE_COUNSELOR; ?>) {
            counselorFields.classList.remove('d-none');
        }
    });
    
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Handle active/inactive account toggle
    document.getElementById('edit_is_active').addEventListener('change', function() {
        const activeLabel = document.querySelector('.active-label');
        const inactiveLabel = document.querySelector('.inactive-label');
        
        if (this.checked) {
            activeLabel.classList.remove('d-none');
            inactiveLabel.classList.add('d-none');
        } else {
            activeLabel.classList.add('d-none');
            inactiveLabel.classList.remove('d-none');
        }
    });

    // Handle verified account toggle
    document.getElementById('edit_is_verified').addEventListener('change', function() {
        const verifiedLabel=document.querySelector('.verified-label');
        const unverifiedLabel=document.querySelector('.unverified-label');
        if(this.checked){ verifiedLabel.classList.remove('d-none'); unverifiedLabel.classList.add('d-none'); }
        else { verifiedLabel.classList.add('d-none'); unverifiedLabel.classList.remove('d-none'); }
    });
    
    // Function to get role name
    function getRoleName(roleId) {
        const roleMap = {
            <?php echo ROLE_STUDENT; ?>: 'Student',
            <?php echo ROLE_COUNSELOR; ?>: 'Counselor',
            <?php echo ROLE_ADMIN; ?>: 'Administrator'
        };
        return roleMap[roleId] || 'Unknown';
    }
    
    // Wait for Bootstrap modal to be ready and populate edit user modal
    const editUserBtns = document.querySelectorAll('.edit-user-btn');
    editUserBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            console.log('Edit button clicked'); // Debug log
            
            const userId = this.dataset.userId;
            const username = this.dataset.username;
            const firstName = this.dataset.firstName;
            const lastName = this.dataset.lastName;
            const email = this.dataset.email;
            const roleId = parseInt(this.dataset.roleId);
            const isActive = this.dataset.isActive === '1';
            const isVerified = this.dataset.isVerified === '1';
            
            console.log('User data:', {userId, username, firstName, lastName, email, roleId, isActive, isVerified}); // Debug log
            
            // Clear previous password value
            document.getElementById('edit_password').value = '';
            
            // Populate basic user info
            const editUserId = document.getElementById('edit_user_id');
            const editUsername = document.getElementById('edit_username');
            const editFirstName = document.getElementById('edit_first_name');
            const editLastName = document.getElementById('edit_last_name');
            const editEmail = document.getElementById('edit_email');
            const editRoleId = document.getElementById('edit_role_id');
            const editRoleDisplay = document.getElementById('edit_role_display');
            
            if (editUserId) editUserId.value = userId || '';
            if (editUsername) editUsername.value = username || '';
            if (editFirstName) editFirstName.value = firstName || '';
            if (editLastName) editLastName.value = lastName || '';
            if (editEmail) editEmail.value = email || '';
            if (editRoleId) editRoleId.value = roleId || '';
            if (editRoleDisplay) editRoleDisplay.value = getRoleName(roleId);
            
            // Set active status
            const activeCheckbox = document.getElementById('edit_is_active');
            if (activeCheckbox) {
                activeCheckbox.checked = isActive;
                // Trigger change event to update labels
                activeCheckbox.dispatchEvent(new Event('change'));
            }

            // Set verified status
            const verifiedCheckbox = document.getElementById('edit_is_verified');
            if (verifiedCheckbox) {
                verifiedCheckbox.checked = isVerified;
                // Trigger change event to update labels
                verifiedCheckbox.dispatchEvent(new Event('change'));
            }
            
            console.log('Modal populated successfully'); // Debug log
        });
    });
    
    // Additional event listener for when modal is actually shown
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('shown.bs.modal', function () {
            console.log('Edit modal is now visible');
            // Focus on first input when modal opens
            const firstNameInput = document.getElementById('edit_first_name');
            if (firstNameInput) {
                firstNameInput.focus();
            }
        });
        
        editUserModal.addEventListener('hidden.bs.modal', function () {
            console.log('Edit modal hidden - clearing form');
            // Clear form when modal is hidden
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                document.getElementById('edit_password').value = '';
            }
        });
    }
});
</script>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 