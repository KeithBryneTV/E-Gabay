<?php
// Include path fix helper
require_once __DIR__ . '/../../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Redirect to consultations.php
redirect(SITE_URL . '/dashboard/student/consultations.php');
exit;
?> 