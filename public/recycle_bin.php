<?php
require_once __DIR__ . '/../app/auth.php';
require_login();
require_once __DIR__ . '/../app/crypto.php';
require_once __DIR__ . '/../app/layout.php';

$msg = $_GET['msg'] ?? '';

// Ambil hanya dokumen yang berstatus dihapus (is_deleted = 1)
$docs = db()->query('
    SELECT d.*, c.name as category, u.name as uploader 
    FROM documents d 
    JOIN categories c ON c.id = d.category_id 
    JOIN users u ON u.id = d.uploaded_by 
    WHERE d.is_deleted = 1 
    ORDER BY d.id DESC
')->fetchAll();

page_header('Recycle Bin (Keranjang Sampah)');
?>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= h($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="text-secondary mb-0">Berkas yang Dihapus Sementara</h5>
    <a href="documents.php" class="btn btn-secondary btn-sm">← Kembali ke Brankas</a>
</div>

<div class="card p-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nama Dokumen</th>
                    <th>Kategori</th>
                    <th>Uploader</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs as $d): ?>
                <tr>
                    <td class="text-muted text-decoration-line-through fw-bold"><?= h($d['original_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= h($d['category']) ?></span></td>
                    <td><small class="text-muted"><?= h($d['uploader']) ?></small></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <!-- Tombol Pulihkan File -->
                            <a href="delete_document.php?id=<?= h($d['id']) ?>&action=restore" class="btn btn-success">🔄 Pulihkan</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($docs)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Keranjang sampah kosong.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php page_footer(); ?>
