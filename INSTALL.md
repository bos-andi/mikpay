# 📦 Panduan Instalasi MIKPAY

Panduan lengkap untuk menginstall MIKPAY dari GitHub.

## ✅ Checklist Sebelum Install

- [ ] PHP 7.4+ terinstall
- [ ] Web Server (Apache/Nginx) running
- [ ] MikroTik Router dengan API enabled (port 8728)
- [ ] Akses ke router (IP, username, password)

## 🚀 Langkah Instalasi

### 1. Download/Clone Repository

```bash
git clone https://github.com/yourusername/mikpay.git
cd mikpay
```

Atau download ZIP dari GitHub dan extract.

### 2. Setup Web Server

#### Apache
Pastikan mod_rewrite enabled dan arahkan DocumentRoot ke folder mikpay.

#### Nginx
Tambahkan konfigurasi:
```nginx
server {
    listen 80;
    server_name mikpay.local;
    root /path/to/mikpay;
    index index.php admin.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 3. Set Permissions

```bash
# Linux/Mac
chmod 755 include/
chmod 644 include/*.php
chmod 755 img/
chmod 644 img/*.png

# Windows - Pastikan folder include/ writable
```

### 4. Konfigurasi Router

**PENTING:** File `config.php` tidak ada di repository karena berisi kredensial.

**Langkah:**
1. Copy template:
   ```bash
   cp include/config.php.example include/config.php
   ```

2. Edit `include/config.php` dan tambahkan router Anda:
   ```php
   <?php 
   if(substr($_SERVER["REQUEST_URI"], -10) == "config.php"){header("Location:./");}; 
   
   // Admin credentials (default: mikpay/mikpay)
   $data['mikpay'] = array ('1'=>'mikpay<|<mikpay','mikpay>|>aWNlbA==');
   
   // Router Anda
   $data['ROUTER1'] = array (
     '1'=>'ROUTER1!192.168.1.1:8728',           // IP:PORT
     'ROUTER1@|@admin',                         // Username
     'ROUTER1#|#YOUR_PASSWORD_BASE64',          // Password (Base64)
     'ROUTER1%My Router Name',                   // Nama Router
     'ROUTER1^mydomain.com',                    // Domain
     'ROUTER1&Rp',                              // Currency
     'ROUTER1*10',                              // Currency position
     'ROUTER1(1',                               // Expiry mode
     'ROUTER1)',                                // 
     'ROUTER1=10',                              // Expiry days
     'ROUTER1@!@enable'                         // Status
   );
   ```

3. **Encode Password ke Base64:**
   ```php
   // Gunakan PHP
   echo base64_encode('your_password');
   
   // Atau online tool: https://www.base64encode.org/
   ```

### 5. Akses Aplikasi

Buka browser:
```
http://localhost/mikpay/admin.php?id=login
```

**Default Login:**
- Username: `mikpay`
- Password: `mikpay`

⚠️ **SEGERA GANTI PASSWORD SETELAH LOGIN PERTAMA!**

### 6. Konfigurasi Awal

#### A. Ganti Password Admin
1. Login dengan default credentials
2. Settings > Admin Settings
3. Ganti username dan password

#### B. Setup Business Settings
1. Settings > Session Settings
2. Isi:
   - Business Name
   - Address
   - Phone
   - Email
   - Bank Account (untuk billing)

#### C. Upload Logo (Opsional)
1. Settings > Upload Logo
2. Upload logo Anda (PNG format)
3. Atau letakkan di `img/logo-{SESSION_NAME}.png`

### 7. Test Koneksi Router

1. Pilih router dari dropdown di navbar
2. Atau akses: `http://localhost/mikpay/?session=ROUTER1`
3. Jika berhasil, akan muncul dashboard

## 🔧 Troubleshooting

### Error: Cannot connect to router
**Solusi:**
- Cek IP dan port router (default: 8728)
- Pastikan API MikroTik enabled di router
- Cek firewall/router settings
- Test koneksi: `telnet ROUTER_IP 8728`

### Error: Permission denied
**Solusi:**
```bash
chmod 755 include/
chmod 644 include/*.php
```

### File config.php tidak ditemukan
**Solusi:**
- Pastikan sudah copy dari `config.php.example`
- Cek file permissions

### Logo tidak muncul
**Solusi:**
- Upload logo di Settings > Upload Logo
- Atau letakkan file `logo-{SESSION}.png` di folder `img/`
- Format: PNG, recommended size: 200x200px

### Subscription expired
**Solusi:**
- Aplikasi default trial 7 hari
- Untuk extend, pilih paket di menu Subscription
- Atau edit `include/subscription.json` (tidak disarankan)

## 📝 File yang Auto-Created

Aplikasi akan otomatis membuat file-file berikut saat pertama kali diakses:
- `include/business_settings.json` - Business settings (default: MIKPAY)
- `include/subscription.json` - Subscription (default: Trial 7 hari)
- `include/billing_customers.json` - Customer data (kosong)
- `include/billing_payments.json` - Payment data (kosong)
- `include/fonnte_settings.json` - WhatsApp API settings (kosong)
- `include/users.json` - User data (kosong)

**Tidak perlu membuat manual!**

## ✅ Verifikasi Instalasi

Setelah install, pastikan:
- [ ] Bisa login dengan default credentials
- [ ] Bisa connect ke router
- [ ] Dashboard muncul dengan data
- [ ] Folder `include/` writable
- [ ] File JSON terbuat otomatis

## 🎉 Selesai!

Aplikasi siap digunakan. Selamat menggunakan MIKPAY!

Untuk dokumentasi lengkap, lihat [README.md](README.md)
