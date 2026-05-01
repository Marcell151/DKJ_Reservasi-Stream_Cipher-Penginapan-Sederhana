<?php
// Handle Add/Edit/Delete
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare("INSERT INTO vehicles (kode_kendaraan, nama_kendaraan, jenis, plat_nomor, tarif_harian, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['kode_kendaraan'], $_POST['nama_kendaraan'], $_POST['jenis'], $_POST['plat_nomor'], $_POST['tarif_harian'], 'tersedia']);
        set_flash_message("Kendaraan berhasil ditambahkan.");
    } elseif ($_POST['action'] === 'update') {
        $stmt = $db->prepare("UPDATE vehicles SET kode_kendaraan = ?, nama_kendaraan = ?, jenis = ?, plat_nomor = ?, tarif_harian = ?, status = ? WHERE id = ?");
        $stmt->execute([$_POST['kode_kendaraan'], $_POST['nama_kendaraan'], $_POST['jenis'], $_POST['plat_nomor'], $_POST['tarif_harian'], $_POST['status'], $_POST['id']]);
        set_flash_message("Data kendaraan berhasil diperbarui.");
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        set_flash_message("Kendaraan berhasil dihapus.");
    }
    redirect("?page=vehicles");
}

$vehicles = $db->query("SELECT * FROM vehicles ORDER BY kode_kendaraan ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Data Kendaraan</h3>
        <p class="text-sm text-gray-500">Kelola informasi kendaraan rental secara real-time</p>
    </div>
    <button onclick="openModal('addVehicleModal')" class="btn-primary flex items-center gap-2">
        <i data-lucide="plus" class="w-4 h-4"></i> Tambah Kendaraan
    </button>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kendaraan</th>
                    <th>Jenis</th>
                    <th>Plat Nomor</th>
                    <th>Tarif/Hari</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td class="font-bold text-gray-700"><?= $v['kode_kendaraan'] ?></td>
                    <td class="font-semibold text-gray-800"><?= $v['nama_kendaraan'] ?></td>
                    <td><span class="text-xs px-2 py-1 bg-gray-100 rounded-md font-medium"><?= $v['jenis'] ?></span></td>
                    <td class="text-gray-600"><?= $v['plat_nomor'] ?></td>
                    <td class="font-semibold text-gray-800">Rp <?= number_format($v['tarif_harian'], 0, ',', '.') ?></td>
                    <td>
                        <span class="badge-status <?= $v['status'] === 'tersedia' ? 'badge-success' : ($v['status'] === 'disewa' ? 'badge-warning' : 'badge-danger') ?>">
                            <?= ucfirst($v['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick='editVehicle(<?= json_encode($v) ?>)' class="p-2 text-indigo-400 hover:text-indigo-600 bg-indigo-50 rounded-lg">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Hapus kendaraan ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                <button type="submit" class="p-2 text-red-400 hover:text-red-600 bg-red-50 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit Vehicle -->
<div id="vehicleModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
        <h3 id="modalTitle" class="text-xl font-bold mb-6 text-gray-800">Tambah Kendaraan</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="vehicleAction" value="add">
            <input type="hidden" name="id" id="vehicleId">
            
            <div class="form-group">
                <label>Kode Kendaraan</label>
                <input type="text" name="kode_kendaraan" id="vehicleKode" class="form-control" required placeholder="Contoh: MT-001">
            </div>
            
            <div class="form-group">
                <label>Nama Kendaraan</label>
                <input type="text" name="nama_kendaraan" id="vehicleNama" class="form-control" required placeholder="Contoh: Honda Vario">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>Jenis</label>
                    <select name="jenis" id="vehicleJenis" class="form-control">
                        <option value="Motor">Motor</option>
                        <option value="Mobil">Mobil</option>
                        <option value="Truk">Truk</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Plat Nomor</label>
                    <input type="text" name="plat_nomor" id="vehiclePlat" class="form-control" required placeholder="B 1234 ABC">
                </div>
            </div>
            
            <div class="form-group">
                <label>Tarif per Hari (Rp)</label>
                <input type="number" name="tarif_harian" id="vehicleTarif" class="form-control" required>
            </div>

            <div id="statusGroup" class="form-group hidden">
                <label>Status</label>
                <select name="status" id="vehicleStatus" class="form-control">
                    <option value="tersedia">Tersedia</option>
                    <option value="disewa">Disewa</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeModal('vehicleModal')" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('vehicleModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Tambah Kendaraan Baru';
        document.getElementById('vehicleAction').value = 'add';
        document.getElementById('statusGroup').classList.add('hidden');
        
        document.getElementById('vehicleId').value = '';
        document.getElementById('vehicleKode').value = '';
        document.getElementById('vehicleNama').value = '';
        document.getElementById('vehiclePlat').value = '';
        document.getElementById('vehicleTarif').value = '';
    }

    function closeModal(id) {
        document.getElementById('vehicleModal').classList.add('hidden');
    }

    function editVehicle(vehicle) {
        document.getElementById('vehicleModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Edit Data Kendaraan';
        document.getElementById('vehicleAction').value = 'update';
        document.getElementById('statusGroup').classList.remove('hidden');
        
        document.getElementById('vehicleId').value = vehicle.id;
        document.getElementById('vehicleKode').value = vehicle.kode_kendaraan;
        document.getElementById('vehicleNama').value = vehicle.nama_kendaraan;
        document.getElementById('vehicleJenis').value = vehicle.jenis;
        document.getElementById('vehiclePlat').value = vehicle.plat_nomor;
        document.getElementById('vehicleTarif').value = vehicle.tarif_harian;
        document.getElementById('vehicleStatus').value = vehicle.status;
    }

    lucide.createIcons();
</script>
