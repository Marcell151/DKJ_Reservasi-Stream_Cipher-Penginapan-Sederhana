<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/security_helper.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clear existing data
    $db->exec("DELETE FROM payments");
    $db->exec("DELETE FROM rentals");
    $db->exec("DELETE FROM customers");
    $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('payments', 'rentals', 'customers')");

    // 1. Get Vehicle IDs
    $vehicles = $db->query("SELECT id FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($vehicles)) die("Kendaraan belum ada.");

    // 2. Dummy Customers with DIFFERENT IP SEEDS
    $customers = [
        ['Budi Santoso', '081234567890', 'budi@gmail.com', 'Jl. Merdeka No. 10', 'KTP-123456789', '127.0.0.1'],
        ['Siti Nurhaliza', '085678901234', 'siti@yahoo.com', 'Jl. Melati No. 5', 'KTP-987654321', '192.168.1.50'],
        ['Ahmad Rahman', '087712345678', 'ahmad@outlook.com', 'Jl. Anggrek No. 2', 'KTP-456789123', '110.45.67.89'],
        ['Jessica Miller', '082233445566', 'jessica@global.com', 'Apartemen Sudirman Park', 'PASSPORT-A1234567', '202.10.20.30'],
        ['Andi Wijaya', '081399887766', 'andi.w@tech.id', 'Jl. Kebon Jeruk No. 12', 'KTP-321654987', '127.0.0.1'],
        ['Indra Jaya', '081122334455', 'indra.j@perusahaan.com', 'Perum Mentari No. 9', 'KTP-554433221', '10.0.0.10']
    ];

    $stmt_cust = $db->prepare("INSERT INTO customers (nama_pelanggan, nomor_hp, email, alamat, nomor_identitas, encrypted_ip_seed) VALUES (?, ?, ?, ?, ?, ?)");
    $customer_ids = [];

    foreach ($customers as $c) {
        $sim_ip = $c[5];
        $stmt_cust->execute([
            $c[0],
            SecurityHelper::encryptData($c[1], $sim_ip),
            SecurityHelper::encryptData($c[2], $sim_ip),
            SecurityHelper::encryptData($c[3], $sim_ip),
            SecurityHelper::encryptData($c[4], $sim_ip),
            SecurityHelper::encryptIP($sim_ip)
        ]);
        $customer_ids[] = $db->lastInsertId();
    }

    // 3. Dummy Rentals
    $rentals = [
        [$customer_ids[0], $vehicles[0], '2026-04-25', '2026-04-27', 2, '500000', 'KTP asli', '127.0.0.1'],
        [$customer_ids[1], $vehicles[1], '2026-04-26', '2026-04-28', 2, '500000', 'KK asli', '192.168.1.50'],
        [$customer_ids[2], $vehicles[2], '2026-04-27', '2026-04-30', 3, '1000000', 'Passport', '110.45.67.89'],
        [$customer_ids[3], $vehicles[3], '2026-05-01', '2026-05-02', 1, '2000000', 'Jaminan Deposit Cash', '202.10.20.30'],
        [$customer_ids[4], $vehicles[4], '2026-05-02', '2026-05-05', 3, '1500000', 'Fotokopi STNK Motor', '127.0.0.1'],
        [$customer_ids[5], $vehicles[0], '2026-05-05', '2026-05-07', 2, '600000', 'KTP & Motor Vario', '10.0.0.10']
    ];

    $stmt_rent = $db->prepare("INSERT INTO rentals (pelanggan_id, kendaraan_id, tanggal_sewa, tanggal_kembali, durasi, deposit, catatan_jaminan, status, encrypted_ip_seed) VALUES (?, ?, ?, ?, ?, ?, ?, 'berjalan', ?)");
    
    foreach ($rentals as $index => $r) {
        $sim_ip = $r[7];
        $stmt_rent->execute([
            $r[0], $r[1], $r[2], $r[3], $r[4], 
            SecurityHelper::encryptData($r[5], $sim_ip), 
            SecurityHelper::encryptData($r[6], $sim_ip),
            SecurityHelper::encryptIP($sim_ip)
        ]);
        
        $res_id = $db->lastInsertId();
        
        // 4. Dummy Payments
        $amount = '300000';
        $p_stmt = $db->prepare("INSERT INTO payments (transaksi_id, nominal_pembayaran, metode, status, payment_date, encrypted_ip_seed) VALUES (?, ?, ?, 'lunas', DATETIME('now', '-$index days'), ?)");
        $p_stmt->execute([
            $res_id, 
            SecurityHelper::encryptData($amount, $sim_ip), 
            'Transfer BCA',
            SecurityHelper::encryptIP($sim_ip)
        ]);
    }

    // Update vehicle statuses
    $db->exec("UPDATE vehicles SET status = 'disewa' WHERE id IN (SELECT kendaraan_id FROM rentals)");

    echo "Rental database seeded successfully with multiple IP seeds!";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
