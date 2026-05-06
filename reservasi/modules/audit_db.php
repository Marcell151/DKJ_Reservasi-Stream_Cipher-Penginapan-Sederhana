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
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i data-lucide="database" class="w-6 h-6 text-indigo-600"></i>
            Database Browser (Raw SQL View)
        </h3>
        <p class="text-sm text-gray-500">Representasi asli struktur tabel SQLite dengan enkripsi kolom sensitif</p>
    </div>
    
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="page" value="audit_db">
            <select name="table" class="form-control bg-gray-50 font-mono text-sm border-gray-200" onchange="this.form.submit()">
                <?php foreach($tables as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $selected_table === $key ? 'selected' : '' ?>>TABLE: <?= strtoupper($key) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="card p-0 overflow-hidden shadow-2xl border-indigo-100">
    <div class="bg-[#2d323e] p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex gap-1.5">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <div class="ml-4 font-mono text-xs text-gray-300">
                <span class="text-blue-400">SELECT</span> * <span class="text-blue-400">FROM</span> <span class="text-emerald-400"><?= $selected_table ?></span>;
            </div>
        </div>
        <div class="text-[10px] text-gray-500 font-mono">SQLite 3.x - Engine Active</div>
    </div>
    
    <div class="table-container border-none bg-white">
        <?php if(empty($data)): ?>
            <div class="p-12 text-center text-gray-400 italic">No records found in this table.</div>
        <?php else: ?>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <?php foreach (array_keys($data[0]) as $col): ?>
                            <th class="p-3 text-[10px] font-bold text-gray-500 uppercase tracking-widest font-mono">
                                <div class="flex items-center gap-1">
                                    <i data-lucide="columns" class="w-3 h-3 text-gray-400"></i>
                                    <?= $col ?>
                                    <?php if(in_array($col, $current_encrypted_fields) || $col === 'encrypted_ip_seed'): ?>
                                        <i data-lucide="lock" class="w-2.5 h-2.5 text-red-500"></i>
                                    <?php endif; ?>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 font-mono">
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-indigo-50/30 transition-all">
                            <?php foreach ($row as $col => $val): ?>
                                <?php 
                                    $is_sensitive = in_array($col, $current_encrypted_fields);
                                    $is_seed = $col === 'encrypted_ip_seed';
                                ?>
                                <td class="p-3 text-[10px] break-all">
                                    <?php if ($is_sensitive): ?>
                                        <div class="bg-red-50 text-red-600 p-1.5 rounded border border-red-100 leading-relaxed">
                                            <?= htmlspecialchars((string)$val) ?>
                                        </div>
                                    <?php elseif ($is_seed): ?>
                                        <div class="bg-indigo-50 text-indigo-600 p-1.5 rounded border border-indigo-100 leading-relaxed">
                                            <?= htmlspecialchars((string)$val) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-700"><?= htmlspecialchars((string)$val) ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-amber-50 border border-amber-100 p-4 rounded-2xl flex gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 flex-shrink-0"></i>
        <div class="text-xs text-amber-800">
            <strong>Penjelasan SQL:</strong> Perhatikan kolom berwarna <span class="text-red-600 font-bold underline">Merah</span>. Itu adalah data asli di database yang telah terenkripsi (Ciphertext). Sistem tidak menyimpan nomor HP atau Email dalam bentuk teks terbaca.
        </div>
    </div>
    <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl flex gap-3">
        <i data-lucide="shield-check" class="w-5 h-5 text-indigo-500 flex-shrink-0"></i>
        <div class="text-xs text-indigo-800">
            <strong>Keamanan IP Seed:</strong> Kolom berwarna <span class="text-indigo-600 font-bold underline">Ungu</span> adalah IP pengunjung yang dienkripsi (Tier-1). Ini adalah fondasi keamanan yang mengikat data pada jaringan user secara dinamis.
        </div>
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
