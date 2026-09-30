<?php
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function page_header(string $title): void {
    $u = user();
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> - DMS KPU Tolikara</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f5f7fb;display:flex;flex-direction:column;min-height:100vh}{background:#f5f7fb}.sidebar{min-height:100vh;background:#111827}.sidebar a{color:#d1d5db;text-decoration:none;display:block;padding:.7rem 1rem;border-radius:.5rem}.sidebar a:hover{background:#1f2937;color:#fff}.brand{font-weight:700;color:#fff}.card{border:0;box-shadow:0 4px 18px rgba(15,23,42,.06)}
</style></head><body>
<nav class="navbar navbar-dark bg-dark d-lg-none"><div class="container-fluid"<a class="navbar-brand" href="index.php">DMS KPU Tolikara</a></div></nav>
<div class="container-fluid"><div class="row">
<aside class="col-lg-2 sidebar d-none d-lg-block p-3">
<div class="brand fs-4 mb-4">🔒 DMS KPU Tolikara</div>
<a href="index.php">🏠 Dashboard</a><a href="documents.php">📁 Dokumen</a>
<?php if($u && $u['role']==='admin'): ?><a href="users.php">👥 Pengguna</a><?php endif; ?>
<a href="audit.php">📜 Audit Log</a><a href="logout.php">🚪 Keluar</a>
</aside><main class="col-lg-10 p-4">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="mb-1"><?=h($title)?></h2><small class="text-muted"><?=h($u['name'] ?? '')?> · <?=h($u['role'] ?? '')?></small></div></div>
<?php }
function page_footer(): void {
    echo '
        </main>
        </div>
        </div>
        <!-- Bagian Footer Copyright Terintegrasi V2 -->
        <footer class="text-center py-3 bg-light border-top text-muted small mt-auto w-100">
            &copy; ' . date('Y') . ' <strong>Rendatin Tolikara</strong>. Hak Cipta Dilindungi Undang-Undang.
        </footer>
        </body>
        </html>
    ';
}

?>
