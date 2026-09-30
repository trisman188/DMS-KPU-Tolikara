<?php
require_once __DIR__.'/../app/auth.php'; require_login(); require_once __DIR__.'/../app/layout.php';
$total=(int)db()->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$users=(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$cats=(int)db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$bytes=(int)db()->query('SELECT COALESCE(SUM(size_bytes),0) FROM documents')->fetchColumn();
$recent=db()->query('SELECT d.*,c.name category,u.name uploader FROM documents d JOIN categories c ON c.id=d.category_id JOIN users u ON u.id=d.uploaded_by ORDER BY d.id DESC LIMIT 8')->fetchAll();
page_header('Dashboard');
?>
<div class="row g-3 mb-4">
<?php foreach([['📄','Dokumen',$total],['📁','Kategori',$cats],['👥','Pengguna',$users],['💾','Penyimpanan',round($bytes/1048576,2).' MB']] as $x): ?>
<div class="col-md-3"><div class="card p-3"><div class="text-muted"><?=$x[0]?> <?=$x[1]?></div><div class="fs-3 fw-bold"><?=h((string)$x[2])?></div></div></div>
<?php endforeach; ?></div>
<div class="card p-4"><div class="d-flex justify-content-between"><h5>Dokumen Terbaru</h5><a class="btn btn-dark btn-sm" href="documents.php">Kelola Dokumen</a></div>
<div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>Nama</th><th>Kategori</th><th>Uploader</th><th>Tanggal</th></tr></thead><tbody>
<?php foreach($recent as $r): ?><tr><td><?=h($r['original_name'])?></td><td><?=h($r['category'])?></td><td><?=h($r['uploader'])?></td><td><?=h($r['created_at'])?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php page_footer(); ?>
