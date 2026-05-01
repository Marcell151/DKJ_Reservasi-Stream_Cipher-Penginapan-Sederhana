<?php
// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // Insert return record
        $stmt = $db->prepare("INSERT INTO returns (transaksi_id, kondisi_kendaraan, denda_keterlambatan, status_selesai, return_date) VALUES (?, ?, ?, 'selesai', DATETIME('now'))");
        $stmt->execute([
            $_POST['transaksi_id'], $_POST['kondisi_kendaraan'], $_POST['denda_keterlambatan']
        ]);
        
        // Update rental status to selesai
        $db->prepare("UPDATE rentals SET status = 'selesai' WHERE id = ?")->execute([$_POST['transaksi_id']]);
        
        // Get vehicle ID to update its status to tersedia
        $v_id_stmt = $db->prepare("SELECT kendaraan_id FROM rentals WHERE id = ?");
        $v_id_stmt->execute([$_POST['transaksi_id']]);
        $veh_id = $v_id_stmt->fetchColumn();
        $db->prepare("UPDATE vehicles SET status = 'tersedia' WHERE id = ?")->execute([$veh_id]);

        set_flash_message("Pengembalian kendaraan berhasil diproses.");
    } elseif ($_POST['action'] === 'delete') {
        // Find rental id
        $r_id_stmt = $db->prepare("SELECT transaksi_id FROM returns WHERE id = ?");
        $r_id_stmt->execute([$_POST['id']]);
        $rental_id = $r_id_stmt->fetchColumn();

        // Optional: revert rental status and vehicle status. Let's just delete the return record.
        $db->prepare("DELETE FROM returns WHERE id = ?")->execute([$_POST['id']]);
        set_flash_message("Data pengembalian dihapus.");
    }
    redirect("?page=returns");
}

$returns = $db->query("
    SELECT rt.*, r.tanggal_kembali, c.nama_pelanggan, v.nama_kendaraan, v.plat_nomor 
    FROM returns rt 
    JOIN rentals r ON rt.transaksi_id = r.id 
    JOIN customers c ON r.pelanggan_id = c.id 
    JOIN vehicles v ON r.kendaraan_id = v.id 
    ORDER BY rt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$active_rentals = $db->query("
    SELECT r.id, c.nama_pelanggan, v.nama_kendaraan, v.plat_nomor, r.tanggal_kembali
    FROM rentals r
    JOIN customers c ON r.pelanggan_id = c.id
    JOIN vehicles v ON r.kendaraan_id = v.id
    WHERE r.status = 'berjalan'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Pengembalian Kendaraan</h3>
        <p class="text-sm text-gray-500">Proses pengecekan dan pengembalian unit rental</p>
    </div>
    <button onclick="openReturnModal()" class="btn-primary flex items-center gap-2">
        <i data-lucide="check-square" class="w-4 h-4"></i> Proses Pengembalian
    </button>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Kendaraan</th>
                    <th>Tanggal Pengembalian</th>
                    <th>Kondisi / Denda</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $rt): ?>
                <tr>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($rt['nama_pelanggan']) ?></div>
                        <div class="text-[10px] text-gray-400">TRX-<?= str_pad($rt['transaksi_id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </td>
                    <td>
                        <div class="font-semibold text-gray-700"><?= $rt['nama_kendaraan'] ?></div>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-bold"><?= $rt['plat_nomor'] ?></span>
                    </td>
                    <td class="text-gray-600">
                        <div class="text-xs font-medium"><?= date('d M Y H:i', strtotime($rt['return_date'])) ?></div>
                    </td>
                    <td>
                        <div class="text-xs text-gray-600 mb-1">Kondisi: <span class="font-bold text-gray-800"><?= $rt['kondisi_kendaraan'] ?></span></div>
                        <?php if($rt['denda_keterlambatan'] > 0): ?>
                            <div class="text-xs text-red-600 font-bold">Denda: Rp <?= number_format($rt['denda_keterlambatan'], 0, ',', '.') ?></div>
                        <?php else: ?>
                            <div class="text-xs text-emerald-600 font-bold">Bebas Denda</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <form method="POST" class="inline" onsubmit="return confirm('Hapus riwayat pengembalian ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $rt['id'] ?>">
                                <button type="submit" class="p-2 text-red-400 hover:text-red-600 bg-red-50 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($returns)): ?>
                <tr>
                    <td colspan="5" class="text-center py-8 text-gray-500">Belum ada data pengembalian.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Return -->
<div id="returnModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-lg shadow-2xl animate-in zoom-in duration-300">
        <h3 class="text-xl font-bold mb-6 text-gray-800">Proses Pengembalian</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>Pilih Transaksi Aktif</label>
                <select name="transaksi_id" class="form-control" required>
                    <option value="">-- Pilih Transaksi --</option>
                    <?php foreach ($active_rentals as $r): ?>
                        <option value="<?= $r['id'] ?>">TRX-<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?> - <?= $r['nama_pelanggan'] ?> (<?= $r['plat_nomor'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Kondisi Kendaraan</label>
                <select name="kondisi_kendaraan" class="form-control" required>
                    <option value="Baik / Sesuai Awal">Baik / Sesuai Awal</option>
                    <option value="Lecet / Rusak Ringan">Lecet / Rusak Ringan</option>
                    <option value="Rusak Berat">Rusak Berat</option>
                    <option value="Kotor Parah">Kotor Parah</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Denda Keterlambatan / Kerusakan (Rp)</label>
                <input type="number" name="denda_keterlambatan" class="form-control" value="0" required>
                <div class="text-xs text-gray-400 mt-1">Isi 0 jika tidak ada denda.</div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeReturnModal()" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> Selesaikan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReturnModal() {
        document.getElementById('returnModal').classList.remove('hidden');
    }

    function closeReturnModal() {
        document.getElementById('returnModal').classList.add('hidden');
    }

    lucide.createIcons();
</script>
