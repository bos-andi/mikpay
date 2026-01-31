# Multi-User System Setup - MIKPAY

## 📋 Ringkasan

Sistem multi-user telah ditambahkan ke MIKPAY. Sekarang aplikasi mendukung:
- Banyak user bisa login dengan akun masing-masing
- Admin bisa menambahkan user/pelanggan baru
- Auto-trial 5 hari untuk user baru
- Manajemen user melalui halaman admin

## 🗄️ Database Setup

### 1. Buat Database

Database sudah dibuat dengan nama `mikpay`. Sekarang perlu membuat tabel:

**Opsi 1: Import SQL Schema**
```bash
mysql -u root -p mikpay < database/schema.sql
```

**Opsi 2: Jalankan via PHPMyAdmin**
1. Buka PHPMyAdmin
2. Pilih database `mikpay`
3. Import file `database/schema.sql`

**Opsi 3: Auto-create (Recommended)**
Tabel akan otomatis dibuat saat pertama kali aplikasi diakses jika database connection berhasil.

### 2. Konfigurasi Database

Edit file `include/database.php` dan sesuaikan dengan konfigurasi database Anda:

```php
define('DB_HOST', 'localhost');  // Host database
define('DB_NAME', 'mikpay');     // Nama database
define('DB_USER', 'root');        // Username database
define('DB_PASS', '');            // Password database
```

## 📊 Struktur Database

### Tabel `users`
- `id` - Primary key
- `username` - Username untuk login (unique)
- `password` - Password (hashed)
- `email` - Email user
- `full_name` - Nama lengkap
- `phone` - Nomor telepon
- `status` - Status: active, inactive, suspended
- `role` - Role: admin, user
- `trial_started` - Tanggal mulai trial
- `trial_ends` - Tanggal akhir trial
- `subscription_package` - Paket subscription
- `subscription_start` - Tanggal mulai subscription
- `subscription_end` - Tanggal akhir subscription
- `created_at` - Tanggal dibuat
- `updated_at` - Tanggal update
- `last_login` - Last login

### Tabel `user_sessions`
- `id` - Primary key
- `user_id` - Foreign key ke users
- `session_name` - Nama session MikroTik
- `router_name` - Nama router
- `router_ip` - IP router
- `router_port` - Port API (default: 8728)
- `router_username` - Username router
- `router_password` - Password router (encrypted)
- `is_active` - Status aktif
- `created_at` - Tanggal dibuat
- `updated_at` - Tanggal update

## 🚀 Cara Menggunakan

### 1. Registrasi User Baru

**Via Halaman Registrasi:**
1. Buka: `http://localhost/mikdev/register.php`
2. Isi form registrasi:
   - Username (min 3 karakter)
   - Nama Lengkap
   - Email (opsional)
   - No. HP (opsional)
   - Password (min 6 karakter)
   - Konfirmasi Password
3. Klik "Daftar"
4. User otomatis mendapat trial 5 hari

**Via Admin Panel:**
1. Login sebagai admin
2. Buka: `admin.php?id=users`
3. Isi form "Tambah User Baru"
4. Klik "Tambah User"
5. User otomatis mendapat trial 5 hari

### 2. Login User

1. Buka: `http://localhost/mikdev/admin.php?id=login`
2. Masukkan username dan password
3. Sistem akan:
   - Cek di database terlebih dahulu
   - Jika tidak ada, fallback ke admin lama (backward compatible)
   - Set session dengan user_id dan role
   - Redirect ke halaman sessions

### 3. Manage User (Admin Only)

**Akses:**
- URL: `admin.php?id=users`
- Menu: Settings > User Management

**Fitur:**
- **Tambah User** - Tambah user baru dengan trial 5 hari atau paket langsung
- **Edit User** - Edit email, nama, phone, status
- **Perpanjang Subscription** - Perpanjang subscription user
- **Hapus User** - Hapus user dari sistem

## 🔐 Auto-Trial 5 Hari

Setiap user baru yang didaftarkan akan otomatis mendapat:
- **Trial Start:** Tanggal registrasi
- **Trial End:** 5 hari dari tanggal registrasi
- **Subscription Package:** `trial`
- **Status:** `active`

## 📝 Fitur yang Ditambahkan

### 1. File Baru
- `include/database.php` - Database connection dan helper functions
- `database/schema.sql` - SQL schema untuk tabel
- `register.php` - Halaman registrasi user
- `admin/users.php` - Halaman admin untuk manage user

### 2. File yang Diupdate
- `admin.php` - Support multi-user login
- `index.php` - Check subscription dari database
- `include/login.php` - Link ke halaman registrasi
- `include/menu.php` - Menu User Management untuk admin

## 🔧 Konfigurasi

### Database Connection

Edit `include/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mikpay');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### Default Admin

Default admin user dibuat saat import schema:
- Username: `admin`
- Password: `admin123`
- Role: `admin`
- Subscription: Enterprise (1 tahun)

**⚠️ PENTING:** Ganti password default setelah setup!

## 🧪 Testing

### Test Registrasi
1. Buka `register.php`
2. Daftar user baru
3. Cek database - user harus ada dengan trial 5 hari

### Test Login
1. Login dengan user baru
2. Pastikan redirect ke sessions
3. Cek subscription status

### Test Admin
1. Login sebagai admin
2. Buka User Management
3. Test tambah/edit/hapus user

## 📋 Checklist Setup

- [ ] Database `mikpay` sudah dibuat
- [ ] Import schema atau biarkan auto-create
- [ ] Edit konfigurasi database di `include/database.php`
- [ ] Test registrasi user baru
- [ ] Test login user
- [ ] Test admin user management
- [ ] Ganti password default admin

## 🔄 Backward Compatibility

Sistem tetap support:
- Login admin lama (dari config.php)
- Session management lama
- Semua fitur yang sudah ada

## 📞 Troubleshooting

### Database connection failed
- Pastikan MySQL/MariaDB running
- Cek username/password database
- Pastikan database `mikpay` sudah dibuat

### User tidak bisa login
- Cek apakah user ada di database
- Cek status user (harus 'active')
- Cek subscription_end (harus belum expired)

### Trial tidak aktif
- Cek `trial_ends` di database
- Pastikan `createUserSubscription()` dipanggil saat registrasi

---

**Last Updated:** 2026-01-31
**Version:** 1.0.0
