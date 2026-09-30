-- 1. Tabel Users (Diperluas untuk 2FA dan Manajemen Aktif)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin','operator','viewer')),
    two_factor_secret TEXT DEFAULT NULL, -- Untuk Google Authenticator 2FA/OTP
    status TEXT DEFAULT 'active',        -- active, suspended
    created_at TEXT NOT NULL
);

-- 2. Tabel Folder (Manajemen Hirarki Folder)
CREATE TABLE IF NOT EXISTS folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    parent_id INTEGER DEFAULT NULL,      -- Mengacu ke id folder di atasnya (sub-folder)
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(parent_id) REFERENCES folders(id) ON DELETE CASCADE,
    FOREIGN KEY(created_by) REFERENCES users(id)
);

-- 3. Tabel Documents (Mendukung Recycle Bin & Status Persetujuan)
CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    folder_id INTEGER DEFAULT NULL,       -- Relasi ke folder
    title TEXT NOT NULL,
    description TEXT,
    is_deleted INTEGER DEFAULT 0,        -- 0 = Aktif, 1 = Di Recycle Bin
    approval_status TEXT DEFAULT 'approved' CHECK(approval_status IN ('pending', 'approved', 'rejected')),
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(folder_id) REFERENCES folders(id) ON DELETE SET NULL,
    FOREIGN KEY(created_by) REFERENCES users(id)
);

-- 4. Tabel Document Versions (Fitur Versioning)
CREATE TABLE IF NOT EXISTS document_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_id INTEGER NOT NULL,
    version_number INTEGER NOT NULL,     -- Versi 1, 2, 3, dst
    file_path TEXT NOT NULL,             -- Jalur penyimpanan fisik file
    file_size INTEGER NOT NULL,
    mime_type TEXT NOT NULL,
    uploaded_by INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY(uploaded_by) REFERENCES users(id)
);

-- 5. Tabel Document Permissions (Hak Akses Spesifik Per Dokumen)
CREATE TABLE IF NOT EXISTS document_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    permission_type TEXT NOT NULL CHECK(permission_type IN ('view', 'edit', 'download')),
    FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Tabel Audit Trail (Immutable & Detail)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,                -- e.g., 'UPLOAD_DOC', 'DELETE_DOC', 'APPROVE_DOC'
    details TEXT,                       -- JSON string detail aktivitas
    ip_address TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

