<?php
$enc_result = "";
$dec_result = "";
$input_plaintext = $_POST['input_plaintext'] ?? "";
$input_ciphertext = $_POST['input_ciphertext'] ?? "";

// Capture current IP for demo purposes (DYNAMIC IPv4 CAPTURE)
$current_ip = SecurityHelper::getUserIP();

// Handle Encryption Action
if (isset($_POST['action']) && $_POST['action'] === 'encrypt') {
    $enc_result = SecurityHelper::encryptData($input_plaintext, $current_ip);
}

// Handle Decryption Action
if (isset($_POST['action']) && $_POST['action'] === 'decrypt') {
    $dec_result = SecurityHelper::decryptData($input_ciphertext, $current_ip);
}

$key = SecurityHelper::getDataKey($current_ip);
// Ambil data lengkap untuk simulasi "SQL View" yang asli
$raw_reservations = $db->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Box 1: ENCRYPTOR -->
    <div class="card border-t-4 border-indigo-600">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <h3 class="text-lg font-bold">Text Encryptor</h3>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="encrypt">
            <div class="form-group">
                <label>Input Teks Biasa (Plaintext)</label>
                <textarea name="input_plaintext" rows="3" class="form-control font-mono text-sm" placeholder="Masukkan teks rahasia di sini..."><?= htmlspecialchars($input_plaintext) ?></textarea>
            </div>
            <button type="submit" class="btn-primary w-full flex items-center justify-center gap-2">
                Enkripsi ke ChaCha20 <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

        <?php if ($enc_result): ?>
            <div class="mt-6 p-4 bg-indigo-50 rounded-2xl border border-indigo-100 animate-in fade-in slide-in-from-bottom-2">
                <label class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-2 block">Hasil Ciphertext (Base64)</label>
                <div class="font-mono text-xs text-indigo-700 break-all select-all cursor-pointer bg-white p-3 rounded-lg border border-indigo-200">
                    <?= $enc_result ?>
                </div>
                <p class="text-[10px] text-indigo-400 mt-2 italic">*Klik untuk menyalin data ini ke box dekripsi</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Box 2: DECRYPTOR -->
    <div class="card border-t-4 border-emerald-600">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600">
                <i data-lucide="unlock" class="w-5 h-5"></i>
            </div>
            <h3 class="text-lg font-bold">Data Decryptor</h3>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="decrypt">
            <div class="form-group">
                <label>Input Ciphertext (Base64)</label>
                <textarea name="input_ciphertext" rows="3" class="form-control font-mono text-sm" placeholder="Paste kode Base64 di sini..."><?= htmlspecialchars($input_ciphertext) ?></textarea>
            </div>
            <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                Dekripsi ke Plaintext <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </button>
        </form>

        <?php if ($dec_result): ?>
            <div class="mt-6 p-4 bg-emerald-50 rounded-2xl border border-emerald-100 animate-in fade-in slide-in-from-bottom-2">
                <label class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-2 block">Hasil Teks Asli</label>
                <div class="font-bold text-lg text-emerald-700 break-all bg-white p-3 rounded-lg border border-emerald-200">
                    <?= htmlspecialchars($dec_result) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- AUDIT LOG / EXPLANATION -->
<div class="card mt-8 bg-[#030213] text-white overflow-hidden">
    <div class="p-8">
        <div class="flex items-center gap-3 mb-6">
            <i data-lucide="activity" class="w-6 h-6 text-indigo-400"></i>
            <h3 class="text-lg font-bold">Deep Audit: Dynamic IP & Two-Tier Security</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Real-time IP Detection</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-[10px]">
                        <span class="text-gray-500">Detected IPv4:</span>
                        <span class="text-indigo-300 font-mono"><?= $current_ip ?></span>
                    </div>
                    <div class="text-[8px] text-gray-500 mt-2">
                        *Diperoleh secara dinamis melalui <code>REMOTE_ADDR</code> atau Proxy Header.
                    </div>
                </div>
            </div>
            <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Two-Tier Key Hierarchy</div>
                <div class="text-xs text-emerald-400 font-bold flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4"></i> 
                    <span>Tier 1: Master Encryption</span>
                </div>
                <div class="text-xs text-indigo-400 font-bold flex items-center gap-2 mt-1">
                    <i data-lucide="key" class="w-4 h-4"></i> 
                    <span>Tier 2: Per-Session Data Key</span>
                </div>
            </div>
            <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Security Note on IP Seed</div>
                <div class="text-[9px] text-gray-400 leading-relaxed">
                    Kolom <code>encrypted_ip_seed</code> di database adalah IP yang telah <strong>dienkripsi</strong> oleh Master Key. Ini aman ditampilkan karena tidak membocorkan IP asli tanpa Master Secret.
                </div>
             </div>
        </div>

        <div class="mt-8 pt-8 border-t border-white/10">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold flex items-center gap-2 text-indigo-400">
                    <i data-lucide="database" class="w-4 h-4"></i> Bukti Enkripsi Database (Full SQL View)
                </h4>
                <div class="text-[9px] bg-red-500/20 text-red-400 px-2 py-1 rounded border border-red-500/30">
                    RAW SQL FORMAT
                </div>
            </div>
            
            <div class="overflow-x-auto rounded-xl border border-white/10 bg-black/40">
                <table class="w-full text-left text-[9px] font-mono">
                    <thead class="bg-white/5 text-gray-500 uppercase">
                        <tr>
                            <th class="p-3 border-b border-white/10">id</th>
                            <th class="p-3 border-b border-white/10">customer_name</th>
                            <th class="p-3 border-b border-white/10 text-red-400">phone (ENC)</th>
                            <th class="p-3 border-b border-white/10 text-red-400">email (ENC)</th>
                            <th class="p-3 border-b border-white/10 text-red-400">address (ENC)</th>
                            <th class="p-3 border-b border-white/10 text-indigo-400">encrypted_ip_seed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($raw_reservations as $row): ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="p-3 text-gray-500"><?= $row['id'] ?></td>
                            <td class="p-3 text-gray-300"><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td class="p-3 text-red-400 break-all"><?= $row['phone'] ?></td>
                            <td class="p-3 text-red-400 break-all"><?= $row['email'] ?></td>
                            <td class="p-3 text-red-400 break-all"><?= $row['address'] ?></td>
                            <td class="p-3 text-indigo-400 break-all"><?= $row['encrypted_ip_seed'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-2 text-[8px] text-gray-500 italic">
                *Tabel di atas adalah representasi asli isi database SQLite. Kolom bertanda (ENC) menyimpan ciphertext ChaCha20.
            </div>
        </div>

        <div class="mt-8 pt-8 border-t border-white/10">
            <h4 class="font-bold mb-4 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4"></i> Mekanisme Pengamanan
            </h4>
            <ul class="text-sm text-gray-400 space-y-3 leading-relaxed">
                <li class="flex gap-3">
                    <span class="text-indigo-400 font-bold">01.</span>
                    <span><strong>Dynamic Binding</strong>: Setiap baris data diikat pada IP pengunjung saat itu. Jika IP berubah, data tidak bisa dibuka tanpa prosedur pemulihan Tier-1.</span>
                </li>
                <li class="flex gap-3">
                    <span class="text-indigo-400 font-bold">02.</span>
                    <span><strong>Master Cloaking</strong>: IP Address tidak disimpan mentah, melainkan dibungkus enkripsi (<code>encrypted_ip_seed</code>) agar audit database tidak membocorkan informasi jaringan.</span>
                </li>
                <li class="flex gap-3">
                    <span class="text-indigo-400 font-bold">03.</span>
                    <span><strong>Full Transparency</strong>: Dashboard utama menampilkan ciphertext secara langsung untuk membuktikan bahwa tidak ada data sensitif yang tersimpan dalam bentuk teks biasa (plaintext).</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
