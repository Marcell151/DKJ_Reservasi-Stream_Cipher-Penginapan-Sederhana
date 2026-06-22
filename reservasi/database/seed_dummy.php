<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/security_helper.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Hapus seluruh data transaksi agar bersih
    $db->exec("DELETE FROM payments");
    $db->exec("DELETE FROM reservations");
    $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('reservations', 'payments')");

    // Ambil ID kamar untuk variasi (fallback ke 1 jika kosong)
    $room_ids = $db->query("SELECT id FROM rooms LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($room_ids)) {
        $db->exec("INSERT INTO rooms (room_number, type, price, status) VALUES ('999', 'Dummy Room', 100000, 'available')");
        $room_ids = [$db->lastInsertId()];
    }

    // 8 data dummy transaksi dengan kriteria:
    // Kolom 0: customer_name
    // Kolom 1: phone (akan dienkripsi)
    // Kolom 2: email (akan dienkripsi)
    // Kolom 3: address (akan dienkripsi)
    // Kolom 4: check_in
    // Kolom 5: check_out
    // Kolom 6: notes
    // Kolom 7: amount (untuk tabel payments)
    // Kolom 8: ip_transaksi
    $dummy_data = [
        // 6 Data Pertama: Menggunakan IP Statis Office (192.168.10.10) -> Matched saat Demo Mode ON
        ['Siti Aminah', '081122334455', 'siti.aminah@mail.com', 'Jl. Merdeka No. 1, Jakarta', '2026-03-10', '2026-03-12', 'Minta extra bantal', 500000, '192.168.10.10'],
        ['Bagus Prakoso', '081234567890', 'bagus.p@mail.com', 'Jl. Sudirman No. 2, Bandung', '2026-04-05', '2026-04-07', 'Late check-in', 750000, '192.168.10.10'],
        ['Ratna Sari', '082199887766', 'ratna_sari@mail.com', 'Jl. Pemuda No. 3, Semarang', '2026-05-01', '2026-05-04', '-', 450000, '192.168.10.10'],
        ['Dwi Santoso', '085611223344', 'dwi.s@mail.com', 'Jl. Gatot Subroto No. 4, Surabaya', '2026-05-15', '2026-05-18', 'No smoking room', 1200000, '192.168.10.10'],
        ['Lestari Ayu', '081344556677', 'lestari.a@mail.com', 'Jl. Thamrin No. 5, Medan', '2026-06-01', '2026-06-03', '-', 600000, '192.168.10.10'],
        ['Eko Cahyono', '087755664433', 'eko.c@mail.com', 'Jl. Veteran No. 6, Bali', '2026-06-10', '2026-06-15', 'View pantai', 2500000, '192.168.10.10'],
        
        // 4 Data Terakhir: Menggunakan IP Publik acak -> Mismatch/Terkunci saat Demo Mode ON
        ['Fajar Hidayat', '089912345678', 'fajar.h@mail.com', 'Jl. Diponegoro No. 7, Makassar', '2026-06-18', '2026-06-20', 'Breakfast include', 800000, '114.12.55.9'],
        ['Gita Permata', '081577889900', 'gita.p@mail.com', 'Jl. Ahmad Yani No. 8, Balikpapan', '2026-06-22', '2026-06-25', '-', 950000, '180.22.14.5'],
        ['Hendra Wijaya', '085299887766', 'hendra.w@mail.com', 'Jl. Pahlawan No. 9, Palembang', '2026-06-26', '2026-06-28', 'Minta extra bed', 600000, '202.16.55.12'],
        ['Indah Kusuma', '081933445566', 'indah.k@mail.com', 'Jl. Gajah Mada No. 10, Pontianak', '2026-06-28', '2026-06-30', 'Check-out telat', 700000, '110.13.44.88'],
    ];

    $stmt_res = $db->prepare("INSERT INTO reservations (customer_name, phone, email, address, check_in, check_out, notes, room_id, status, encrypted_ip_seed, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_pay = $db->prepare("INSERT INTO payments (reservation_id, amount, method, status, payment_date) VALUES (?, ?, 'transfer', 'paid', ?)");

    foreach ($dummy_data as $i => $data) {
        $ip_transaksi = $data[8];
        $room_id = $room_ids[$i % count($room_ids)];
        
        // Kalkulasi Status berdasarkan Tanggal
        $today = date('Y-m-d');
        $status = 'Pending';
        if ($data[5] < $today) {
            $status = 'Completed';
        } elseif ($data[4] <= $today && $data[5] >= $today) {
            $status = 'Active';
        }

        // Kalkulasi Amount berdasarkan selisih hari x Harga (asumsi Rp250.000)
        $ci = new DateTime($data[4]);
        $co = new DateTime($data[5]);
        $diff = $ci->diff($co)->days;
        if ($diff == 0) $diff = 1;
        $amount = $diff * 250000;

        // Enkripsi IP Seed dengan Master Key
        $encrypted_ip = SecurityHelper::encryptIP($ip_transaksi);
        
        // Enkripsi Data Sensitif dengan Data Key (yang diracik dari $ip_transaksi)
        $encrypted_phone = SecurityHelper::encryptData($data[1], $ip_transaksi);
        $encrypted_email = SecurityHelper::encryptData($data[2], $ip_transaksi);
        $encrypted_address = SecurityHelper::encryptData($data[3], $ip_transaksi);
        
        // Eksekusi INSERT ke tabel reservations
        $stmt_res->execute([
            $data[0], // customer_name (Plaintext)
            $encrypted_phone,
            $encrypted_email,
            $encrypted_address,
            $data[4], // check_in (Plaintext)
            $data[5], // check_out (Plaintext)
            $data[6], // notes (Plaintext)
            $room_id,
            $status,
            $encrypted_ip,
            $amount
        ]);

        $reservation_id = $db->lastInsertId();
        
        // Eksekusi INSERT ke tabel payments untuk menampung 'amount'
        $payment_date = $data[4] . ' 10:00:00'; // Set waktu bayar = tanggal check-in
        $stmt_pay->execute([$reservation_id, $amount, $payment_date]);
    }

    echo "Reset database dan Seeding 10 data dummy berhasil!";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
