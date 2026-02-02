# 🚀 Tutorial Upload MIKPAY ke VPS

Panduan lengkap dan mudah untuk mengupload aplikasi MIKPAY ke VPS.

## 📋 Prerequisites

Sebelum mulai, pastikan Anda memiliki:
- ✅ VPS dengan Ubuntu 20.04+ atau Debian 11+
- ✅ Akses SSH ke VPS (username dan password/SSH key)
- ✅ Domain name (opsional, bisa pakai IP address)
- ✅ MikroTik Router dengan API enabled (port 8728)
- ✅ Router harus accessible dari VPS (via internet atau VPN)

## 🔧 Langkah 1: Persiapan VPS

### 1.1 Login ke VPS via SSH

```bash
ssh root@your-vps-ip
# atau
ssh username@your-vps-ip
```

### 1.2 Update System

```bash
sudo apt update
sudo apt upgrade -y
```

### 1.3 Install Dependencies

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

### 1.4 Verifikasi Instalasi

```bash
php -v
nginx -v
git --version
```

## 📥 Langkah 2: Download Aplikasi dari GitHub

### 2.1 Clone Repository

```bash
# Buat directory untuk aplikasi
sudo mkdir -p /var/www
cd /var/www

# Clone repository (ganti dengan URL repository Anda)
sudo git clone https://github.com/bos-andi/mikpay.git

# Atau jika sudah ada, update saja
cd /var/www/mikpay
sudo git pull origin main
```

### 2.2 Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/mikpay

# Set permissions
sudo chmod -R 755 /var/www/mikpay

# Set permissions khusus untuk file sensitif
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chmod 600 /var/www/mikpay/include/*.json
```

### 2.3 Buat Directory yang Diperlukan

```bash
cd /var/www/mikpay
sudo mkdir -p logs voucher/temp img
sudo chown -R www-data:www-data logs voucher img
sudo chmod -R 755 logs voucher img
```

## ⚙️ Langkah 3: Konfigurasi Nginx

### 3.1 Buat Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/mikpay
```

### 3.2 Isi dengan Konfigurasi Berikut

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

### 3.3 Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/mikpay /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

## 🔐 Langkah 4: Konfigurasi Aplikasi

### 4.1 Setup Config File

```bash
cd /var/www/mikpay/include

# Copy example config
sudo cp config.php.example config.php

# Edit config file
sudo nano config.php
```

### 4.2 Edit Config.php

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

# Cara 4: Via Online tool
# Kunjungi: https://www.base64encode.org/
```

**Contoh penggunaan encoder tool:**
```bash
cd /var/www/mikpay/deploy
php encode-password.php admin123
# Output: admin123 -> YWRtaW4xMjM=
# Gunakan: ROUTER1#|#YWRtaW4xMjM=
```

**Contoh Konfigurasi Router:**
```php
$data['ROUTER1'] = array (
    '1' => 'ROUTER1!192.168.1.1:8728',        // IP:PORT
    'ROUTER1@|@admin',                         // Username
    'ROUTER1#|#YOUR_PASSWORD_BASE64',         // Password (Base64)
    'ROUTER1%My Router Name',                  // Nama router
    'ROUTER1^mydomain.com',                    // Domain
    'ROUTER1&Rp',                              // Currency
    'ROUTER1*10',                              // Currency position
    'ROUTER1(1',                               // Expiry mode
    'ROUTER1)',                                // Reserved
    'ROUTER1=10',                              // Expiry days
    'ROUTER1@!@enable'                         // Status
);
```

**Catatan:**
- SESSION_NAME harus UNIK (tidak boleh sama dengan router lain)
- SESSION_NAME tidak boleh ada spasi
- Password HARUS di-encode Base64
- Pastikan API MikroTik enabled di router (port 8728)

### 4.3 Set Permissions untuk Config

```bash
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chown www-data:www-data /var/www/mikpay/include/config.php
```

## 🔒 Langkah 5: Setup SSL (Opsional tapi Direkomendasikan)

### 5.1 Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 5.2 Generate SSL Certificate

```bash
sudo certbot --nginx -d your-domain.com
```

Certbot akan otomatis:
- Generate SSL certificate
- Update Nginx configuration
- Setup auto-renewal

### 5.3 Test Auto-Renewal

```bash
sudo certbot renew --dry-run
```

## 🎯 Langkah 6: Setup Cron Job (Untuk Fonnte Auto-Send)

### 6.1 Edit Crontab

```bash
sudo crontab -e
```

### 6.2 Tambahkan Cron Job

```bash
# Fonnte auto-send (setiap 5 menit)
*/5 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/www/mikpay/logs/cron.log 2>&1
```

### 6.3 Verifikasi Cron

```bash
sudo crontab -l
```

## ✅ Langkah 7: Testing

### 7.1 Test Aplikasi

1. Buka browser dan akses: `http://your-domain.com` atau `http://your-vps-ip`
2. Login dengan default credentials: `mikpay` / `1234`
3. Test koneksi ke router
4. Test generate voucher
5. Test report

### 7.2 Check Logs

```bash
# Nginx error log
sudo tail -f /var/log/nginx/mikpay-error.log

# PHP error log
sudo tail -f /var/www/mikpay/logs/php_errors.log

# Cron log
sudo tail -f /var/www/mikpay/logs/cron.log
```

## 🔄 Langkah 8: Update Aplikasi (Setelah Ada Update)

### 8.1 Update dari GitHub

```bash
cd /var/www/mikpay
sudo git pull origin main
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod 600 /var/www/mikpay/include/config.php
sudo chmod 600 /var/www/mikpay/include/*.json
```

### 8.2 Clear Cache (jika perlu)

```bash
# Clear PHP opcache
sudo systemctl reload php8.1-fpm
```

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

### Masalah: Error "Cannot create file" atau "Permission denied" saat Generate Voucher

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

## 🔗 Referensi

- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.php)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)

## 📞 Support

Jika ada masalah, cek:
1. Error logs di `/var/log/nginx/mikpay-error.log`
2. PHP error logs di `/var/www/mikpay/logs/php_errors.log`
3. Nginx configuration: `sudo nginx -t`
4. PHP-FPM status: `sudo systemctl status php8.1-fpm`

---

**Selamat! Aplikasi MIKPAY sudah berjalan di VPS Anda! 🎉**

 chown -R www-data:www-data /var/www/itsnet.mikpay.link
 chmod -R 755 /var/www/itsnet.mikpay.link
 chmod 600 /var/www/itsnet.mikpay.link/include/config.php
 chmod 600 /var/www/itsnet.mikpay.link/include/*.json