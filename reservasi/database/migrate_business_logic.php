<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tambah kolom amount (Status sudah ada sebagai TEXT, kita tambahkan amount)
    try {
        $db->exec("ALTER TABLE reservations ADD COLUMN amount INTEGER DEFAULT 0");
    } catch(PDOException $e) {
        // Kolom mungkin sudah ada
    }

    // 2. Lakukan update otomatis pada data yang ada
    $today = date('Y-m-d');
    $reservations = $db->query("SELECT id, check_in, check_out FROM reservations")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("UPDATE reservations SET status = ?, amount = ? WHERE id = ?");
    $room_price = 250000;

    foreach ($reservations as $r) {
        $ci = new DateTime($r['check_in']);
        $co = new DateTime($r['check_out']);
        
        $diff = $ci->diff($co)->days;
        if ($diff == 0) $diff = 1;
        $amount = $diff * $room_price;

        $status = 'Pending';
        if ($r['check_out'] < $today) {
            $status = 'Completed';
        } elseif ($r['check_in'] <= $today && $r['check_out'] >= $today) {
            $status = 'Active';
        }

        $stmt->execute([$status, $amount, $r['id']]);
    }
    
    echo "Migrasi Logika Bisnis (Status & Kalkulasi Biaya) berhasil!";

} catch(Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
