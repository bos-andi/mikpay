# 🛡️ Security Recommendations - MIKPAY

## ⚠️ Hasil Audit Keamanan

Setelah melakukan audit keamanan, ditemukan beberapa **vulnerability** yang perlu diperbaiki:

### 🔴 CRITICAL Issues

1. **Password Storage - Base64 (BUKAN HASH)**
   - Password disimpan Base64, mudah di-decode
   - Lokasi: `lib/routeros_api.class.php` fungsi `encrypt()` dan `decrypt()`
   - Dampak: Password bisa langsung dibaca dari file

2. **No CSRF Protection**
   - Semua form tidak ada CSRF token
   - Dampak: Bisa di-exploit via phishing

3. **Session Security**
   - Tidak ada session regeneration
   - Tidak ada session timeout
   - Dampak: Session hijacking risk

### 🟡 HIGH Issues

4. **Hardcoded Credentials**
   - Superadmin password di kode: `MikPayandidev.id`
   - Default password: `mikpay/1234`
   - Dampak: Jika source code ter-expose, password langsung diketahui

5. **Input Validation**
   - Banyak input langsung digunakan tanpa validasi
   - Dampak: Path traversal, injection attacks

6. **File Upload Security**
   - Validasi bisa diperkuat
   - Dampak: Malicious file upload

### 🟢 MEDIUM Issues

7. **XSS Protection**
   - Sebagian sudah pakai `htmlspecialchars()`, tapi tidak semua
   - Perlu audit lebih lanjut

8. **Error Disclosure**
   - `error_reporting(0)` menyembunyikan error
   - Perlu logging yang proper

## 📋 Rekomendasi Perbaikan

### 1. Password Security (PRIORITAS TINGGI)

**Masalah:** Password disimpan Base64, bukan hash.

**Solusi:**
```php
// Buat file: include/password_security.php
function secureHashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifySecurePassword($password, $hash) {
    return password_verify($password, $hash);
}

// Untuk backward compatibility, buat wrapper:
function encrypt($string, $key=128) {
    // Tetap gunakan Base64 untuk router password (karena perlu decrypt)
    // Tapi untuk user password, gunakan hash
    return base64_encode($string);
}

function decrypt($string, $key=128) {
    return base64_decode($string);
}
```

**Action:**
- Migrasi password user ke hash
- Update login function untuk verify hash
- Tetap gunakan Base64 untuk router password (karena perlu decrypt untuk API)

### 2. CSRF Protection (PRIORITAS TINGGI)

**Solusi:**
```php
// Buat file: include/csrf.php
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

**Implementasi:**
- Tambahkan token di semua form
- Validasi token di semua POST handler

### 3. Session Security (PRIORITAS TINGGI)

**Solusi:**
```php
// Set session security di admin.php setelah login:
session_regenerate_id(true);
$_SESSION['last_activity'] = time();
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

// Check di setiap request:
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_destroy();
    header("Location: ./admin.php?id=login&msg=timeout");
    exit;
}

if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    session_destroy();
    header("Location: ./admin.php?id=login&msg=security");
    exit;
}

$_SESSION['last_activity'] = time();
```

### 4. Input Validation (PRIORITAS TINGGI)

**Solusi:**
```php
// Buat file: include/input_validation.php
function sanitizeInput($input, $type = 'string') {
    switch($type) {
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'int':
            return intval($input);
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

function validateSessionName($session) {
    // Only allow alphanumeric and underscore
    return preg_match('/^[a-zA-Z0-9_]+$/', $session);
}
```

### 5. File Upload Security (PEDIUM)

**Perbaikan di settings/uplogo.php:**
```php
// Validasi MIME type (bukan hanya extension)
$allowedMimeTypes = ['image/png'];
$fileMimeType = mime_content_type($_FILES["UploadLogo"]["tmp_name"]);
if (!in_array($fileMimeType, $allowedMimeTypes)) {
    $uploadOk = 0;
}

// Rename file setelah upload
$newFileName = 'logo-' . $session . '-' . time() . '.png';
$logo_file = $logo_dir . $newFileName;
```

### 6. Security Headers (MEDIUM)

**Tambahkan di .htaccess atau Nginx config:**
```apache
# Security Headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Content-Security-Policy "default-src 'self'"
```

### 7. Rate Limiting (MEDIUM)

**Implementasi untuk login:**
```php
// Limit login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
    // Block untuk 15 menit
}
```

## 🔐 Quick Wins (Bisa Dilakukan Sekarang)

1. ✅ **Ganti Default Password**
   - Ubah default password dari `mikpay/1234` ke password yang lebih kuat
   - Wajibkan ganti password saat first login

2. ✅ **Hapus Hardcoded Password**
   - Pindahkan superadmin password ke environment variable atau file terpisah

3. ✅ **Enable HTTPS**
   - Install SSL certificate di VPS
   - Redirect HTTP ke HTTPS

4. ✅ **Set File Permissions**
   ```bash
   chmod 600 include/config.php
   chmod 600 include/users.json
   chmod 600 include/*.json
   ```

5. ✅ **Add .htaccess Protection**
   ```apache
   # Protect config files
   <Files "config.php">
       Order Allow,Deny
       Deny from all
   </Files>
   
   <Files "*.json">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

## 📊 Security Score

**Current Score: 4/10** ⚠️

**Target Score: 8/10** ✅

## 🎯 Action Plan

### Week 1 (Critical)
- [ ] Implement password hashing untuk user passwords
- [ ] Add CSRF protection
- [ ] Improve session security

### Week 2 (High)
- [ ] Input validation
- [ ] Remove hardcoded credentials
- [ ] File upload security

### Week 3 (Medium)
- [ ] Security headers
- [ ] Rate limiting
- [ ] Error logging

## 📚 Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [OWASP CSRF Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)

---

**Catatan:** Perbaikan keamanan harus dilakukan secara bertahap dan di-test setelah setiap perubahan.
