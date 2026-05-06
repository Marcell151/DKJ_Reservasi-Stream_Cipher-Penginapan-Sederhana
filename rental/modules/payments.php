<?php
// Handle Payment Status Update
if (isset($_POST['action']) && $_POST['action'] === 'pay') {
    $current_ip = SecurityHelper::getUserIP();
    $enc_ip_seed = SecurityHelper::encryptIP($current_ip);
    $enc_amount = SecurityHelper::encryptData($_POST['amount'], $current_ip);
    
    $stmt = $db->prepare("UPDATE payments SET nominal_pembayaran = ?, metode = ?, status = 'lunas', payment_date = DATETIME('now'), encrypted_ip_seed = ? WHERE id = ?");
    $stmt->execute([$enc_amount, $_POST['method'], $enc_ip_seed, $_POST['id']]);
    set_flash_message("Pembayaran berhasil diproses dan dienkripsi.");
    redirect("?page=payments");
}

// Ensure every rental has a payment record (lazy initialize)
$rentals_without_payment = $db->query("SELECT id, encrypted_ip_seed FROM rentals WHERE id NOT IN (SELECT transaksi_id FROM payments)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rentals_without_payment as $r) {
    $current_ip = SecurityHelper::getUserIP();
    $enc_ip_seed = SecurityHelper::encryptIP($current_ip);
    $db->prepare("INSERT INTO payments (transaksi_id, nominal_pembayaran, metode, status, encrypted_ip_seed) VALUES (?, ?, ?, 'pending', ?)")->execute([$r['id'], SecurityHelper::encryptData('0', $current_ip), 'Belum Ditentukan', $enc_ip_seed]);
}

$payments = $db->query("
    SELECT p.*, r.tanggal_sewa, r.durasi, c.nama_pelanggan, v.nama_kendaraan, v.tarif_harian 
    FROM payments p 
    JOIN rentals r ON p.transaksi_id = r.id 
    JOIN customers c ON r.pelanggan_id = c.id
    JOIN vehicles v ON r.kendaraan_id = v.id
    ORDER BY p.status DESC, p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Manajemen Pembayaran</h3>
        <p class="text-sm text-gray-500">Nominal transaksi diamankan dengan ChaCha20</p>
    </div>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Detail Rental</th>
                    <th>Total Tagihan</th>
                    <th>Nominal (Encrypted)</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): 
                    $total_tagihan = $pay['durasi'] * $pay['tarif_harian'];
                    $ip_historis = SecurityHelper::decryptIP($pay['encrypted_ip_seed'] ?? '');
                    $decrypted_amount = SecurityHelper::decryptData($pay['nominal_pembayaran'], $ip_historis);
                ?>
                <tr>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($pay['nama_pelanggan']) ?></div>
                        <div class="text-[10px] text-gray-400">TRX-<?= str_pad($pay['transaksi_id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </td>
                    <td>
                        <div class="text-xs font-semibold text-gray-700"><?= $pay['nama_kendaraan'] ?></div>
                        <div class="text-[10px] text-gray-500"><?= $pay['durasi'] ?> Hari x Rp <?= number_format($pay['tarif_harian'], 0, ',', '.') ?></div>
                    </td>
                    <td class="font-bold text-gray-800">Rp <?= number_format($total_tagihan, 0, ',', '.') ?></td>
                    <td>
                        <?php if ($pay['status'] === 'lunas'): ?>
                            <div class="font-mono text-[9px] text-red-400 bg-red-50 p-1 rounded mb-1 break-all select-all truncate max-w-[150px]" title="<?= $pay['nominal_pembayaran'] ?>">
                                <?= $pay['nominal_pembayaran'] ?>
                            </div>
                            <div class="text-[9px] text-gray-400 italic">Encrypted Value</div>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">Belum bayar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-status <?= $pay['status'] === 'lunas' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ucfirst($pay['status']) ?>
                        </span>
                        <?php if ($pay['status'] === 'lunas'): ?>
                            <div class="text-[10px] text-gray-400 mt-1"><?= $pay['metode'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pay['status'] === 'pending'): ?>
                        <button onclick="processPayment(<?= $pay['id'] ?>, <?= $total_tagihan ?>)" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-lg shadow-md transition-all">
                            Proses Bayar
                        </button>
                        <?php else: ?>
                        <div class="flex items-center gap-1 text-emerald-600 text-[10px] font-bold">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> SELESAI
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Modal -->
<div id="payModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-bold mb-6 text-gray-800">Proses Pembayaran</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="pay">
            <input type="hidden" name="id" id="payId">
            
            <div class="p-4 bg-indigo-50 rounded-xl mb-4 text-center border border-indigo-100">
                <div class="text-xs text-indigo-500 font-bold uppercase tracking-wider mb-1">Total Tagihan</div>
                <div class="text-2xl font-black text-indigo-900" id="displayAmount">Rp 0</div>
            </div>

            <div class="form-group">
                <label class="flex items-center gap-2">Nominal Bayar <span class="badge-encrypted">ChaCha20</span></label>
                <input type="number" name="amount" id="payAmount" class="form-control" required readonly>
                <div class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-3 h-3"></i> Nominal ini akan dienkripsi di database
                </div>
            </div>
            
            <div class="form-group">
                <label>Metode Pembayaran</label>
                <select name="method" class="form-control" required>
                    <option value="Cash / Tunai">Cash / Tunai</option>
                    <option value="Transfer BCA">Transfer BCA</option>
                    <option value="Transfer Mandiri">Transfer Mandiri</option>
                    <option value="QRIS / E-Wallet">QRIS / E-Wallet</option>
                </select>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="document.getElementById('payModal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan & Enkripsi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function processPayment(id, amount) {
        document.getElementById('payModal').classList.remove('hidden');
        document.getElementById('payId').value = id;
        document.getElementById('payAmount').value = amount;
        document.getElementById('displayAmount').innerText = 'Rp ' + amount.toLocaleString('id-ID');
    }
    
    lucide.createIcons();
</script>
