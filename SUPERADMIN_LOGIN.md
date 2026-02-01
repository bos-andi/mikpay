# 🔐 Superadmin Login Guide

## Kredensial Default Superadmin

**Email:** `ndiandie@gmail.com`  
**Password:** `MikPayandidev.id`

## 🚨 Masalah Login di VPS

Jika tidak bisa login di VPS, kemungkinan penyebabnya:

### 1. CSRF Token Error
CSRF token validation mungkin gagal karena session tidak tersimpan dengan benar.

**Solusi:**
```bash
# Pastikan folder session writable
sudo chmod 777 /var/lib/php/sessions
# Atau jika menggunakan custom session path
sudo chmod 777 /var/www/mikpay/sessions
```

### 2. Session Security Check
Session security check mungkin terlalu ketat untuk VPS.

**Solusi Sementara (untuk testing):**
Edit file `superadmin/index.php` dan tambahkan error logging:

```php
// Di bagian verifySuperAdmin
if (verifySuperAdmin($email, $password)) {
    error_log("Superadmin login attempt: SUCCESS - Email: $email");
    $_SESSION['superadmin'] = true;
    $_SESSION['superadmin_email'] = $email;
    regenerateSessionID();
    header('Location: index.php');
    exit;
} else {
    error_log("Superadmin login attempt: FAILED - Email: $email");
    $loginError = 'Email atau password salah!';
}
```

### 3. Check Error Logs
Cek error logs untuk melihat masalah sebenarnya:

```bash
# PHP Error Log
sudo tail -f /var/www/mikpay/logs/php_errors.log

# Nginx Error Log
sudo tail -f /var/log/nginx/mikpay-error.log

# PHP-FPM Error Log
sudo tail -f /var/log/php8.1-fpm.log
```

### 4. File Permissions
Pastikan file permission benar:

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/mikpay

# Fix permissions
sudo chmod -R 755 /var/www/mikpay
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chmod 755 /var/www/mikpay/superadmin
```

### 5. Session Path
Pastikan session path writable:

```bash
# Cek session path di php.ini
php -i | grep session.save_path

# Buat folder session jika belum ada
sudo mkdir -p /var/lib/php/sessions
sudo chmod 777 /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions
```

### 6. Test Login dengan Debug Mode
Tambahkan debug mode sementara di `superadmin/index.php`:

```php
// Tambahkan di bagian awal file (setelah include)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

## 🔧 Quick Fix Script

Buat file `fix-superadmin-login.sh`:

```bash
#!/bin/bash

echo "Fixing Superadmin Login Issues..."

# Fix permissions
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod 600 /var/www/mikpay/include/config.php

# Fix session directory
sudo mkdir -p /var/lib/php/sessions
sudo chmod 777 /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions

# Create logs directory
sudo mkdir -p /var/www/mikpay/logs
sudo chmod 755 /var/www/mikpay/logs
sudo chown www-data:www-data /var/www/mikpay/logs

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Restart Nginx
sudo systemctl restart nginx

echo "Done! Try login again."
```

Jalankan:
```bash
chmod +x fix-superadmin-login.sh
sudo ./fix-superadmin-login.sh
```

## 📝 Checklist Troubleshooting

- [ ] Email dan password sudah benar: `ndiandie@gmail.com` / `MikPayandidev.id`
- [ ] File permissions sudah benar (755 untuk folder, 600 untuk config.php)
- [ ] Session directory writable
- [ ] Error logs sudah dicek
- [ ] PHP-FPM sudah restart
- [ ] Nginx sudah restart
- [ ] Browser cache sudah di-clear
- [ ] CSRF token tidak expired (refresh halaman login)

## 🔍 Debug Steps

1. **Cek apakah form login muncul:**
   ```
   https://your-domain.com/superadmin/
   ```

2. **Cek error di browser console:**
   - Buka Developer Tools (F12)
   - Cek tab Console untuk JavaScript errors
   - Cek tab Network untuk HTTP errors

3. **Test dengan curl:**
   ```bash
   # Get CSRF token
   curl -c cookies.txt https://your-domain.com/superadmin/
   
   # Extract CSRF token dari response
   # Lalu login dengan token tersebut
   ```

4. **Cek session files:**
   ```bash
   ls -la /var/lib/php/sessions/
   # Pastikan ada file session yang dibuat
   ```

## ⚠️ Security Note

**PENTING:** Setelah berhasil login, segera ganti password superadmin dengan mengedit file `include/superadmin.php`:

```php
// Ganti password di line 18
function verifySuperAdmin($email, $password) {
    if ($email === SUPERADMIN_EMAIL && $password === 'PASSWORD_BARU_ANDA') {
        return true;
    }
    return false;
}
```

Dan juga update di line 9:
```php
define('SUPERADMIN_PASSWORD_HASH', password_hash('PASSWORD_BARU_ANDA', PASSWORD_DEFAULT));
```

## 📞 Still Not Working?

Jika masih tidak bisa login setelah mencoba semua solusi di atas:

1. Cek error logs dengan detail
2. Pastikan semua file sudah ter-upload dengan benar
3. Pastikan PHP version compatible (PHP 7.4+)
4. Cek apakah ada firewall yang memblokir
5. Test dengan user biasa dulu untuk memastikan aplikasi berjalan

---

**Default Credentials:**
- Email: `ndiandie@gmail.com`
- Password: `MikPayandidev.id`
