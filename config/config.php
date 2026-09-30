<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string {
    static $loaded = false;
    static $vars = [];
    if (!$loaded) {
        $path = dirname(__DIR__) . '/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v, " \t\n\r\0\x0B\"");
            }
        }
        $loaded = true;
    }
    return $_ENV[$key] ?? $vars[$key] ?? $default;
}

define('BASE_PATH', dirname(__DIR__));
define('APP_NAME', env('APP_NAME', 'VaultSafe'));
define('DB_PATH', BASE_PATH . '/' . env('DB_PATH', 'database/vaultsafe.sqlite'));
define('MAX_UPLOAD_BYTES', (int)env('MAX_UPLOAD_MB', '20') * 1024 * 1024);

$key = env('APP_KEY');
if (!$key) {
    die('APP_KEY belum diatur. Salin .env.example menjadi .env dan isi APP_KEY.');
}
$decoded = base64_decode($key, true);
if ($decoded === false || strlen($decoded) !== 32) {
    die('APP_KEY harus berupa base64 dari tepat 32 byte.');
}
define('APP_KEY_BIN', $decoded);

date_default_timezone_set('Asia/Jakarta');
