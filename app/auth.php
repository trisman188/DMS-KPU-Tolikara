<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419); exit('CSRF token tidak valid.');
    }
}
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => $user['role']
    ];
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
    if (!user()) { header('Location: login.php'); exit; }
}
function require_role(array $roles): void {
    require_login();
    if (!in_array(user()['role'], $roles, true)) {
        http_response_code(403); exit('Akses ditolak.');
    }
}
function audit(string $action, ?int $documentId = null, string $detail = ''): void {
    $stmt = db()->prepare('INSERT INTO audit_logs(user_id, action, document_id, detail, ip_address, created_at) VALUES(?,?,?,?,?,datetime("now"))');
    $stmt->execute([user()['id'] ?? null, $action, $documentId, $detail, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}
