<?php
// Include path fix helper
if (!defined('BASE_PATH_DEFINED')) {
    require_once __DIR__ . '/path_fix.php';
    define('BASE_PATH_DEFINED', true);
}

// Include configuration
require_once $base_path . '/config/config.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get notification count if user is logged in
$notification_count = 0;
if (isLoggedIn()) {
    $notification_count = getTotalNotificationCount($_SESSION['user_id']);
}

// Get user role
$role = '';
if (isLoggedIn()) {
    $role = getUserRole();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo SITE_URL; ?>">
    
    <?php
    // Generate canonical URL to prevent duplicate indexing
$current_url = rtrim(SITE_URL, '/') . $_SERVER['REQUEST_URI'];

// Remove .php extension from canonical URL if present
$canonical_url = str_replace('.php', '', $current_url);
    
    // Clean up any query parameters for SEO
    $canonical_url = strtok($canonical_url, '?');
    ?>
    <link rel="canonical" href="<?php echo $canonical_url; ?>">
    <meta name="robots" content="index, follow">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'E-GABAY ASC - Academic Support and Counseling System for students and counselors'; ?>">
    <meta name="keywords" content="academic support, counseling, student services, ASC, guidance, consultation">
    
    <!-- Google Search Console Verification (uncomment and add your verification code) -->
    <!-- <meta name="google-site-verification" content="YOUR_VERIFICATION_CODE_HERE"> -->
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Print CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/print.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery (for plugins early) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <meta name="base-url" content="<?php echo SITE_URL; ?>">
    
    <style>
        :root {
            --bs-primary: #2c5aa0;
            --bs-primary-rgb: 44, 90, 160;
            --bs-secondary: #6c7293;
            --bs-success: #00a65a;
            --bs-info: #00c0ef;
            --bs-warning: #f39c12;
            --bs-danger: #dd4b39;
            --bs-light: #f8f9fa;
            --bs-dark: #2f3349;
            --sidebar-width: 260px;
            --navbar-height: 64px;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 50%, #fff3e0 100%);
            background-attachment: fixed;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .wrapper {
            display: flex;
            flex: 1;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-right: 1px solid #e3e6f0;
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            height: calc(100% - var(--navbar-height));
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            transition: width 0.2s ease;
            box-shadow: 2px 0 15px rgba(0,0,0,0.08);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar-header {
            min-height: 60px;
            background: rgba(44, 90, 160, 0.05);
            border-bottom: 1px solid #e3e6f0;
        }
        
        .sidebar-title {
            font-size: 1.1rem;
            transition: opacity 0.15s ease;
            white-space: nowrap;
        }
        
        .sidebar.collapsed .sidebar-title {
            opacity: 0;
            transform: translateX(-20px);
            pointer-events: none;
        }
        
        .sidebar-toggle {
            border: none !important;
            transition: all 0.3s ease;
            padding: 0.25rem !important;
            border-radius: 50% !important;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-toggle:hover {
            background-color: rgba(44, 90, 160, 0.1) !important;
        }
        
        .sidebar.collapsed .sidebar-toggle i {
            transform: rotate(180deg);
        }
        
        .sidebar .nav-link {
            color: #5a6c7d;
            padding: 0.875rem 1.25rem;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            border-radius: 0 12px 12px 0;
            margin: 0.125rem 0.75rem 0.125rem 0;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
            flex-shrink: 0;
            font-size: 1rem;
        }
        
        .sidebar .nav-link span {
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .sidebar.collapsed .nav-link {
            padding: 0.875rem;
            margin: 0.125rem 0.5rem;
            border-radius: 8px;
            justify-content: center;
            border-left: none;
            position: relative;
        }
        
        .sidebar.collapsed .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0;
            background-color: var(--primary-color);
            transition: height 0.3s ease;
        }
        
        .sidebar.collapsed .nav-link.active::before,
        .sidebar.collapsed .nav-link:hover::before {
            height: 70%;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.1rem;
        }
        
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            transform: translateX(-10px);
            pointer-events: none;
            position: absolute;
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(44, 90, 160, 0.1) 0%, rgba(74, 123, 200, 0.1) 100%);
            transition: left 0.3s ease;
            z-index: -1;
        }
        
        .sidebar .nav-link:hover {
            background-color: #f8f9fc;
            border-left-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(44, 90, 160, 0.15);
        }
        
        .sidebar .nav-link:hover::before {
            left: 0;
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, rgba(44, 90, 160, 0.1) 0%, rgba(74, 123, 200, 0.1) 100%);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            transform: translateX(8px);
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.2);
        }
        

        
        .content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            margin-top: var(--navbar-height);
            min-height: calc(100vh - var(--navbar-height));
            transition: margin-left 0.2s ease;
        }
        
        .content.expanded {
            margin-left: 80px;
            width: calc(100% - 80px);
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                box-shadow: 4px 0 20px rgba(0,0,0,0.15);
                z-index: 1050;
                width: var(--sidebar-width) !important;
            }
            
            .sidebar.show {
                margin-left: 0 !important;
                transform: translateX(0);
            }
            
            .sidebar.collapsed {
                margin-left: calc(-1 * var(--sidebar-width));
                width: var(--sidebar-width) !important;
            }
            
            /* Force full width navigation on mobile */
            .sidebar .nav-link {
                width: auto !important;
                display: flex !important;
                align-items: center !important;
                padding: 1rem 1.25rem !important;
                margin: 0.125rem 0.5rem 0.125rem 0 !important;
                border-radius: 0 12px 12px 0 !important;
                border-left: 3px solid transparent !important;
            }
            
            /* Ensure text is visible on mobile */
            .sidebar .nav-link span {
                display: inline !important;
                opacity: 1 !important;
                transform: none !important;
                position: static !important;
                pointer-events: auto !important;
                flex: 1 !important;
            }
            
            .sidebar .nav-link i {
                margin-right: 0.75rem !important;
                width: 20px !important;
                text-align: center !important;
                flex-shrink: 0 !important;
            }
            
            .content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem;
            }
            
            /* Hide collapse button on mobile */
            .sidebar-header .sidebar-toggle {
                display: none !important;
            }
            
            /* Mobile navbar adjustments */
            #sidebarCollapseDesktop {
                display: none !important;
            }
            
            #sidebarToggle {
                display: inline-block !important;
            }
            
            /* Mobile overlay */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }
            
            /* No dropdown styles needed - using modal instead */
            
            /* Sidebar overlay pointer events */
            .sidebar-overlay {
                pointer-events: none !important;
            }
            
            .sidebar-overlay.show {
                pointer-events: auto !important;
            }
            
            /* Remove previous debug visual styles */
            /* .navbar .dropdown-menu, .navbar .dropdown-item { border and background debug lines removed */
        }
        
        @media (max-width: 768px) {
            .content {
                padding: 0.75rem;
            }
            
            .sidebar .nav-link {
                padding: 1rem 1.25rem;
                margin: 0.125rem 0.5rem 0.125rem 0;
                border-radius: 0 12px 12px 0;
            }
            
            .sidebar .nav-link i {
                margin-right: 1rem;
                width: 20px;
                text-align: center;
            }
            
            .sidebar .nav-link span {
                display: inline !important;
                opacity: 1 !important;
                transform: none !important;
                position: static !important;
                pointer-events: auto !important;
            }
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            height: var(--navbar-height);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.15rem; /* smaller to fit */
            line-height: 1;
        }
        
        /* Custom styling for user icon button */
        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
        }
        
        .navbar-toggler:hover {
            border-color: rgba(255, 255, 255, 0.8);
            background-color: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.3);
        }
        
        .navbar-toggler .fas.fa-user {
            font-size: 1.1rem;
            color: #ffffff;
        }
        

        
        /* Responsive: hide text on very small screens to save space */
        @media (max-width: 480px) {
            .navbar-brand span.brand-text {
                display: none;
            }
        }
        
        /* Modal styling for user profile */
        #userProfileModal .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        #userProfileModal .modal-header {
            border-radius: 12px 12px 0 0;
            border-bottom: none;
        }
        
        #userProfileModal .list-group-item {
            border: none;
            padding: 1rem 1.5rem;
            transition: all 0.2s ease;
        }
        
        #userProfileModal .list-group-item:hover {
            background-color: #f8f9fc;
        }
        
        #userProfileModal .list-group-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Mobile modal optimization */
        @media (max-width: 576px) {
            #userProfileModal .modal-dialog {
                margin: 1rem;
                max-width: calc(100vw - 2rem);
            }
            
            #userProfileModal .modal-content {
                border-radius: 8px;
            }
            
            #userProfileModal .list-group-item {
                padding: 1.25rem 1.5rem;
                font-size: 1.1rem;
            }
            
            #userProfileModal .list-group-item i {
                font-size: 1.2rem;
                width: 24px;
            }
        }
        
        /* No dropdown toggle styles needed - using modal */

        .navbar .nav-link {
            padding: 0.5rem 0.75rem;
            color: rgba(255,255,255,0.9);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 0.25rem;
        }
        
        .navbar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        /* Compact navbar buttons */
        .navbar .nav-item .nav-link {
            padding: 0.4rem 0.6rem;
            font-size: 0.9rem;
        }
        
        /* Make notification badge smaller and tighter */
        .notification-badge {
            font-size: 0.6rem !important;
            padding: 0.2rem 0.4rem !important;
            min-width: 16px !important;
        }

        /* Mobile: hide navbar while modal open */
        @media (max-width: 767.98px) {
            .modal-open .navbar {
                opacity: 0;
                pointer-events: none;
            }
        }
    </style>
    
    <?php if (isLoggedIn()): ?>
        <?php addAntiCacheScript(); ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <button id="sidebarToggle" class="btn btn-link text-white me-2 d-lg-none">
                <i class="fas fa-bars"></i>
            </button>
            <button id="sidebarCollapseDesktop" class="btn btn-link text-white me-2 d-none d-lg-block">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/">
                <span class="brand-text">EGABAY ASC</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-user"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (!isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'login') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/login">Login</a>
                        </li>
                    <?php else: ?>
                        <!-- Notifications Link -->
                        <li class="nav-item d-none d-md-block">
                            <a class="nav-link position-relative" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/<?php echo $role; ?>/notifications.php">
                                <i class="fas fa-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge"><?php echo $notification_count > 99 ? '99+' : $notification_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <!-- User Profile Button -->
                        <li class="nav-item">
                            <a href="#" id="userProfileToggle" class="nav-link d-flex align-items-center px-2" data-bs-toggle="modal" data-bs-target="#userProfileModal">
                                <span class="d-none d-md-inline ms-1 small"><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'User'; ?></span>
                                <i class="fas fa-chevron-down ms-1 d-none d-md-inline" style="font-size:0.7rem;"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

            <div class="wrapper">
        <?php if (isLoggedIn()): ?>
        <!-- Mobile Sidebar Overlay -->
        <div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>
    
    <!-- User Profile Modal -->
    <div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="userProfileModalLabel">
                        Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="text-center py-3 bg-light">
                        <i class="fas fa-user-circle text-primary mb-2" style="font-size: 2.5rem;"></i>
                        <h6 class="mb-1 fw-semibold"><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'].' '.$_SESSION['last_name'] : 'Account'; ?></h6>
                        <small class="text-muted"><?php echo ucfirst($_SESSION['role_name'] ?? 'User'); ?></small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/<?php echo $role; ?>/notifications.php" class="list-group-item list-group-item-action d-flex align-items-center position-relative">
                            <i class="fas fa-bell text-primary me-3"></i>
                            <span>Notifications</span>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge" style="position:absolute; right:1rem; top:50%; transform:translateY(-50%);">
                                    <?php echo $notification_count > 99 ? '99+' : $notification_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/profile" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-id-card text-primary me-3"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="<?php echo rtrim(SITE_URL, '/'); ?>/logout" class="list-group-item list-group-item-action d-flex align-items-center text-danger">
                            <i class="fas fa-sign-out-alt me-3"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <!-- Sidebar Header with Toggle -->
            <div class="sidebar-header d-flex justify-content-between align-items-center px-3 py-2">
                <span class="sidebar-title fw-bold text-primary">E-GABAY</span>
                <button class="btn btn-link sidebar-toggle d-none d-lg-block" id="sidebarCollapseBtn">
                    <i class="fas fa-chevron-left text-primary"></i>
                </button>
            </div>
            <ul class="nav flex-column pt-2">
                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/dashboard/admin/">
                            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'consultations') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/consultations">
                            <i class="fas fa-clipboard-list"></i> <span>Consultations</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'users') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/users">
                            <i class="fas fa-users"></i> <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'reports') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/reports">
                            <i class="fas fa-chart-bar"></i> <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'manage_consultations') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/manage_consultations">
                            <i class="fas fa-cogs"></i> <span>Manage Consultations</span>
                        </a>
                    </li>
                    <li class="nav-item">
                                                <a class="nav-link <?php echo ($current_page == 'settings') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/settings">
                            <i class="fas fa-cog"></i> <span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'send_notification') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/send_notification">
                            <i class="fas fa-bell"></i> <span>Send Notifications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'logs') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/logs">
                            <i class="fas fa-history"></i> <span>Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'backup') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/backup">
                            <i class="fas fa-database"></i> <span>Backup</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'notifications') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/admin/notifications">
                            <i class="fas fa-bell"></i> <span>Notifications</span>
                        </a>
                    </li>
                <?php elseif (isCounselor()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/dashboard/counselor/">
                            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'consultations') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/consultations">
                            <i class="fas fa-clipboard-list"></i> <span>Consultations</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'schedule') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/schedule">
                            <i class="fas fa-calendar-alt"></i> <span>Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'messages') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/messages">
                            <i class="fas fa-comments"></i> <span>Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'reports') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/reports">
                            <i class="fas fa-chart-bar"></i> <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'notifications') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/counselor/notifications">
                            <i class="fas fa-bell"></i> <span>Notifications</span>
                        </a>
                    </li>
                <?php elseif (isStudent()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/dashboard/student/">
                            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'consultations' || $current_page == 'my_consultations') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/student/consultations">
                            <i class="fas fa-clipboard-list"></i> <span>My Consultations</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'request_consultation') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/student/request_consultation">
                            <i class="fas fa-calendar-plus"></i> <span>Request Consultation</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'messages') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/student/messages">
                            <i class="fas fa-comments"></i> <span>Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'notifications') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/student/notifications">
                            <i class="fas fa-bell"></i> <span>Notifications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'feedback') ? 'active' : ''; ?>" href="<?php echo rtrim(SITE_URL, '/'); ?>/dashboard/student/consultations?status=completed">
                            <i class="fas fa-star"></i> <span>Feedback</span>
                            <?php
                            // Show badge for pending feedback
                            if (isLoggedIn()) {
                                $user_id = $_SESSION['user_id'];
                                $db = (new Database())->getConnection();
                                $stmt = $db->prepare("SELECT COUNT(*) as count FROM consultation_requests cr 
                                                     WHERE cr.student_id = ? AND cr.status = 'completed' 
                                                     AND NOT EXISTS (SELECT 1 FROM feedback f WHERE f.consultation_id = cr.id AND f.student_id = cr.student_id)");
                                $stmt->execute([$user_id]);
                                $pending_feedback = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                if ($pending_feedback > 0): ?>
                                    <span class="badge bg-warning text-dark ms-2"><?php echo $pending_feedback; ?></span>
                                <?php endif;
                            }
                            ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Content -->
        <div class="content">
            <div class="container-fluid">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                <?php endif; ?> 