<?php
// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'pay') {
        // ATURAN 1: Jangan enkripsi nominal_pembayaran agar bisa dijumlahkan
        $amount_raw = $_POST['amount']; 
        $stmt = $db->prepare("INSERT INTO payments (reservation_id, amount, method, status, payment_date) VALUES (?, ?, ?, ?, DATETIME('now'))");
        $stmt->execute([$_POST['reservation_id'], $amount_raw, $_POST['method'], 'paid']);
        set_flash_message("Pembayaran berhasil dicatat sebagai Plaintext (Sesuai Aturan 1).");
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        set_flash_message("Data pembayaran dihapus.");
    }
    redirect("?page=payments");
}

$pending_payments = $db->query("SELECT r.id, r.customer_name, rm.price, rm.room_number 
                                FROM reservations r 
                                JOIN rooms rm ON r.room_id = rm.id 
                                LEFT JOIN payments p ON r.id = p.reservation_id 
                                WHERE p.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);

$payments = $db->query("SELECT p.*, r.customer_name 
                        FROM payments p 
                        JOIN reservations r ON p.reservation_id = r.id 
                        ORDER BY p.payment_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Keuangan & Pembayaran</h3>
        <p class="text-sm text-gray-500">Log transaksi terenkripsi ChaCha20</p>
    </div>
    <button onclick="document.getElementById('addPaymentModal').classList.toggle('hidden')" class="btn-primary flex items-center gap-2">
        <i data-lucide="plus" class="w-4 h-4"></i> Input Pembayaran
    </button>
</div>

<div class="card p-0 overflow-hidden mb-6">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th class="flex items-center gap-2">Nominal <i data-lucide="lock" class="w-3 h-3 text-orange-500"></i></th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($p['customer_name']) ?></div>
                        <div class="text-[10px] text-gray-400">REF: PY-<?= $p['id'] ?></div>
                    </td>
                    <td>
                        <?php 
                            // ATURAN 1: Data amount sekarang Plaintext
                            $fmt_amount = "Rp " . (is_numeric($p['amount']) ? number_format((float)$p['amount'], 0, ',', '.') : '0');
                        ?>
                        <div class="flex items-center gap-3">
                            <div class="bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-100 min-w-[140px]">
                                <span class="font-bold text-indigo-700 text-sm">
                                    <?= $fmt_amount ?>
                                </span>
                            </div>
                            <div class="text-[10px] text-gray-400 italic font-medium">Plaintext Data</div>
                        </div>
                    </td>
                    <td><span class="text-xs px-2 py-1 bg-blue-50 text-blue-700 rounded-md font-bold"><?= $p['method'] ?></span></td>
                    <td><span class="badge-status badge-success">Selesai</span></td>
                    <td class="text-gray-500 text-xs"><?= date('d/m/Y H:i', strtotime($p['payment_date'])) ?></td>
                    <td>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus record pembayaran ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="p-2 text-red-400 hover:text-red-600 bg-red-50 rounded-lg">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card bg-indigo-50 border-indigo-100 flex items-start gap-4 p-6">
    <div class="p-3 bg-indigo-500 rounded-xl text-white shadow-lg shadow-indigo-100">
        <i data-lucide="calculator" class="w-6 h-6"></i>
    </div>
    <div>
        <h4 class="text-indigo-900 font-bold mb-1">Audit Keuangan Plaintext (Aturan 1)</h4>
        <p class="text-sm text-indigo-700 leading-relaxed">
            Sesuai <strong>Aturan 1</strong>, data nominal di atas disimpan sebagai <strong>Plaintext</strong> agar sistem dapat melakukan fungsi agregasi (SUM/AVG) secara akurat. Meskipun demikian, data identitas pembayar di modul lain tetap terlindungi dengan arsitektur <strong>Two-Tier Key</strong>.
        </p>
    </div>
</div>

<!-- Modal Payment -->
<div id="addPaymentModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Input Pembayaran</h3>
            <button onclick="document.getElementById('addPaymentModal').classList.add('hidden')" class="text-gray-400">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="pay">
            <div class="form-group">
                <label>Pilih Reservasi Aktif</label>
                <select name="reservation_id" class="form-control" required>
                    <?php if (empty($pending_payments)): ?>
                        <option disabled>Tidak ada tagihan tertunda</option>
                    <?php endif; ?>
                    <?php foreach ($pending_payments as $pp): ?>
                        <option value="<?= $pp['id'] ?>"><?= $pp['customer_name'] ?> (Kamar <?= $pp['room_number'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nominal Pembayaran (Rp)</label>
                <input type="number" name="amount" class="form-control" placeholder="Contoh: 500000" required>
            </div>
            <div class="form-group">
                <label>Metode Pembayaran</label>
                <select name="method" class="form-control">
                    <option value="Transfer BCA">Transfer BCA</option>
                    <option value="Transfer Mandiri">Transfer Mandiri</option>
                    <option value="QRIS">QRIS / E-Wallet</option>
                    <option value="Tunai">Tunai / Cash</option>
                </select>
            </div>
            <button type="submit" class="w-full py-4 bg-[#1a237e] text-white font-bold rounded-xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all mt-4">
                Konfirmasi & Enkripsi
            </button>
        </form>
    </div>
</div>

<script>
    function toggleNominal(id) {
        const enc = document.getElementById('enc-' + id);
        const dec = document.getElementById('dec-' + id);
        const icon = document.getElementById('icon-' + id);
        
        if (dec.classList.contains('hidden')) {
            dec.classList.remove('hidden');
            enc.classList.add('hidden');
            icon.setAttribute('data-lucide', 'eye-off');
            icon.classList.add('text-emerald-600');
        } else {
            dec.classList.add('hidden');
            enc.classList.remove('hidden');
            icon.setAttribute('data-lucide', 'eye');
            icon.classList.remove('text-emerald-600');
        }
        lucide.createIcons();
    }

    lucide.createIcons();
</script>
