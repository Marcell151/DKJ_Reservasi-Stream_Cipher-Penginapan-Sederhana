<?php
// Configuration for DKJ Rental ChaCha20
define('DB_PATH', __DIR__ . '/../database/rental.sqlite');
define('MASTER_SECRET', 'dkj_rental_secure_2024_top_secret'); 

// --- HYBRID KEY GENERATION COMPONENTS ---
// 1. Network Component (Dynamic IPv4 Capture)
$ip_server = '127.0.0.1';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_server = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip_server = trim($ip_list[0]);
} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip_server = $_SERVER['REMOTE_ADDR'];
}
if ($ip_server === '::1') $ip_server = '127.0.0.1';
if (!filter_var($ip_server, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    // If not valid IPv4, we keep it as is or fallback if needed
}

// 2. Device Component (Hostname)
$device_sig = gethostname() ?: 'Unknown-Device';

// 3. Combined Hybrid Seed (Stable across CLI & Web)
$combined_seed_raw = $ip_server . "|" . $device_sig . "|" . MASTER_SECRET;
define('SERVER_SEED', hash('sha256', $combined_seed_raw)); 

// Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); // Enable output buffering to prevent header errors

// Global Helpers
function redirect($path) {
    header("Location: $path");
    exit();
}

function set_flash_message($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>
