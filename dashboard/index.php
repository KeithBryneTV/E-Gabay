<?php
// Include path fix helper
require_once __DIR__ . '/../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    header("Location: " . SITE_URL . "/login.php");
    exit;
}

// Get user role
$role = isset($_SESSION['role_name']) ? strtolower($_SESSION['role_name']) : '';

// Redirect to role-specific dashboard
switch ($role) {
    case 'student':
        header("Location: " . SITE_URL . "/dashboard/student/");
        break;
    case 'counselor':
        header("Location: " . SITE_URL . "/dashboard/counselor/");
        break;
    case 'admin':
        header("Location: " . SITE_URL . "/dashboard/admin/");
        break;
    case 'staff':
        header("Location: " . SITE_URL . "/dashboard/staff/");
        break;
    default:
        // Fallback to login page if role is unknown
        header("Location: " . SITE_URL . "/login.php");
        break;
}
exit;
?> 