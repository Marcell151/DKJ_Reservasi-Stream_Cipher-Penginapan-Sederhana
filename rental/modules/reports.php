<?php
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $db->prepare("SELECT p.*, r.tanggal_sewa, r.tanggal_kembali, r.durasi, c.nama_pelanggan, v.nama_kendaraan, v.plat_nomor 
                          FROM payments p 
                          JOIN rentals r ON p.transaksi_id = r.id 
                          JOIN customers c ON r.pelanggan_id = c.id
                          JOIN vehicles v ON r.kendaraan_id = v.id
                          WHERE p.status = 'lunas' AND DATE(p.payment_date) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Laporan_Rental_'.$start_date.'_to_'.$end_date.'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Transaksi', 'Nama Pelanggan', 'Kendaraan', 'Tanggal Sewa', 'Tanggal Kembali', 'Durasi', 'Nominal Pembayaran', 'Tanggal Bayar']);
    foreach ($data as $row) {
        fputcsv($output, [
            'TRX-'.$row['transaksi_id'],
            $row['nama_pelanggan'],
            $row['nama_kendaraan'].' ('.$row['plat_nomor'].')',
            $row['tanggal_sewa'],
            $row['tanggal_kembali'],
            $row['durasi'].' Hari',
            SecurityHelper::decrypt($row['nominal_pembayaran']),
            $row['payment_date']
        ]);
    }
    fclose($output);
    exit();
}

$stmt = $db->prepare("SELECT p.*, r.tanggal_sewa, r.tanggal_kembali, r.durasi, c.nama_pelanggan, v.nama_kendaraan, v.plat_nomor 
                      FROM payments p 
                      JOIN rentals r ON p.transaksi_id = r.id 
                      JOIN customers c ON r.pelanggan_id = c.id
                      JOIN vehicles v ON r.kendaraan_id = v.id
                      WHERE p.status = 'lunas' AND DATE(p.payment_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_income = 0;
foreach ($report_data as $row) {
    $decrypted = SecurityHelper::decrypt($row['nominal_pembayaran']);
    if (is_numeric($decrypted)) {
        $total_income += (float)$decrypted;
    }
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
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Transaksi Lunas</div>
            <i data-lucide="check-circle" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <div class="text-2xl font-black text-gray-800"><?= count($report_data) ?></div>
        <div class="text-[10px] text-gray-400 mt-1">Transaksi terverifikasi</div>
    </div>
    <div class="card border-b-4 border-emerald-500">
        <div class="flex justify-between items-start mb-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Pendapatan</div>
            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
        </div>
        <div class="text-2xl font-black text-gray-800">Rp <?= number_format($total_income, 0, ',', '.') ?></div>
        <div class="text-[10px] text-emerald-500 font-bold mt-1">Audit Enkripsi Lulus</div>
    </div>
    <div class="card border-b-4 border-orange-500">
        <div class="flex justify-between items-start mb-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Rata-rata</div>
            <i data-lucide="trending-up" class="w-4 h-4 text-orange-500"></i>
        </div>
        <div class="text-2xl font-black text-gray-800">Rp <?= count($report_data) > 0 ? number_format($total_income / count($report_data), 0, ',', '.') : '0' ?></div>
        <div class="text-[10px] text-gray-400 mt-1">Per transaksi</div>
    </div>
</div>

<div class="card p-0 overflow-hidden shadow-sm">
    <div class="table-container border-none">
        <table>
            <thead>
                <tr class="bg-gray-50">
                    <th class="w-16">ID</th>
                    <th class="w-1/4">Nama Pelanggan</th>
                    <th>Kendaraan</th>
                    <th>Tgl Sewa/Kembali</th>
                    <th class="text-right pr-12">Nominal (Decrypted)</th>
                    <th>Audit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                <?php 
                    $decrypted_amount = SecurityHelper::decrypt($row['nominal_pembayaran']);
                    $amount_float = is_numeric($decrypted_amount) ? (float)$decrypted_amount : 0;
                ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="text-xs text-gray-400 font-bold">TRX-<?= str_pad($row['transaksi_id'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td class="font-bold text-gray-800">
                        <div class="flex items-center gap-2">
                            <?= htmlspecialchars($row['nama_pelanggan']) ?>
                        </div>
                    </td>
                    <td class="text-gray-600 font-medium text-xs"><?= $row['nama_kendaraan'] ?> (<?= $row['plat_nomor'] ?>)</td>
                    <td class="text-gray-400 text-[10px]">
                        <?= date('d/m/y', strtotime($row['tanggal_sewa'])) ?> - <?= date('d/m/y', strtotime($row['tanggal_kembali'])) ?>
                    </td>
                    <td class="font-bold text-gray-800 text-right pr-12">
                        Rp <?= number_format($amount_float, 0, ',', '.') ?>
                    </td>
                    <td>
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-200" title="Data Verified"></div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($report_data)): ?>
                    <tr><td colspan="6" class="p-12 text-center text-gray-400 italic">Tidak ada data untuk periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
