<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Utility.php';

$maintenance_message = Utility::getSetting('maintenance_message', 'The system is currently undergoing maintenance. Please check back later.');
$maintenance_end_time = Utility::getSetting('maintenance_end_time', 'soon');

// If admin or staff accidentally hits this page while logged in, redirect them back to dashboard
if (isLoggedIn() && (isAdmin() || isStaff())) {
    header('Location: ' . rtrim(SITE_URL, '/') . '/dashboard/' . getUserRole());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 50%, #fff3e0 100%); background-attachment:fixed; }
        .card { max-width:600px; }
    </style>
</head>
<body>
    <div class="card shadow">
        <div class="card-header bg-warning text-dark text-center">
            <h3 class="mb-0">Maintenance Mode</h3>
        </div>
        <div class="card-body text-center">
            <p class="lead mb-4"><?php echo nl2br(htmlspecialchars($maintenance_message)); ?></p>
            <p class="text-muted">Estimated availability: <strong><?php echo htmlspecialchars($maintenance_end_time); ?></strong></p>
            <a href="<?php echo rtrim(SITE_URL, '/'); ?>/login" class="btn btn-primary mt-3">Return to Login</a>
        </div>
    </div>
</body>
</html> 