# VaultSafe — Digital Vault Web App

MVP aplikasi brankas digital berbasis PHP 8+ dan SQLite.

## Fitur
- Login dengan password hash
- Role: admin, operator, viewer
- Dashboard statistik
- Folder/kategori dokumen
- Upload dokumen
- Enkripsi file AES-256-GCM sebelum disimpan
- Download melalui otorisasi aplikasi
- Audit log aktivitas
- CSRF protection
- Validasi ekstensi dan ukuran file
- Session security dasar
- Responsive Bootstrap UI

## Jalankan
1. Pastikan PHP 8.1+ dan ekstensi OpenSSL/PDO_SQLite aktif.
2. Salin `.env.example` menjadi `.env`.
3. Isi `APP_KEY` dengan 32 byte random yang di-base64-kan. Contoh:
   `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`
4. Jalankan:
   `php setup.php`
5. Jalankan server:
   `php -S localhost:8000 -t public`
6. Buka http://localhost:8000

## Akun awal
- username: admin
- password: Admin@12345

SEGERA ganti password setelah login.

## Catatan produksi
- Gunakan HTTPS.
- Simpan APP_KEY di environment server, jangan di Git.
- Storage encrypted sebaiknya di luar web root.
- Gunakan backup terenkripsi.
- Tambahkan 2FA/SSO untuk deployment instansi.
- Untuk skala besar, migrasikan SQLite ke MySQL/PostgreSQL.
