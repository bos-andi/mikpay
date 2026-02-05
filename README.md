# MIKPAY - MikroTik Management System

Sistem manajemen lengkap untuk MikroTik Router yang mencakup Hotspot Management, PPP/PPPoE Management, Billing System, WhatsApp Integration, dan fitur-fitur lengkap lainnya.

## 🚀 Fitur Utama

- **Dashboard** - Statistik dan monitoring real-time
- **Hotspot Management** - Manajemen user, profile, dan aktifitas hotspot
- **PPP/PPPoE Management** - Manajemen PPP secrets, profiles, dan koneksi aktif
- **Billing System** - Sistem tagihan WiFi dengan invoice dan laporan keuangan
- **Voucher System** - Generate dan print voucher dengan template custom
- **WhatsApp Integration** - Integrasi dengan Fonnte API untuk reminder otomatis
- **Multi-Router Support** - Kelola multiple router dalam satu aplikasi
- **Reporting** - Laporan penjualan, keuangan, dan aktivitas user
- **Subscription System** - Sistem langganan dengan berbagai paket
- **Super Admin Panel** - Panel khusus untuk manage user dan subscription

## 📋 Requirements

- **PHP** 7.4 atau lebih tinggi (PHP 8.1+ recommended untuk VPS)
- **Web Server** (Apache/Nginx)
- **MikroTik Router** dengan API enabled (port 8728)
- **MySQL** (opsional, untuk beberapa fitur)

---

## 🚀 Quick Start

### Opsi 1: Deploy ke VPS (Recommended untuk Production)

**Langkah tercepat dengan script otomatis:**
```bash
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/setup-vps.sh
chmod +x setup-vps.sh
sudo ./setup-vps.sh
```

**Atau ikuti panduan manual di bagian [Deployment ke VPS](#-deployment-ke-vps)**

### Opsi 2: Instalasi Lokal (Development/Testing)

**1. Clone Repository**
```bash
git clone https://github.com/bos-andi/mikpay.git
cd mikpay
```

**2. Setup Web Server**

**Apache:** Pastikan mod_rewrite enabled dan arahkan DocumentRoot ke folder mikpay.

**Nginx:** Tambahkan konfigurasi:
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

**3. Set Permissions**
```bash
chmod 755 include/
chmod 644 include/*.php
chmod 755 img/
```

**4. Konfigurasi Router**

Copy template config:
```bash
cp include/config.php.example include/config.php
```

Edit `include/config.php` dan tambahkan router Anda (lihat bagian [Konfigurasi Router](#-konfigurasi-router))

**5. Akses Aplikasi**

Buka browser: `http://localhost/mikpay/admin.php?id=login`

**Default Login:**
- Username: `mikpay`
- Password: `1234`

⚠️ **PENTING:** Ganti password default setelah login pertama kali!

---

## ⚙️ Konfigurasi Router

### Format Konfigurasi

Edit file `include/config.php` dan tambahkan router Anda:

```php
$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',        // IP:PORT
  'ROUTER1@|@admin',                       // Username
  'ROUTER1#|#YOUR_PASSWORD_BASE64',       // Password (Base64)
  'ROUTER1%My Router Name',                // Nama Router
  'ROUTER1^mydomain.com',                  // Domain
  'ROUTER1&Rp',                            // Currency
  'ROUTER1*10',                            // Currency position (10=depan, 20=belakang)
  'ROUTER1(1',                             // Expiry mode (1=remove, 2=notify)
  'ROUTER1)',                              // Reserved
  'ROUTER1=10',                            // Expiry days
  'ROUTER1@!@enable'                       // Status (enable/disable)
);
```

### Encode Password ke Base64

**Cara 1: Via PHP**
```bash
php -r "echo base64_encode('your-password');"
```

**Cara 2: Via Bash**
```bash
echo -n 'your-password' | base64
```

**Cara 3: Via Tool Online**
Kunjungi: https://www.base64encode.org/

**Cara 4: Via Tool Aplikasi**
```bash
cd deploy
php encode-password.php your-password
```

### Catatan Penting

- **SESSION_NAME** harus UNIK (tidak boleh sama dengan router lain)
- **SESSION_NAME** tidak boleh ada spasi (gunakan underscore atau huruf/angka saja)
- **Password HARUS di-encode Base64** sebelum disimpan
- Pastikan **API MikroTik enabled** di router (port 8728)
- Pastikan router **accessible dari server** (bisa via internet atau VPN)

### Test Koneksi

Dari server, test koneksi ke router:
```bash
telnet router-ip 8728
```

Jika bisa connect, berarti router accessible.

---

## 🚀 Deployment ke VPS

Panduan lengkap untuk mengupload aplikasi MIKPAY ke VPS.

### 📋 Prerequisites

Sebelum mulai, pastikan Anda memiliki:
- ✅ VPS dengan Ubuntu 20.04+ atau Debian 11+
- ✅ Akses SSH ke VPS (username dan password/SSH key)
- ✅ Domain name (opsional, bisa pakai IP address)
- ✅ MikroTik Router dengan API enabled (port 8728)
- ✅ Router harus accessible dari VPS (via internet atau VPN)

### 🔧 Langkah 1: Persiapan VPS

#### 1.1 Login ke VPS via SSH

```bash
ssh root@your-vps-ip
# atau
ssh username@your-vps-ip
```

#### 1.2 Update System

```bash
sudo apt update
sudo apt upgrade -y
```

#### 1.3 Install Dependencies

```bash
# Install PHP 8.1 dan ekstensi yang diperlukan
sudo apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip

# Install Nginx
sudo apt install -y nginx

# Install Git (untuk clone dari GitHub)
sudo apt install -y git

# Install tools lainnya
sudo apt install -y curl wget unzip
```

**Catatan:** Jika PHP 8.1 tidak tersedia, gunakan versi terbaru yang tersedia:
```bash
sudo apt install -y php-fpm php-cli php-common php-curl php-mbstring php-xml php-zip
```

#### 1.4 Verifikasi Instalasi

```bash
php -v
nginx -v
git --version
```

### 📥 Langkah 2: Download Aplikasi dari GitHub

#### 2.1 Clone Repository

```bash
# Buat directory untuk aplikasi
sudo mkdir -p /var/www
cd /var/www

# Clone repository
sudo git clone https://github.com/bos-andi/mikpay.git

# Atau jika sudah ada, update saja
cd /var/www/mikpay
sudo git pull origin main
```

#### 2.2 Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/mikpay

# Set permissions
sudo chmod -R 755 /var/www/mikpay

# Set permissions khusus untuk file sensitif
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chmod 600 /var/www/mikpay/include/*.json
```

#### 2.3 Buat Directory yang Diperlukan

```bash
cd /var/www/mikpay
sudo mkdir -p logs voucher/temp img
sudo chown -R www-data:www-data logs voucher img
sudo chmod -R 755 logs voucher img
```

### ⚙️ Langkah 3: Konfigurasi Nginx

#### 3.1 Buat Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/mikpay
```

#### 3.2 Isi dengan Konfigurasi Berikut

```nginx
server {
    listen 80;
    server_name your-domain.com;  # Ganti dengan domain Anda atau IP
    root /var/www/mikpay;
    index index.php admin.php;

    # Log files
    access_log /var/log/nginx/mikpay-access.log;
    error_log /var/log/nginx/mikpay-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handler
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Sesuaikan versi PHP
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(include|logs|deploy|database) {
        deny all;
    }

    # Deny access to config and JSON files
    location ~ \.(json|php\.example)$ {
        deny all;
    }

    # Allow access to specific files
    location ~ ^/(include/config\.php|include/.*\.json)$ {
        deny all;
    }
}
```

**Catatan:** Ganti `php8.1-fpm.sock` dengan versi PHP yang terinstall:
- PHP 8.1: `php8.1-fpm.sock`
- PHP 8.0: `php8.0-fpm.sock`
- PHP 7.4: `php7.4-fpm.sock`

#### 3.3 Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/mikpay /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 🔐 Langkah 4: Konfigurasi Aplikasi

#### 4.1 Setup Config File

```bash
cd /var/www/mikpay/include

# Copy example config
sudo cp config.php.example config.php

# Edit config file
sudo nano config.php
```

#### 4.2 Edit Config.php

Edit file `config.php` dan isi dengan:
1. **Admin credentials** (default: mikpay/1234)
2. **Router configuration** (IP, port, username, password)

**Format lengkap ada di file `config.php.example` yang sudah di-copy.**

**Cara Encode Password ke Base64:**

Di VPS, jalankan salah satu command berikut:
```bash
# Cara 1: Gunakan tool encoder yang sudah disediakan
cd /var/www/mikpay/deploy
php encode-password.php your-password

# Cara 2: Via PHP langsung
php -r "echo base64_encode('your-password');"

# Cara 3: Via Bash
echo -n 'your-password' | base64
```

#### 4.3 Set Permissions untuk Config

```bash
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chown www-data:www-data /var/www/mikpay/include/config.php
```

### 🔒 Langkah 5: Setup SSL (Opsional tapi Direkomendasikan)

#### 5.1 Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

#### 5.2 Generate SSL Certificate

```bash
sudo certbot --nginx -d your-domain.com
```

Certbot akan otomatis:
- Generate SSL certificate
- Update Nginx configuration
- Setup auto-renewal

#### 5.3 Test Auto-Renewal

```bash
sudo certbot renew --dry-run
```

### 🎯 Langkah 6: Setup Cron Job (Untuk Fonnte Auto-Send)

**PENTING:** Cron job memungkinkan auto-send berjalan **otomatis di background** tanpa perlu website diakses atau user login. Setelah setup, reminder WhatsApp akan terkirim otomatis sesuai jadwal.

#### 6.1 Edit Crontab

```bash
sudo crontab -e
```

#### 6.2 Tambahkan Cron Job

Pilih salah satu jadwal berikut:

**Opsi 1: Setiap 5 menit (Recommended untuk testing)**
```bash
# Fonnte auto-send (setiap 5 menit)
*/5 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/www/mikpay/logs/cron.log 2>&1
```

**Opsi 2: Setiap jam (Recommended untuk production)**
```bash
# Fonnte auto-send (setiap jam)
0 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/www/mikpay/logs/cron.log 2>&1
```

**Opsi 3: Setiap 30 menit**
```bash
# Fonnte auto-send (setiap 30 menit)
*/30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/www/mikpay/logs/cron.log 2>&1
```

#### 6.3 Verifikasi Cron

```bash
# Cek cron job yang sudah di-setup
sudo crontab -l

# Test jalankan script manual (untuk debugging)
cd /var/www/mikpay
php cron/fonnte-auto-send.php

# Cek log untuk melihat hasil
tail -f /var/www/mikpay/logs/cron.log
```

#### 6.4 Aktifkan Auto-Send di Settings

Setelah cron job di-setup, aktifkan auto-send di aplikasi:

1. **Login ke aplikasi** (hanya sekali untuk setup)
2. **Settings > WhatsApp API (Fonnte)**
3. **Aktifkan checkbox:**
   - ✅ Auto Send H-3 (3 hari sebelum jatuh tempo)
   - ✅ Auto Send H-0 (Hari ini jatuh tempo)
   - ✅ Auto Send Overdue (Sudah lewat jatuh tempo)
4. **Save settings**

**Catatan:** Setelah diaktifkan, auto-send akan berjalan otomatis sesuai jadwal cron, **tanpa perlu login lagi**.

### ✅ Langkah 7: Testing

#### 7.1 Test Aplikasi

1. Buka browser dan akses: `http://your-domain.com` atau `http://your-vps-ip`
2. Login dengan default credentials: `mikpay` / `1234`
3. Test koneksi ke router
4. Test generate voucher
5. Test report

#### 7.2 Check Logs

```bash
# Nginx error log
sudo tail -f /var/log/nginx/mikpay-error.log

# PHP error log
sudo tail -f /var/www/mikpay/logs/php_errors.log

# Cron log
sudo tail -f /var/www/mikpay/logs/cron.log
```

### 🔄 Langkah 8: Update Aplikasi (Setelah Ada Update)

#### 8.1 Update dari GitHub

```bash
cd /var/www/mikpay
sudo git pull origin main
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chmod 600 /var/www/mikpay/include/*.json
```

#### 8.2 Clear Cache (jika perlu)

```bash
# Clear PHP opcache
sudo systemctl reload php8.1-fpm
```

---

## 📱 WhatsApp API (Fonnte) Setup

Fonnte memungkinkan Anda mengirim reminder WhatsApp otomatis ke pelanggan untuk:
- ✅ Reminder tagihan H-3 (3 hari sebelum jatuh tempo)
- ✅ Reminder tagihan H-0 (hari ini jatuh tempo)
- ✅ Reminder Overdue (sudah lewat jatuh tempo)
- ✅ Manual send dari menu Billing

### ⚙️ Setup Awal

#### 1. Daftar di Fonnte

1. Kunjungi [https://fonnte.com](https://fonnte.com)
2. Daftar dan dapatkan API Token
3. Top up saldo (jika diperlukan)

#### 2. Konfigurasi di MIKPAY

1. **Login ke aplikasi**
2. **Settings > WhatsApp API (Fonnte)**
3. **Isi konfigurasi:**
   - ✅ Aktifkan Fonnte
   - Masukkan API Token dari Fonnte
   - Nama Pengirim (default: MIKPAY WiFi)
   - Atur template pesan (opsional)

4. **Test koneksi:**
   - Klik "Test Connection"
   - Pastikan status "Connected"

### 🤖 Auto-Send di Background

**YA!** Fonnte bisa berjalan di background tanpa perlu login dengan menggunakan **Cron Job**.

**Setup Cron Job sudah dijelaskan di [Langkah 6: Setup Cron Job](#-langkah-6-setup-cron-job-untuk-fonnte-auto-send)**

### 📋 Manual Send (Saat Login)

Selain auto-send, Anda juga bisa kirim manual:

1. **Menu PPP > Billing**
2. **Cari customer** yang ingin dikirim reminder
3. **Klik tombol WhatsApp** (ikon hijau)
4. **Pilih template** (Reminder, Due Today, atau Overdue)
5. **Klik "Kirim via Fonnte"**

### 🔒 Anti-Spam Protection

Fonnte memiliki fitur anti-spam built-in:

- **Rate Limiting:** Delay antar pesan 15-30 detik (random), batas per jam/hari
- **Message Randomization:** Variasi salam dan penutup pesan
- **Unique ID:** Setiap pesan memiliki ID unik

**Konfigurasi Anti-Spam:**

Di Settings > WhatsApp API:
- ✅ Aktifkan Anti-Spam
- Atur delay min/max (detik)
- Atur batas per jam/hari
- ✅ Aktifkan randomize message

### 📊 Monitoring

**Cek Log Auto-Send:**
```bash
# Log cron execution
tail -f /var/log/mikpay-fonnte-cron.log

# Log detail script
tail -f /var/www/mikpay/cron/fonnte-auto-send.log

# Error log
tail -f /var/www/mikpay/logs/fonnte-auto-send-error.log
```

**Cek Quota:**
1. Settings > WhatsApp API
2. Lihat "Remaining Quota" (Hourly, Daily, Last sent)

**Cek Log Pengiriman:**
1. Settings > WhatsApp API
2. Scroll ke "Message Logs"
3. Lihat history pengiriman

---

## 🔐 Super Admin Panel

Panel khusus untuk manage user dan subscription.

### Kredensial Default

**Email:** `ndiandie@gmail.com`  
**Password:** `MikPayandidev.id`

**Akses:** `http://your-domain.com/superadmin/`

⚠️ **PENTING:** Ganti password default setelah login pertama kali!

### Fitur Super Admin

- **Dashboard** - Statistik pembayaran dan user
- **Kelola User** - Tambah, edit, aktifkan/nonaktifkan user
- **Langganan** - Update paket, perpanjang subscription
- **Paket** - Lihat daftar paket yang tersedia
- **Pembayaran** - Approve/reject payment request

### Troubleshooting Login

Jika tidak bisa login di VPS:

**1. Cek File Permissions**
```bash
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chmod 755 /var/www/mikpay/superadmin
```

**2. Cek Session Directory**
```bash
sudo mkdir -p /var/lib/php/sessions
sudo chmod 777 /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions
```

**3. Cek Error Logs**
```bash
sudo tail -f /var/www/mikpay/logs/php_errors.log
sudo tail -f /var/log/nginx/mikpay-error.log
```

**4. Restart Services**
```bash
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

---

## ⚙️ Konfigurasi Awal

### 1. Business Settings

Setelah login, konfigurasi pengaturan bisnis:
- Nama bisnis
- Alamat
- Nomor telepon
- Email
- Rekening bank (untuk billing)

### 2. Subscription

Aplikasi akan otomatis membuat trial 7 hari. Untuk mengaktifkan fitur lengkap, pilih paket langganan:
- **Starter** - Rp 50.000/bulan
- **Professional** - Rp 150.000/bulan
- **Business** - Rp 300.000/bulan
- **Enterprise** - Rp 500.000/bulan

### 3. Upload Logo (Opsional)

1. Settings > Upload Logo
2. Upload logo Anda (PNG format)
3. Atau letakkan di `img/logo-{SESSION_NAME}.png`

---

## 📁 Struktur Folder

```
mikpay/
├── admin.php              # Admin login & settings
├── index.php              # Main application
├── include/               # Core files
│   ├── config.php         # Router configuration (buat dari .example)
│   ├── subscription.php   # Subscription system
│   ├── business_config.php # Business settings
│   └── ...
├── dashboard/             # Dashboard pages
├── hotspot/               # Hotspot management
├── ppp/                   # PPP management & billing
├── settings/              # Settings pages
├── superadmin/            # Super admin panel
├── cron/                  # Cron jobs (auto-send)
├── deploy/                # Deployment scripts
├── css/                   # Stylesheets
├── js/                    # JavaScript files
├── img/                   # Images & logos
└── lang/                  # Language files
```

---

## 🛠️ Troubleshooting

### Masalah: 502 Bad Gateway

**Solusi:**
```bash
# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Check socket path di Nginx config
ls -la /var/run/php/php8.1-fpm.sock
```

### Masalah: Permission Denied

**Solusi:**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/mikpay

# Fix permissions
sudo chmod -R 755 /var/www/mikpay
sudo chmod 600 /var/www/mikpay/include/config.php
```

### Masalah: Router Tidak Connect

**Solusi:**
1. Pastikan API enabled di MikroTik (IP → Services → API)
2. Pastikan firewall allow port 8728
3. Test koneksi dari VPS: `telnet router-ip 8728`
4. Pastikan IP router accessible dari VPS

### Masalah: Voucher Tidak Muncul di Report

**Solusi:**
1. Pastikan generate voucher berhasil
2. Check MikroTik script dengan comment "mikpay"
3. Pastikan format script sesuai

### Masalah: Error "Cannot create file" saat Generate Voucher

**Solusi:**
```bash
# Pastikan directory voucher/temp ada dan writable
sudo mkdir -p /var/www/mikpay/voucher/temp
sudo chown -R www-data:www-data /var/www/mikpay/voucher
sudo chmod -R 755 /var/www/mikpay/voucher
```

### Masalah: Error saat Save Settings atau Create Router

**Solusi:**
```bash
# Pastikan include directory writable
sudo chmod 755 /var/www/mikpay/include
sudo chmod 644 /var/www/mikpay/include/*.php
sudo chmod 600 /var/www/mikpay/include/config.php

# Pastikan bisa write JSON files
sudo touch /var/www/mikpay/include/business_settings.json
sudo touch /var/www/mikpay/include/subscription.json
sudo touch /var/www/mikpay/include/users.json
sudo chown www-data:www-data /var/www/mikpay/include/*.json
sudo chmod 644 /var/www/mikpay/include/*.json
```

### Masalah: Logs Directory Tidak Ada

**Solusi:**
```bash
# Buat logs directory
sudo mkdir -p /var/www/mikpay/logs
sudo chown www-data:www-data /var/www/mikpay/logs
sudo chmod 755 /var/www/mikpay/logs
```

### Masalah: Error 500 Internal Server Error

**Solusi:**
1. Check PHP error log: `sudo tail -f /var/www/mikpay/logs/php_errors.log`
2. Check Nginx error log: `sudo tail -f /var/log/nginx/mikpay-error.log`
3. Pastikan semua directory yang diperlukan sudah dibuat:
   ```bash
   sudo mkdir -p /var/www/mikpay/{logs,voucher/temp,img}
   sudo chown -R www-data:www-data /var/www/mikpay
   sudo chmod -R 755 /var/www/mikpay
   ```

### Masalah: Auto-Send Fonnte Tidak Jalan

**Cek:**
```bash
# Cek cron job
sudo crontab -l

# Test script manual
cd /var/www/mikpay
php cron/fonnte-auto-send.php

# Cek log
tail -f /var/www/mikpay/logs/cron.log
```

**Solusi:**
1. Pastikan Fonnte sudah diaktifkan di Settings
2. Pastikan API Token valid
3. Pastikan cron job sudah di-setup
4. Pastikan customer sudah di-set nomor teleponnya

### Masalah: Logo Tidak Muncul

**Solusi:**
- Upload logo di folder `img/`
- Format: `logo-{SESSION_NAME}.png` untuk logo per router
- Atau `logo.png` untuk logo default
- Recommended size: 200x200px, format PNG

---

## 📝 Data Storage

Aplikasi menyimpan data dalam file JSON di folder `include/`:
- `business_settings.json` - Pengaturan bisnis
- `subscription.json` - Status langganan
- `billing_customers.json` - Data pelanggan billing
- `billing_payments.json` - Data pembayaran
- `fonnte_settings.json` - Pengaturan WhatsApp API
- `users.json` - Data user admin
- `pending_payments.json` - Data payment request

**Catatan:** File-file ini tidak di-commit ke Git karena berisi data user. Aplikasi akan otomatis membuat file-file ini saat pertama kali diakses.

---

## 🔐 Security Recommendations

### Quick Wins (Bisa Dilakukan Sekarang)

1. ✅ **Ganti Default Password**
   - Ubah default password dari `mikpay/1234` ke password yang lebih kuat
   - Wajibkan ganti password saat first login

2. ✅ **Enable HTTPS**
   - Install SSL certificate di VPS
   - Redirect HTTP ke HTTPS

3. ✅ **Set File Permissions**
   ```bash
   chmod 600 include/config.php
   chmod 600 include/*.json
   ```

4. ✅ **Protect Config Files**
   - Pastikan Nginx/Apache sudah dikonfigurasi untuk deny access ke file sensitif
   - File `config.php` dan `*.json` tidak boleh accessible via browser

### Best Practices

- **Ganti password default** segera setelah instalasi
- **Gunakan HTTPS** untuk production
- **Backup data** secara berkala
- **Monitor logs** untuk aktivitas mencurigakan
- **Update aplikasi** secara berkala

---

## 📝 Checklist Deploy

- [ ] VPS sudah di-setup dengan PHP, Nginx, Git
- [ ] Aplikasi sudah di-clone dari GitHub
- [ ] Permissions sudah di-set dengan benar
- [ ] Config.php sudah di-configure
- [ ] Nginx sudah di-configure dan running
- [ ] SSL sudah di-setup (opsional)
- [ ] Cron job sudah di-setup
- [ ] Aplikasi bisa diakses via browser
- [ ] Login berhasil
- [ ] Koneksi router berhasil
- [ ] Generate voucher berhasil
- [ ] Report berfungsi
- [ ] WhatsApp API sudah dikonfigurasi (opsional)
- [ ] Auto-send sudah diaktifkan (opsional)

---

## 🔗 Referensi

- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.php)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [Fonnte API Documentation](https://fonnte.com)

---

## 📞 Support

Jika ada masalah, cek:
1. Error logs di `/var/log/nginx/mikpay-error.log`
2. PHP error logs di `/var/www/mikpay/logs/php_errors.log`
3. Nginx configuration: `sudo nginx -t`
4. PHP-FPM status: `sudo systemctl status php8.1-fpm`

---

## 📄 License

GPL v2 - Lihat file LICENSE untuk detail lengkap

## 👨‍💻 Author

Muhammad Andi

## 🤝 Contributing

Contributions are welcome! Silakan buat issue atau pull request.

---

**Selamat menggunakan MIKPAY! 🎉**
