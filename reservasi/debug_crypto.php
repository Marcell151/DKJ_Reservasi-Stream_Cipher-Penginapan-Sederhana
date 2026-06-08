<?php
// debug_crypto.php

// 1. Include config and helpers
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/security_helper.php';

echo "<h2>Alat Edukasi/Debug Kriptografi ChaCha20 (Two-Tier Key)</h2>";
echo "<pre style='background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 5px; font-size: 14px;'>";

try {
    // 2. Koneksi ke SQLite dan ambil 1 data
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Coba ambil data pertama yang memiliki encrypted_ip_seed
    $stmt = $db->query("SELECT id, customer_name, encrypted_ip_seed FROM reservations WHERE encrypted_ip_seed IS NOT NULL LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo "Tidak ada data reservasi dengan encrypted_ip_seed di database.\n";
        echo "</pre>";
        exit;
    }

    $base64_enc = $data['encrypted_ip_seed'];
    echo "<b>=== DATA RESERVASI ===</b>\n";
    echo "ID            : " . htmlspecialchars($data['id']) . "\n";
    echo "Nama Customer : " . htmlspecialchars($data['customer_name']) . "\n\n";

    // A. Teks Base64 asli
    echo "<b>A. TEKS BASE64 ASLI DARI DATABASE:</b>\n";
    echo htmlspecialchars($base64_enc) . "\n\n";

    // B. Hasil decode Base64 format Hexadecimal
    $raw_bytes = base64_decode($base64_enc);
    echo "<b>B. HASIL DECODE BASE64 (HEXADECIMAL):</b>\n";
    echo bin2hex($raw_bytes) . "\n\n";

    // C. Deteksi Ekstensi & Panjang Nonce
    $ext = function_exists('sodium_crypto_stream_chacha20_xor') ? 'Sodium' : 'OpenSSL';
    $nonce_len = ($ext === 'Sodium') ? 8 : 16;
    echo "<b>C. EKSTENSI CRYPTO & PANJANG NONCE:</b>\n";
    echo "Ekstensi yang aktif : " . $ext . "\n";
    echo "Panjang Nonce       : " . $nonce_len . " bytes\n\n";

    // D. Potong dan tampilkan NONCE
    $nonce = substr($raw_bytes, 0, $nonce_len);
    echo "<b>D. NONCE (HEXADECIMAL) [ " . $nonce_len . " bytes ]:</b>\n";
    echo bin2hex($nonce) . "\n\n";

    // E. Potong dan tampilkan CIPHERTEXT
    $ciphertext = substr($raw_bytes, $nonce_len);
    echo "<b>E. CIPHERTEXT (HEXADECIMAL):</b>\n";
    echo bin2hex($ciphertext) . "\n\n";

    // F. Master Key hash (Key yang sebenarnya dipakai untuk ChaCha20)
    $master_key = hash('sha256', MASTER_SECRET, true);
    echo "<b>F. MASTER KEY (SHA-256 dari MASTER_SECRET) (HEXADECIMAL):</b>\n";
    echo bin2hex($master_key) . "\n\n";

    // G. Dekripsi IP Asli
    $ip_asli = SecurityHelper::decryptIP($base64_enc);
    echo "<b>G. HASIL AKHIR DEKRIPSI (IP ASLI PLAINTEXT):</b>\n";
    echo "IP Address : <span style='color: #ffaa00; font-weight: bold;'>" . htmlspecialchars($ip_asli) . "</span>\n";

} catch (Exception $e) {
    echo "Terjadi Error: " . htmlspecialchars($e->getMessage());
}

echo "</pre>";
?>
