<?php
require_once __DIR__.'/../app/auth.php'; require_role(['admin','operator']); require_once __DIR__.'/../app/layout.php';
$logs=db()->query('SELECT a.*,u.username,d.original_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN documents d ON d.id=a.document_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
page_header('Audit Log');
?><div class="card p-4"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Dokumen</th><th>IP</th><th>Detail</th></tr></thead><tbody>
<?php foreach($logs as $l): ?><tr><td><?=h($l['created_at'])?></td><td><?=h($l['username']??'-')?></td><td><?=h($l['action'])?></td><td><?=h($l['original_name']??'-')?></td><td><?=h($l['ip_address']??'-')?></td><td><?=h($l['detail']??'')?></td></tr><?php endforeach; ?></tbody></table></div></div><?php page_footer(); ?>
