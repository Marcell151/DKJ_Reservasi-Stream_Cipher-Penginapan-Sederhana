<?php
require_once 'config/config.php';
require_once 'helpers/security_helper.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$page = $_GET['page'] ?? 'stats';
$db = new PDO("sqlite:" . DB_PATH);

// Logout handling
if ($page === 'logout') {
    session_destroy();
    redirect('index.php');
}

// Active Menu Helper
function is_active($p, $current) {
    return $p === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DKJ Reservasi</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#f4f7fa]">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar h-screen fixed">
            <div class="sidebar-brand text-white">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/10 rounded-lg">
                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg leading-tight">Sistem Reservasi</span>
                        <span class="text-xs font-normal text-white/50">Penginapan Sederhana</span>
                    </div>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="?page=stats" class="<?= is_active('stats', $page) ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="?page=rooms" class="<?= is_active('rooms', $page) ?>">
                        <i data-lucide="bed" class="w-5 h-5"></i> Data Kamar
                    </a>
                </li>
                <li>
                    <a href="?page=reservations" class="<?= is_active('reservations', $page) ?>">
                        <i data-lucide="calendar-check" class="w-5 h-5"></i> Reservasi
                    </a>
                </li>
                <li>
                    <a href="?page=payments" class="<?= is_active('payments', $page) ?>">
                        <i data-lucide="credit-card" class="w-5 h-5"></i> Pembayaran
                    </a>
                </li>
                <li>
                    <a href="?page=reports" class="<?= is_active('reports', $page) ?>">
                        <i data-lucide="file-bar-chart" class="w-5 h-5"></i> Laporan
                    </a>
                </li>
                <li class="mt-8 pt-8 border-t border-white/10">
                    <a href="?page=demo" class="<?= is_active('demo', $page) ?>">
                        <i data-lucide="zap" class="w-5 h-5"></i> Demo Enkripsi
                    </a>
                </li>
                <li class="mt-auto absolute bottom-8 left-6 right-6">
                    <a href="?page=logout" class="text-red-300 hover:bg-red-500/20 hover:text-red-200">
                        <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main -->
        <main class="main-wrapper flex-1">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <?php 
                        $titles = [
                            'stats' => 'Dashboard',
                            'rooms' => 'Data Kamar',
                            'reservations' => 'Reservasi',
                            'payments' => 'Pembayaran',
                            'reports' => 'Laporan',
                            'demo' => 'Demo ChaCha20'
                        ];
                        echo $titles[$page] ?? 'Dashboard';
                        ?>
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">
                        Selamat datang di sistem manajemen reservasi penginapan
                    </p>
                </div>
                <div class="flex items-center gap-4 bg-white px-4 py-2 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex flex-col text-right">
                        <span class="text-sm font-bold text-gray-800 leading-none"><?= $_SESSION['username'] ?></span>
                        <span class="text-[10px] text-gray-400 font-medium uppercase mt-1 tracking-wider"><?= $_SESSION['role'] ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-100">
                        <i data-lucide="user" class="w-5 h-5 text-white"></i>
                    </div>
                </div>
            </header>

            <?php
            $flash = get_flash_message();
            if ($flash): ?>
                <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $flash['type'] == 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-red-50 text-red-700 border border-red-100' ?>">
                    <i data-lucide="<?= $flash['type'] == 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                    <span class="text-sm font-medium"><?= $flash['msg'] ?></span>
                </div>
            <?php endif; ?>

            <div class="content-body animate-in fade-in slide-in-from-bottom-4 duration-500">
                <?php
                $module_path = "modules/$page.php";
                if (file_exists($module_path)) {
                    include $module_path;
                } else {
                    include "modules/stats.php";
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>
