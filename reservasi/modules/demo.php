<?php
$enc_result = "";
$dec_result = "";
$input_plaintext = $_POST['input_plaintext'] ?? "";
$input_ciphertext = $_POST['input_ciphertext'] ?? "";

// Handle Encryption Action
if (isset($_POST['action']) && $_POST['action'] === 'encrypt') {
    $enc_result = SecurityHelper::encrypt($input_plaintext);
}

// Handle Decryption Action
if (isset($_POST['action']) && $_POST['action'] === 'decrypt') {
    $dec_result = SecurityHelper::decrypt($input_ciphertext);
}

$key = SecurityHelper::generateKey();
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
<div class="card mt-8 bg-[#030213] text-white">
    <div class="flex items-center gap-3 mb-6">
        <i data-lucide="activity" class="w-6 h-6 text-indigo-400"></i>
        <h3 class="text-lg font-bold">Deep Audit: ChaCha20 Internal</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
            <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Stable Server Key (HEX)</div>
            <div class="font-mono text-[10px] text-indigo-300 break-all"><?= bin2hex($key) ?></div>
        </div>
        <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
            <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Algorithm</div>
            <div class="text-sm font-bold text-emerald-400 flex items-center gap-2">
                <i data-lucide="cpu" class="w-4 h-4"></i> ChaCha20 Stream Cipher
            </div>
        </div>
        <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
            <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Nonce Handling</div>
            <div class="text-xs text-gray-300">16-byte random IV appended to payload.</div>
         </div>

         <!-- NEW: Hybrid Source Panel -->
         <div class="p-4 bg-white/5 rounded-2xl border border-white/10 md:col-span-3">
            <div class="text-[10px] font-bold text-gray-400 uppercase mb-3 flex items-center gap-2 border-b border-white/10 pb-2">
                <i data-lucide="network" class="w-3 h-3 text-indigo-400"></i> Hybrid Key Derivation Source (Network & Device Binding)
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-xs">
                    <span class="text-gray-500 block text-[9px] uppercase">IP Server:</span>
                    <span class="font-mono text-indigo-300"><?= $GLOBALS['ip_server'] ?? 'N/A' ?></span>
                </div>
                <div class="text-xs">
                    <span class="text-gray-500 block text-[9px] uppercase">Hostname:</span>
                    <span class="font-mono text-indigo-300"><?= $GLOBALS['device_sig'] ?? 'N/A' ?></span>
                </div>
                <div class="text-xs overflow-hidden">
                    <span class="text-gray-500 block text-[9px] uppercase">User Agent:</span>
                    <span class="font-mono text-indigo-300 truncate block" title="<?= htmlspecialchars($GLOBALS['user_agent'] ?? '') ?>"><?= substr(htmlspecialchars($GLOBALS['user_agent'] ?? 'CLI'), 0, 15) ?>...</span>
                </div>
                 <div class="text-xs">
                    <span class="text-gray-500 block text-[9px] uppercase">Master Secret:</span>
                    <span class="font-mono text-indigo-300">dkj_reservasi_***</span>
                </div>
            </div>
            <div class="mt-3 text-[9px] text-gray-500 italic">
                *Key di-generate otomatis dari hash SHA-256 kombinasi 4 faktor di atas.
            </div>
        </div>
    </div>

    <div class="mt-8 pt-8 border-t border-white/10">
        <h4 class="font-bold mb-4 flex items-center gap-2">
            <i data-lucide="info" class="w-4 h-4"></i> Cara Kerja Simulasi
        </h4>
        <ol class="text-sm text-gray-400 space-y-3 list-decimal list-inside leading-relaxed">
            <li><strong>Enkripsi</strong>: Plaintext di-XOR dengan keystream yang dihasilkan dari Key & Nonce. Hasilnya digabung dengan Nonce lalu di-encode ke Base64.</li>
            <li><strong>Dekripsi</strong>: Data Base64 di-decode, 16 byte pertama diambil sebagai Nonce, sisanya adalah Ciphertext. Keduanya di-XOR kembali dengan Key yang sama untuk mendapatkan teks asli.</li>
        </ol>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
