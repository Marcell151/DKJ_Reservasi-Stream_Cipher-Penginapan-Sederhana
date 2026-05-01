<?php
// Configuration for DKJ Reservasi ChaCha20
define('DB_PATH', __DIR__ . '/../database/reservasi.sqlite');
define('MASTER_SECRET', 'dkj_reservasi_secure_2024_top_secret'); 

// --- HYBRID KEY GENERATION COMPONENTS ---
// 1. Network Component (IP Server)
$ip_server = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()) ?? '127.0.0.1';
if ($ip_server === '::1') $ip_server = '127.0.0.1';

// 2. Device Component (Hostname)
$device_sig = gethostname() ?: 'Unknown-Device';

// 3. Browser/Environment Component
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI-Environment';

// 4. Combined Hybrid Seed
$combined_seed_raw = $ip_server . "|" . $device_sig . "|" . $user_agent . "|" . MASTER_SECRET;
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
