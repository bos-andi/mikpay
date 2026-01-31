# Multi-User System Setup - MIKPAY

## 📋 Ringkasan

Sistem multi-user telah diimplementasikan dengan database MySQL. Setiap user memiliki router yang terisolasi dan tidak bisa melihat router user lain.

## ✅ Fitur yang Diimplementasikan

1. **Multi-User Login**: Login dari database dengan fallback ke sistem lama
2. **Isolasi Router**: Setiap user hanya melihat router miliknya
3. **Superadmin Management**: Superadmin bisa manage semua user
4. **Auto Migration**: Superadmin dari `superadmin.php` otomatis di-migrate ke database
5. **Backward Compatible**: Sistem lama tetap berjalan jika database tidak tersedia

## 🗄️ Database Setup

### 1. Buat Database

```sql
CREATE DATABASE mikpay;
```

### 2. Import Schema

```bash
mysql -u root -p mikpay < database/schema.sql
```

Atau via PHPMyAdmin:
1. Buka PHPMyAdmin
2. Pilih database `mikpay`
3. Import file `database/schema.sql`

### 3. Konfigurasi Database

Edit file `include/database.php` dan sesuaikan:

```php
define('DB_HOST', 'localhost');  // Host database
define('DB_NAME', 'mikpay');     // Nama database
define('DB_USER', 'root');        // Username database
define('DB_PASS', '');            // Password database
```

## 👤 Default Users

### Superadmin
- **Username**: `superadmin`
- **Password**: `MikPayandidev.id` (dari `superadmin.php`)
- **Role**: `superadmin`
- **Auto-migrated**: Ya, otomatis dari `superadmin.php`

### Admin (Default)
- **Username**: `admin`
- **Password**: `admin123`
- **Role**: `admin`

## 🔐 Roles

1. **superadmin**: Full access, bisa manage semua user termasuk superadmin lain
2. **admin**: Bisa manage user biasa, tidak bisa manage superadmin
3. **user**: User biasa, hanya bisa manage router sendiri

## 📊 Struktur Database

### Tabel `users`
- `id` - Primary key
- `username` - Username untuk login (unique)
- `password` - Password (hashed)
- `email` - Email user
- `full_name` - Nama lengkap
- `phone` - Nomor telepon
- `role` - Role: superadmin, admin, user
- `status` - Status: active, inactive, suspended
- `trial_started` - Tanggal mulai trial
- `trial_ends` - Tanggal akhir trial
- `subscription_package` - Paket subscription
- `subscription_start` - Tanggal mulai subscription
- `subscription_end` - Tanggal akhir subscription
- `created_at` - Tanggal dibuat
- `last_login` - Last login

### Tabel `user_sessions`
- `id` - Primary key
- `user_id` - Foreign key ke users
- `session_name` - Nama session MikroTik
- `router_name` - Nama router
- `host` - IP:Port router
- `username` - Username router
- `password` - Password router (encrypted)
- `currency` - Currency
- `currency_position` - Currency position
- `expiry_mode` - Expiry mode
- `expiry_days` - Expiry days
- `domain` - Domain
- `status` - Status: active, inactive

## 🚀 Cara Menggunakan

### 1. Login sebagai Superadmin

1. Buka `admin.php?id=login`
2. Login dengan:
   - Username: `superadmin`
   - Password: `MikPayandidev.id`

### 2. Tambah User Baru

1. Login sebagai superadmin atau admin
2. Klik menu "User Management"
3. Klik "Tambah User"
4. Isi form:
   - Username (required)
   - Password (required)
   - Email (optional)
   - Nama Lengkap (optional)
   - Phone (optional)
   - Role (superadmin/admin/user)
5. Klik "Simpan"

### 3. User Login dan Tambah Router

1. User login dengan username/password yang dibuat
2. User otomatis mendapat trial 5 hari
3. User bisa tambah router di halaman Sessions
4. Router yang ditambahkan hanya terlihat untuk user tersebut

### 4. Manage User (Superadmin/Admin)

1. Login sebagai superadmin atau admin
2. Klik menu "User Management"
3. Lihat semua user
4. Edit user: Klik "Edit" pada user
5. Delete user: Klik "Delete" pada user (superadmin tidak bisa dihapus)

## 🔄 Backward Compatibility

Sistem tetap support:
- ✅ Login admin lama (dari `config.php`)
- ✅ Router dari `config.php` (jika database tidak tersedia)
- ✅ Semua fitur yang sudah ada tetap berjalan

## 🔒 Security

1. **Password Hashing**: Menggunakan `password_hash()` dan `password_verify()`
2. **SQL Injection Protection**: Menggunakan prepared statements
3. **Session Security**: Session ID di-regenerate setelah login
4. **Authorization**: Check `user_id` di setiap query
5. **Input Validation**: Semua input di-sanitize

## 📝 File yang Diubah/Ditambahkan

### File Baru:
- `include/database.php` - Database connection dan helper functions
- `admin/users.php` - Halaman manage user
- `MULTI_USER_ANALYSIS.md` - Analisis dan saran
- `MULTI_USER_SETUP.md` - Dokumentasi setup (file ini)

### File yang Diupdate:
- `admin.php` - Support login database dengan fallback
- `include/readcfg.php` - Load router dari database dengan fallback
- `settings/sessions.php` - Filter router per user
- `settings/settings.php` - Save router ke database
- `include/menu.php` - Tambah menu User Management

## 🧪 Testing

### Test Login Database
1. Login dengan user dari database
2. Pastikan session ter-set dengan benar
3. Pastikan router hanya terlihat untuk user tersebut

### Test Backward Compatibility
1. Hapus atau rename database
2. Login dengan admin lama (dari `config.php`)
3. Pastikan semua fitur tetap berjalan

### Test User Management
1. Login sebagai superadmin
2. Tambah user baru
3. Edit user
4. Delete user
5. Pastikan semua berjalan dengan baik

## ⚠️ Catatan Penting

1. **Database Wajib**: Untuk fitur multi-user, database MySQL wajib ada
2. **Backward Compatible**: Jika database tidak tersedia, sistem akan fallback ke sistem lama
3. **Superadmin Migration**: Superadmin dari `superadmin.php` otomatis di-migrate saat pertama kali database diakses
4. **Password**: Default password superadmin adalah `MikPayandidev.id` (dari `superadmin.php`)

## 🐛 Troubleshooting

### Database connection failed
- Pastikan MySQL/MariaDB running
- Cek username/password database di `include/database.php`
- Pastikan database `mikpay` sudah dibuat

### User tidak bisa login
- Cek apakah user ada di database
- Cek status user (harus 'active')
- Cek password (harus di-hash dengan benar)

### Router tidak muncul
- Cek apakah router sudah di-save ke database
- Cek `user_id` di tabel `user_sessions`
- Pastikan user login dari database (bukan admin lama)

### Menu User Management tidak muncul
- Pastikan user login sebagai admin atau superadmin
- Cek role user di database
- Pastikan `include/database.php` ter-load dengan benar

---

**Last Updated**: 2026-01-31
**Version**: 1.0.0
