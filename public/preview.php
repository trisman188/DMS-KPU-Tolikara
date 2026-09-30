<?php
require_once __DIR__ . '/../app/auth.php';
require_login();
require_once __DIR__ . '/../app/crypto.php';

$id = (int)($_GET['id'] ?? 0);

// 1. Ambil metadata dokumen dari database
$stmt = db()->prepare('SELECT * FROM documents WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Dokumen tidak ditemukan.');
}

// 2. Cek apakah tipe file didukung untuk preview (PDF atau Gambar)
if (!isPreviewable($doc['mime_type'])) {
    http_response_code(400);
    die('Format dokumen ini tidak mendukung fitur Live Preview. Silakan unduh langsung.');
}

$filePath = BASE_PATH . '/storage/encrypted/' . $doc['stored_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Berkas fisik dokumen tidak ditemukan di penyimpanan server.');
}

try {
    // 3. Dekripsi file secara real-time di memori RAM
    $decryptedData = decrypt_file($filePath);
    
    // 4. Catat aktivitas preview ke dalam sistem Audit Trail
    audit('preview', $id, 'Melihat preview dokumen secara live');
    
    // 5. Kirim Header HTTP yang sesuai agar browser merender sebagai halaman visual, bukan download
    header('Content-Type: ' . $doc['mime_type']);
    header('Content-Length: ' . strlen($decryptedData));
    header('Content-Disposition: inline; filename="' . basename($doc['original_name']) . '"');
    header('Cache-Control: private, max-age=3600, must-revalidate');
    
    // 6. Keluarkan data dekripsi ke browser
    echo $decryptedData;
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    die('Gagal mendekripsi dokumen untuk preview: ' . $e->getMessage());
}
