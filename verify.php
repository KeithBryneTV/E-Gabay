<?php
require_once __DIR__ . '/includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/includes/utility.php';

$token = sanitizeInput($_GET['token'] ?? '');
if (!$token) {
    setMessage('Invalid verification link.','danger');
    redirect('login.php');
    exit;
}

$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT user_id FROM users WHERE verification_token = ?');
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $db->prepare('UPDATE users SET is_verified=1, verification_token=NULL WHERE user_id = ?')->execute([$row['user_id']]);
    setMessage('Your e-mail has been verified! You may now log in.','success');
} else {
    setMessage('Verification link is invalid or has already been used.','danger');
}
redirect('login.php'); 