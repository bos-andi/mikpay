# Analisis & Saran: Multi-User System untuk MIKPAY

## 📋 Kebutuhan yang Diinginkan

1. ✅ **Multi-User System**: Setiap user punya akun sendiri
2. ✅ **Isolasi Router**: User A hanya lihat router miliknya, User B hanya lihat router miliknya
3. ✅ **Superadmin**: Bisa mendaftarkan user baru
4. ✅ **Session Terpisah**: Setiap user punya session/router yang berbeda

## 🔍 Analisis Sistem Saat Ini

### Struktur Data Router
- **Lokasi**: `include/config.php`
- **Format**: Array PHP `$data['SESSION_NAME']`
- **Masalah**: Semua router di-share untuk semua user (tidak ada isolasi)

### Struktur Data User
- **Lokasi**: `include/users.json` (jika ada)
- **Format**: JSON
- **Masalah**: Tidak ada sistem user management yang proper

### Struktur Subscription
- **Lokasi**: `include/subscription.json`
- **Format**: JSON per session
- **Masalah**: Subscription tidak terikat ke user

## 💡 Saran Implementasi

### **Opsi 1: Database MySQL (RECOMMENDED) ⭐**

#### Kelebihan:
- ✅ **Scalable**: Bisa handle banyak user tanpa masalah
- ✅ **Isolasi Data**: Setiap user punya data terpisah di database
- ✅ **Query Efisien**: Mudah filter router per user
- ✅ **Relasi Data**: Foreign key untuk integritas data
- ✅ **Backup Mudah**: Export/import database
- ✅ **Security**: Password hashing, prepared statements
- ✅ **Performance**: Index untuk query cepat

#### Struktur Database:
```sql
-- Tabel Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255), -- hashed
    email VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    subscription_package VARCHAR(50),
    subscription_start DATE,
    subscription_end DATE,
    created_at DATETIME,
    last_login DATETIME
);

-- Tabel User Routers (Isolasi Router per User)
CREATE TABLE user_routers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT, -- Foreign key ke users
    session_name VARCHAR(100),
    router_name VARCHAR(255),
    host VARCHAR(255),
    username VARCHAR(100),
    password TEXT, -- encrypted
    currency VARCHAR(10),
    currency_position INT,
    expiry_mode INT,
    expiry_days INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_session (user_id, session_name)
);
```

#### Perubahan yang Diperlukan:
1. **Database Connection**: Buat `include/database.php`
2. **User Management**: 
   - `admin.php` - Login dengan database
   - `admin/users.php` - Halaman manage user (superadmin)
   - `register.php` - Registrasi user baru
3. **Router Management**:
   - `settings/sessions.php` - Filter router berdasarkan `user_id`
   - `include/readcfg.php` - Load router dari database untuk user yang login
4. **Session Management**:
   - Set `$_SESSION['user_id']` saat login
   - Filter semua query router berdasarkan `user_id`

#### Estimasi Waktu: 4-6 jam

---

### **Opsi 2: JSON File per User**

#### Kelebihan:
- ✅ **Tidak Perlu Database**: Tidak perlu setup MySQL
- ✅ **Sederhana**: File-based, mudah dipahami
- ✅ **Portable**: Mudah backup (copy folder)

#### Kekurangan:
- ❌ **Tidak Scalable**: Banyak file = lambat
- ❌ **Race Condition**: Bisa conflict saat write simultan
- ❌ **Tidak Efisien**: Harus baca semua file untuk list user
- ❌ **Security**: Password di JSON (kurang aman)
- ❌ **Query Sulit**: Harus loop semua file untuk search

#### Struktur File:
```
include/
  users/
    user1.json  (data user + routers)
    user2.json  (data user + routers)
    admin.json  (superadmin)
```

#### Format JSON:
```json
{
  "username": "user1",
  "password": "hashed_password",
  "email": "user1@example.com",
  "role": "user",
  "subscription": {
    "package": "monthly",
    "start": "2026-01-01",
    "end": "2026-02-01"
  },
  "routers": [
    {
      "session_name": "ROUTER1",
      "router_name": "My Router",
      "host": "192.168.1.1:8728",
      "username": "admin",
      "password": "encrypted",
      ...
    }
  ]
}
```

#### Perubahan yang Diperlukan:
1. **User Storage**: Buat folder `include/users/`
2. **User Management**: 
   - Load user dari JSON file
   - Write user ke JSON file (dengan file locking)
3. **Router Management**:
   - Load router dari JSON user yang login
   - Save router ke JSON user yang login

#### Estimasi Waktu: 2-3 jam

---

## 🎯 Rekomendasi: **Opsi 1 (Database MySQL)**

### Alasan:
1. **Scalability**: Aplikasi bisa berkembang tanpa masalah
2. **Security**: Password hashing, SQL injection protection
3. **Performance**: Query cepat dengan index
4. **Maintainability**: Mudah di-maintain dan debug
5. **Future-proof**: Bisa ditambah fitur (logs, analytics, dll)

### Implementasi Step-by-Step:

#### **Step 1: Setup Database**
```sql
-- Buat database
CREATE DATABASE mikpay;

-- Import schema
USE mikpay;
SOURCE database/schema.sql;
```

#### **Step 2: Buat Database Connection**
File: `include/database.php`
- PDO connection
- Helper functions untuk user & router

#### **Step 3: Update Login System**
File: `admin.php`
- Check database untuk login
- Set `$_SESSION['user_id']`
- Fallback ke admin lama jika database tidak ada

#### **Step 4: Update Router Management**
File: `settings/sessions.php`
- Filter router berdasarkan `user_id`
- Save router ke database dengan `user_id`

File: `include/readcfg.php`
- Load router dari database untuk user yang login
- Fallback ke `config.php` untuk backward compatibility

#### **Step 5: Buat User Management**
File: `admin/users.php`
- List semua user (superadmin only)
- Tambah/edit/hapus user
- Reset password
- Manage subscription

#### **Step 6: Update Semua Halaman**
- Filter data berdasarkan `user_id`
- Pastikan user hanya akses data miliknya

---

## 🔒 Security Considerations

1. **Password Hashing**: Gunakan `password_hash()` dan `password_verify()`
2. **SQL Injection**: Gunakan prepared statements
3. **Session Security**: 
   - Set `session_regenerate_id()` setelah login
   - Set `httponly` dan `secure` flags
4. **Authorization**: Check `user_id` di setiap query
5. **Input Validation**: Sanitize semua input

---

## 📊 Perbandingan

| Aspek | Database MySQL | JSON File |
|-------|---------------|-----------|
| **Scalability** | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Performance** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Security** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Maintainability** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Setup Complexity** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Backup** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Query Flexibility** | ⭐⭐⭐⭐⭐ | ⭐⭐ |

---

## 🚀 Kesimpulan

**Rekomendasi: Gunakan Database MySQL**

Meskipun setup lebih kompleks, database memberikan:
- ✅ Isolasi data yang proper
- ✅ Scalability untuk masa depan
- ✅ Security yang lebih baik
- ✅ Performance yang optimal
- ✅ Kemudahan maintenance

Jika Anda setuju, saya bisa implementasikan sistem multi-user dengan database MySQL. Semua perubahan akan backward compatible dengan sistem lama.

---

**Last Updated**: 2026-01-31
