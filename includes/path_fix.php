<?php
// Define the base path for includes
$base_path = dirname(__DIR__);

// Define site URL only if not already defined
if (!defined('SITE_URL')) {
    // Auto-detect the base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Get the script path
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $script_path = dirname($script_name);
    
    // Remove /dashboard or other subdirectories from path
    $root_path = preg_replace('/\/dashboard(\/.*)?$/', '', $script_path);
    $root_path = preg_replace('/\/includes(\/.*)?$/', '', $root_path);
    $root_path = preg_replace('/\/classes(\/.*)?$/', '', $root_path);
    $root_path = preg_replace('/\/config(\/.*)?$/', '', $root_path);
    $root_path = preg_replace('/\/api(\/.*)?$/', '', $root_path);
    
    // Ensure path ends with a slash
    if (substr($root_path, -1) !== '/') {
        $root_path .= '/';
    }
    
    // Define the site URL
    define('SITE_URL', $protocol . "://" . $host . $root_path);
}
?> 