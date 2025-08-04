<?php
/**
 * Global maintenance mode enforcement.
 * This file is included automatically from config/config.php.
 */

// Ensure required helpers are loaded
if (!function_exists('Utility::getSetting')) {
    // config.php already loads these, but in case of direct call
    require_once __DIR__ . '/config/config.php';
}

// Retrieve maintenance flag
$maintenance_mode = (int)Utility::getSetting('maintenance_mode', 0);

// If not in maintenance mode, nothing to do
if ($maintenance_mode === 0) {
    return;
}

// Allowlist of pages that remain accessible even during maintenance
$allowed_pages = [
    'maintenance.php',
    'login.php',
    'logout.php',
    'forgot_password.php',
    'verify.php',
    'register.php'
];

$current_page = basename($_SERVER['PHP_SELF']);

// Skip enforcement if we are already on an allowed page
if (in_array($current_page, $allowed_pages, true)) {
    return;
}

// Allow administrators and staff roles to bypass maintenance
$role_name = $_SESSION['role_name'] ?? '';
if (in_array($role_name, ['admin', 'staff'], true)) {
    return;
}

// Redirect everyone else to maintenance page
header('Location: ' . SITE_URL . '/maintenance.php');
exit; 