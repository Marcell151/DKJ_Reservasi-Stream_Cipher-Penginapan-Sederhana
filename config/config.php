<?php
// Configuration for DKJ Reservasi ChaCha20
define('DB_PATH', __DIR__ . '/../database/reservasi.sqlite');
define('MASTER_SECRET', 'dkj_reservasi_secure_2024_top_secret'); 

// SERVER-SIDE NETWORK SEED (Captured once during system setup)
// Ini adalah identitas jaringan server yang bersifat statis/tetap
define('SERVER_SEED', 'SRV-192-168-1-10-XAMPP-STABLE-SEED'); 

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
