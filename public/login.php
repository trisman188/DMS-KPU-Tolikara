<?php
if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
	}
}
require_once __DIR__.'/../app/auth.php';
if (user()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $stmt = db()->prepare('SELECT * FROM users WHERE username=? LIMIT 1');
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $u = $stmt->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
    // KONDISI PENYESUAIAN V2 (Pemeriksaan Ganda 2FA)
            // PERBAIKAN TOTAL: Gunakan validasi variabel kustom agar pasti terbaca SQLite
        $secretKey = $u['two_factor_secret'] ?? $u['TWO_FACTOR_SECRET'] ?? '';
        
        if ($secretKey !== null && $secretKey !== '') {
            // Tahan login penuh, simpan ID user sementara di session pra-login
            $_SESSION['preload_user_id'] = $u['id'];
            header('Location: login_2fa.php');
            exit;
        } else {

        // Jalur login biasa jika user belum mengaktifkan 2FA
        login_user($u); 
        audit('login'); 
        header('Location: index.php'); 
        exit;
    }
}

}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - DMS KPU Tolikara</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-dark"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-5">
<div class="card shadow border-0"><div class="card-body p-4"><h2 class="mb-1">🔒 DMS KPU Tolikara</h2><p class="text-muted">Brankas dokumen digital</p>
<?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
<div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required autofocus></div>
<div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
<button class="btn btn-dark w-100">Masuk ke DMS KPU Tolikara</button></form></div></div></div></div></div></body></html>
