<?php
// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'update') {
        $deposit_enc = SecurityHelper::encrypt($_POST['deposit']);
        $catatan_enc = SecurityHelper::encrypt($_POST['catatan_jaminan']);

        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("INSERT INTO rentals (pelanggan_id, kendaraan_id, tanggal_sewa, tanggal_kembali, durasi, deposit, catatan_jaminan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['pelanggan_id'], $_POST['kendaraan_id'], $_POST['tanggal_sewa'], 
                $_POST['tanggal_kembali'], $_POST['durasi'], $deposit_enc, $catatan_enc, 'berjalan'
            ]);
            $db->prepare("UPDATE vehicles SET status = 'disewa' WHERE id = ?")->execute([$_POST['kendaraan_id']]);
            set_flash_message("Transaksi rental berhasil disimpan & dienkripsi.");
        } else {
            // Need to check if vehicle changed to update status
            $old_veh = $db->prepare("SELECT kendaraan_id FROM rentals WHERE id = ?");
            $old_veh->execute([$_POST['id']]);
            $old_v_id = $old_veh->fetchColumn();

            if ($old_v_id != $_POST['kendaraan_id']) {
                $db->prepare("UPDATE vehicles SET status = 'tersedia' WHERE id = ?")->execute([$old_v_id]);
                $db->prepare("UPDATE vehicles SET status = 'disewa' WHERE id = ?")->execute([$_POST['kendaraan_id']]);
            }

            $stmt = $db->prepare("UPDATE rentals SET pelanggan_id = ?, kendaraan_id = ?, tanggal_sewa = ?, tanggal_kembali = ?, durasi = ?, deposit = ?, catatan_jaminan = ? WHERE id = ?");
            $stmt->execute([
                $_POST['pelanggan_id'], $_POST['kendaraan_id'], $_POST['tanggal_sewa'], 
                $_POST['tanggal_kembali'], $_POST['durasi'], $deposit_enc, $catatan_enc, $_POST['id']
            ]);
            set_flash_message("Transaksi rental diperbarui.");
        }
    } elseif ($_POST['action'] === 'delete') {
        $r = $db->prepare("SELECT kendaraan_id FROM rentals WHERE id = ?");
        $r->execute([$_POST['id']]);
        $veh_id = $r->fetchColumn();
        $db->prepare("UPDATE vehicles SET status = 'tersedia' WHERE id = ?")->execute([$veh_id]);
        $db->prepare("DELETE FROM rentals WHERE id = ?")->execute([$_POST['id']]);
        set_flash_message("Transaksi rental dihapus.");
    }
    redirect("?page=rentals");
}

$rentals = $db->query("
    SELECT r.*, c.nama_pelanggan, v.nama_kendaraan, v.plat_nomor 
    FROM rentals r 
    JOIN customers c ON r.pelanggan_id = c.id 
    JOIN vehicles v ON r.kendaraan_id = v.id 
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$customers = $db->query("SELECT id, nama_pelanggan FROM customers ORDER BY nama_pelanggan")->fetchAll(PDO::FETCH_ASSOC);
$available_vehicles = $db->query("SELECT * FROM vehicles WHERE status = 'tersedia' OR id IN (SELECT kendaraan_id FROM rentals)")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Transaksi Rental</h3>
        <p class="text-sm text-gray-500">Manajemen penyewaan kendaraan dengan ChaCha20</p>
    </div>
    <button onclick="openRentalModal()" class="btn-primary flex items-center gap-2">
        <i data-lucide="plus" class="w-4 h-4"></i> Rental Baru
    </button>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Kendaraan</th>
                    <th>Periode</th>
                    <th>Status Data</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $res): ?>
                <tr>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($res['nama_pelanggan']) ?></div>
                        <div class="text-[10px] text-gray-400">TRX-<?= str_pad($res['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </td>
                    <td>
                        <div class="font-semibold text-gray-700"><?= $res['nama_kendaraan'] ?></div>
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs font-bold"><?= $res['plat_nomor'] ?></span>
                    </td>
                    <td class="text-gray-600">
                        <div class="text-xs font-medium"><?= date('d M Y', strtotime($res['tanggal_sewa'])) ?> s/d</div>
                        <div class="text-xs font-medium"><?= date('d M Y', strtotime($res['tanggal_kembali'])) ?> (<?= $res['durasi'] ?> hari)</div>
                    </td>
                    <td>
                        <div class="flex items-center gap-1 text-emerald-600 text-[10px] font-bold mb-1">
                            <i data-lucide="shield-check" class="w-3 h-3"></i> ENCRYPTED
                        </div>
                        <span class="badge-status <?= $res['status'] === 'berjalan' ? 'badge-warning' : 'badge-success' ?>">
                            <?= ucfirst($res['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick='viewDetail(<?= json_encode($res) ?>, <?= json_encode([
                                "deposit" => SecurityHelper::decrypt($res["deposit"]),
                                "catatan_jaminan" => SecurityHelper::decrypt($res["catatan_jaminan"])
                            ]) ?>)' class="p-2 text-blue-400 hover:text-blue-600 bg-blue-50 rounded-lg" title="Audit Data">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <?php if($res['status'] === 'berjalan'): ?>
                            <button onclick='editRental(<?= json_encode($res) ?>, <?= json_encode([
                                "deposit" => SecurityHelper::decrypt($res["deposit"]),
                                "catatan_jaminan" => SecurityHelper::decrypt($res["catatan_jaminan"])
                            ]) ?>)' class="p-2 text-indigo-400 hover:text-indigo-600 bg-indigo-50 rounded-lg">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Rental -->
<div id="rentalModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-2xl shadow-2xl overflow-y-auto max-h-[90vh]">
        <h3 id="rentalModalTitle" class="text-xl font-bold mb-6">Tambah Transaksi Rental</h3>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" id="rentalAction" value="add">
            <input type="hidden" name="id" id="rentalId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Pelanggan</label>
                    <select name="pelanggan_id" id="rentalPelanggan" class="form-control" required>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['nama_pelanggan'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kendaraan</label>
                    <select name="kendaraan_id" id="rentalKendaraan" class="form-control" required>
                        <?php foreach ($available_vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= $v['nama_kendaraan'] ?> (<?= $v['plat_nomor'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="form-group">
                    <label>Tanggal Sewa</label>
                    <input type="date" name="tanggal_sewa" id="rentalSewa" class="form-control" required onchange="calculateDuration()">
                </div>
                <div class="form-group">
                    <label>Tanggal Kembali</label>
                    <input type="date" name="tanggal_kembali" id="rentalKembali" class="form-control" required onchange="calculateDuration()">
                </div>
                <div class="form-group">
                    <label>Durasi (Hari)</label>
                    <input type="number" name="durasi" id="rentalDurasi" class="form-control bg-gray-100" readonly>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="flex items-center gap-2">Deposit (Rp) <span class="badge-encrypted"><i data-lucide="lock" class="w-3 h-3 inline"></i></span></label>
                    <input type="number" name="deposit" id="rentalDeposit" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2">Catatan Jaminan <span class="badge-encrypted"><i data-lucide="lock" class="w-3 h-3 inline"></i></span></label>
                    <input type="text" name="catatan_jaminan" id="rentalJaminan" class="form-control" placeholder="Contoh: KTP Asli">
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeRentalModal()" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan & Enkripsi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Audit -->
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[210] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-3xl shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Audit Enkripsi Data Rental</h3>
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 text-xs font-bold uppercase tracking-widest text-gray-400">
                <div>Data Terenkripsi (DB)</div>
                <div>Data Terdekripsi (UI)</div>
            </div>

            <div id="auditContent" class="space-y-4">
                <!-- Dynamic Content -->
            </div>
        </div>
    </div>
</div>

<script>
    function calculateDuration() {
        const start = document.getElementById('rentalSewa').value;
        const end = document.getElementById('rentalKembali').value;
        if(start && end) {
            const date1 = new Date(start);
            const date2 = new Date(end);
            const diffTime = Math.abs(date2 - date1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            document.getElementById('rentalDurasi').value = diffDays || 1; // minimum 1 day
        }
    }

    function openRentalModal() {
        document.getElementById('rentalModal').classList.remove('hidden');
        document.getElementById('rentalModalTitle').innerText = 'Tambah Transaksi Rental';
        document.getElementById('rentalAction').value = 'add';
        document.getElementById('rentalId').value = '';
        
        document.getElementById('rentalSewa').value = '';
        document.getElementById('rentalKembali').value = '';
        document.getElementById('rentalDurasi').value = '';
        document.getElementById('rentalDeposit').value = '';
        document.getElementById('rentalJaminan').value = '';
    }

    function closeRentalModal() {
        document.getElementById('rentalModal').classList.add('hidden');
    }

    function editRental(res, dec) {
        document.getElementById('rentalModal').classList.remove('hidden');
        document.getElementById('rentalModalTitle').innerText = 'Edit Transaksi Rental';
        document.getElementById('rentalAction').value = 'update';
        
        document.getElementById('rentalId').value = res.id;
        document.getElementById('rentalPelanggan').value = res.pelanggan_id;
        document.getElementById('rentalKendaraan').value = res.kendaraan_id;
        document.getElementById('rentalSewa').value = res.tanggal_sewa;
        document.getElementById('rentalKembali').value = res.tanggal_kembali;
        document.getElementById('rentalDurasi').value = res.durasi;
        
        document.getElementById('rentalDeposit').value = dec.deposit;
        document.getElementById('rentalJaminan').value = dec.catatan_jaminan;
    }

    function viewDetail(res, dec) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('auditContent');
        modal.classList.remove('hidden');
        
        const fields = [
            { label: 'Deposit', enc: res.deposit, dec: 'Rp ' + dec.deposit },
            { label: 'Catatan Jaminan', enc: res.catatan_jaminan, dec: dec.catatan_jaminan }
        ];

        content.innerHTML = fields.map(f => `
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                <div class="text-[10px] font-bold text-indigo-600 mb-2 uppercase">${f.label}</div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="font-mono text-[10px] text-red-500 break-all bg-white p-2 rounded-lg border border-red-100">${f.enc}</div>
                    <div class="font-mono text-sm text-emerald-600 break-all bg-white p-2 rounded-lg border border-emerald-100 font-bold">${f.dec}</div>
                </div>
            </div>
        `).join('');
        
        lucide.createIcons();
    }
</script>
