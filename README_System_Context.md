# Ringkasan Sistem: DKJ Reservasi Penginapan Sederhana (Secured with ChaCha20 & Key Escrow)

Dokumen ini berisi konteks lengkap mengenai arsitektur keamanan, logika bisnis, dan pembaruan terakhir dari sistem "DKJ Reservasi". Dokumen ini dapat diberikan kepada AI (Gemini) di masa depan agar AI langsung memahami struktur dan riwayat pekerjaan proyek ini.

## 1. Identitas Proyek
- **Nama Proyek:** Sistem Manajemen Reservasi Penginapan Sederhana
- **Fokus Utama:** Implementasi Kriptografi **ChaCha20** (Stream Cipher) dengan arsitektur **Two-Tier Key Escrow** berbasis *Dynamic IP Binding*.
- **Tech Stack:** PHP (Native/Procedural), SQLite3 (Database), Tailwind CSS & Lucide Icons (UI/UX).

## 2. Arsitektur Keamanan (Two-Tier Key System)
Sistem ini dirancang untuk melindungi privasi data pelanggan (Nomor HP, Email, Alamat) dengan enkripsi berlapis:
- **Tier 1 (Master Key):** Sebuah *Static Key* yang di-hash menggunakan SHA-256 dari `MASTER_SECRET` (disimpan di `config.php`). Master Key ini HANYA digunakan untuk mengenkripsi IP Address pelanggan saat mereka melakukan reservasi, lalu menyimpannya ke database di kolom `encrypted_ip_seed`.
- **Tier 2 (Data Key):** Sebuah *Dynamic Key* yang dihasilkan secara real-time (gabungan dari IP Address asli jaringan pengguna + `MASTER_SECRET`). Data Key ini digunakan untuk mengenkripsi data sensitif pelanggan (`phone`, `email`, `address`).

**Alur Kerja (Workflow):**
- **Saat Insert/Simpan:** Sistem menangkap IP pengguna `getUserIP()`, membuat Data Key dari IP tersebut untuk mengenkripsi profil pelanggan, lalu mengenkripsi IP itu sendiri dengan Master Key, dan menyimpan semuanya ke SQLite.
- **Saat Laporan/Dekripsi:** Sistem membaca `encrypted_ip_seed` dari SQLite, mendekripsinya dengan Master Key untuk mendapatkan IP historis, merakit kembali Data Key dari IP tersebut, lalu mendekripsi data profil pelanggan untuk ditampilkan di layar.
- **Pemisahan Data:** Data administratif seperti Tanggal Check-in/out, Catatan (Notes), dan Nominal Pembayaran (Amount) dibiarkan **Plaintext** agar tidak mengganggu kecepatan dan logika sistem pelaporan hotel.

## 3. Struktur Modul Utama
- `config/config.php`: Konfigurasi database dan penetapan `MASTER_SECRET`.
- `helpers/security_helper.php`: Otak dari seluruh algoritma kriptografi (Generate Key, Encrypt/Decrypt ChaCha20 via Sodium/OpenSSL, dan penangkap IP Dinamis).
- `database/init_db.php`: Skrip inisialisasi tabel SQLite (terdapat tabel `users`, `rooms`, `reservations`, `payments`).
- `modules/reservations.php`: Modul CRUD reservasi (melakukan enkripsi saat data ditambahkan).
- `modules/reports.php`: Modul laporan dan Export CSV (melakukan dekripsi on-the-fly untuk laporan).

## 4. Fitur Edukasi & Audit (Pembaruan Terakhir)
Untuk mendemonstrasikan cara kerja algoritma kepada dosen/penguji, telah dibangun dua alat audit khusus:
1. **Demo Enkripsi (`demo.php`):** Halaman interaktif untuk mengetikkan teks dan mengubahnya menjadi Ciphertext. Fitur ini telah diperbarui agar menampilkan **Pemecahan Kriptografi** (Byte-packing breakdown), memperlihatkan *Nonce*, *Ciphertext* mentah, dan *Data Key* secara live. Ini juga membuktikan bahwa memasukkan data yang salah ke proses dekripsi Stream Cipher akan menghasilkan karakter acak (*garbled text*).
2. **Audit Kriptografi (`audit_crypto.php`):** Halaman khusus di sidebar yang mengambil data reservasi langsung dari database dan membedah *Base64* dari `encrypted_ip_seed`. Halaman ini mendeteksi ekstensi kripto (Sodium/OpenSSL), memisahkan *Nonce* (8 atau 16 bytes), membedah *Ciphertext*, memperlihatkan *Master Key* dalam format Hexadecimal, dan mengeksekusi hasil dekripsi akhir.
3. **Database Browser (Raw SQL):** Memperlihatkan secara transparan bagaimana data di tabel `reservations` disimpan (membedakan mana kolom Plaintext dan mana kolom Ciphertext).

## 5. Penyesuaian Antarmuka (UI/UX) Terakhir
- Melakukan peningkatan skalabilitas pembacaan (*Readability Improvement*) pada modul Laporan dan Reservasi.
- Mengubah teks yang ukurannya terlalu kecil (`10px` / `text-[10px]`) menjadi ukuran standar (`text-sm` / `text-xs`).
- Meningkatkan kontras warna teks dari abu-abu pudar (`text-gray-400`) menjadi abu-abu gelap/hitam (`text-gray-800` / `text-gray-600`) agar data terdekripsi lebih jelas dilihat oleh pengguna.
