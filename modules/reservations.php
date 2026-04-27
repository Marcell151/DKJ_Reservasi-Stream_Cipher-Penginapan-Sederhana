<?php
// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'update') {
        $phone_enc = SecurityHelper::encrypt($_POST['phone']);
        $email_enc = SecurityHelper::encrypt($_POST['email']);
        $address_enc = SecurityHelper::encrypt($_POST['address']);
        $notes_enc = SecurityHelper::encrypt($_POST['notes']);

        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("INSERT INTO reservations (customer_name, phone, email, address, check_in, check_out, notes, room_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['customer_name'], $phone_enc, $email_enc, $address_enc, $_POST['check_in'], $_POST['check_out'], $notes_enc, $_POST['room_id'], 'confirmed']);
            $db->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?")->execute([$_POST['room_id']]);
            set_flash_message("Reservasi berhasil disimpan & dienkripsi.");
        } else {
            $stmt = $db->prepare("UPDATE reservations SET customer_name = ?, phone = ?, email = ?, address = ?, check_in = ?, check_out = ?, notes = ?, room_id = ? WHERE id = ?");
            $stmt->execute([$_POST['customer_name'], $phone_enc, $email_enc, $address_enc, $_POST['check_in'], $_POST['check_out'], $notes_enc, $_POST['room_id'], $_POST['id']]);
            set_flash_message("Reservasi diperbarui.");
        }
    } elseif ($_POST['action'] === 'delete') {
        $r = $db->prepare("SELECT room_id FROM reservations WHERE id = ?");
        $r->execute([$_POST['id']]);
        $room_id = $r->fetchColumn();
        $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$room_id]);
        $db->prepare("DELETE FROM reservations WHERE id = ?")->execute([$_POST['id']]);
        set_flash_message("Reservasi dihapus.");
    }
    redirect("?page=reservations");
}

$reservations = $db->query("SELECT r.*, rm.room_number, rm.type as room_type FROM reservations r JOIN rooms rm ON r.room_id = rm.id ORDER BY r.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$available_rooms = $db->query("SELECT * FROM rooms WHERE status = 'available' OR id IN (SELECT room_id FROM reservations)")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Manajemen Reservasi</h3>
        <p class="text-sm text-gray-500">Sistem terenkripsi ChaCha20</p>
    </div>
    <button onclick="openResModal()" class="btn-primary flex items-center gap-2">
        <i data-lucide="plus" class="w-4 h-4"></i> Reservasi Baru
    </button>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Periode</th>
                    <th>Kamar</th>
                    <th>Status Data</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                <tr>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($res['customer_name']) ?></div>
                        <div class="text-[10px] text-gray-400">ID: RES-<?= $res['id'] ?></div>
                    </td>
                    <td class="text-gray-600">
                        <div class="text-xs font-medium"><?= date('d M Y', strtotime($res['check_in'])) ?></div>
                        <div class="text-[10px] text-gray-400">s/d <?= date('d M Y', strtotime($res['check_out'])) ?></div>
                    </td>
                    <td>
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs font-bold">No. <?= $res['room_number'] ?></span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1 text-emerald-600 text-[10px] font-bold">
                            <i data-lucide="shield-check" class="w-3 h-3"></i> ENCRYPTED
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick='viewDetail(<?= json_encode($res) ?>, <?= json_encode([
                                "phone" => SecurityHelper::decrypt($res["phone"]),
                                "email" => SecurityHelper::decrypt($res["email"]),
                                "address" => SecurityHelper::decrypt($res["address"]),
                                "notes" => SecurityHelper::decrypt($res["notes"])
                            ]) ?>)' class="p-2 text-blue-400 hover:text-blue-600 bg-blue-50 rounded-lg">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick='editRes(<?= json_encode($res) ?>, <?= json_encode([
                                "phone" => SecurityHelper::decrypt($res["phone"]),
                                "email" => SecurityHelper::decrypt($res["email"]),
                                "address" => SecurityHelper::decrypt($res["address"]),
                                "notes" => SecurityHelper::decrypt($res["notes"])
                            ]) ?>)' class="p-2 text-indigo-400 hover:text-indigo-600 bg-indigo-50 rounded-lg">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Reservasi -->
<div id="resModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-2xl shadow-2xl overflow-y-auto max-h-[90vh]">
        <h3 id="resModalTitle" class="text-xl font-bold mb-6">Tambah Reservasi</h3>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" id="resAction" value="add">
            <input type="hidden" name="id" id="resId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Nama Pelanggan</label>
                    <input type="text" name="customer_name" id="resName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2">Nomor HP <span class="badge-encrypted">ChaCha20</span></label>
                    <input type="text" name="phone" id="resPhone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2">Email <span class="badge-encrypted">ChaCha20</span></label>
                    <input type="email" name="email" id="resEmail" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Pilih Kamar</label>
                    <select name="room_id" id="resRoom" class="form-control">
                        <?php foreach ($available_rooms as $rm): ?>
                            <option value="<?= $rm['id'] ?>"><?= $rm['room_number'] ?> (<?= $rm['type'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="flex items-center gap-2">Alamat <span class="badge-encrypted">ChaCha20</span></label>
                <textarea name="address" id="resAddress" class="form-control h-20" required></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Check-in</label>
                    <input type="date" name="check_in" id="resIn" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Check-out</label>
                    <input type="date" name="check_out" id="resOut" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="flex items-center gap-2">Catatan <span class="badge-encrypted">ChaCha20</span></label>
                <input type="text" name="notes" id="resNotes" class="form-control">
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeResModal()" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan & Enkripsi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Audit -->
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[210] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-3xl shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Audit Enkripsi Data</h3>
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
    function openResModal() {
        document.getElementById('resModal').classList.remove('hidden');
        document.getElementById('resModalTitle').innerText = 'Tambah Reservasi';
        document.getElementById('resAction').value = 'add';
        document.getElementById('resId').value = '';
        document.getElementById('resName').value = '';
        document.getElementById('resPhone').value = '';
        document.getElementById('resEmail').value = '';
        document.getElementById('resAddress').value = '';
        document.getElementById('resNotes').value = '';
    }

    function closeResModal() {
        document.getElementById('resModal').classList.add('hidden');
    }

    function editRes(res, dec) {
        document.getElementById('resModal').classList.remove('hidden');
        document.getElementById('resModalTitle').innerText = 'Edit Reservasi';
        document.getElementById('resAction').value = 'update';
        
        document.getElementById('resId').value = res.id;
        document.getElementById('resName').value = res.customer_name;
        document.getElementById('resPhone').value = dec.phone;
        document.getElementById('resEmail').value = dec.email;
        document.getElementById('resAddress').value = dec.address;
        document.getElementById('resIn').value = res.check_in;
        document.getElementById('resOut').value = res.check_out;
        document.getElementById('resNotes').value = dec.notes;
        document.getElementById('resRoom').value = res.room_id;
    }

    function viewDetail(res, dec) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('auditContent');
        modal.classList.remove('hidden');
        
        const fields = [
            { label: 'Nomor HP', enc: res.phone, dec: dec.phone },
            { label: 'Email', enc: res.email, dec: dec.email },
            { label: 'Alamat', enc: res.address, dec: dec.address },
            { label: 'Catatan', enc: res.notes, dec: dec.notes }
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
