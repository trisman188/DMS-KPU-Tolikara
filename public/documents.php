<?php
require_once __DIR__ . '/../app/auth.php'; 
require_login(); 
require_once __DIR__ . '/../app/crypto.php'; 
require_once __DIR__ . '/../app/layout.php';

$msg = $_GET['msg'] ?? ''; 
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array(user()['role'], ['admin', 'operator'], true)) { 
        $err = 'Anda tidak memiliki izin upload.'; 
    } else {
        try {
            $f = $_FILES['document'] ?? null; 
            $cat = (int)($_POST['category_id'] ?? 0);
            $customName = trim($_POST['custom_name'] ?? ''); 
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt', 'zip'];
            
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal.');
            if ($f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Ukuran file melebihi batas.');
            
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) throw new RuntimeException('Ekstensi file tidak diizinkan.');
            
            $check = db()->prepare('SELECT id FROM categories WHERE id = ?');
            $check->execute([$cat]);
            if (!$check->fetch()) throw new RuntimeException('Kategori tidak valid.');
            
            $stored = bin2hex(random_bytes(20)) . '.vault';
            $path = BASE_PATH . '/storage/encrypted/' . $stored;
            
            encrypt_file($f['tmp_name'], $path);
            // 1. Cari nama kategori berdasarkan category_id yang dipilih user
            $catStmt = db()->prepare('SELECT name FROM categories WHERE id = ?');
            $catStmt->execute([$cat]);
            $categoryRow = $catStmt->fetch();
            
            // Bersihkan nama folder (contoh: "Surat Keputusan" -> "surat_keputusan")
            $categoryFolder = 'umum';
            if ($categoryRow) {
                $categoryFolder = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $categoryRow['name']));
            }
            
            // 2. Definisikan jalur folder target baru sesuai kategori di dalam kontainer Docker
            $targetDir = BASE_PATH . '/storage/encrypted/' . $categoryFolder;
            
            // 3. Buat folder fisik otomatis di harddisk jika foldernya belum ada
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // 4. Tentukan jalur simpan akhir file terenkripsi di dalam sub-folder kategori
            $stored = bin2hex(random_bytes(20)) . '.vault';
            $path = $targetDir . '/' . $stored;
            
            // Jalankan enkripsi file ke folder tujuan yang baru
            encrypt_file($f['tmp_name'], $path);
            
            // Amankan nama folder relatifnya ke database agar fungsi unduh/preview tidak bingung
            $storedNameDb = $categoryFolder . '/' . $stored;
            
            $finalTitle = ($customName !== '') ? $customName . '.' . $ext : $f['name'];
            
            $stmt = db()->prepare('INSERT INTO documents (category_id, original_name, title, stored_name, mime_type, size_bytes, uploaded_by, is_deleted, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, datetime("now"))');
            $stmt->execute([$cat, $f['name'], $finalTitle, $storedNameDb, $f['type'] ?: 'application/octet-stream', $f['size'], user()['id']]);

            audit('upload');
            $msg = 'Dokumen berhasil disimpan dengan nama kustom secara terenkripsi.';
        } catch (Throwable $e) { 
            $err = $e->getMessage(); 
        }
    }
}

$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = db()->prepare('SELECT d.*, c.name as category, u.name as uploader FROM documents d JOIN categories c ON c.id = d.category_id JOIN users u ON u.id = d.uploaded_by WHERE d.is_deleted = 0 AND (d.title LIKE ? OR d.original_name LIKE ?) ORDER BY d.id DESC');
    $stmt->execute(["%$q%", "%$q%"]);
    $docs = $stmt->fetchAll();
} else {
    $docs = db()->query('SELECT d.*, c.name as category, u.name as uploader FROM documents d JOIN categories c ON c.id = d.category_id JOIN users u ON u.id = d.uploaded_by WHERE d.is_deleted = 0 ORDER BY d.id DESC')->fetchAll();
}

page_header('DMS KPU Tolikara - Brankas Dokumen');
?>

<!-- Tombol Akses Keranjang Sampah V2 -->
<div class="d-flex justify-content-end mb-3">
    <a href="recycle_bin.php" class="btn btn-outline-danger btn-sm">🗑️ Buka Recycle Bin</a>
</div>

<!-- Alert Notification -->
<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= h($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= h($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Pencarian dan Filter -->
<div class="card p-3 mb-4 shadow-sm">
    <form method="get" class="row g-2">
        <div class="col-md-10">
            <input type="text" name="q" class="form-control" value="<?= h($q) ?>" placeholder="Cari berdasarkan nama/judul dokumen...">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Cari</button>
        </div>
    </form>
</div>

<!-- Form Upload Drag & Drop Modern dengan Input Nama Kustom -->
<?php if (in_array(user()['role'], ['admin', 'operator'], true)): ?>
<div class="card p-4 mb-4 shadow-sm">
    <h5 class="mb-3 font-bold text-secondary">Upload Dokumen Baru (DMS KPU Tolikara V2)</h5>
    <form method="post" enctype="multipart/form-data" class="space-y-3">
        
        <!-- Area Drop Zone -->
        <div class="border-2 border-dashed border-primary rounded-3 p-4 text-center cursor-pointer bg-light hover:bg-white transition mb-3" id="drop-zone" style="cursor: pointer;">
            <input type="file" name="document" id="file-input" class="d-none" required />
            <div class="py-2">
                <svg xmlns="http://w3.org" width="40" height="40" fill="currentColor" class="bi bi-cloud-arrow-up text-primary mb-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708z"/>
                    <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.517l-.518.003C2.067 6.68 1 7.74 1 9.318 1 10.759 2.165 12 3.781 12h8.906C13.98 12 15 11.02 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.725 10.334 3 8 3a4.48 4.48 0 0 0-2.941 1.1z"/>
                </svg>
                <p class="mb-1 text-dark fw-bold">Tarik & Lepaskan berkas di sini</p>
                <p class="text-muted small">atau <span class="text-primary text-decoration-underline">pilih berkas dari komputer</span></p>
                <div id="file-name-preview" class="mt-2 badge bg-primary text-wrap p-2 d-none"></div>
            </div>
        </div>

        <!-- Input Nama File Kustom -->
        <div class="mb-3">
            <label class="form-label fw-bold text-secondary">Nama Dokumen Kustom (Opsional)</label>
            <input type="text" name="custom_name" class="form-control" placeholder="Contoh: Surat Perjanjian Kontrak Kerja (Kosongkan jika ingin nama asli berkas)">
        </div>

        <!-- Pilihan Kategori dan Tombol -->
        <div class="row g-2">
            <div class="col-md-9">
                <select class="form-select" name="category_id" required>
                    <option value="">-- Pilih Kategori Dokumen --</option>
                    <?php foreach($cats as $cat): ?>
                        <option value="<?= h($cat['id']) ?>"><?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Unggah Aman</button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Tabel Daftar Dokumen -->
<div class="card p-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nama Dokumen</th>
                    <th>Kategori</th>
                    <th>Ukuran</th>
                    <th>Uploader</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs as $d): ?>
                <?php 
                    $ext = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif'], true);
                    $isPdf = ($ext === 'pdf');
                ?>
                <tr>
                    <td class="fw-bold text-secondary"><?= h($d['title'] ?? $d['original_name']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= h($d['category']) ?></span></td>
                    <td><?= number_format($d['size_bytes'] / 1024, 1) ?> KB</td>
                    <td><small class="text-muted"><?= h($d['uploader']) ?></small></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if ($isImage || $isPdf): ?>
                                <a href="preview.php?id=<?= h($d['id']) ?>" target="_blank" class="btn btn-outline-success" title="Live Preview">👁️ Preview</a>
                            <?php endif; ?>
                            
                            <?php if (in_array(user()['role'], ['admin', 'operator'], true)): ?>
                                <a href="edit_document.php?id=<?= h($d['id']) ?>" class="btn btn-warning text-white" title="Edit">✏️ Edit</a>
                                <a href="delete_document.php?id=<?= h($d['id']) ?>&action=trash" onclick="return confirm('Apakah Anda yakin ingin membuang dokumen ini ke Recycle Bin?')" class="btn btn-danger" title="Hapus">🗑️ Hapus</a>
                            <?php endif; ?>
                            
                            <a href="download.php?id=<?= h($d['id']) ?>" class="btn btn-primary" title="Download">⬇️ Unduh</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($docs)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada dokumen yang tersimpan di brankas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// 1. Definisikan semua variabel di bagian paling atas (Wajib)
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const filePreview = document.getElementById('file-name-preview');

// 2. Bungkus semua logika di dalam satu kondisi if (dropZone) saja
if (dropZone) {
    // Memastikan klik di area mana pun di dalam drop-zone membuka file picker
    dropZone.addEventListener('click', (e) => {
        fileInput.click();
    });
    
    // Logika visual saat file ditarik di atas kotak (Drag Over)
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.replace('bg-light', 'bg-white');
        dropZone.style.borderColor = '#0d6efd';
    });
    
    // Logika visual saat file batal ditarik atau dilepaskan (Drag Leave & Drop)
    ['dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, () => {
            dropZone.classList.replace('bg-white', 'bg-light');
            dropZone.style.borderColor = '#0d6efd';
        });
    });
    
    // Menangkap file yang dijatuhkan (Dropped)
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            showFileName(e.dataTransfer.files[0].name);
        }
    });
    
    // Menangkap file yang dipilih via klik biasa (Changed)
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            showFileName(fileInput.files[0].name);
        }
    });
}

// 3. Fungsi pembantu untuk memunculkan nama berkas di layar browser
function showFileName(name) {
    filePreview.textContent = "📎 Berkas Terpilih: " + name;
    filePreview.classList.remove('d-none');
}
</script>

