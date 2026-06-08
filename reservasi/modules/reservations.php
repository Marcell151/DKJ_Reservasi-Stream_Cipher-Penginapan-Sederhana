<?php
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'update') {
        $current_ip = SecurityHelper::getUserIP();

        // ATURAN 3: Generate Data Key & Encrypt IP dengan Master Key
        $phone_enc = SecurityHelper::encryptData($_POST['phone'] ?? '', $current_ip);
        $email_enc = SecurityHelper::encryptData($_POST['email'] ?? '', $current_ip);
        $address_enc = SecurityHelper::encryptData($_POST['address'] ?? '', $current_ip);
        $enc_ip_seed = SecurityHelper::encryptIP($current_ip); // Tier 1: Master Key Tier
        
        // ATURAN 2: Notes sekarang Plaintext
        $notes_raw = $_POST['notes'] ?? '';

        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("INSERT INTO reservations (customer_name, phone, email, address, check_in, check_out, notes, room_id, status, encrypted_ip_seed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['customer_name'], $phone_enc, $email_enc, $address_enc, $_POST['check_in'], $_POST['check_out'], $notes_raw, $_POST['room_id'], 'confirmed', $enc_ip_seed]);
            $db->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?")->execute([$_POST['room_id']]);
            set_flash_message("Reservasi disimpan dengan Two-Tier Key (IP Terenkripsi).");
        } else {
            $stmt = $db->prepare("UPDATE reservations SET customer_name = ?, phone = ?, email = ?, address = ?, check_in = ?, check_out = ?, notes = ?, room_id = ? WHERE id = ?");
            $stmt->execute([$_POST['customer_name'], $phone_enc, $email_enc, $address_enc, $_POST['check_in'], $_POST['check_out'], $notes_raw, $_POST['room_id'], $_POST['id']]);
            set_flash_message("Reservasi diperbarui (Data Key diperbarui).");
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
        <p class="text-sm text-gray-500">Arsitektur Keamanan Two-Tier Key (Dynamic IP Binding)</p>
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
                    <th>Kontak (Ciphertext)</th>
                    <th>Alamat (Encrypted)</th>
                    <th>Catatan (Plaintext)</th>
                    <th>Status Data</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                <?php 
                    // Dekripsi IP Seed (Tier 1) untuk mendapatkan kunci Tier 2
                    $ip_historis = SecurityHelper::decryptIP($res['encrypted_ip_seed'] ?? '');
                    
                    // Dekripsi Data (Tier 2) untuk keperluan edit modal (tetap aman di memory)
                    $phone_dec = SecurityHelper::decryptData($res['phone'], $ip_historis);
                    $email_dec = SecurityHelper::decryptData($res['email'], $ip_historis);
                    $address_dec = SecurityHelper::decryptData($res['address'], $ip_historis);
                ?>
                <tr>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($res['customer_name']) ?></div>
                        <div class="text-xs text-gray-500 mt-1">Kamar: <?= $res['room_number'] ?></div>
                    </td>
                    <td>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-indigo-600 uppercase">HP:</span>
                                <div class="font-mono text-xs text-red-500 truncate max-w-[150px]" title="<?= $res['phone'] ?>"><?= $res['phone'] ?></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-indigo-600 uppercase">Mail:</span>
                                <div class="font-mono text-xs text-red-500 truncate max-w-[150px]" title="<?= $res['email'] ?>"><?= $res['email'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="font-mono text-xs text-gray-500 break-all max-w-[200px]" title="<?= $res['address'] ?>">
                            <?= $res['address'] ?>
                        </div>
                    </td>
                    <td class="text-xs text-gray-600 italic max-w-[200px] truncate" title="<?= htmlspecialchars($res['notes']) ?>">
                        <?= htmlspecialchars($res['notes']) ?>
                    </td>
                    <td>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                                <i data-lucide="shield-check" class="w-3 h-3"></i> TIER-2 ACTIVE
                            </span>
                            <span class="text-xs text-gray-500 font-mono mt-1">IP Binding: <?= $ip_historis ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick='editRes(<?= json_encode([
                                "id" => $res["id"],
                                "customer_name" => $res["customer_name"],
                                "phone" => $phone_dec,
                                "email" => $email_dec,
                                "address" => $address_dec,
                                "check_in" => $res["check_in"],
                                "check_out" => $res["check_out"],
                                "room_id" => $res["room_id"],
                                "notes" => $res["notes"]
                            ]) ?>)' class="p-2 text-indigo-400 hover:text-indigo-600 bg-indigo-50 rounded-lg">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Hapus reservasi ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $res['id'] ?>">
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
                <label>Catatan (Plaintext)</label>
                <input type="text" name="notes" id="resNotes" class="form-control">
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeResModal()" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan & Enkripsi</button>
            </div>
        </form>
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
        document.getElementById('resIn').value = '';
        document.getElementById('resOut').value = '';
    }

    function closeResModal() {
        document.getElementById('resModal').classList.add('hidden');
    }

    function editRes(res) {
        document.getElementById('resModal').classList.remove('hidden');
        document.getElementById('resModalTitle').innerText = 'Edit Reservasi';
        document.getElementById('resAction').value = 'update';
        
        document.getElementById('resId').value = res.id;
        document.getElementById('resName').value = res.customer_name;
        document.getElementById('resPhone').value = res.phone;
        document.getElementById('resEmail').value = res.email;
        document.getElementById('resAddress').value = res.address;
        document.getElementById('resIn').value = res.check_in;
        document.getElementById('resOut').value = res.check_out;
        document.getElementById('resNotes').value = res.notes;
        document.getElementById('resRoom').value = res.room_id;
    }

    lucide.createIcons();
</script>
