<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/crypto.php';
require_once __DIR__ . '/../app/layout.php';

// Pastikan session pra-login ada
if (!isset($_SESSION['preload_user_id'])) {
    header('Location: login.php');
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['otp_code'] ?? '');
    
    // Ambil data user dari database SQLite
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['preload_user_id']]);
    $user = $stmt->fetch();

    if ($user && verify_2fa_code($user['two_factor_secret'], $code)) {
        // Jika OTP Benar, resmikan session login penuh
        // PERBAIKAN UTAMA: Panggil fungsi asli aplikasi V1 untuk mendaftarkan session
        login_user($user); 
        audit('login');
        unset($_SESSION['preload_user_id']);
        header('Location: index.php');
        exit;

        
        audit('login');
        header('Location: index.php');
        exit;
    } else {
        $err = 'Kode OTP Google Authenticator tidak valid atau telah kadaluwarsa.';
        audit('login_2fa_failed', $_SESSION['preload_user_id'], 'Gagal memasukkan kode OTP');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keamanan Ganda 2FA | VaultSafe</title>
    <link rel="stylesheet" href="https://jsdelivr.net">
    <style>
        body { background: #f4f6f9; display: flex; align-items: center; min-height: 100vh; }
        .card-2fa { max-width: 420px; width: 100%; margin: auto; border-radius: 12px; }
    </style>
</head>
<body>
    <div class="card card-2fa shadow p-4 bg-white border-0">
        <div class="text-center mb-3">
            <h3 class="fw-bold text-dark">🔒 Otentikasi 2FA</h3>
            <p class="text-muted small">Buka aplikasi <strong>Google Authenticator</strong> Anda dan masukkan 6 digit kode verifikasi VaultSafe V2.</p>
        </div>

        <?php if ($err): ?><div class="alert alert-danger text-center small"><?= h($err) ?></div><?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-4">
                <input type="text" name="otp_code" class="form-control form-control-lg text-center fw-bold letter-spacing" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">Verifikasi dan Masuk</button>
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none small text-muted">← Kembali ke halaman Login</a>
            </div>
        </form>
    </div>
</body>
</html>
