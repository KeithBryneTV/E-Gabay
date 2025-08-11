<?php
require_once __DIR__ . '/path_fix.php';
require_once $base_path . '/config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add strict cache control headers for all protected pages
header("Cache-Control: no-cache, no-store, must-revalidate, private");
header("Pragma: no-cache");
header("Expires: 0");

/**
 * Check if user is logged in with strict validation
 */
function isLoggedIn() {
    // Check if required session variables exist
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role_name'])) {
        return false;
    }
    
    // Check if session is valid (not expired)
    if (isset($_SESSION['last_activity'])) {
        $timeout = 7200; // 2 hours timeout
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session expired, destroy it
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Additional validation - check if session matches server records
    if (isset($_SESSION['session_token'])) {
        // You can add database validation here if you store session tokens
        // For now, we'll just validate the basic structure
        if (empty($_SESSION['session_token']) || strlen($_SESSION['session_token']) < 32) {
            return false;
        }
    }
    
    return true;
}

/**
 * Enhanced session validation with regeneration
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    } else {
        $regen_timeout = 1800; // 30 minutes
        if (time() - $_SESSION['session_regenerated'] > $regen_timeout) {
            session_regenerate_id(true);
            $_SESSION['session_regenerated'] = time();
        }
    }
    
    return true;
}

/**
 * Require login with strict validation
 */
function requireLogin() {
    // Add no-cache headers
    header("Cache-Control: no-cache, no-store, must-revalidate, private");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    if (!validateSession()) {
        // Clear any remaining session data
        session_unset();
        session_destroy();
        
        // Set message and redirect
        session_start();
        $_SESSION['message'] = 'Your session has expired. Please log in again.';
        $_SESSION['message_type'] = 'warning';
        header("Location: " . rtrim(SITE_URL, '/') . "/login");
        exit();
    }
}

/**
 * Check user role with strict validation
 */
function requireRole($required_roles) {
    requireLogin();
    
    $user_role = $_SESSION['role_name'] ?? '';
    
    if (!in_array($user_role, (array)$required_roles)) {
        $_SESSION['message'] = 'You do not have permission to access this page.';
        $_SESSION['message_type'] = 'danger';
        header("Location: " . rtrim(SITE_URL, '/') . "/unauthorized");
        exit();
    }
}

// Helper functions for specific roles
function isAdmin() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'admin';
}

function isCounselor() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'counselor';
}

function isStudent() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'student';
}

function isStaff() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'staff';
}

function getUserRole() {
    return $_SESSION['role_name'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUsername() {
    return $_SESSION['username'] ?? null;
}

// Add JavaScript to prevent back button cache issues
function addAntiCacheScript() {
    echo '<script>
        // Prevent back button cache issues (only for actual back navigation, not tab switching)
        // Removed auto-reload on pageshow to avoid unwanted refresh when switching tabs
        
        // Log tab visibility changes for debugging
        document.addEventListener("visibilitychange", function() {
            if (document.hidden) {
                console.log("Tab became inactive - session checks paused");
            } else {
                console.log("Tab became active - session checks resumed");
            }
        });
        
        // Session checks disabled to prevent automatic redirects on tab switching
    </script>';
}
?> 