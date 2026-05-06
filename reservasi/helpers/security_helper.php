<?php
require_once __DIR__ . '/../config/config.php';

class SecurityHelper {
    
    /**
     * Tier 1: Master Key (Static)
     * Digunakan khusus untuk membungkus IP Address
     */
    private static function getMasterKey() {
        return hash('sha256', MASTER_SECRET, true);
    }

    /**
     * Tier 2: Dynamic Data Key (On-the-fly)
     * Dihasilkan dari kombinasi IP + APP_SECRET
     */
    public static function getDataKey($ip_address) {
        return hash('sha256', $ip_address . MASTER_SECRET, true);
    }

    /**
     * Jalur 1: Enkripsi Data Pelanggan (Menggunakan Data Key Dinamis)
     */
    public static function encryptData($plaintext, $ip_address) {
        if (empty($plaintext)) return "";
        $key = self::getDataKey($ip_address);
        return self::coreEncrypt($plaintext, $key);
    }

    public static function decryptData($ciphertext, $ip_address) {
        if (empty($ciphertext)) return "";
        $key = self::getDataKey($ip_address);
        return self::coreDecrypt($ciphertext, $key);
    }

    /**
     * Jalur 2: Enkripsi IP Seed (Menggunakan Master Key Statis)
     */
    public static function encryptIP($ip_address) {
        if (empty($ip_address)) return "";
        $key = self::getMasterKey();
        return self::coreEncrypt($ip_address, $key);
    }

    public static function decryptIP($encrypted_ip) {
        if (empty($encrypted_ip)) return "";
        $key = self::getMasterKey();
        return self::coreDecrypt($encrypted_ip, $key);
    }

    /**
     * Core ChaCha20 Engine
     */
    private static function coreEncrypt($plaintext, $key) {
        if (function_exists('sodium_crypto_stream_chacha20_xor')) {
            $nonce = random_bytes(8);
            $ciphertext = sodium_crypto_stream_chacha20_xor($plaintext, $nonce, $key);
        } else {
            $nonce = random_bytes(16);
            $ciphertext = openssl_encrypt($plaintext, 'chacha20', $key, OPENSSL_RAW_DATA, $nonce);
        }
        return base64_encode($nonce . $ciphertext);
    }

    private static function coreDecrypt($base64_data, $key) {
        $decoded = base64_decode($base64_data);
        if (function_exists('sodium_crypto_stream_chacha20_xor')) {
            $nonce_len = 8;
            if (strlen($decoded) < $nonce_len) return "";
            $nonce = substr($decoded, 0, $nonce_len);
            $ciphertext = substr($decoded, $nonce_len);
            return (string)sodium_crypto_stream_chacha20_xor($ciphertext, $nonce, $key);
        } else {
            $nonce_len = 16;
            if (strlen($decoded) < $nonce_len) return "";
            $nonce = substr($decoded, 0, $nonce_len);
            $ciphertext = substr($decoded, $nonce_len);
            return (string)openssl_decrypt($ciphertext, 'chacha20', $key, OPENSSL_RAW_DATA, $nonce);
        }
    }

    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * DYNAMIC IPv4 CAPTURE
     * Mendeteksi IPv4 asli dari client/user secara real-time
     */
    public static function getUserIP() {
        $ip = '127.0.0.1'; // Fallback
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Bisa berupa list IP (proxy), ambil yang pertama
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ip_list[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Konversi IPv6 loopback ke IPv4
        if ($ip === '::1') $ip = '127.0.0.1';
        
        // Validasi IPv4 (Hanya mengizinkan format IPv4 sesuai instruksi dosen)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
        
        // Jika bukan IPv4 valid (misal IPv6 lain), fallback ke 127.0.0.1 atau biarkan apa adanya?
        // Instruksi minta prioritas IPv4.
        return $ip; 
    }
}
?>
