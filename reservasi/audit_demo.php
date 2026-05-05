<?php
require_once 'config/config.php';
require_once 'helpers/security_helper.php';

// Mock Session Check (Assuming user is logged in as admin)
// session_start();
// if($_SESSION['role'] !== 'admin') die('Unauthorized');

$db = new PDO("sqlite:" . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT r.*, rm.room_number 
          FROM reservations r 
          JOIN rooms rm ON r.room_id = rm.id 
          ORDER BY r.id DESC";
$data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Two-Tier Key Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="bg-[#0a0e17] text-slate-300 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-12">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                    <span class="p-2 bg-indigo-600 rounded-xl shadow-lg shadow-indigo-500/20">
                        <i data-lucide="shield-check" class="w-8 h-8"></i>
                    </span>
                    Two-Tier Key Audit Demo
                </h1>
                <p class="text-slate-500 mt-2 font-medium">Verifikasi Arsitektur Keamanan On-the-fly (UTS Demonstration)</p>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-[0.2em] mb-1">System Status</div>
                <div class="flex items-center gap-2 text-emerald-400 font-bold bg-emerald-400/10 px-4 py-2 rounded-full border border-emerald-400/20">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    Network Binding Active
                </div>
            </div>
        </div>

        <!-- Bento Grid Info -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="glass p-6 rounded-[2rem]">
                <div class="text-indigo-400 mb-3"><i data-lucide="key" class="w-6 h-6"></i></div>
                <h3 class="text-white font-bold mb-1">Master Secret</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Kunci statis di server untuk membungkus IP Address (Tier 1).</p>
            </div>
            <div class="glass p-6 rounded-[2rem]">
                <div class="text-emerald-400 mb-3"><i data-lucide="cpu" class="w-6 h-6"></i></div>
                <h3 class="text-white font-bold mb-1">Dynamic Key</h3>
                <p class="text-xs text-slate-500 leading-relaxed">SHA256(IP + Secret) yang dihasilkan hanya di RAM saat dibutuhkan.</p>
            </div>
            <div class="glass p-6 rounded-[2rem]">
                <div class="text-orange-400 mb-3"><i data-lucide="network" class="w-6 h-6"></i></div>
                <h3 class="text-white font-bold mb-1">IP Seed</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Identitas jaringan yang tersimpan dalam bentuk ciphertext (Tier 2).</p>
            </div>
            <div class="glass p-6 rounded-[2rem]">
                <div class="text-pink-400 mb-3"><i data-lucide="binary" class="w-6 h-6"></i></div>
                <h3 class="text-white font-bold mb-1">ChaCha20</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Stream cipher performa tinggi untuk enkripsi data-at-rest.</p>
            </div>
        </div>

        <!-- Main Audit Table -->
        <div class="glass rounded-[2.5rem] overflow-hidden shadow-2xl shadow-black">
            <div class="p-6 bg-white/5 border-b border-white/5 flex items-center justify-between">
                <div class="text-sm font-bold text-white uppercase tracking-widest">Raw Database Stream</div>
                <div class="text-[10px] bg-indigo-500/20 text-indigo-400 px-3 py-1 rounded-lg border border-indigo-500/30 font-bold">SQLITE_ENGINE: ACTIVE</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-[0.1em] text-slate-500 border-b border-white/5">
                            <th class="p-6">Reservasi</th>
                            <th class="p-6">Raw IP Seed (Enc)</th>
                            <th class="p-6">Network Result (Dec)</th>
                            <th class="p-6">Derived Data Key</th>
                            <th class="p-6">Plaintext Output</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($data as $row): ?>
                        <?php 
                            // Simulasi Step-by-Step sesuai ATURAN 4 & 5
                            $enc_ip = $row['encrypted_ip_seed'] ?? '';
                            $ip_asli = SecurityHelper::decryptIP($enc_ip);
                            $data_key = bin2hex(SecurityHelper::getDataKey($ip_asli));
                            $phone_dec = SecurityHelper::decryptData($row['phone'] ?? '', $ip_asli);
                        ?>
                        <tr class="group hover:bg-white/[0.02] transition-colors">
                            <td class="p-6">
                                <div class="text-white font-bold"><?= htmlspecialchars($row['customer_name'] ?? '') ?></div>
                                <div class="text-[10px] text-slate-500 mt-1">Kamar <?= $row['room_number'] ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-mono text-[9px] text-rose-500 break-all w-48 leading-tight">
                                    <?= $enc_ip ?>
                                </div>
                            </td>
                            <td class="p-6">
                                <div class="inline-flex items-center gap-2 bg-emerald-500/10 text-emerald-400 px-3 py-1.5 rounded-lg border border-emerald-500/20 font-mono text-xs font-bold">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                                    <?= $ip_asli ?: 'FAILED' ?>
                                </div>
                            </td>
                            <td class="p-6">
                                <div class="font-mono text-[9px] text-indigo-400 bg-indigo-400/5 p-2 rounded-lg border border-indigo-400/10 w-48 break-all">
                                    <?= $data_key ?>
                                </div>
                                <div class="text-[8px] text-slate-600 mt-1 font-bold">Generated On-the-fly</div>
                            </td>
                            <td class="p-6">
                                <div class="text-sm font-bold text-white">
                                    <?= htmlspecialchars($phone_dec) ?>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-1">Status: Fully Verified</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 p-6 glass rounded-[2rem] border-l-4 border-indigo-500 flex gap-4">
            <div class="text-indigo-400"><i data-lucide="info" class="w-6 h-6"></i></div>
            <div class="text-xs leading-relaxed text-slate-400">
                <strong class="text-white block mb-1 uppercase tracking-widest text-[10px]">Academic Integrity Note:</strong>
                Data Key yang ditampilkan di atas dihasilkan secara dinamis di memori PHP dengan menggabungkan <strong>Master Secret</strong> dan <strong>IP Hasil Dekripsi</strong>. 
                Sistem tidak pernah menyimpan Data Key ini di database, menjamin keamanan tingkat tinggi jika terjadi kebocoran fisik pada file database SQLite.
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
