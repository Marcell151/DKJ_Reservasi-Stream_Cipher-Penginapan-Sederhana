<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/security_helper.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get Room IDs
    $rooms = $db->query("SELECT id FROM rooms")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($rooms)) die("Kamar belum ada.");

    // 2. Dummy Reservations
    $reservations = [
        ['Budi Santoso', '081234567890', 'budi@gmail.com', 'Jl. Merdeka No. 10', '2026-04-25', '2026-04-27', 'Bawa selimut extra', $rooms[0]],
        ['Siti Nurhaliza', '085678901234', 'siti@yahoo.com', 'Jl. Melati No. 5', '2026-04-26', '2026-04-28', '-', $rooms[1]],
        ['Ahmad Rahman', '087712345678', 'ahmad@outlook.com', 'Jl. Anggrek No. 2', '2026-04-27', '2026-04-30', 'Kamar non-smoking', $rooms[2]],
        ['Dewi Lestari', '081122334455', 'dewi.l@gmail.com', 'Jl. Thamrin No. 8', '2026-05-01', '2026-05-03', 'Late check-in', $rooms[3]],
        ['Eko Prasetyo', '089988776655', 'eko.p@perusahaan.com', 'Gedung Cyber Lt. 5', '2026-05-02', '2026-05-05', 'Butuh invoice perusahaan', $rooms[4]]
    ];

    $stmt = $db->prepare("INSERT INTO reservations (customer_name, phone, email, address, check_in, check_out, notes, room_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
    
    foreach ($reservations as $index => $r) {
        $stmt->execute([
            $r[0], 
            SecurityHelper::encrypt($r[1]), 
            SecurityHelper::encrypt($r[2]), 
            SecurityHelper::encrypt($r[3]), 
            $r[4], $r[5], 
            SecurityHelper::encrypt($r[6]), 
            $r[7]
        ]);
        
        $res_id = $db->lastInsertId();
        
        // 3. Dummy Payments for most
        if ($index < 4) { // First 4 are paid
            $amounts = ['300000', '500000', '450000', '1500000'];
            $amount = $amounts[$index] ?? '300000';
            $p_stmt = $db->prepare("INSERT INTO payments (reservation_id, amount, method, status, payment_date) VALUES (?, ?, ?, 'paid', DATETIME('now', '-$index hours'))");
            $methods = ['Transfer BCA', 'Transfer Mandiri', 'QRIS', 'Tunai'];
            $p_stmt->execute([
                $res_id, 
                SecurityHelper::encrypt($amount), 
                $methods[$index] ?? 'Tunai'
            ]);
        }
    }

    // Update room statuses
    $db->exec("UPDATE rooms SET status = 'booked' WHERE id IN (SELECT room_id FROM reservations)");

    echo "Dummy data inserted successfully!";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
