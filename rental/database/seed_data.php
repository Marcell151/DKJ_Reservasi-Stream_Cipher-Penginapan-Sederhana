<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/security_helper.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get Vehicle IDs
    $vehicles = $db->query("SELECT id FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($vehicles)) die("Kendaraan belum ada.");

    // 2. Dummy Customers
    $customers = [
        ['Budi Santoso', '081234567890', 'budi@gmail.com', 'Jl. Merdeka No. 10', 'KTP-123456789'],
        ['Siti Nurhaliza', '085678901234', 'siti@yahoo.com', 'Jl. Melati No. 5', 'KTP-987654321'],
        ['Ahmad Rahman', '087712345678', 'ahmad@outlook.com', 'Jl. Anggrek No. 2', 'KTP-456789123'],
        ['Jessica Miller', '082233445566', 'jessica@global.com', 'Apartemen Sudirman Park', 'PASSPORT-A1234567'],
        ['Andi Wijaya', '081399887766', 'andi.w@tech.id', 'Jl. Kebon Jeruk No. 12', 'KTP-321654987']
    ];

    $stmt_cust = $db->prepare("INSERT INTO customers (nama_pelanggan, nomor_hp, email, alamat, nomor_identitas) VALUES (?, ?, ?, ?, ?)");
    $customer_ids = [];

    foreach ($customers as $c) {
        $stmt_cust->execute([
            $c[0],
            SecurityHelper::encrypt($c[1]),
            SecurityHelper::encrypt($c[2]),
            SecurityHelper::encrypt($c[3]),
            SecurityHelper::encrypt($c[4])
        ]);
        $customer_ids[] = $db->lastInsertId();
    }

    // 3. Dummy Rentals
    $rentals = [
        [$customer_ids[0], $vehicles[0], '2026-04-25', '2026-04-27', 2, '500000', 'KTP dan Motor Vario ditinggal'],
        [$customer_ids[1], $vehicles[1], '2026-04-26', '2026-04-28', 2, '500000', 'KK asli'],
        [$customer_ids[2], $vehicles[2], '2026-04-27', '2026-04-30', 3, '1000000', 'Passport'],
        [$customer_ids[3], $vehicles[3], '2026-05-01', '2026-05-02', 1, '2000000', 'Jaminan Deposit Cash'],
        [$customer_ids[4], $vehicles[4], '2026-05-02', '2026-05-05', 3, '1500000', 'Fotokopi STNK Motor Pribadi']
    ];

    $stmt_rent = $db->prepare("INSERT INTO rentals (pelanggan_id, kendaraan_id, tanggal_sewa, tanggal_kembali, durasi, deposit, catatan_jaminan, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'berjalan')");
    
    foreach ($rentals as $index => $r) {
        $stmt_rent->execute([
            $r[0], $r[1], $r[2], $r[3], $r[4], 
            SecurityHelper::encrypt($r[5]), 
            SecurityHelper::encrypt($r[6])
        ]);
        
        $res_id = $db->lastInsertId();
        
        // 4. Dummy Payments for some
        if ($index !== 2 && $index !== 4) { // Some rentals not yet paid
            $amount = $index === 0 ? '200000' : ($index === 1 ? '300000' : '300000');
            $p_stmt = $db->prepare("INSERT INTO payments (transaksi_id, nominal_pembayaran, metode, status, payment_date) VALUES (?, ?, ?, 'lunas', DATETIME('now', '-$index days'))");
            $methods = ['Transfer BCA', 'Transfer Mandiri', 'QRIS', 'Cash'];
            $p_stmt->execute([
                $res_id, 
                SecurityHelper::encrypt($amount), 
                $methods[$index] ?? 'Cash'
            ]);
        }
    }

    // Update vehicle statuses
    $db->exec("UPDATE vehicles SET status = 'disewa' WHERE id IN (SELECT kendaraan_id FROM rentals)");

    echo "Dummy data inserted successfully!";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
