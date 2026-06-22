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

        // Ambil harga kamar dari database
        $room_stmt = $db->prepare("SELECT price FROM rooms WHERE id = ?");
        $room_stmt->execute([$_POST['room_id']]);
        $room_price = $room_stmt->fetchColumn() ?: 0;

        // Kalkulasi harga otomatis (Untuk Add)
        $ci = new DateTime($_POST['check_in']);
        $co = new DateTime($_POST['check_out']);
        $diff = $ci->diff($co)->days;
        if ($diff == 0)
            $diff = 1;
        $amount = $diff * $room_price;

        // Penentuan Status
        $today = date('Y-m-d');
        $status = 'Pending';
        if ($_POST['check_out'] < $today) {
            $status = 'Completed';
        } elseif ($_POST['check_in'] <= $today && $_POST['check_out'] >= $today) {
            $status = 'Active';
        }

        if ($_POST['action'] === 'add') {
            // Validasi Insert (Backend)
            if ($_POST['check_in'] < $today || $_POST['check_out'] <= $_POST['check_in']) {
                set_flash_message("Error: Tanggal tidak valid!");
                redirect("?page=reservations");
                exit;
            }

            // Validasi Overlap (Double Booking Prevention)
            $check_overlap = $db->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ? AND status IN ('Pending', 'Active') AND check_in < ? AND check_out > ?");
            $check_overlap->execute([$_POST['room_id'], $_POST['check_out'], $_POST['check_in']]);
            if ($check_overlap->fetchColumn() > 0) {
                set_flash_message("Gagal! Kamar tersebut sudah terpesan pada rentang tanggal yang dipilih.");
                redirect("?page=reservations");
                exit;
            }

            $stmt = $db->prepare("INSERT INTO reservations (customer_name, phone, email, address, check_in, check_out, notes, room_id, status, encrypted_ip_seed, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['customer_name'], $phone_enc, $email_enc, $address_enc, $_POST['check_in'], $_POST['check_out'], $notes_raw, $_POST['room_id'], $status, $enc_ip_seed, $amount]);
            $db->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?")->execute([$_POST['room_id']]);
            set_flash_message("Reservasi disimpan dengan Kalkulasi Biaya & Status.");
        } else {
            // Cek apakah ini perpanjangan (Extend) dan Validasi
            $old_stmt = $db->prepare("SELECT check_in, check_out, notes FROM reservations WHERE id = ?");
            $old_stmt->execute([$_POST['id']]);
            $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);

            // Validasi Update (Extend)
            if (!$old_data || $_POST['check_out'] <= $old_data['check_out']) {
                set_flash_message("Error: Tanggal perpanjangan harus lebih besar dari tanggal check-out sebelumnya!");
                redirect("?page=reservations");
                exit;
            }

            // JANGAN ambil check_in dari $_POST saat update
            $safe_check_in = $old_data['check_in'];

            // Validasi Overlap (Double Booking Prevention saat Extend)
            $check_overlap = $db->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ? AND status IN ('Pending', 'Active') AND check_in < ? AND check_out > ? AND id != ?");
            $check_overlap->execute([$_POST['room_id'], $_POST['check_out'], $safe_check_in, $_POST['id']]);
            if ($check_overlap->fetchColumn() > 0) {
                set_flash_message("Gagal Extend! Kamar sudah di-booking oleh tamu lain pada tanggal perpanjangan tersebut.");
                redirect("?page=reservations");
                exit;
            }

            // Ambil harga kamar dari database
            $room_stmt = $db->prepare("SELECT price FROM rooms WHERE id = ?");
            $room_stmt->execute([$_POST['room_id']]);
            $room_price = $room_stmt->fetchColumn() ?: 0;

            // Kalkulasi ulang amount karena check_in menggunakan $safe_check_in
            $ci = new DateTime($safe_check_in);
            $co = new DateTime($_POST['check_out']);
            $diff = $ci->diff($co)->days;
            if ($diff == 0) $diff = 1;
            $amount = $diff * $room_price;

            if (strpos($notes_raw, '[EXTENDED]') === false) {
                $notes_raw = '[EXTENDED] ' . ltrim($notes_raw);
            }

            // ATURAN EXTEND: DILARANG mengupdate kolom terenkripsi (phone, email, address, ip_seed)
            $stmt = $db->prepare("UPDATE reservations SET check_in = ?, check_out = ?, notes = ?, room_id = ?, status = ?, amount = ? WHERE id = ?");
            $stmt->execute([$safe_check_in, $_POST['check_out'], $notes_raw, $_POST['room_id'], $status, $amount, $_POST['id']]);
            set_flash_message("Reservasi diperbarui/diperpanjang (Tanpa mengubah data terenkripsi).");
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
$available_rooms = $db->query("
    SELECT rm.*, 
           (SELECT COUNT(*) FROM reservations WHERE room_id = rm.id AND status = 'Active') as is_active 
    FROM rooms rm
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Manajemen Reservasi</h3>
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
                    <th>Status Reservasi</th>
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
                                    <div class="font-mono text-xs text-red-500 truncate max-w-[150px]"
                                        title="<?= $res['phone'] ?>"><?= $res['phone'] ?></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-indigo-600 uppercase">Mail:</span>
                                    <div class="font-mono text-xs text-red-500 truncate max-w-[150px]"
                                        title="<?= $res['email'] ?>"><?= $res['email'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-mono text-xs text-gray-500 break-all max-w-[200px]"
                                title="<?= $res['address'] ?>">
                                <?= $res['address'] ?>
                            </div>
                        </td>
                        <td class="text-xs text-gray-600 italic max-w-[200px] truncate"
                            title="<?= htmlspecialchars($res['notes']) ?>">
                            <?= htmlspecialchars($res['notes']) ?>
                        </td>
                        <td>
                            <div class="flex flex-col gap-2 items-start">
                                <?php
                                $badge_class = 'bg-gray-100 text-gray-600';
                                if ($res['status'] === 'Active')
                                    $badge_class = 'bg-emerald-100 text-emerald-700';
                                elseif ($res['status'] === 'Pending')
                                    $badge_class = 'bg-amber-100 text-amber-700';
                                ?>
                                <span
                                    class="text-[10px] font-bold px-2 py-1 uppercase tracking-widest rounded-md <?= $badge_class ?>">
                                    <?= $res['status'] ?>
                                </span>
                                <div class="text-xs font-bold text-indigo-700">
                                    Rp <?= number_format($res['amount'] ?? 0, 0, ',', '.') ?>
                                </div>
                                <span class="text-[9px] text-gray-400 font-mono">IP Binding: <?= $ip_historis ?></span>
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
                                ]) ?>)'
                                    class="px-2 py-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg flex items-center gap-1">
                                    <i data-lucide="edit-3" class="w-3 h-3"></i> Extend
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
<div id="resModal"
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
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
                    <label class="flex items-center gap-2">Nomor HP <span
                            class="badge-encrypted">ChaCha20</span></label>
                    <input type="text" name="phone" id="resPhone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2">Email <span class="badge-encrypted">ChaCha20</span></label>
                    <input type="email" name="email" id="resEmail" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Pilih Kamar</label>
                    <select name="room_id" id="resRoom" class="form-control" required onchange="calculatePrice()">
                        <?php foreach ($available_rooms as $rm): ?>
                            <?php $original_text = $rm['room_number'] . ' (' . $rm['type'] . ') - Rp ' . number_format($rm['price'], 0, ',', '.') . '/malam'; ?>
                            <option value="<?= $rm['id'] ?>" data-price="<?= $rm['price'] ?>" data-active="<?= $rm['is_active'] ?>" data-original-text="<?= htmlspecialchars($original_text) ?>">
                                <?= $original_text ?>
                            </option>
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
                    <input type="date" name="check_in" id="resIn" class="form-control" required onchange="calculatePrice()">
                </div>
                <div class="form-group">
                    <label>Check-out</label>
                    <input type="date" name="check_out" id="resOut" class="form-control" required onchange="calculatePrice()">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Total Harga (Otomatis)</label>
                    <input type="text" id="resAmount" class="form-control bg-gray-100 font-bold text-indigo-700" readonly>
                    <p id="price_breakdown" class="text-xs text-gray-500 mt-1">Detail: 0 Malam x Rp 250.000 = Rp 0</p>
                </div>
                <div class="form-group">
                    <label>Catatan (Plaintext)</label>
                    <input type="text" name="notes" id="resNotes" class="form-control">
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeResModal()"
                    class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan &
                    Enkripsi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function getTodayStr() {
        const d = new Date();
        const month = '' + (d.getMonth() + 1);
        const day = '' + d.getDate();
        const year = d.getFullYear();
        return [year, month.padStart(2, '0'), day.padStart(2, '0')].join('-');
    }

    document.getElementById('resIn').addEventListener('change', function() {
        if (this.value) {
            const d = new Date(this.value);
            d.setDate(d.getDate() + 1);
            const month = '' + (d.getMonth() + 1);
            const day = '' + d.getDate();
            const year = d.getFullYear();
            const minOut = [year, month.padStart(2, '0'), day.padStart(2, '0')].join('-');
            
            document.getElementById('resOut').min = minOut;
            if (document.getElementById('resOut').value && document.getElementById('resOut').value <= this.value) {
                document.getElementById('resOut').value = '';
                calculatePrice();
            }
        }
    });

    function openResModal() {
        document.getElementById('resModal').classList.remove('hidden');
        document.getElementById('resModalTitle').innerText = 'Tambah Reservasi';
        document.getElementById('resAction').value = 'add';
        document.getElementById('resId').value = '';

        document.getElementById('resName').value = '';
        document.getElementById('resName').readOnly = false;
        document.getElementById('resName').classList.remove('bg-gray-100', 'text-gray-500');

        document.getElementById('resPhone').value = '';
        document.getElementById('resPhone').readOnly = false;
        document.getElementById('resPhone').classList.remove('bg-gray-100', 'text-gray-500');

        document.getElementById('resEmail').value = '';
        document.getElementById('resEmail').readOnly = false;
        document.getElementById('resEmail').classList.remove('bg-gray-100', 'text-gray-500');

        document.getElementById('resAddress').value = '';
        document.getElementById('resAddress').readOnly = false;
        document.getElementById('resAddress').classList.remove('bg-gray-100', 'text-gray-500');

        document.getElementById('resNotes').value = '';
        
        // Reset min dates for new reservation
        document.getElementById('resIn').value = '';
        document.getElementById('resIn').min = getTodayStr();
        document.getElementById('resIn').readOnly = false;
        document.getElementById('resIn').classList.remove('bg-gray-100', 'text-gray-500');
        document.getElementById('resOut').value = '';
        document.getElementById('resOut').min = '';

        // Disable active rooms for new reservations
        const options = document.getElementById('resRoom').options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].dataset.active > 0) {
                options[i].disabled = true;
                options[i].text = options[i].dataset.originalText + " [Sedang Terisi]";
            } else {
                options[i].disabled = false;
                options[i].text = options[i].dataset.originalText;
            }
        }

        // Auto select first available if current is disabled
        if (options.length > 0 && options[document.getElementById('resRoom').selectedIndex]?.disabled) {
            for (let i = 0; i < options.length; i++) {
                if (!options[i].disabled) {
                    document.getElementById('resRoom').selectedIndex = i;
                    break;
                }
            }
        }
        
        calculatePrice();
    }

    function calculatePrice() {
        const inDate = document.getElementById('resIn').value;
        const outDate = document.getElementById('resOut').value;
        
        const roomSelect = document.getElementById('resRoom');
        let roomPrice = 0;
        if (roomSelect && roomSelect.options.length > 0 && roomSelect.selectedIndex >= 0) {
            const selectedOption = roomSelect.options[roomSelect.selectedIndex];
            roomPrice = parseInt(selectedOption.dataset.price) || 0;
        }

        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });

        if (inDate && outDate) {
            const d1 = new Date(inDate);
            const d2 = new Date(outDate);
            let diffTime = d2.getTime() - d1.getTime();
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            if (diffDays <= 0) diffDays = 1;
            
            const total = diffDays * roomPrice;
            
            document.getElementById('resAmount').value = formatter.format(total);
            document.getElementById('price_breakdown').innerText = `Detail: ${diffDays} Malam x ${formatter.format(roomPrice)} = ${formatter.format(total)}`;
        } else {
            document.getElementById('resAmount').value = 'Rp 0';
            document.getElementById('price_breakdown').innerText = `Detail: 0 Malam x ${formatter.format(roomPrice)} = Rp 0`;
        }
    }

    function closeResModal() {
        document.getElementById('resModal').classList.add('hidden');
    }

    function editRes(res) {
        document.getElementById('resModal').classList.remove('hidden');
        document.getElementById('resModalTitle').innerText = 'Edit / Extend Reservasi';
        document.getElementById('resAction').value = 'update';

        document.getElementById('resId').value = res.id;
        document.getElementById('resName').value = res.customer_name;
        document.getElementById('resPhone').value = res.phone;
        document.getElementById('resEmail').value = res.email;
        document.getElementById('resAddress').value = res.address;

        // Disable encrypted fields during update to maintain cryptography integrity
        document.getElementById('resName').readOnly = true;
        document.getElementById('resName').classList.add('bg-gray-100', 'text-gray-500');
        document.getElementById('resPhone').readOnly = true;
        document.getElementById('resPhone').classList.add('bg-gray-100', 'text-gray-500');
        document.getElementById('resEmail').readOnly = true;
        document.getElementById('resEmail').classList.add('bg-gray-100', 'text-gray-500');
        document.getElementById('resAddress').readOnly = true;
        document.getElementById('resAddress').classList.add('bg-gray-100', 'text-gray-500');

        document.getElementById('resIn').value = res.check_in;
        document.getElementById('resIn').readOnly = true;
        document.getElementById('resIn').classList.add('bg-gray-100', 'text-gray-500');
        document.getElementById('resIn').min = '';

        document.getElementById('resOut').value = res.check_out;
        
        // Set min check_out to old check_out + 1
        const oldOut = new Date(res.check_out);
        oldOut.setDate(oldOut.getDate() + 1);
        const minOutMonth = '' + (oldOut.getMonth() + 1);
        const minOutDay = '' + oldOut.getDate();
        const minOutYear = oldOut.getFullYear();
        document.getElementById('resOut').min = [minOutYear, minOutMonth.padStart(2, '0'), minOutDay.padStart(2, '0')].join('-');

        document.getElementById('resNotes').value = res.notes;

        // Re-enable rooms for extending
        const options = document.getElementById('resRoom').options;
        for (let i = 0; i < options.length; i++) {
            options[i].disabled = false;
            options[i].text = options[i].dataset.originalText;
        }

        document.getElementById('resRoom').value = res.room_id;
        calculatePrice();
    }

    lucide.createIcons();
</script>