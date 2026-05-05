<?php
$tables = [
    'reservations' => 'Reservasi',
    'payments' => 'Pembayaran',
    'rooms' => 'Kamar'
];

$selected_table = $_GET['table'] ?? 'reservations';
if (!array_key_exists($selected_table, $tables)) {
    $selected_table = 'reservations';
}

$compare_mode = isset($_GET['compare']) && $_GET['compare'] == 1;

$query = "SELECT * FROM $selected_table ORDER BY id DESC";
$data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

$encrypted_fields = [
    'reservations' => ['phone', 'email', 'address', 'notes'],
    'payments' => [] // ATURAN 1: Nominal Pembayaran sekarang Plaintext
];
$current_encrypted_fields = $encrypted_fields[$selected_table] ?? [];
?>

<div class="flex justify-between items-end mb-6">
    <div>
        <h3 class="text-lg font-bold text-gray-800">Audit Storage Database</h3>
        <p class="text-sm text-gray-500">Melihat langsung isi raw tabel SQLite untuk membuktikan penyimpanan ciphertext</p>
    </div>
    
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="page" value="audit_db">
            <?php if($compare_mode): ?>
                <input type="hidden" name="compare" value="1">
            <?php endif; ?>
            <select name="table" class="form-control" onchange="this.form.submit()">
                <?php foreach($tables as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $selected_table === $key ? 'selected' : '' ?>><?= $label ?> (<?= $key ?>)</option>
                <?php endforeach; ?>
            </select>
        </form>
        
        <form method="GET">
            <input type="hidden" name="page" value="audit_db">
            <input type="hidden" name="table" value="<?= $selected_table ?>">
            <input type="hidden" name="compare" value="<?= $compare_mode ? '0' : '1' ?>">
            <button type="submit" class="py-2.5 px-4 <?= $compare_mode ? 'bg-indigo-100 text-indigo-700' : 'bg-[#1a237e] text-white' ?> font-bold rounded-xl flex items-center gap-2 text-sm shadow hover:opacity-90 transition-all">
                <i data-lucide="<?= $compare_mode ? 'eye-off' : 'eye' ?>" class="w-4 h-4"></i>
                <?= $compare_mode ? 'Tutup Compare Mode' : 'Buka Compare Mode' ?>
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 text-white p-4 rounded-2xl flex items-center gap-3">
        <div class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg"><i data-lucide="shield-check" class="w-5 h-5"></i></div>
        <div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status Storage</div>
            <div class="text-sm font-bold text-emerald-400">Encrypted Storage Active</div>
        </div>
    </div>
    <div class="bg-gray-800 text-white p-4 rounded-2xl flex items-center gap-3">
        <div class="p-2 bg-indigo-500/20 text-indigo-400 rounded-lg"><i data-lucide="database" class="w-5 h-5"></i></div>
        <div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Akses Level</div>
            <div class="text-sm font-bold text-indigo-400">SQLite Direct Access</div>
        </div>
    </div>
    <div class="bg-gray-800 text-white p-4 rounded-2xl flex items-center gap-3">
        <div class="p-2 bg-orange-500/20 text-orange-400 rounded-lg"><i data-lucide="binary" class="w-5 h-5"></i></div>
        <div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Format Data</div>
            <div class="text-sm font-bold text-orange-400">Raw Ciphertext View</div>
        </div>
    </div>
</div>

<div class="card p-0 overflow-x-auto shadow-sm border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <div class="font-mono text-xs text-gray-600 font-bold">SELECT * FROM <?= $selected_table ?>;</div>
        <div class="text-[10px] bg-red-100 text-red-600 px-2 py-1 rounded font-bold uppercase tracking-widest">Modul Khusus Audit / Presentasi</div>
    </div>
    <div class="table-container border-none">
        <?php if(empty($data)): ?>
            <div class="p-8 text-center text-gray-500 text-sm">Tidak ada data pada tabel ini.</div>
        <?php else: ?>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white">
                        <?php foreach (array_keys($data[0]) as $col): ?>
                            <th class="p-3 border-b text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-50">
                                <?= $col ?>
                                <?php if(in_array($col, $current_encrypted_fields)): ?>
                                    <i data-lucide="lock" class="w-3 h-3 inline text-red-500 ml-1" title="Encrypted Field"></i>
                                <?php endif; ?>
                            </th>
                            <?php if($compare_mode && in_array($col, $current_encrypted_fields)): ?>
                                <th class="p-3 border-b text-xs font-bold text-indigo-700 uppercase tracking-wider bg-indigo-50 border-l border-indigo-100">
                                    [Decrypted] <?= $col ?>
                                </th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <?php foreach ($row as $col => $val): ?>
                                <?php 
                                    $is_encrypted = in_array($col, $current_encrypted_fields); 
                                ?>
                                <td class="p-3 border-b text-sm <?= $is_encrypted ? 'font-mono text-[10px] text-red-600 break-all select-all' : 'text-gray-700' ?>" <?= $is_encrypted ? 'title="'.htmlspecialchars((string)$val).'"' : '' ?>>
                                    <?php 
                                        echo htmlspecialchars((string)$val);
                                    ?>
                                </td>
                                
                                <?php if($compare_mode && $is_encrypted): ?>
                                    <td class="p-3 border-b border-l border-indigo-100 bg-indigo-50/30 text-sm font-bold text-emerald-700">
                                        <?php 
                                            // ATURAN 2: Gunakan IP Historis dari kolom entry_ip untuk dekripsi
                                            $ip_source = ($selected_table === 'reservations') ? ($row['entry_ip'] ?? null) : null;
                                            echo htmlspecialchars((string)SecurityHelper::decrypt($val, $ip_source)); 
                                        ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-xl text-sm text-blue-800 flex gap-3">
    <i data-lucide="info" class="w-5 h-5 flex-shrink-0 text-blue-500"></i>
    <div>
        <strong>Penjelasan Akademik:</strong> Halaman ini mensimulasikan akses langsung ke database (`SELECT * FROM table`). 
        Kolom dengan icon gembok (<i data-lucide="lock" class="w-3 h-3 inline text-red-500"></i>) menyimpan nilai yang telah terenkripsi dengan algoritma <strong>ChaCha20</strong>.
        Jika terjadi insiden kebocoran database (Data Breach), penyerang hanya akan mendapatkan deretan karakter acak (ciphertext) pada kolom-kolom sensitif ini, sehingga data pelanggan dan nilai transaksi tetap aman.
    </div>
</div>

<script>
    lucide.createIcons();
</script>
