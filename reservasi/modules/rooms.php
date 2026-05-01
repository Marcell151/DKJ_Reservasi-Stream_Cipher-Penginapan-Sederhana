<?php
// Handle Add/Edit/Delete
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare("INSERT INTO rooms (room_number, type, price, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['room_number'], $_POST['type'], $_POST['price'], 'available']);
        set_flash_message("Kamar berhasil ditambahkan.");
    } elseif ($_POST['action'] === 'update') {
        $stmt = $db->prepare("UPDATE rooms SET room_number = ?, type = ?, price = ?, status = ? WHERE id = ?");
        $stmt->execute([$_POST['room_number'], $_POST['type'], $_POST['price'], $_POST['status'], $_POST['id']]);
        set_flash_message("Data kamar berhasil diperbarui.");
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        set_flash_message("Kamar berhasil dihapus.");
    }
    redirect("?page=rooms");
}

$rooms = $db->query("SELECT * FROM rooms ORDER BY room_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Data Kamar</h3>
        <p class="text-sm text-gray-500">Kelola informasi kamar penginapan secara real-time</p>
    </div>
    <button onclick="openModal('addRoomModal')" class="btn-primary flex items-center gap-2">
        <i data-lucide="plus" class="w-4 h-4"></i> Tambah Kamar
    </button>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>No. Kamar</th>
                    <th>Tipe</th>
                    <th>Harga/Malam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $r): ?>
                <tr>
                    <td class="font-bold text-gray-700"><?= $r['room_number'] ?></td>
                    <td><span class="text-xs px-2 py-1 bg-gray-100 rounded-md font-medium"><?= $r['type'] ?></span></td>
                    <td class="font-semibold text-gray-800">Rp <?= number_format($r['price'], 0, ',', '.') ?></td>
                    <td>
                        <span class="badge-status <?= $r['status'] === 'available' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $r['status'] === 'available' ? 'Tersedia' : 'Terisi' ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick='editRoom(<?= json_encode($r) ?>)' class="p-2 text-indigo-400 hover:text-indigo-600 bg-indigo-50 rounded-lg">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Hapus kamar ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
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

<!-- Modal Add/Edit Room -->
<div id="roomModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
        <h3 id="modalTitle" class="text-xl font-bold mb-6 text-gray-800">Tambah Kamar</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="roomAction" value="add">
            <input type="hidden" name="id" id="roomId">
            
            <div class="form-group">
                <label>Nomor Kamar</label>
                <input type="text" name="room_number" id="roomNumber" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Tipe Kamar</label>
                <select name="type" id="roomType" class="form-control">
                    <option value="Single">Single</option>
                    <option value="Double">Double</option>
                    <option value="Suite">Suite</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Harga per Malam</label>
                <input type="number" name="price" id="roomPrice" class="form-control" required>
            </div>

            <div id="statusGroup" class="form-group hidden">
                <label>Status</label>
                <select name="status" id="roomStatus" class="form-control">
                    <option value="available">Tersedia</option>
                    <option value="booked">Terisi</option>
                </select>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeModal('roomModal')" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('roomModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Tambah Kamar Baru';
        document.getElementById('roomAction').value = 'add';
        document.getElementById('statusGroup').classList.add('hidden');
        document.getElementById('roomId').value = '';
        document.getElementById('roomNumber').value = '';
        document.getElementById('roomPrice').value = '';
    }

    function closeModal(id) {
        document.getElementById('roomModal').classList.add('hidden');
    }

    function editRoom(room) {
        document.getElementById('roomModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Edit Data Kamar';
        document.getElementById('roomAction').value = 'update';
        document.getElementById('statusGroup').classList.remove('hidden');
        
        document.getElementById('roomId').value = room.id;
        document.getElementById('roomNumber').value = room.room_number;
        document.getElementById('roomType').value = room.type;
        document.getElementById('roomPrice').value = room.price;
        document.getElementById('roomStatus').value = room.status;
    }

    lucide.createIcons();
</script>
