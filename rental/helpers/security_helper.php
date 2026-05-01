<?php
require_once __DIR__ . '/../config/config.php';

class SecurityHelper {
    
    /**
     * Generate 256-bit Key (Server-Side Consistent)
     * Menggunakan Server Seed + Master Secret agar kunci stabil
     * di perangkat mana pun saat data dibuka.
     */
    public static function generateKey() {
        // Kunci dibangun dari identitas Jaringan Server (Stable)
        $seed = SERVER_SEED . MASTER_SECRET;
        return hash('sha256', $seed, true); 
    }

    /**
     * Encrypt Data using ChaCha20 (Sodium or OpenSSL Fallback)
     */
    public static function encrypt($plaintext) {
        if (empty($plaintext)) return "";
        
        $key = self::generateKey();

        if (function_exists('sodium_crypto_stream_chacha20_xor')) {
            $nonce_len = 8;
            $nonce = random_bytes($nonce_len);
            $ciphertext = sodium_crypto_stream_chacha20_xor($plaintext, $nonce, $key);
        } else {
            // Fallback to OpenSSL - Use 16 bytes IV to satisfy some OpenSSL versions
            $nonce_len = 16;
            $nonce = random_bytes($nonce_len);
            $ciphertext = openssl_encrypt($plaintext, 'chacha20', $key, OPENSSL_RAW_DATA, $nonce);
        }
        
        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Decrypt Data using ChaCha20
     */
    public static function decrypt($base64_data) {
        if (empty($base64_data)) return "";
        
        $decoded = base64_decode($base64_data);
        $key = self::generateKey();

        if (function_exists('sodium_crypto_stream_chacha20_xor')) {
            $nonce_len = 8;
            if (strlen($decoded) < $nonce_len) return "[Error]";
            $nonce = substr($decoded, 0, $nonce_len);
            $ciphertext = substr($decoded, $nonce_len);
            return sodium_crypto_stream_chacha20_xor($ciphertext, $nonce, $key);
        } else {
            $nonce_len = 16;
            if (strlen($decoded) < $nonce_len) return "[Error]";
            $nonce = substr($decoded, 0, $nonce_len);
            $ciphertext = substr($decoded, $nonce_len);
            return openssl_decrypt($ciphertext, 'chacha20', $key, OPENSSL_RAW_DATA, $nonce);
        }
    }

    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>
