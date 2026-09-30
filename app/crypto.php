<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

function encrypt_file(string $inputPath, string $outputPath): void {
    $plain = file_get_contents($inputPath);
    if ($plain === false) throw new RuntimeException('File tidak dapat dibaca.');
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', APP_KEY_BIN, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) throw new RuntimeException('Enkripsi gagal.');
    $blob = "VS1" . pack('C', strlen($iv)) . $iv . pack('C', strlen($tag)) . $tag . $cipher;
    if (file_put_contents($outputPath, $blob, LOCK_EX) === false) throw new RuntimeException('Gagal menyimpan file terenkripsi.');
}
function decrypt_file(string $inputPath): string {
    $blob = file_get_contents($inputPath);
    if ($blob === false || substr($blob, 0, 3) !== 'VS1') throw new RuntimeException('Format file terenkripsi tidak valid.');
    $offset = 3;
    $ivLen = ord($blob[$offset++]);
    $iv = substr($blob, $offset, $ivLen); $offset += $ivLen;
    $tagLen = ord($blob[$offset++]);
    $tag = substr($blob, $offset, $tagLen); $offset += $tagLen;
    $cipher = substr($blob, $offset);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', APP_KEY_BIN, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false) throw new RuntimeException('Dekripsi gagal.');
    return $plain;
}
// Di dalam app/crypto.php

/**
 * Membuat tautan QR Code eksternal untuk verifikasi keaslian dokumen
 */
function generateDocumentQRCode($documentUuid) {
    // Kita memanfaatkan API open-source untuk merender QR Code secara instan
    $verificationUrl = urlencode("http://localhost:8080/verify.php?id=" . $documentUuid);
    return "https://qrserver.com" . $verificationUrl;
}


/**
 * Memvalidasi apakah file aman untuk di-preview langsung (PDF/Gambar)
 */
function isPreviewable($mimeType) {
    $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
    return in_array($mimeType, $allowed);
}
/**
 * Menghasilkan secret key acak sepanjang 16 karakter Base32 untuk 2FA
 */
function generate_2fa_secret() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 16; $i++) {
        $secret .= $chars[random_int(0, 31)];
    }
    return $secret;
}

/**
 * Menghasilkan URL QR Code agar secret key bisa di-scan oleh Google Authenticator
 */
function get_2fa_qr_url($username, $secret) {
    $issuer = urlencode('DMS KPU Tolikara');
    $account = urlencode($username);
    $otpauthUrl = "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}";
    return "https://qrserver.com" . urlencode($otpauthUrl);
}

/**
 * Memvalidasi apakah kode 6 digit yang dimasukkan user cocok dengan kode server saat ini
 */
function verify_2fa_code($secret, $code) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $map = array_flip(str_split($chars));
    $secret = strtoupper($secret);
    $binary = '';
    
    // Decode Base32 Secret Key ke bentuk Biner
    foreach (str_split($secret) as $c) {
        if (!isset($map[$c])) continue;
        $binary .= str_pad(decbin($map[$c]), 5, '0', STR_PAD_LEFT);
    }
    $binarySecret = '';
    foreach (str_split($binary, 8) as $bin8) {
        if (strlen($bin8) === 8) $binarySecret .= chr(bindec($bin8));
    }

    // Ambil timestamp waktu saat ini (kelipatan 30 detik)
    $timeSlice = floor(time() / 30);

    // Cek toleransi waktu (sekarang, 30 detik lalu, 30 detik ke depan) untuk mengantisipasi selisih jam hp user
    for ($i = -1; $i <= 1; $i++) {
        $time = pack('N*', 0) . pack('N*', $timeSlice + $i);
        $hash = hash_hmac('sha1', $time, $binarySecret, true);
        $offset = ord($hash[19]) & 0xf;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        );
        $calculatedCode = str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
        if ($calculatedCode === $code) {
            return true; // Token Sah!
        }
    }
    return false; // Token Salah/Kadaluwarsa
}
