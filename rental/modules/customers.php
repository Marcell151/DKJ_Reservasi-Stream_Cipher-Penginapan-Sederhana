<?php
// Handle Add/Edit/Delete
if (isset($_POST['action'])) {
    $current_ip = SecurityHelper::getUserIP();
    $enc_ip_seed = SecurityHelper::encryptIP($current_ip);

    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare("INSERT INTO customers (nama_pelanggan, nomor_hp, email, alamat, nomor_identitas, encrypted_ip_seed) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nama_pelanggan'], 
            SecurityHelper::encryptData($_POST['nomor_hp'], $current_ip), 
            SecurityHelper::encryptData($_POST['email'], $current_ip), 
            SecurityHelper::encryptData($_POST['alamat'], $current_ip), 
            SecurityHelper::encryptData($_POST['nomor_identitas'], $current_ip),
            $enc_ip_seed
        ]);
        set_flash_message("Pelanggan berhasil ditambahkan.");
    } elseif ($_POST['action'] === 'update') {
        $stmt = $db->prepare("UPDATE customers SET nama_pelanggan = ?, nomor_hp = ?, email = ?, alamat = ?, nomor_identitas = ?, encrypted_ip_seed = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nama_pelanggan'], 
            SecurityHelper::encryptData($_POST['nomor_hp'], $current_ip), 
            SecurityHelper::encryptData($_POST['email'], $current_ip), 
            SecurityHelper::encryptData($_POST['alamat'], $current_ip), 
            SecurityHelper::encryptData($_POST['nomor_identitas'], $current_ip), 
            $enc_ip_seed,
            $_POST['id']
        ]);
        set_flash_message("Data pelanggan berhasil diperbarui.");
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        set_flash_message("Pelanggan berhasil dihapus.");
    }
    redirect("?page=customers");
}

$customers = $db->query("SELECT * FROM customers ORDER BY nama_pelanggan ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Data Pelanggan</h3>
        <p class="text-sm text-gray-500">Informasi pelanggan dengan enkripsi ChaCha20 pada data sensitif</p>
    </div>
    <button onclick="openModal('customerModal')" class="btn-primary flex items-center gap-2">
        <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah Pelanggan
    </button>
</div>

<div class="card p-0 overflow-hidden">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Data Terenkripsi (Phone/Email)</th>
                    <th>Alamat & ID (Ciphertext)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): 
                    $ip_historis = SecurityHelper::decryptIP($c['encrypted_ip_seed'] ?? '');
                    $hp_dec = SecurityHelper::decryptData($c['nomor_hp'], $ip_historis);
                    $email_dec = SecurityHelper::decryptData($c['email'], $ip_historis);
                    $alamat_dec = SecurityHelper::decryptData($c['alamat'], $ip_historis);
                    $identitas_dec = SecurityHelper::decryptData($c['nomor_identitas'], $ip_historis);
                ?>
                <tr>
                    <td class="font-bold text-gray-700">
                        <?= $c['nama_pelanggan'] ?>
                        <div class="text-[9px] text-gray-400">ID: CUST-<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </td>
                    <td>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold text-indigo-500 uppercase">HP:</span>
                                <div class="font-mono text-[9px] text-red-400 truncate max-w-[120px]" title="<?= $c['nomor_hp'] ?>"><?= $c['nomor_hp'] ?></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold text-indigo-500 uppercase">Mail:</span>
                                <div class="font-mono text-[9px] text-red-400 truncate max-w-[120px]" title="<?= $c['email'] ?>"><?= $c['email'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="space-y-1">
                             <div class="flex items-center gap-2">
                                <span class="text-[8px] font-bold text-gray-400 uppercase">ADDR:</span>
                                <div class="font-mono text-[8px] text-gray-400 truncate max-w-[150px]" title="<?= $c['alamat'] ?>"><?= $c['alamat'] ?></div>
                             </div>
                             <div class="flex items-center gap-2">
                                <span class="text-[8px] font-bold text-gray-400 uppercase">IDN:</span>
                                <div class="font-mono text-[8px] text-gray-400 truncate max-w-[150px]" title="<?= $c['nomor_identitas'] ?>"><?= $c['nomor_identitas'] ?></div>
                             </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick='editCustomer(<?= json_encode([
                                "id" => $c["id"],
                                "nama_pelanggan" => $c["nama_pelanggan"],
                                "nomor_hp" => $hp_dec,
                                "email" => $email_dec,
                                "alamat" => $alamat_dec,
                                "nomor_identitas" => $identitas_dec
                            ]) ?>)' class="p-2 text-indigo-400 hover:text-indigo-600 bg-indigo-50 rounded-lg">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

<!-- Modal Add/Edit Customer -->
<div id="customerModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-6">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl animate-in zoom-in duration-300">
        <h3 id="modalTitle" class="text-xl font-bold mb-6 text-gray-800">Tambah Pelanggan</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="customerAction" value="add">
            <input type="hidden" name="id" id="customerId">
            
            <div class="form-group">
                <label>Nama Pelanggan</label>
                <input type="text" name="nama_pelanggan" id="custNama" class="form-control" required>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>No. HP <i data-lucide="lock" class="w-3 h-3 inline text-indigo-500"></i></label>
                    <input type="text" name="nomor_hp" id="custHp" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>No. Identitas <i data-lucide="lock" class="w-3 h-3 inline text-indigo-500"></i></label>
                    <input type="text" name="nomor_identitas" id="custIdentitas" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email <i data-lucide="lock" class="w-3 h-3 inline text-indigo-500"></i></label>
                <input type="email" name="email" id="custEmail" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Alamat <i data-lucide="lock" class="w-3 h-3 inline text-indigo-500"></i></label>
                <textarea name="alamat" id="custAlamat" class="form-control" rows="3" required></textarea>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 flex gap-3 mt-4">
                <div class="text-indigo-500 mt-0.5"><i data-lucide="info" class="w-4 h-4"></i></div>
                <div class="text-xs text-indigo-700">Data dengan icon gembok akan otomatis dienkripsi sebelum disimpan ke database menggunakan ChaCha20.</div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeModal('customerModal')" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-[#1a237e] text-white font-bold rounded-xl">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Tambah Pelanggan Baru';
        document.getElementById('customerAction').value = 'add';
        
        document.getElementById('customerId').value = '';
        document.getElementById('custNama').value = '';
        document.getElementById('custHp').value = '';
        document.getElementById('custEmail').value = '';
        document.getElementById('custAlamat').value = '';
        document.getElementById('custIdentitas').value = '';
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function editCustomer(customer) {
        document.getElementById('customerModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Edit Data Pelanggan';
        document.getElementById('customerAction').value = 'update';
        
        document.getElementById('customerId').value = customer.id;
        document.getElementById('custNama').value = customer.nama_pelanggan;
        document.getElementById('custHp').value = customer.nomor_hp;
        document.getElementById('custEmail').value = customer.email;
        document.getElementById('custAlamat').value = customer.alamat;
        document.getElementById('custIdentitas').value = customer.nomor_identitas;
    }

    lucide.createIcons();
</script>
