<?php
$is_demo_mode = $_SESSION['demo_ip_mode'] ?? false;
$current_ip = $is_demo_mode ? '192.168.10.10' : SecurityHelper::getUserIP();

$query = "SELECT id, customer_name, encrypted_ip_seed FROM reservations WHERE encrypted_ip_seed IS NOT NULL ORDER BY id DESC";
$reservations = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

$selected_id = $_GET['id'] ?? ($reservations[0]['id'] ?? null);

$selected_data = null;
if ($selected_id) {
    foreach ($reservations as $res) {
        if ($res['id'] == $selected_id) {
            $selected_data = $res;
            break;
        }
    }
}
?>

<div class="flex justify-between items-end mb-6">
    <div>
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i data-lucide="key" class="w-6 h-6 text-indigo-600"></i>
            Audit Kriptografi ChaCha20
        </h3>
        <p class="text-sm text-gray-500">Alat inspeksi mendalam (Byte-packing & Nonce) pada arsitektur Key Escrow Two-Tier</p>
    </div>
    
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="page" value="audit_crypto">
            <select name="id" class="form-control bg-white font-mono text-sm border-gray-200 min-w-[250px]" onchange="this.form.submit()">
                <option value="">-- Pilih Transaksi --</option>
                <?php foreach($reservations as $res): ?>
                    <option value="<?= $res['id'] ?>" <?= $selected_id == $res['id'] ? 'selected' : '' ?>>
                        RES-<?= str_pad($res['id'], 3, '0', STR_PAD_LEFT) ?> : <?= htmlspecialchars($res['customer_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (!$selected_data): ?>
    <div class="card p-12 text-center">
        <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-4"></i>
        <h4 class="text-lg font-bold text-gray-600">Pilih Transaksi</h4>
        <p class="text-gray-400">Silakan pilih transaksi dari dropdown di atas untuk melihat detail kriptografi.</p>
    </div>
<?php else: ?>
    <?php
        $base64_enc = $selected_data['encrypted_ip_seed'];
        $raw_bytes = base64_decode($base64_enc);
        $ext = function_exists('sodium_crypto_stream_chacha20_xor') ? 'Sodium' : 'OpenSSL';
        $nonce_len = ($ext === 'Sodium') ? 8 : 16;
        $nonce = substr($raw_bytes, 0, $nonce_len);
        $ciphertext = substr($raw_bytes, $nonce_len);
        $master_key = hash('sha256', MASTER_SECRET, true);
        
        // Decrypt IP Asli
        $ip_asli = SecurityHelper::decryptIP($base64_enc);
        $is_ip_match = ($ip_asli === $current_ip);
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel Kiri: Identitas -->
        <div class="card border-t-4 border-indigo-600 lg:col-span-1 space-y-6">
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Data Reservasi</label>
                <div class="font-bold text-lg text-gray-800 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-indigo-500"></i> <?= htmlspecialchars($selected_data['customer_name']) ?>
                </div>
                <div class="text-xs text-gray-500 mt-1">ID Transaksi: RES-<?= str_pad($selected_data['id'], 3, '0', STR_PAD_LEFT) ?></div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Ekstensi Crypto</label>
                <div class="flex items-center gap-2 font-mono text-sm">
                    <span class="px-2 py-1 bg-gray-100 rounded text-gray-600 border border-gray-200"><?= $ext ?></span>
                    <span class="text-gray-400">&rarr; Nonce <?= $nonce_len ?> bytes</span>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">IP Tersimpan (Hasil Dekripsi)</label>
                <div class="<?= $is_ip_match ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100' ?> border rounded-xl p-4 flex items-center gap-3">
                    <div class="p-2 <?= $is_ip_match ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?> rounded-lg">
                        <i data-lucide="<?= $is_ip_match ? 'unlock' : 'alert-triangle' ?>" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="font-bold <?= $is_ip_match ? 'text-emerald-700' : 'text-red-700' ?> text-lg font-mono"><?= htmlspecialchars($ip_asli) ?></div>
                        <?php if($is_ip_match): ?>
                            <div class="text-[10px] text-emerald-600 uppercase tracking-wider">Berhasil Didekripsi</div>
                        <?php else: ?>
                            <div class="text-[10px] text-red-600 uppercase tracking-wider font-bold">Akses Ditolak (Mismatch)</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">IP Anda Saat Ini (Deteksi)</label>
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                        <i data-lucide="monitor" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="font-bold text-indigo-700 text-lg font-mono"><?= htmlspecialchars($current_ip) ?></div>
                        <div class="text-[10px] text-indigo-600 uppercase tracking-wider"><?= $is_demo_mode ? 'Simulasi Demo Mode' : 'Real Dynamic IP' ?></div>
                    </div>
                </div>
                
                <?php if(!$is_ip_match): ?>
                <div class="mt-3 p-3 bg-red-100 text-red-700 border border-red-200 rounded-lg text-xs flex gap-2">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <strong>Perbandingan Gagal:</strong> IP Anda (<?= htmlspecialchars($current_ip) ?>) berbeda dengan IP Transaksi (<?= htmlspecialchars($ip_asli) ?>). Data tidak dapat didekripsi.
                    </div>
                </div>
                <?php else: ?>
                <div class="mt-3 p-3 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs flex gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <strong>Perbandingan Sukses:</strong> IP Jaringan Anda telah terverifikasi (Matched). Data profil berhasil didekripsi.
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel Kanan: Bedah Hexadecimal -->
        <div class="card bg-[#0a0a0a] text-gray-300 border border-gray-800 lg:col-span-2 overflow-hidden shadow-2xl relative">
            <!-- Decorative Hex pattern -->
            <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(#4f46e5 1px, transparent 1px); background-size: 20px 20px;"></div>
            
            <div class="relative z-10 space-y-6 font-mono">
                <!-- 1. Original Base64 -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-5 h-5 rounded bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs font-bold">1</div>
                        <h4 class="text-xs text-indigo-300 font-bold uppercase tracking-wider">Raw Database Base64</h4>
                    </div>
                    <div class="bg-black/50 p-3 rounded-lg text-[11px] text-gray-400 break-all border border-white/5 select-all">
                        <?= htmlspecialchars($base64_enc) ?>
                    </div>
                </div>

                <!-- 2. Hexadecimal Format -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-5 h-5 rounded bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs font-bold">2</div>
                        <h4 class="text-xs text-purple-300 font-bold uppercase tracking-wider">Full Bytes (Hexadecimal)</h4>
                    </div>
                    <div class="bg-black/50 p-3 rounded-lg text-[11px] text-gray-400 break-all border border-white/5 select-all">
                        <?= bin2hex($raw_bytes) ?>
                    </div>
                </div>

                <!-- 3. Breakdown Nonce & Ciphertext -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-5 h-5 rounded bg-amber-500/20 text-amber-400 flex items-center justify-center text-xs font-bold">3a</div>
                            <h4 class="text-xs text-amber-300 font-bold uppercase tracking-wider">Nonce (<?= $nonce_len ?> Bytes)</h4>
                        </div>
                        <div class="bg-amber-900/10 p-3 rounded-lg text-[11px] text-amber-500/80 break-all border border-amber-500/20 h-full select-all">
                            <?= bin2hex($nonce) ?>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-5 h-5 rounded bg-red-500/20 text-red-400 flex items-center justify-center text-xs font-bold">3b</div>
                            <h4 class="text-xs text-red-300 font-bold uppercase tracking-wider">Ciphertext</h4>
                        </div>
                        <div class="bg-red-900/10 p-3 rounded-lg text-[11px] text-red-500/80 break-all border border-red-500/20 h-full select-all">
                            <?= bin2hex($ciphertext) ?>
                        </div>
                    </div>
                </div>

                <!-- 4. Master Key -->
                <div class="pt-4 border-t border-white/10">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-5 h-5 rounded bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">4</div>
                        <h4 class="text-xs text-emerald-300 font-bold uppercase tracking-wider">Master Key (SHA-256 of Secret)</h4>
                    </div>
                    <div class="bg-emerald-900/10 p-3 rounded-lg text-[11px] text-emerald-500/80 break-all border border-emerald-500/20 select-all flex items-center justify-between">
                        <span><?= bin2hex($master_key) ?></span>
                        <i data-lucide="key" class="w-4 h-4 text-emerald-500/50"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    lucide.createIcons();
</script>
