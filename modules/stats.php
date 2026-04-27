<?php
// Get Counts
$total_reservations = $db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$available_rooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
$total_rooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$total_income_encrypted = $db->query("SELECT amount FROM payments WHERE status = 'paid'")->fetchAll(PDO::FETCH_COLUMN);

// Decrypt total income
$total_income = 0;
foreach ($total_income_encrypted as $enc_amount) {
    $decrypted = SecurityHelper::decrypt($enc_amount);
    if (is_numeric($decrypted)) {
        $total_income += (float)$decrypted;
    }
}

// Counts for encrypted data fields
$encrypted_fields_count = ($total_reservations * 4) + count($total_income_encrypted);
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card flex flex-col gap-4">
        <div class="w-12 h-12 rounded-2xl bg-blue-500 flex items-center justify-center text-white">
            <i data-lucide="calendar" class="w-6 h-6"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-800"><?= $total_reservations ?></div>
            <div class="text-sm text-gray-500 font-medium">Total Reservasi</div>
            <div class="text-xs text-gray-400 mt-1">Bulan ini</div>
        </div>
    </div>

    <div class="card flex flex-col gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500 flex items-center justify-center text-white">
            <i data-lucide="bed" class="w-6 h-6"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-800"><?= $available_rooms ?>/<?= $total_rooms ?></div>
            <div class="text-sm text-gray-500 font-medium">Kamar Tersedia</div>
            <div class="text-xs text-gray-400 mt-1">Kamar aktif</div>
        </div>
    </div>

    <div class="card flex flex-col gap-4">
        <div class="w-12 h-12 rounded-2xl bg-violet-500 flex items-center justify-center text-white">
            <i data-lucide="dollar-sign" class="w-6 h-6"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-800">Rp <?= number_format($total_income/1000000, 1, ',', '.') ?>jt</div>
            <div class="text-sm text-gray-500 font-medium">Total Pembayaran</div>
            <div class="text-xs text-gray-400 mt-1">Bulan ini</div>
        </div>
    </div>

    <div class="card flex flex-col gap-4">
        <div class="w-12 h-12 rounded-2xl bg-orange-500 flex items-center justify-center text-white">
            <i data-lucide="shield" class="w-6 h-6"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-800"><?= $encrypted_fields_count ?></div>
            <div class="text-sm text-gray-500 font-medium">Data Terenkripsi</div>
            <div class="text-xs text-gray-400 mt-1">ChaCha20</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Aktivitas Terbaru</h3>
            <button class="text-indigo-600 text-sm font-semibold">Lihat Semua</button>
        </div>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-5 h-5 text-gray-600"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-gray-800">Reservasi Baru</span>
                            <span class="badge-encrypted"><i data-lucide="lock" class="w-3 h-3"></i> Encrypted</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Budi Santoso</div>
                    </div>
                </div>
                <div class="text-xs text-gray-400 font-medium">2 jam lalu</div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <i data-lucide="credit-card" class="w-5 h-5 text-gray-600"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-gray-800">Pembayaran</span>
                            <span class="badge-encrypted"><i data-lucide="lock" class="w-3 h-3"></i> Encrypted</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Siti Nurhaliza</div>
                    </div>
                </div>
                <div class="text-xs text-gray-400 font-medium">3 jam lalu</div>
            </div>
        </div>
    </div>

    <div class="card bg-[#1a237e] text-white border-none relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-6">
                <i data-lucide="shield-check" class="w-6 h-6 text-indigo-300"></i>
                <h3 class="text-lg font-bold">Status Keamanan</h3>
            </div>
            
            <div class="bg-white/10 rounded-2xl p-4 mb-4 backdrop-blur-sm border border-white/10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-indigo-200">Enkripsi Aktif</span>
                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                </div>
                <div class="text-lg font-bold">ChaCha20</div>
            </div>

            <div class="bg-white/10 rounded-2xl p-4 backdrop-blur-sm border border-white/10">
                <span class="text-xs font-medium text-indigo-200 block mb-2">Key Source: Network Components</span>
                <div class="space-y-2 opacity-80">
                    <div class="flex items-center gap-2 text-[10px]">
                        <i data-lucide="globe" class="w-3 h-3"></i> IPv4 Address
                    </div>
                    <div class="flex items-center gap-2 text-[10px]">
                        <i data-lucide="monitor" class="w-3 h-3"></i> User Agent Hash
                    </div>
                </div>
            </div>
        </div>
        <!-- Decorative Circle -->
        <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl"></div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
