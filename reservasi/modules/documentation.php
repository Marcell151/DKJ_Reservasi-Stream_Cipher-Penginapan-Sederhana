<style>
/* CSS Reset Khusus untuk Layar vs Print */
@media screen {
    .print-only { display: none !important; }
}

@media print {
    /* 1. Sembunyikan elemen bawaan Dashboard Admin (Sidebar, Header, dll) */
    .screen-only, 
    .sidebar, 
    header, 
    .flash-message,
    button.no-print { 
        display: none !important; 
    }
    
    /* 2. Reset struktur layout utama agar PDF penuh 100% */
    html, body, .main-wrapper, .content-body {
        background: white !important;
        color: black !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        position: static !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* Pengaturan Kertas Resmi (Laporan KTI / Thesis) */
    @page {
        size: A4;
        margin: 3cm 2.5cm 2.5cm 3cm; /* Margin formal: Kiri 3, Kanan 2.5, Atas 3, Bawah 2.5 */
    }
    
    .print-only { 
        display: block !important; 
        font-family: "Times New Roman", Times, serif !important;
        font-size: 12pt !important;
        line-height: 1.5 !important;
    }

    /* MENCEGAH TEKS TERPOTONG DI TENGAH HALAMAN */
    .print-only p, 
    .print-only li, 
    .print-only tr, 
    .print-only .workflow-box {
        page-break-inside: avoid !important;
    }

    .print-only p {
        text-align: justify !important;
        text-indent: 1.25cm;
        margin-bottom: 12pt;
    }

    .print-only h1 { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 24pt; text-transform: uppercase; page-break-after: avoid; margin-top: 1cm; }
    .print-only h2 { font-size: 12pt; font-weight: bold; margin-top: 24pt; margin-bottom: 12pt; text-transform: uppercase; page-break-after: avoid; }
    .print-only h3 { font-size: 12pt; font-weight: bold; margin-top: 12pt; margin-bottom: 6pt; page-break-after: avoid; }
    
    .print-only ul, .print-only ol {
        margin-left: 1.25cm;
        margin-bottom: 12pt;
        text-align: justify;
    }
    
    .print-only li {
        margin-bottom: 6pt;
    }

    .print-only .cover-page {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 90vh; /* Agar tidak tumpah ke halaman 2 */
        text-align: center;
        page-break-after: always;
        padding-top: 5cm;
    }

    .print-only .cover-page h1 { font-size: 16pt; margin-bottom: 1.5cm; }
    .print-only .cover-page p { text-align: center; text-indent: 0; }

    .page-break { page-break-before: always; }

    .print-only table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
        font-size: 11pt;
    }
    .print-only th, .print-only td {
        border: 1px solid black;
        padding: 6pt;
        text-align: left;
        vertical-align: top;
    }
    .print-only th { font-weight: bold; text-align: center; background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }

    /* Sembunyikan URL/Tanggal bawaan browser sebisa mungkin */
    @page { margin-top: 0; margin-bottom: 0; }
    body { padding-top: 3cm; padding-bottom: 2.5cm; }
}
</style>

<!-- ========================================== -->
<!-- 1. TAMPILAN WEB (DASHBOARD SCREEN ONLY)    -->
<!-- ========================================== -->
<div class="screen-only card p-8 bg-white rounded-2xl shadow-sm border border-gray-100 max-w-6xl mx-auto mb-10">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-6 border-b border-gray-100 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Dokumen Teknikal & Arsitektur Keamanan</h1>
            <p class="text-sm text-gray-500 mt-2 font-medium">Spesifikasi Sistem Manajemen Reservasi Penginapan Sederhana (V2.0)</p>
        </div>
        <button onclick="window.print()" class="py-3 px-6 bg-[#1a237e] hover:bg-indigo-800 text-white font-bold rounded-xl flex items-center gap-2 transition-all shadow-md shrink-0">
            <i data-lucide="printer" class="w-5 h-5"></i> Export PDF Resmi
        </button>
    </div>

    <div class="space-y-12 text-gray-700 leading-relaxed">
        
        <!-- BAB I -->
        <section>
            <h2 class="text-xl font-bold text-indigo-700 mb-6 flex items-center gap-2 border-l-4 border-indigo-600 pl-3">
                <i data-lucide="shield" class="w-6 h-6 text-indigo-500"></i> BAB I: Pendahuluan & Keunggulan Sistem
            </h2>
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 space-y-4 shadow-sm">
                <h3 class="font-bold text-gray-800 text-lg">1.1 Ikhtisar Produk (Product Overview)</h3>
                <p class="text-sm text-gray-600 text-justify">Sistem ini merupakan purwarupa perangkat lunak berbasis web untuk manajemen reservasi penginapan yang dirancang khusus memenuhi standar privasi tingkat tinggi. Di tengah rentannya kebocoran data pribadi pada sistem perhotelan, aplikasi ini mengimplementasikan algoritma modern <strong>Stream Cipher ChaCha20</strong> secara penuh.</p>
                <p class="text-sm text-gray-600 text-justify">Sistem tidak menggunakan framework berat (seperti Laravel/Dash). Keputusan arsitektural ini diambil dengan sengaja agar sistem beroperasi dengan performa maksimal (low overhead) melalui PHP Native. Tanpa framework, fungsi-fungsi enkripsi tingkat rendah (low-level cryptography) dapat dieksekusi dengan kalkulasi presisi tinggi dan terhindar dari pemborosan memori (redundancy).</p>

                <h3 class="font-bold text-gray-800 text-lg mt-6">1.2 Keunggulan Komparatif (Competitive Advantage)</h3>
                <p class="text-sm text-gray-600 text-justify">Keunggulan absolut sistem ini terletak pada mekanisme <strong>Strict Location-Based Access Validation</strong>. Tidak sekadar mengubah teks menjadi acak, sistem mengikat erat kunci dekripsi tersebut ke Alamat fisik jaringan (IP Address) pengguna secara <em>real-time</em> saat transaksi dilakukan.</p>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-md mt-2">
                    <strong class="text-red-800 text-sm">Skenario Keamanan (Database Breach):</strong>
                    <p class="text-sm text-red-700 mt-1">Jika terjadi pencurian basis data (hacked/leaked), seluruh informasi sensitif tamu akan tetap aman. Peretas yang mencoba mendekripsi dari jaringan mereka (luar jaringan hotel) akan gagal total akibat penolakan kunci (IP Mismatch). Seluruh profil krusial tamu tetap berwujud <em>Ciphertext Base64</em> yang tidak bisa didekripsi selamanya oleh pihak luar.</p>
                </div>
            </div>
        </section>

        <!-- BAB II -->
        <section>
            <h2 class="text-xl font-bold text-indigo-700 mb-6 flex items-center gap-2 border-l-4 border-indigo-600 pl-3">
                <i data-lucide="layers" class="w-6 h-6 text-indigo-500"></i> BAB II: Arsitektur Teknologi & Antarmuka Pengguna
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Stack Kiri -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h3 class="font-bold text-gray-800 text-lg mb-3 flex items-center gap-2"><i data-lucide="cpu" class="w-4 h-4 text-emerald-500"></i> Backend & Database</h3>
                    <ul class="text-sm text-gray-600 space-y-3">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0"></i>
                            <span><strong>PHP 8.x Native:</strong> Prosedural murni untuk kemudahan audit alur kriptografi tanpa kerumitan OOP MVC yang memberatkan.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0"></i>
                            <span><strong>SQLite3:</strong> Database serverless portabel. Data ditampung dalam satu file (`database.sqlite`), bebas repot instalasi MySQL.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0"></i>
                            <span><strong>Libsodium & OpenSSL:</strong> Eksekusi `sodium_crypto_stream_chacha20_xor` dengan fallback otomatis ke OpenSSL untuk kompatibilitas cloud.</span>
                        </li>
                    </ul>
                </div>

                <!-- Stack Kanan -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h3 class="font-bold text-gray-800 text-lg mb-3 flex items-center gap-2"><i data-lucide="layout" class="w-4 h-4 text-blue-500"></i> Frontend (UI/UX)</h3>
                    <ul class="text-sm text-gray-600 space-y-3">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-blue-500 mt-0.5 shrink-0"></i>
                            <span><strong>Tailwind CSS (Utility-first):</strong> Menjamin antarmuka elegan, modern, dan sangat responsif tanpa CSS kustom raksasa. Di-load via CDN untuk kelancaran.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-blue-500 mt-0.5 shrink-0"></i>
                            <span><strong>Vanilla JavaScript:</strong> Komputasi hari (Check-in/Out) dan validasi modal di-handle murni via JS tanpa React/Vue, mencegah celah XSS.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-blue-500 mt-0.5 shrink-0"></i>
                            <span><strong>Lucide Icons:</strong> Render ikon ringan untuk memberi petunjuk visual intuitif (contoh: ikon gembok pada data terenkripsi).</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- BAB III -->
        <section>
            <h2 class="text-xl font-bold text-indigo-700 mb-6 flex items-center gap-2 border-l-4 border-indigo-600 pl-3">
                <i data-lucide="server" class="w-6 h-6 text-indigo-500"></i> BAB III: Distribusi Server & Solusi Anomali IP
            </h2>
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-800 text-lg">3.1 Hosting cPanel & Zero Configuration</h3>
                <p class="text-sm text-gray-600 text-justify">Dalam ekosistem produksi awan (cloud), administrator cukup mengunggah seluruh arsip ke folder <code>public_html</code> pada layanan cPanel konvensional. Berkas pangkalan data SQLite otomatis di-<i>generate</i> pada proses muat pertama tanpa pengaturan PHPMyAdmin.</p>
                
                <h3 class="font-bold text-gray-800 text-lg mt-6">3.2 Mengatasi Anomali IP Publik Dinamis (Demo Mode)</h3>
                <p class="text-sm text-gray-600 text-justify">Masalah terbesar cloud hosting di Indonesia adalah <strong>IP Dinamis dari ISP</strong>. Setiap kali router hotel di-restart, IP berubah. Sistem pertahanan ChaCha20 di aplikasi ini akan membaca perubahan IP ini sebagai "serangan peretas", dan akan <strong>mengunci seluruh laporan profil tamu secara otomatis</strong>.</p>
                <p class="text-sm text-gray-600 text-justify">Untuk keperluan presentasi tugas akhir/akademis tanpa gangguan, sistem diakali dengan fitur <strong>"Demo Mode (Static IP)"</strong>. Saat *toggle switch* di atas diklik menjadi ON, sistem akan mematikan pembacaan IP eksternal dan memaksa Session server menggunakan *dummy IP* statis jaringan LAN, yakni <code>192.168.10.10</code>. Dengan ini, dosen penguji dapat memvalidasi jalannya fungsi dekripsi tanpa hambatan pergeseran IP jaringan Wi-Fi kampus/rumah.</p>
            </div>
        </section>

        <!-- BAB IV -->
        <section>
            <h2 class="text-xl font-bold text-indigo-700 mb-6 flex items-center gap-2 border-l-4 border-indigo-600 pl-3">
                <i data-lucide="database" class="w-6 h-6 text-indigo-500"></i> BAB IV: Skema Pemisahan Database & Logika Immutabilitas
            </h2>
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="font-bold text-gray-800 text-lg mb-4">4.1 Tabel Utama `reservations`</h3>
                <p class="text-sm text-gray-600 text-justify mb-4">Sistem menyeimbangkan performa komputasi dan keamanan dengan membelah fisik atribut tabel. Data logistik operasional berformat Plaintext, sedangkan data privasi berformat Ciphertext.</p>
                
                <div class="overflow-x-auto rounded-lg border border-gray-200 mb-6">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th class="p-4 border-b text-indigo-900 font-bold">Entitas Kolom</th>
                                <th class="p-4 border-b text-indigo-900 font-bold">Sifat Data</th>
                                <th class="p-4 border-b text-indigo-900 font-bold">Rasionalisasi Kriptografi & Bisnis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-4 font-mono text-gray-800">id, room_id</td>
                                <td class="p-4"><span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold">Plaintext</span></td>
                                <td class="p-4 text-gray-600">Primary Key dan relasi Foreign Key ke kamar.</td>
                            </tr>
                            <tr class="border-b bg-red-50 hover:bg-red-100/50">
                                <td class="p-4 font-mono text-red-900">phone, email, address</td>
                                <td class="p-4"><span class="bg-red-200 text-red-800 px-3 py-1 rounded-full text-xs font-bold">Ciphertext (Base64)</span></td>
                                <td class="p-4 text-red-700">Profil privasi tamu. Terkunci penuh oleh <strong>Tier 2 Data Key</strong>.</td>
                            </tr>
                            <tr class="border-b bg-orange-50 hover:bg-orange-100/50">
                                <td class="p-4 font-mono text-orange-900">encrypted_ip_seed</td>
                                <td class="p-4"><span class="bg-orange-200 text-orange-800 px-3 py-1 rounded-full text-xs font-bold">Ciphertext (Base64)</span></td>
                                <td class="p-4 text-orange-700">IP perekam historis jaringan tamu. Terkunci kuat oleh <strong>Tier 1 Master Key</strong>.</td>
                            </tr>
                            <tr class="hover:bg-emerald-50">
                                <td class="p-4 font-mono text-emerald-900">check_in, check_out, amount</td>
                                <td class="p-4"><span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold">Plaintext</span></td>
                                <td class="p-4 text-emerald-700">Variabel dibiarkan telanjang untuk kelancaran query laporan keuangan dan cek double booking.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="font-bold text-gray-800 text-lg mb-2">4.2 Logika Immutabilitas Perpanjangan Sewa (Extend)</h3>
                <p class="text-sm text-gray-600 text-justify mb-4">Pada skenario perpanjangan menginap, aturan arsitektur mutlak berbunyi: <strong>Dilarang keras menyentuh kolom Ciphertext!</strong> Melakukan <code>UPDATE</code> pada kolom enkripsi tanpa kalibrasi <em>Nonce</em> akan mematahkan gembok Stream Cipher permanen. Kode reservasi hanya mengizinkan pembaruan kolom plaintext `check_out`, `amount`, status, dan mem-<i>flag</i> catatan dengan teks `[EXTENDED]`.</p>

                <h3 class="font-bold text-gray-800 text-lg mb-2">4.3 Alat Audit Forensik Kripto (Integrasi Sistem)</h3>
                <p class="text-sm text-gray-600 text-justify">Sistem memiliki lab kriptografi sendiri di sidebar admin. Menu <strong>"Audit Kriptografi"</strong> mendemonstrasikan secara *live* bagaimana sistem PHP membaca database mentah, mengekstrasi ekstensi (*Sodium/OpenSSL*), membongkar panjang 16-byte Nonce, membedah Master Key, dan menembak Ciphertext. Di sisi lain, menu <strong>"Demo Enkripsi"</strong> mengajarkan bagaimana Stream Cipher mengacak karakter berdasarkan *keystream* secara kasat mata.</p>
            </div>
        </section>

        <!-- Web: Diagram Workflow -->
        <section class="bg-gray-50 p-6 rounded-xl border border-gray-100 shadow-sm">
            <h2 class="text-xl font-bold text-indigo-700 mb-6 flex items-center gap-2 border-l-4 border-indigo-600 pl-3">
                <i data-lucide="git-merge" class="w-6 h-6 text-indigo-500"></i> BAB V: Visualisasi Alur Dekripsi Laporan
            </h2>
            <p class="text-sm text-gray-600 mb-6 text-justify">Berikut diagram mekanisme dekripsi dua lapis (Two-Tier) yang secara ketat memvalidasi IP admin saat mencoba membuka daftar tamu di modul laporan.</p>
            
            <div class="bg-white p-8 rounded-xl border border-gray-200 shadow-inner flex flex-col items-center text-center">
                <h3 class="font-bold text-indigo-900 mb-6 uppercase tracking-widest text-sm bg-indigo-100 px-4 py-2 rounded-lg">Mekanisme Dekripsi Berlapis (Two-Tier Flow)</h3>
                
                <div class="w-full max-w-lg bg-gray-50 border border-gray-300 p-4 rounded-lg shadow-sm">
                    <strong class="text-gray-800 block mb-1">Tahap 1: Ekstraksi Data Kasar</strong>
                    <span class="text-sm text-gray-600">Sistem menarik data reservasi, termasuk kolom <code class="bg-gray-200 px-1 rounded">encrypted_ip_seed</code>.</span>
                </div>
                
                <div class="h-8 w-0 border-l-2 border-dashed border-indigo-300 my-2"></div>
                
                <div class="w-full max-w-lg bg-gray-50 border border-gray-300 p-4 rounded-lg shadow-sm">
                    <strong class="text-gray-800 block mb-1">Tahap 2: Pembongkaran Tier 1</strong>
                    <span class="text-sm text-gray-600">Fungsi <code>decryptIP()</code> dipanggil bersamaan dengan <i>Master Key</i> statis untuk memulihkan nilai <strong>IP Historis</strong> transaksi.</span>
                </div>
                
                <div class="h-8 w-0 border-l-2 border-dashed border-indigo-300 my-2"></div>

                <div class="w-full max-w-2xl bg-indigo-50 border-2 border-dashed border-indigo-400 p-6 rounded-xl">
                    <strong class="text-indigo-900 block mb-2 text-lg">Tahap 3: Validasi Gerbang Kritis (IP Matching)</strong>
                    <p class="text-sm text-indigo-700 mb-6">Sistem mengkomparasi: Apakah <strong>IP Historis</strong> IDENTIK dengan <strong>Current Admin IP</strong>?</p>
                    
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex-1 bg-white border border-emerald-300 p-5 rounded-lg shadow-sm">
                            <strong class="text-emerald-700 block border-b border-emerald-100 pb-2 mb-3">Kondisi A (Identik)</strong>
                            <ul class="text-sm text-left text-gray-600 space-y-2">
                                <li><i data-lucide="check" class="w-4 h-4 inline text-emerald-500"></i> Rakit <i>Data Key</i> (Tier 2).</li>
                                <li><i data-lucide="check" class="w-4 h-4 inline text-emerald-500"></i> Eksekusi <code>decryptData()</code>.</li>
                                <li><i data-lucide="check" class="w-4 h-4 inline text-emerald-500"></i> Tampilkan profil tamu secara utuh.</li>
                            </ul>
                        </div>
                        
                        <div class="flex-1 bg-white border border-red-300 p-5 rounded-lg shadow-sm">
                            <strong class="text-red-700 block border-b border-red-100 pb-2 mb-3">Kondisi B (Tidak Identik)</strong>
                            <ul class="text-sm text-left text-gray-600 space-y-2">
                                <li><i data-lucide="x" class="w-4 h-4 inline text-red-500"></i> Dekripsi DIBATALKAN.</li>
                                <li><i data-lucide="x" class="w-4 h-4 inline text-red-500"></i> Pertahankan wujud Base64.</li>
                                <li><i data-lucide="x" class="w-4 h-4 inline text-red-500"></i> Tampilkan label peringatan <i>[Locked]</i>.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

<!-- ========================================== -->
<!-- 2. TAMPILAN PRINT (FORMAL REPORT PDF ONLY) -->
<!-- ========================================== -->
<div class="print-only">
    
    <!-- Halaman Sampul Laporan -->
    <div class="cover-page">
        <h1>DOKUMEN TEKNIKAL DAN ARSITEKTUR KEAMANAN</h1>
        <h2 style="font-size: 14pt; margin-top: 0.5cm; margin-bottom: 2cm; line-height: 1.5;">SISTEM MANAJEMEN RESERVASI PENGINAPAN SEDERHANA<br>DENGAN IMPLEMENTASI STREAM CIPHER CHACHA20</h2>
        
        <div style="margin-top: 3cm; text-align: center;">
            <p style="text-align: center;">Disusun Untuk Memenuhi Persyaratan Dokumentasi Teknis<br>Sistem Berbasis Kriptografi Terapan</p>
        </div>
        
        <div style="margin-top: 3cm;">
            <p style="text-align: center; font-weight: bold;">Versi Dokumen: 2.0 (Final Release)</p>
            <p style="text-align: center;">Tahun 2026</p>
        </div>
    </div>

    <!-- Konten Dokumen Resmi -->
    <div class="page-break"></div>

    <h1>BAB I<br>PENDAHULUAN DAN KEUNGGULAN SISTEM</h1>
    
    <h2>1.1 Ikhtisar Produk (Product Overview)</h2>
    <p>Sistem ini merupakan purwarupa (prototype) perangkat lunak berbasis web untuk manajemen reservasi penginapan yang dirancang khusus untuk memenuhi standar privasi dan keamanan tingkat tinggi. Di tengah rentannya kebocoran data pribadi pada sistem informasi perhotelan konvensional, sistem ini mengimplementasikan algoritma kriptografi modern berupa Stream Cipher ChaCha20.</p>
    <p>Sistem ini tidak dirancang menggunakan kerangka kerja (framework) yang berat seperti Laravel atau CodeIgniter. Keputusan arsitektural ini diambil secara sengaja agar sistem beroperasi dengan performa tinggi dan latensi rendah (low overhead) melalui bahasa pemrograman PHP Native murni. Pendekatan tanpa framework ini meniadakan redundansi kode yang tidak diperlukan, sehingga fungsi-fungsi enkripsi tingkat rendah (low-level cryptography) dapat dieksekusi dengan kalkulasi yang sangat presisi dan efisien.</p>

    <h2>1.2 Keunggulan Komparatif (Competitive Advantage)</h2>
    <p>Sistem ini memiliki keunggulan absolut yang tidak lazim diimplementasikan pada perangkat lunak komersial skala kecil hingga menengah, yakni mekanisme pertahanan "Strict Location-Based Access Validation" berbasis enkripsi hibrida. Sistem tidak sekadar mengubah teks menjadi acak (enkripsi), tetapi secara proaktif mengikat kunci dekripsi tersebut dengan entitas fisik berupa Alamat IP (IP Address) dari jaringan lokal pengguna pada saat transaksi dilakukan.</p>
    <p>Signifikansi dari arsitektur ini sangat besar: apabila terjadi penetrasi ilegal (database breach) yang mengakibatkan peretas berhasil mengunduh keseluruhan basis data, informasi sensitif tamu tetap tidak akan terbaca. Peretas yang berupaya melakukan proses dekripsi akan selalu mengalami kegagalan, dikarenakan dekripsi dilakukan melalui Alamat IP yang berbeda dari IP orisinal tamu (IP Mismatch). Seluruh profil krusial akan tetap berwujud Ciphertext Base64 selamanya, memberikan ketenangan privasi yang absolut bagi klien penginapan.</p>

    <h1>BAB II<br>ARSITEKTUR TEKNOLOGI DAN ANTARMUKA PENGGUNA</h1>

    <h2>2.1 Spesifikasi Teknologi Utama (Technology Stack)</h2>
    <p>Sistem dirancang mengusung prinsip arsitektur hampa konfigurasi (Zero Configuration Architecture) guna memaksimalkan portabilitas aplikasi lintas server. Spesifikasi lingkungan teknis ditetapkan sebagai berikut:</p>
    <ul>
        <li>Bahasa Pemrograman Inti: PHP versi 8.x. Penulisan kode berbasis prosedural murni diaplikasikan secara terstruktur untuk mengeliminasi lapisan abstraksi berlebih, sehingga alur eksekusi algoritma kriptografi mudah diaudit.</li>
        <li>Manajemen Basis Data: SQLite3. Pilihan dijatuhkan pada SQLite3 yang merupakan sistem relasional nirserver (serverless). Seluruh rekaman data ditampung dalam satu entitas berkas portabel, meniadakan dependensi eksternal.</li>
        <li>Mesin Kriptografi Utama: Memanfaatkan pustaka Sodium (Libsodium) bawaan PHP untuk eksekusi fungsi <code>sodium_crypto_stream_chacha20_xor</code> secara natif. Sebagai lapis pengaman, sistem ini dilengkapi modul substitusi (fallback) otomatis ke pustaka OpenSSL apabila ekstensi Sodium absen di lingkungan server produksi.</li>
        <li>Manajemen Sesi Terenkripsi: Menggunakan sesi PHP standar untuk fungsionalitas login admin dan mode simulasi, tanpa mengekspos token rentan ke klien.</li>
    </ul>

    <h2>2.2 Pendekatan Desain dan Antarmuka Pengguna (UI/UX)</h2>
    <p>Antarmuka sistem (frontend) dibangun dengan mengutamakan prinsip desain monolitik yang ringan (lightweight). Alih-alih membebani peramban (browser) klien dengan kerangka kerja reaktif bertonase raksasa (layaknya React.js atau Vue.js), antarmuka dikonstruksi melalui perkawinan teknologi dasar berikut:</p>
    <ul>
        <li><strong>Tailwind CSS (Utility-first CSS):</strong> Digunakan sebagai tulang punggung arsitektur visual. Penggunaan Tailwind via CDN meniadakan keharusan menulis berkas CSS secara manual. Kelas utilitas (utility classes) yang ditanamkan langsung pada markah HTML menciptakan tampilan yang sangat konsisten, bersih, serta secara inheren bersifat responsif (mobile-friendly).</li>
        <li><strong>Vanilla JavaScript murni:</strong> Manipulasi interaksi logis sisi klien—seperti validasi konfirmasi sebelum penghapusan reservasi, hingga kalkulasi komputasi dinamis durasi waktu menginap dan biaya penyewaan ruangan—dikendalikan sepenuhnya melalui skrip fundamental. Hal ini signifikan meminimalisasi vektor serangan keamanan (attack vectors) seperti penyisipan skrip lintas situs (Cross-Site Scripting).</li>
        <li><strong>Ikonografi Komprehensif (Lucide Icons):</strong> Diterapkan secara asinkron (asynchronous loading) untuk melengkapi pengalaman pengguna (User Experience). Penggunaan palet warna dan ikon—seperti ikon gembok indikator pada peringatan enkripsi—memberikan petunjuk visual intuitif tanpa membebani ukuran memori dokumen (DOM payload).</li>
    </ul>

    <div class="page-break"></div>

    <h1>BAB III<br>DISTRIBUSI SERVER DAN PENCEGAHAN ANOMALI HOSTING</h1>

    <h2>3.1 Standarisasi Hosting Pada Lingkungan Produksi (cPanel)</h2>
    <p>Berkat pemanfaatan SQLite3, sistem ini memecahkan dilema pengaturan server yang kerap terjadi pada fase deployment. Administrator hanya perlu melakukan ekstraksi seluruh direktori arsip aplikasi (ZIP) ke dalam penampungan standar peladen awan, khususnya di direktori publik <code>public_html</code> pada layanan Hosting cPanel berdomain komersial. Sistem otomatis mendeteksi keberadaan berkas pangkalan data. Jika absen, berkas <code>database.sqlite</code> akan diciptakan dan diinisialisasi skemanya secara swadaya pada eksekusi pramuat pertama.</p>

    <h2>3.2 Strategi Penanggulangan Kendala IP Dinamis (Demo Mode)</h2>
    <p>Tantangan terbesar yang dihadapi dalam implementasi peladen awan (cloud server) adalah fluktuasi topologi jaringan. Penyedia Layanan Internet (ISP) di Indonesia, pada umumnya, mengalokasikan Alamat IP Publik yang bersifat dinamis. Ketika IP publik peladen mengalami perubahan secara periodik, mekanisme pertahanan "Location-Based Access" yang diadopsi sistem ini akan bekerja secara agresif; mendeteksi perubahan IP sebagai anomali jaringan, lalu secara otomatis mengunci seluruh pangkalan data profil tamu.</p>
    <p>Guna memfasilitasi kebutuhan presentasi, pengujian eksternal, atau simulasi akademis tanpa membongkar arsitektur dasar, sistem telah diperkuat dengan utilitas khusus bernama "Demo Mode". Fungsionalitas peralihan status (toggle) ini, apabila diaktifkan, akan mengabaikan IP perutean internet eksternal, dan menginstruksikan variabel Session peladen untuk secara konstan merujuk pada alamat proksi internal (misalnya 192.168.10.10). Teknik ini menjamin stabilitas demonstrasi kriptografi tanpa mengorbankan integritas arsitektur keamanan di lingkungan sesungguhnya.</p>

    <h1>BAB IV<br>ISOLASI BASIS DATA DAN ALIRAN KRIPTOGRAFI</h1>

    <h2>4.1 Skema Dekomposisi Variabel Basis Data (Data Isolation)</h2>
    <p>Jantung operasional aplikasi ini berada pada tabel <code>reservations</code>. Sistem mengimplementasikan pemisahan fisikal-logis secara ketat di level atribut kolom untuk menyeimbangkan performa sistem (kecepatan pencarian rekam jejak) dengan kerahasiaan tingkat tinggi.</p>
    
    <table>
        <thead>
            <tr>
                <th>Entitas Kolom Basis Data</th>
                <th>Format Leksikal Data</th>
                <th>Rasionalisasi Teknis dan Keamanan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>id, room_id</td>
                <td>Plaintext (INTEGER)</td>
                <td>Berfungsi sebagai Identitas Primer (PK) dan kunci tamu (FK) untuk menjamin relasi integritas.</td>
            </tr>
            <tr>
                <td>phone, email, address</td>
                <td>Ciphertext (Base64)</td>
                <td>Kumpulan zona privasi tamu. Data ini dieksekusi sepenuhnya oleh <strong>Tier 2 Data Key</strong> sebelum disimpan, sehingga bentuk di dalam database berupa string asimetris.</td>
            </tr>
            <tr>
                <td>encrypted_ip_seed</td>
                <td>Ciphertext (Base64)</td>
                <td>Entitas rekam jejak topologi klien. Nilai IP ini diselimuti secara independen oleh <strong>Tier 1 Master Key</strong> yang berasal dari rahasia sistem.</td>
            </tr>
            <tr>
                <td>check_in, check_out, amount, notes</td>
                <td>Plaintext (DATE/NUMERIC)</td>
                <td>Zona operasional logistik pelaporan. Dibiarkan murni untuk mendukung komputasi fungsi diferensiasi tanggal, validasi pencegahan jadwal bentrok, dan kalkulasi uang masuk.</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>4.2 Visualisasi Alur Proses Validasi Keamanan Laporan</h2>
    <p>Untuk memahami kompleksitas arsitektur Strict Location-Based Access yang terjadi saat Administrator meminta tayangan laporan, proses tersebut diabstraksikan melalui Diagram Alur Berurut (Sequence Workflow) sebagai berikut:</p>

    <!-- HTML Workflow Box untuk Print-Only (Mencegah ketergantungan pada ekstensi PDF yang tidak merender Mermaid) -->
    <div class="workflow-box" style="border: 2px solid #ccc; padding: 15pt; text-align: center; margin: 20pt 0;">
        <div style="font-weight: bold; margin-bottom: 10pt; text-transform: uppercase;">Mekanisme Dekripsi Berlapis (Two-Tier Decryption Flow)</div>
        
        <div style="border: 1px solid black; padding: 5pt; margin: 5pt auto; width: 80%;">
            <strong>Tahap 1: Ekstraksi Data Kasar</strong><br>
            Sistem menarik data reservasi, termasuk kolom <code>encrypted_ip_seed</code>.
        </div>
        <div style="font-size: 14pt;">&#8595;</div>
        
        <div style="border: 1px solid black; padding: 5pt; margin: 5pt auto; width: 80%;">
            <strong>Tahap 2: Pembongkaran Tier 1</strong><br>
            Fungsi <code>decryptIP()</code> dipanggil bersamaan dengan <i>Master Key</i> statis untuk membongkar <code>encrypted_ip_seed</code> dan memulihkan nilai <strong>IP Historis</strong> transaksi.
        </div>
        <div style="font-size: 14pt;">&#8595;</div>

        <div style="border: 2px dashed black; padding: 10pt; margin: 5pt auto; width: 90%;">
            <strong>Tahap 3: Validasi Gerbang Kritis (IP Matching)</strong><br>
            Sistem mengkomparasi: Apakah <strong>IP Historis</strong> IDENTIK dengan <strong>Current Admin IP</strong>?
            
            <table style="width: 100%; margin-top: 10pt; border: none;">
                <tr>
                    <td style="border: none; width: 50%; padding-right: 5pt; text-align: center;">
                        <div style="border: 1px solid black; padding: 5pt;">
                            <strong style="text-decoration: underline;">Kondisi A (Identik)</strong><br>
                            - Rakit <i>Data Key</i> (Tier 2).<br>
                            - Eksekusi dekripsi <code>decryptData()</code>.<br>
                            - Tampilkan profil tamu secara utuh.
                        </div>
                    </td>
                    <td style="border: none; width: 50%; padding-left: 5pt; text-align: center;">
                        <div style="border: 1px solid black; padding: 5pt;">
                            <strong style="text-decoration: underline;">Kondisi B (Tidak Identik)</strong><br>
                            - Dekripsi DIBATALKAN sepenuhnya.<br>
                            - Pertahankan wujud Ciphertext Base64.<br>
                            - Tampilkan label peringatan <i>[Locked]</i>.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <h2>4.3 Logika Immutabilitas Perpanjangan Sewa (Extend Immutability)</h2>
    <p>Modul pembaruan reservasi (extend) mengamanatkan satu aturan struktural mutlak: Dilarang memodifikasi atau memperbarui (update) kolom yang berisi Ciphertext melalui mekanisme apapun. Apabila kolom terenkripsi dipaksa untuk diubah tanpa melakukan sinkronisasi ulang terhadap parameter komputasi <i>Nonce</i> dari awal penciptaan struktur kriptografi, dekripsi pada Stream Cipher akan mengalami kerusakan permanen (total decryption failure). Oleh karenanya, sistem pembaruan dibatasi ketat; hanya mutasi variabel <code>check_out</code>, pembaruan komputasi beban biaya akhir, perubahan status fungsional kamar, dan penambahan anotasi label otomatis <code>[EXTENDED]</code> pada catatan operasional yang diizinkan dieksekusi sistem.</p>

    <h2>4.4 Fasilitas Rekayasa Forensik Terintegrasi (Audit Tools)</h2>
    <p>Keunikan arsitektur sistem ini terletak pada ketersediaan instrumen pembedah yang tersemat secara permanen pada Menu Navigasi Admin. Fasilitas "Demo Enkripsi ChaCha20" berfungsi krusial sebagai laboratorium simulasi waktu-nyata, memperlihatkan kepada penguji akademis bagaimana algoritma Stream Cipher secara mekanis mengubah rentetan abjad menjadi himpunan karakter acak mutlak. Sementara itu, menu "Audit Kriptografi Database" beroperasi sebagai panel forensik; modul ini langsung menjangkau ke relung terdalam SQLite, melakukan isolasi terhadap nilai <i>Nonce</i>—baik 8-byte (Sodium) maupun 16-byte (OpenSSL)—dari komposit <i>Ciphertext</i>, dan mendemonstrasikan proses komparasi kunci secara visual langkah-demi-langkah.</p>

</div>

<!-- Mermaid JS untuk Diagram Web -->
<script type="module">
    import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
    mermaid.initialize({ startOnLoad: true, theme: 'neutral' });
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
