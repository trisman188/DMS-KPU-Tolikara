<?php
require_once __DIR__.'/app/db.php';
$sql = file_get_contents(__DIR__.'/database/schema.sql');
db()->exec($sql);
$exists = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ((int)$exists === 0) {
    $stmt = db()->prepare("INSERT INTO users(username,password_hash,name,role,created_at) VALUES(?,?,?,?,datetime('now'))");
    $stmt->execute(['admin', password_hash('Admin@12345', PASSWORD_DEFAULT), 'Administrator', 'admin']);
}
$cats = ['Administrasi','Kepegawaian','Keuangan','Kegiatan','Surat Masuk','Surat Keluar','Arsip'];
$stmt = db()->prepare("INSERT OR IGNORE INTO categories(name,created_at) VALUES(?,datetime('now'))");
foreach($cats as $c) $stmt->execute([$c]);
echo "VaultSafe siap digunakan. Hapus atau blokir setup.php setelah instalasi.";
