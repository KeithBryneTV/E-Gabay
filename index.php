<?php
require_once __DIR__ . '/includes/path_fix.php';
require_once $base_path . '/config/config.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard/');
} else {
    header('Location: ' . rtrim(SITE_URL, '/') . '/login');
}
exit;
?> 