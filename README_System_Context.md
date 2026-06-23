# Sistem Manajemen Reservasi Penginapan Sederhana
**Dokumen Konteks Sistem (System Context & Architecture)**

Dokumen ini ditulis secara komprehensif agar AI (seperti Gemini) atau pengembang lanjutan dapat memahami arsitektur, alur logika, dan skema keamanan yang telah diterapkan pada sistem "DKJ Reservasi Penginapan Sederhana". Fokus utama dari penjelasan di bawah ini adalah pada **Sistem Reservasi** dan **Kriptografi Hybrid (ChaCha20 + Key Escrow)**.

---

## 1. Identitas & Teknologi Proyek
- **Nama Proyek:** Sistem Manajemen Reservasi Penginapan Sederhana
- **Tech Stack:** PHP (Native/Procedural), SQLite3 (Database lokal), Tailwind CSS & Lucide Icons (UI/UX).
- **Fokus Keamanan:** Implementasi **ChaCha20** (Stream Cipher) dengan **Two-Tier Key Escrow** berbasis *Dynamic IP Binding*.

---

## 2. Arsitektur Keamanan (Two-Tier Key System)
Sistem ini menggunakan teknik enkripsi *stream cipher* (ChaCha20) untuk mengamankan data sensitif pengguna dengan dua lapis kunci (Tier 1 dan Tier 2).

### Konsep Kunci (Keys)
1. **Tier 1 (Master Key):** 
   - Bersifat statis.
   - Dihasilkan dari proses hashing `SHA-256` terhadap `MASTER_SECRET` (didefinisikan di `config.php`).
   - **Tujuan:** *Hanya* digunakan untuk mengenkripsi IP Address pengguna (disimpan dalam `encrypted_ip_seed`).
   
2. **Tier 2 (Data Key):**
   - Bersifat dinamis (berubah sesuai dengan IP pengguna saat melakukan reservasi).
   - Dihasilkan secara real-time dari kombinasi IP Address pengguna (`getUserIP()`) + `MASTER_SECRET` yang kemudian di-hash dengan `SHA-256`.
   - **Tujuan:** Digunakan untuk mengenkripsi dan mendekripsi data sensitif profil pelanggan (seperti `phone`, `email`, dan `address`).

### Engine Kriptografi (`SecurityHelper` di `helpers/security_helper.php`)
- **Core Engine:** Menggunakan `sodium_crypto_stream_chacha20_xor` dengan *Nonce* 8 byte. Jika library Sodium tidak tersedia, sistem otomatis *fallback* menggunakan `openssl_encrypt/decrypt` (`chacha20`) dengan *Nonce* 16 byte.
- Format penyimpanan data dalam database adalah **Base64** dari gabungan `Nonce + Ciphertext` agar aman saat disimpan dalam kolom `TEXT` di SQLite.
- **Dynamic IPv4 Capture:** Sistem menangkap IP pengguna secara hierarkis (mengutamakan `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, lalu `REMOTE_ADDR`), melakukan fallback jika berupa IPv6 `::1` ke `127.0.0.1`, dan memvalidasinya secara ketat sebagai IPv4.

---

## 3. Alur Kerja Reservasi (Reservation Workflow)
Modul utama berada di `modules/reservations.php`. Berikut adalah alur mendetail dari proses penambahan, penyimpanan, dan perpanjangan (extend) reservasi.

### A. Proses Insert (Tambah Reservasi Baru)
1. **Capture Input:** Sistem menerima data pelanggan (`customer_name`, `phone`, `email`, `address`), detail pemesanan (`room_id`, `check_in`, `check_out`), dan `notes` dari form modal.
2. **Key Generation & Encryption:** 
   - Menangkap IP saat ini (`getUserIP()`).
   - `phone`, `email`, dan `address` dienkripsi dengan **Data Key** (Tier 2).
   - IP pengguna itu sendiri dienkripsi dengan **Master Key** (Tier 1) menjadi `encrypted_ip_seed`.
3. **Pemisahan Plaintext & Ciphertext:**
   - Kolom kriptografi (Ciphertext): `phone`, `email`, `address`, `encrypted_ip_seed`.
   - Kolom operasional (Plaintext): `customer_name`, `check_in`, `check_out`, `notes`, `room_id`, `status`, `amount`. *Catatan:* `notes` sengaja dibiarkan plaintext untuk mempercepat pencarian/indexing operasional.
4. **Validasi & Kalkulasi Logika Bisnis:**
   - **Validasi Tanggal:** `check_out` harus lebih besar dari `check_in`, dan `check_in` tidak boleh lebih kecil dari hari ini.
   - **Cek Overlap (Double Booking):** Melakukan query SQLite untuk memastikan tidak ada reservasi berstatus 'Pending' atau 'Active' di rentang `check_in` hingga `check_out` pada `room_id` yang sama.
   - **Kalkulasi Biaya (`amount`):** `(Durasi malam) x (Harga kamar dari tabel rooms)`. Jika durasi 0, otomatis dianggap 1 malam.
   - **Penentuan Status:** Jika `check_out` < hari ini: `Completed`. Jika rentang hari ini masuk ke dalam check-in & check-out: `Active`. Lainnya: `Pending`.
5. **Database Execution:** Insert ke tabel `reservations`, lalu update `status` di tabel `rooms` menjadi `booked`.

### B. Proses Pembacaan & Laporan (Read & Strict Location-Based Access)
Saat halaman laporan dimuat (`modules/reports.php`):
1. Data ditarik dari database.
2. Sistem mengekstrak dan mendekripsi `encrypted_ip_seed` menggunakan **Master Key** (Tier 1) untuk mendapatkan IP historis saat reservasi dibuat.
3. **STRICT LOCATION-BASED ACCESS (Aturan Dosen):** Sistem membandingkan IP Historis dengan IP Administrator Saat Ini (Current IP).
   - **Jika SAMA:** IP historis digunakan untuk merekonstruksi **Data Key** (Tier 2). `phone`, `email`, dan `address` didekripsi dengan Data Key tersebut dan ditampilkan.
   - **Jika BEDA:** Data profil tidak bisa didekripsi, dibiarkan dalam wujud Base64, dan sistem menampilkan label peringatan `[Locked: IP Mismatch]`.

### C. Dilema Hosting & Solusi "Demo Mode"
Karena aplikasi ini di-hosting dengan IP Publik yang dinamis, logika *Strict Access* di atas bisa mengunci data secara permanen setiap kali IP berubah. 
**Solusi:** Disediakan fitur **"Demo Mode Toggle"** menggunakan PHP Session.
- Jika ON: Sistem akan memanipulasi *Current IP* menjadi `192.168.10.10` (mensimulasikan IP LAN statis/whitelisted layaknya jaringan internal hotel).
- Jika OFF: Sistem membaca IP dinamis asli dari ISP pengguna.

### C. Proses Update/Extend (Perpanjangan Reservasi)
1. **Validasi Extend:** `check_out` baru harus lebih besar dari `check_out` sebelumnya. Data `check_in` tidak diambil dari input form, melainkan dari data *existing* di database untuk mencegah manipulasi form.
2. **Cek Overlap Khusus Extend:** Memastikan rentang tanggal perpanjangan tidak bentrok dengan booking tamu lain pada kamar yang sama (mengecualikan ID reservasi yang sedang di-extend).
3. **Kalkulasi Ulang Amount:** Menghitung kembali total biaya berdasarkan `check_in` asli ke `check_out` yang baru.
4. **Proteksi Kriptografi Mutlak (Immutability):** Pada saat melakukan *update* atau perpanjangan, kolom terenkripsi (`phone`, `email`, `address`, dan `encrypted_ip_seed`) **SAMA SEKALI TIDAK BOLEH DISENTUH ATAU DIUPDATE**. Hal ini sangat krusial untuk menjaga integritas enkripsi ChaCha20 agar gembok tidak rusak. Yang diupdate HANYA `check_out`, `notes` (ditambahkan flag `[EXTENDED]`), `status`, dan `amount` secara *plaintext*.

### D. Proses Delete (Hapus Reservasi)
- Sistem mengambil `room_id` dari reservasi.
- Mengubah status kamar kembali menjadi `available`.
- Menghapus baris reservasi terkait dari database.

---

## 4. Struktur Database Lengkap (SQLite)
Semua tabel diinisialisasi melalui `database/init_db.php`.
* **`rooms`**: `id`, `room_number`, `type`, `price`, `status`.
* **`users`**: `id`, `username`, `password`, `role`.
* **`payments`**: `id`, `reservation_id`, `amount_paid`, `payment_date`, `method`.
* **`reservations`**: 
  - `id`, `customer_name`, `room_id`, `check_in`, `check_out`, `status`, `notes`, `amount` *(Plaintext)*
  - `phone`, `email`, `address`, `encrypted_ip_seed` *(Ciphertext - Base64)*

---

## 5. Fitur Edukasi & Audit Tambahan
Sistem ini dibuat untuk keperluan demonstrasi/akademis, sehingga dilengkapi:
1. **Demo Enkripsi (`modules/demo.php`):** Halaman interaktif yang memperlihatkan visualisasi cara kerja ChaCha20, menampilkan pemecahan *byte-packing*, Nonce, Data Key, dan hasil ciphertext mentah secara live.
2. **Audit Kriptografi (`modules/audit_crypto.php`):** Alat pembedah database yang membaca langsung row data, mengekstrak ekstensi yang dipakai (Sodium/OpenSSL), panjang Nonce, dan mendemonstrasikan dekripsi step-by-step.

*(Catatan: Modul dan UI telah menggunakan Tailwind CSS dengan struktur file per modul yang di-include dari `index.php`)*
