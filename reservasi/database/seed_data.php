<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/security_helper.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clear existing data for fresh seed
    $db->exec("DELETE FROM reservations");
    $db->exec("DELETE FROM rooms");
    $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('reservations', 'rooms')");

    // 1. Seed Rooms
    $rooms = [
        ['101', 'Superior', 500000],
        ['102', 'Superior', 500000],
        ['201', 'Deluxe', 850000],
        ['202', 'Deluxe', 850000],
        ['301', 'Suite', 1500000],
    ];

    $stmt_room = $db->prepare("INSERT INTO rooms (room_number, type, price, status) VALUES (?, ?, ?, 'available')");
    foreach ($rooms as $r) {
        $stmt_room->execute($r);
    }
    $room_ids = $db->query("SELECT id FROM rooms")->fetchAll(PDO::FETCH_COLUMN);

    // 2. Seed Reservations with DIFFERENT IP SEEDS to demonstrate dynamic encryption
    $reservations = [
        ['Andi Wijaya', '08123456789', 'andi@gmail.com', 'Jl. Merdeka No. 10, Jakarta', '2026-05-01', '2026-05-03', 'Deposit Lunas', $room_ids[0], '127.0.0.1'],
        ['Budi Santoso', '08567890123', 'budi.s@yahoo.com', 'Gg. Kelinci No. 5, Bandung', '2026-05-02', '2026-05-05', 'Minta sarapan pagi', $room_ids[1], '192.168.1.15'],
        ['Citra Lestari', '08771122334', 'citra_l@hotmail.com', 'Apartemen Sudirman Park, Jakarta', '2026-05-05', '2026-05-07', '-', $room_ids[2], '110.12.34.56'],
        ['Dedi Kurniawan', '08139988776', 'dedi.k@perusahaan.co.id', 'Perumahan Elit No. 12, Surabaya', '2026-05-10', '2026-05-12', 'Late check-in', $room_ids[3], '202.45.67.89'],
        ['Eka Putri', '08224455667', 'eka.putri@univ.ac.id', 'Kos Hijau, Yogyakarta', '2026-05-15', '2026-05-20', 'Jaminan KTP', $room_ids[4], '10.0.0.5'],
        ['Faisal Ahmad', '08190011223', 'faisal.a@gmail.com', 'Jl. Malioboro No. 1, Yogyakarta', '2026-05-20', '2026-05-22', 'Check-out jam 1 siang', $room_ids[0], '127.0.0.1'],
        ['Gita Savitri', '08523344556', 'gita.s@global.com', 'Kemang Village, Jakarta', '2026-05-25', '2026-05-28', 'Bawa anjing kecil', $room_ids[1], '172.16.0.100'],
        ['Hendra Wijaya', '08998877665', 'hendra.w@outlook.com', 'Jl. Pahlawan No. 9, Semarang', '2026-06-01', '2026-06-03', 'Minta extra bed', $room_ids[2], '110.12.34.56']
    ];

    $stmt = $db->prepare("INSERT INTO reservations (customer_name, phone, email, address, check_in, check_out, notes, room_id, status, encrypted_ip_seed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)");
    
    foreach ($reservations as $r) {
        $simulated_ip = $r[8];
        $enc_ip_seed = SecurityHelper::encryptIP($simulated_ip);
        
        $stmt->execute([
            $r[0], 
            SecurityHelper::encryptData($r[1], $simulated_ip),
            SecurityHelper::encryptData($r[2], $simulated_ip),
            SecurityHelper::encryptData($r[3], $simulated_ip),
            $r[4], $r[5], $r[6], $r[7],
            $enc_ip_seed
        ]);
    }

    echo "Reservasi database seeded successfully with multiple IP seeds!";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
