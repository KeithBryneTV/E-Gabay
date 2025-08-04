<?php
// Include path fix helper
require_once __DIR__ . '/includes/path_fix.php';

// Include configuration
require_once $base_path . '/config/config.php';

// Include header
include_once $base_path . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body text-center p-5">
                    <i class="fas fa-exclamation-triangle text-warning display-1 mb-4"></i>
                    <h1 class="mb-4">Unauthorized Access</h1>
                    <p class="lead mb-4">Sorry, you do not have permission to access this page.</p>
                    <p class="mb-4">Please contact your administrator if you believe this is an error.</p>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/dashboard/" class="btn btn-primary">Return to Dashboard</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/" class="btn btn-outline-secondary ms-2">Go to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $base_path . '/includes/footer.php';
?> 