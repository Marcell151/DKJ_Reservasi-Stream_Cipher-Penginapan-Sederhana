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

    // 2. Create Rooms Table
    $db->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_number TEXT UNIQUE,
        type TEXT,
        price INTEGER,
        status TEXT DEFAULT 'available'
    )");

    // 3. Create Reservations Table
    $db->exec("CREATE TABLE IF NOT EXISTS reservations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        phone TEXT,
        email TEXT,
        address TEXT,
        check_in DATE,
        check_out DATE,
        notes TEXT,
        room_id INTEGER,
        status TEXT DEFAULT 'pending',
        FOREIGN KEY(room_id) REFERENCES rooms(id)
    )");

    // 4. Create Payments Table
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reservation_id INTEGER,
        amount TEXT,
        method TEXT,
        status TEXT DEFAULT 'unpaid',
        payment_date DATETIME,
        FOREIGN KEY(reservation_id) REFERENCES reservations(id)
    )");

    // Insert Admin if not exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = SecurityHelper::hashPassword('admin123');
        $db->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$pass', 'admin')");
    }

    // Insert Dummy Rooms
    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $rooms = [
            ['101', 'Single', 150000, 'available'],
            ['102', 'Single', 150000, 'available'],
            ['201', 'Double', 250000, 'available'],
            ['202', 'Double', 250000, 'available'],
            ['301', 'Suite', 500000, 'available']
        ];
        $insertRoom = $db->prepare("INSERT INTO rooms (room_number, type, price, status) VALUES (?, ?, ?, ?)");
        foreach ($rooms as $r) {
            $insertRoom->execute($r);
        }
    }

    echo "Database initialized successfully!";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
