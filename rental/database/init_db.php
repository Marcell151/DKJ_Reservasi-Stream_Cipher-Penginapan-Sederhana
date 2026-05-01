<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/security_helper.php';

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Users Table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        role TEXT
    )");

    // 2. Create Vehicles Table
    $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_kendaraan TEXT UNIQUE,
        nama_kendaraan TEXT,
        jenis TEXT,
        plat_nomor TEXT,
        tarif_harian INTEGER,
        status TEXT DEFAULT 'tersedia'
    )");

    // 3. Create Customers Table
    $db->exec("CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_pelanggan TEXT,
        nomor_hp TEXT,
        email TEXT,
        alamat TEXT,
        nomor_identitas TEXT
    )");

    // 4. Create Rentals Table
    $db->exec("CREATE TABLE IF NOT EXISTS rentals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pelanggan_id INTEGER,
        kendaraan_id INTEGER,
        tanggal_sewa DATE,
        tanggal_kembali DATE,
        durasi INTEGER,
        deposit TEXT,
        catatan_jaminan TEXT,
        status TEXT DEFAULT 'berjalan',
        FOREIGN KEY(pelanggan_id) REFERENCES customers(id),
        FOREIGN KEY(kendaraan_id) REFERENCES vehicles(id)
    )");

    // 5. Create Payments Table
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        transaksi_id INTEGER,
        nominal_pembayaran TEXT,
        metode TEXT,
        status TEXT DEFAULT 'pending',
        payment_date DATETIME,
        FOREIGN KEY(transaksi_id) REFERENCES rentals(id)
    )");

    // 6. Create Returns Table
    $db->exec("CREATE TABLE IF NOT EXISTS returns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        transaksi_id INTEGER,
        kondisi_kendaraan TEXT,
        denda_keterlambatan INTEGER,
        status_selesai TEXT DEFAULT 'selesai',
        return_date DATETIME,
        FOREIGN KEY(transaksi_id) REFERENCES rentals(id)
    )");

    // Insert Admin if not exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = SecurityHelper::hashPassword('admin123');
        $db->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$pass', 'admin')");
    }

    // Insert Dummy Vehicles
    $stmt = $db->prepare("SELECT COUNT(*) FROM vehicles");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $vehicles = [
            ['MT-001', 'Honda Vario 150', 'Motor', 'B 1234 ABC', 100000, 'tersedia'],
            ['MT-002', 'Yamaha NMAX', 'Motor', 'B 5678 DEF', 150000, 'tersedia'],
            ['MB-001', 'Toyota Avanza', 'Mobil', 'B 9012 GHI', 350000, 'tersedia'],
            ['MB-002', 'Honda Brio', 'Mobil', 'B 3456 JKL', 300000, 'tersedia'],
            ['MB-003', 'Toyota Innova', 'Mobil', 'B 7890 MNO', 500000, 'tersedia']
        ];
        $insertVehicle = $db->prepare("INSERT INTO vehicles (kode_kendaraan, nama_kendaraan, jenis, plat_nomor, tarif_harian, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($vehicles as $v) {
            $insertVehicle->execute($v);
        }
    }

    echo "Database initialized successfully!";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
