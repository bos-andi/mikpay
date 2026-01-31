# 🛠️ Troubleshooting - Masalah Tampilan di VPS

Jika Anda melihat tampilan seperti ini di VPS:
- Router entries menampilkan placeholder text seperti "SESSION_NAME", "SESSION_NAME@@USERNAME", dll
- Data router tidak ter-load dengan benar
- Error atau warning di halaman admin

## 🔍 Diagnosa Masalah

### 1. Validasi Config.php

Akses script validator via browser:
```
http://your-domain.com/deploy/validate-config.php
```

Atau via SSH:
```bash
cd /var/www/mikpay
php deploy/validate-config.php
```

Script ini akan:
- ✅ Cek apakah file config.php ada
- ✅ Validasi format config
- ✅ Tampilkan error detail untuk setiap router
- ✅ Berikan solusi untuk setiap masalah

### 2. Cek File Config.php

```bash
# Cek apakah file ada
ls -la /var/www/mikpay/include/config.php

# Cek permission
ls -l /var/www/mikpay/include/config.php
# Harus: -rw-r--r-- atau -rw-rw-r--

# Cek isi file (preview)
head -30 /var/www/mikpay/include/config.php
```

### 3. Cek PHP Error Log

```bash
# Nginx error log
sudo tail -f /var/log/nginx/mikpay-error.log

# PHP-FPM error log
sudo tail -f /var/log/php7.4-fpm.log

# Atau cek PHP error log umum
sudo tail -f /var/log/php_errors.log
```

### 4. Test Parsing Config

Jalankan script fix parser:
```bash
cd /var/www/mikpay
php deploy/fix-config-parsing.php
```

## 🔧 Solusi Masalah Umum

### Masalah 1: Config.php Format Salah

**Gejala:**
- Router entries menampilkan placeholder text
- Error "Undefined index" di log

**Solusi:**

1. **Cek format config.php harus seperti ini:**
```php
<?php 
if(substr($_SERVER["REQUEST_URI"], -10) == "config.php"){header("Location:./");}; 

// Admin credentials
$data['mikpay'] = array ('1'=>'mikpay<|<mikpay','mikpay>|>aWNlbA==');

// Router configuration - HARUS SATU BARIS PER FIELD
$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',
  'ROUTER1@|@admin',
  'ROUTER1#|#YOUR_PASSWORD_BASE64',
  'ROUTER1%My Router Name',
  'ROUTER1^mydomain.com',
  'ROUTER1&Rp',
  'ROUTER1*10',
  'ROUTER1(1',
  'ROUTER1)',
  'ROUTER1=10',
  'ROUTER1@!@enable'
);
```

2. **Pastikan setiap field di baris terpisah:**
```php
// ❌ SALAH - semua dalam satu baris
$data['ROUTER1'] = array ('1'=>'ROUTER1!192.168.1.1:8728','ROUTER1@|@admin',...);

// ✅ BENAR - setiap field di baris terpisah
$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',
  'ROUTER1@|@admin',
  ...
);
```

3. **Pastikan tidak ada karakter aneh atau encoding issue:**
```bash
# Cek encoding file
file -i /var/www/mikpay/include/config.php
# Harus: UTF-8

# Jika bukan UTF-8, convert:
iconv -f ISO-8859-1 -t UTF-8 /var/www/mikpay/include/config.php > /var/www/mikpay/include/config.php.new
mv /var/www/mikpay/include/config.php.new /var/www/mikpay/include/config.php
```

### Masalah 2: Array $data Tidak Ter-Load

**Gejala:**
- Variable $data kosong atau undefined
- Semua router tidak muncul

**Solusi:**

1. **Cek apakah config.php ter-include dengan benar:**
```bash
# Test include config
cd /var/www/mikpay
php -r "include('./include/config.php'); var_dump(isset(\$data));"
```

2. **Cek path relatif vs absolut:**
```bash
# Di VPS, pastikan working directory benar
cd /var/www/mikpay
pwd
# Harus: /var/www/mikpay
```

3. **Enable error reporting sementara untuk debug:**
Edit `admin.php` baris 20:
```php
// Ganti dari:
error_reporting(0);

// Menjadi:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Masalah 3: Permission Issue

**Gejala:**
- File tidak bisa dibaca
- Error "Permission denied"

**Solusi:**
```bash
# Set permission yang benar
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod 644 /var/www/mikpay/include/config.php
sudo chmod 755 /var/www/mikpay/include/
```

### Masalah 4: Path Issue di VPS

**Gejala:**
- File tidak ditemukan
- Include error

**Solusi:**

1. **Cek apakah path relatif bekerja:**
Di VPS, path relatif mungkin berbeda. Cek di `admin.php`:
```php
// Pastikan path benar
include('./include/config.php');
// atau gunakan absolute path:
include(__DIR__ . '/include/config.php');
```

2. **Test dengan script:**
```bash
cd /var/www/mikpay
php -r "echo __DIR__ . '/include/config.php';"
```

### Masalah 5: Encoding atau Line Ending

**Gejala:**
- Parsing error
- Karakter aneh muncul

**Solusi:**
```bash
# Cek line endings
file /var/www/mikpay/include/config.php

# Convert Windows line endings ke Unix (jika perlu)
dos2unix /var/www/mikpay/include/config.php

# Atau dengan sed
sed -i 's/\r$//' /var/www/mikpay/include/config.php
```

## 📋 Checklist Perbaikan

Setelah memperbaiki, pastikan:

- [ ] File config.php ada di `/var/www/mikpay/include/config.php`
- [ ] Format config.php benar (setiap field di baris terpisah)
- [ ] Permission file benar (644 untuk config.php)
- [ ] Array $data ter-load dengan benar
- [ ] Tidak ada error di PHP error log
- [ ] Router entries muncul dengan benar di admin panel
- [ ] Router name ter-display (bukan placeholder)

## 🧪 Test Setelah Perbaikan

1. **Clear browser cache:**
   - Ctrl+Shift+Delete (Chrome/Firefox)
   - Atau buka dalam incognito mode

2. **Reload halaman:**
   ```
   http://your-domain.com/admin.php?id=sessions
   ```

3. **Cek apakah router muncul:**
   - Router name harus ter-display (bukan "SESSION_NAME")
   - Session name harus ter-display
   - Tombol BUKA, EDIT, DELETE harus muncul

4. **Test koneksi router:**
   - Klik tombol "BUKA" pada router
   - Pastikan bisa connect ke router

## 🆘 Jika Masih Bermasalah

1. **Enable error reporting:**
   Edit `admin.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. **Cek error di browser:**
   - Buka Developer Tools (F12)
   - Tab Console untuk JavaScript errors
   - Tab Network untuk HTTP errors

3. **Cek PHP error:**
   ```bash
   sudo tail -50 /var/log/php7.4-fpm.log
   ```

4. **Test dengan script validator:**
   ```bash
   php deploy/validate-config.php
   ```

5. **Bandingkan dengan config.php.example:**
   ```bash
   diff include/config.php.example include/config.php
   ```

## 📞 Support

Jika masih bermasalah setelah mengikuti panduan ini:
1. Screenshot error message
2. Copy output dari `validate-config.php`
3. Copy relevant lines dari error log
4. Cek format config.php sesuai contoh di DEPLOY_VPS.md

---

**Semoga masalahnya teratasi! 🎉**
