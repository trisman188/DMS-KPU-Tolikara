<?php
require_once __DIR__ . '/../app/auth.php';
require_login();
require_once __DIR__ . '/../app/crypto.php';

// Pastikan hanya Admin atau Operator yang bisa menghapus
if (!in_array(user()['role'], ['admin', 'operator'], true)) {
    die('Anda tidak memiliki izin untuk mengelola penghapusan dokumen.');
}

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'trash'; // trash = ke recycle bin, restore = pulihkan

// 1. Cek apakah dokumennya ada di database
$stmt = db()->prepare('SELECT * FROM documents WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Dokumen tidak ditemukan.');
}

if ($action === 'trash') {
    // Pindahkan ke Recycle Bin (Ubah flag is_deleted menjadi 1)
    $stmt = db()->prepare('UPDATE documents SET is_deleted = 1 WHERE id = ?');
    $stmt->execute([$id]);
    
    audit('delete_to_trash');
    header('Location: documents.php?msg=' . urlencode('Dokumen berhasil dipindahkan ke Recycle Bin.'));
    exit;

} elseif ($action === 'restore') {
    // Pulihkan dokumen kembali ke Brankas Utama (Ubah flag is_deleted menjadi 0)
    $stmt = db()->prepare('UPDATE documents SET is_deleted = 0 WHERE id = ?');
    $stmt->execute([$id]);
    
    audit('restore_from_trash');
    header('Location: recycle_bin.php?msg=' . urlencode('Dokumen berhasil dipulihkan ke brankas utama.'));
    exit;
}
