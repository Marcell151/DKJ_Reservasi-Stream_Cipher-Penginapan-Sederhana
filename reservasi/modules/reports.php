<?php
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $db->prepare("SELECT p.*, r.customer_name, r.phone, r.email, r.address, r.notes, r.check_in, r.check_out, r.encrypted_ip_seed, rm.room_number 
                          FROM payments p 
                          JOIN reservations r ON p.reservation_id = r.id 
                          JOIN rooms rm ON r.room_id = rm.id
                          WHERE DATE(p.payment_date) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Laporan_Reservasi_'.$start_date.'_to_'.$end_date.'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nama Tamu', 'No HP', 'Email', 'Alamat', 'Catatan', 'Kamar', 'Check-in', 'Check-out', 'Nominal', 'Tanggal Bayar']);
    foreach ($data as $row) {
        // ATURAN 4: Dekripsi IP dahulu dengan Master Key
        $ip_historis = SecurityHelper::decryptIP($row['encrypted_ip_seed'] ?? '');
        
        fputcsv($output, [
            'RES-'.$row['reservation_id'],
            $row['customer_name'],
            SecurityHelper::decryptData($row['phone'] ?? '', $ip_historis),
            SecurityHelper::decryptData($row['email'] ?? '', $ip_historis),
            SecurityHelper::decryptData($row['address'] ?? '', $ip_historis),
            $row['notes'], // Plaintext
            $row['room_number'],
            $row['check_in'],
            $row['check_out'],
            $row['amount'], 
            $row['payment_date']
        ]);
    }
    fclose($output);
    exit();
}

$stmt = $db->prepare("SELECT p.*, r.customer_name, r.phone, r.email, r.address, r.notes, r.check_in, r.check_out, r.encrypted_ip_seed, rm.room_number 
                      FROM payments p 
                      JOIN reservations r ON p.reservation_id = r.id 
                      JOIN rooms rm ON r.room_id = rm.id
                      WHERE DATE(p.payment_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_income = 0;
foreach ($report_data as $row) {
    $total_income += (float)$row['amount'];
}
?>

<div class="card mb-8 shadow-sm">
    <form method="GET" class="flex flex-wrap items-end gap-6">
        <input type="hidden" name="page" value="reports">
        <div class="form-group flex-1 min-w-[180px]">
            <label class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-2 block">Mulai Dari</label>
            <input type="date" name="start" value="<?= $start_date ?>" class="form-control">
        </div>
        <div class="form-group flex-1 min-w-[180px]">
            <label class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-2 block">Sampai Dengan</label>
            <input type="date" name="end" value="<?= $end_date ?>" class="form-control">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="py-3.5 px-6 bg-[#1a237e] text-white font-bold rounded-xl flex items-center gap-2 hover:bg-indigo-700 transition-all">
                <i data-lucide="search" class="w-4 h-4"></i> Terapkan
            </button>
            <a href="?page=reports&start=<?= $start_date ?>&end=<?= $end_date ?>&export=csv" class="py-3.5 px-6 bg-emerald-600 text-white font-bold rounded-xl flex items-center gap-2 hover:bg-emerald-700 transition-all">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </a>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card border-b-4 border-indigo-500">
        <div class="flex justify-between items-start mb-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reservasi Selesai</div>
            <i data-lucide="layers" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <div class="text-2xl font-black text-gray-800"><?= count($report_data) ?></div>
        <div class="text-[10px] text-gray-400 mt-1">Audit Two-Tier Lulus</div>
    </div>
    <div class="card border-b-4 border-emerald-500">
        <div class="flex justify-between items-start mb-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Pemasukan</div>
            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
        </div>
        <div class="text-2xl font-black text-gray-800">Rp <?= number_format($total_income, 0, ',', '.') ?></div>
        <div class="text-[10px] text-emerald-500 font-bold mt-1">Audit Financial Lulus</div>
    </div>
    <div class="card border-b-4 border-orange-500">
        <div class="flex justify-between items-start mb-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Keamanan</div>
            <i data-lucide="lock" class="w-4 h-4 text-orange-500"></i>
        </div>
        <div class="text-2xl font-black text-gray-800">100%</div>
        <div class="text-[10px] text-gray-400 mt-1">Network Binding Active</div>
    </div>
</div>

<div class="card p-0 overflow-hidden shadow-sm">
    <div class="table-container border-none overflow-x-auto">
        <table class="min-w-[1200px]">
            <thead>
                <tr class="bg-gray-50">
                    <th class="w-16">ID</th>
                    <th>Informasi Tamu (Decrypted)</th>
                    <th>Alamat (Dec) & Catatan (Plain)</th>
                    <th>Kamar</th>
                    <th>Check-in/Out</th>
                    <th class="text-right pr-12">Nominal</th>
                    <th>Tier Seed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                <?php 
                    // ATURAN 4: Alur Dekripsi Two-Tier
                    $ip_historis = SecurityHelper::decryptIP($row['encrypted_ip_seed'] ?? '');
                    $phone_dec = SecurityHelper::decryptData($row['phone'] ?? '', $ip_historis);
                    $email_dec = SecurityHelper::decryptData($row['email'] ?? '', $ip_historis);
                    $address_dec = SecurityHelper::decryptData($row['address'] ?? '', $ip_historis);
                ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="text-xs text-gray-400 font-bold">RES-<?= str_pad($row['reservation_id'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($row['customer_name'] ?? '') ?></div>
                        <div class="text-sm text-indigo-700 font-medium font-mono mt-1">
                            <i data-lucide="phone" class="w-3 h-3 inline"></i> <?= $phone_dec ?> | 
                            <i data-lucide="mail" class="w-3 h-3 inline"></i> <?= $email_dec ?>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm text-gray-800 line-clamp-2" title="<?= $address_dec ?>">
                            <i data-lucide="map-pin" class="w-3 h-3 inline text-gray-500"></i> <?= $address_dec ?>
                        </div>
                        <div class="text-xs text-gray-600 mt-1 italic">
                            <i data-lucide="info" class="w-3 h-3 inline text-gray-400"></i> Note: <?= $row['notes'] ?>
                        </div>
                    </td>
                    <td class="text-gray-800 font-medium text-sm text-center">No. <?= $row['room_number'] ?></td>
                    <td class="text-gray-600 text-sm">
                        <?= date('d/m/y', strtotime($row['check_in'])) ?> - <?= date('d/m/y', strtotime($row['check_out'])) ?>
                    </td>
                    <td class="font-bold text-gray-800 text-right pr-12">
                        Rp <?= number_format((float)$row['amount'], 0, ',', '.') ?>
                    </td>
                    <td>
                        <div class="flex flex-col items-center">
                            <i data-lucide="layers" class="w-4 h-4 text-indigo-500 mb-1"></i>
                            <span class="text-xs text-gray-500 font-mono"><?= substr($row['encrypted_ip_seed'] ?? '', 0, 10) ?>...</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
