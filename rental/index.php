<?php
require_once 'config/config.php';
require_once 'helpers/security_helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $db = new PDO("sqlite:" . DB_PATH);
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && SecurityHelper::verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            redirect('dashboard.php');
        } else {
            $error = "Username atau password salah.";
        }
    } catch (PDOException $e) {
        $error = "Koneksi database gagal.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DKJ Rental Kendaraan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#030213] flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-md animate-in fade-in zoom-in duration-500">
        <div class="bg-white rounded-[2rem] p-10 shadow-2xl relative overflow-hidden">
            <!-- Decorative background -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
                <div class="flex flex-col items-center mb-10">
                    <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-indigo-200">
                        <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Sistem Rental Kendaraan</h1>
                    <p class="text-gray-500 text-sm mt-2">Rental Sederhana ChaCha20</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center gap-3 text-sm animate-shake">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="form-group">
                        <label class="text-gray-700 font-semibold text-sm mb-2 block">Username</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                            <input type="text" name="username" class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-800" placeholder="admin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="text-gray-700 font-semibold text-sm mb-2 block">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                            <input type="password" name="password" class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-800" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#1a237e] hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-100 transition-all transform hover:-translate-y-1">
                        Masuk Dashboard
                    </button>
                </form>

                <div class="mt-10 text-center">
                    <p class="text-xs text-gray-400">&copy; 2024 Project Keamanan Data - ChaCha20</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
