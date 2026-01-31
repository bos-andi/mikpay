# 🚀 Panduan Deploy MIKPAY ke VPS

Panduan lengkap untuk mengupload dan menjalankan MIKPAY di VPS (Ubuntu/Debian).

## 📋 Prerequisites

Sebelum mulai, pastikan Anda memiliki:
- [ ] VPS dengan Ubuntu 20.04+ atau Debian 11+
- [ ] Akses SSH ke VPS
- [ ] Domain name (opsional, bisa pakai IP)
- [ ] MikroTik Router dengan API enabled (port 8728)
- [ ] Router harus accessible dari VPS (bisa via internet atau VPN)

## 🔧 Langkah 1: Persiapan VPS

### 1.1 Update System

```bash
sudo apt update
sudo apt upgrade -y
```

### 1.2 Install Dependencies

```bash
# Install PHP dan ekstensi yang diperlukan
sudo apt install -y php7.4-fpm php7.4-cli php7.4-common php7.4-curl php7.4-json php7.4-mbstring php7.4-xml php7.4-zip

# Install Nginx
sudo apt install -y nginx

# Install Git (untuk clone dari GitHub)
sudo apt install -y git

# Install tools lainnya
sudo apt install -y curl wget unzip
```

### 1.3 Verifikasi PHP

```bash
php -v
# Harus menampilkan PHP 7.4 atau lebih tinggi
```

## 📥 Langkah 2: Download Aplikasi dari GitHub

### 2.1 Clone Repository

```bash
# Masuk ke directory web server
cd /var/www

# Clone repository
sudo git clone https://github.com/bos-andi/mikpay.git

# Atau jika sudah ada, pull update terbaru
cd /var/www/mikpay
sudo git pull origin main
```

### 2.2 Set Ownership dan Permissions

```bash
# Set ownership ke user www-data
sudo chown -R www-data:www-data /var/www/mikpay

# Set permissions
sudo chmod -R 755 /var/www/mikpay
sudo chmod -R 775 /var/www/mikpay/include
sudo chmod -R 775 /var/www/mikpay/img
```

## ⚙️ Langkah 3: Konfigurasi Nginx

### 3.1 Buat File Konfigurasi Nginx

```bash
sudo nano /etc/nginx/sites-available/mikpay
```

Salin konfigurasi berikut (sesuaikan domain/IP):

```nginx
server {
    listen 80;
    server_name your-domain.com;  # Ganti dengan domain atau IP VPS Anda
    
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
    
    # PHP processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /include/config\.php$ {
        deny all;
    }
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3.2 Aktifkan Site

```bash
# Buat symlink
sudo ln -s /etc/nginx/sites-available/mikpay /etc/nginx/sites-enabled/

# Test konfigurasi
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 3.3 Enable dan Start Services

```bash
# Enable services untuk auto-start
sudo systemctl enable nginx
sudo systemctl enable php7.4-fpm

# Start services
sudo systemctl start nginx
sudo systemctl start php7.4-fpm

# Cek status
sudo systemctl status nginx
sudo systemctl status php7.4-fpm
```

## 🔐 Langkah 4: Konfigurasi Router

### 4.1 Buat File Config

```bash
cd /var/www/mikpay/include
sudo cp config.php.example config.php
sudo nano config.php
```

### 4.2 Edit Konfigurasi Router

Edit file `config.php` dan tambahkan router Anda:

```php
<?php 
if(substr($_SERVER["REQUEST_URI"], -10) == "config.php"){header("Location:./");}; 

// Admin credentials (default: mikpay/mikpay)
$data['mikpay'] = array ('1'=>'mikpay<|<mikpay','mikpay>|>aWNlbA==');

// Router Anda
$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',           // IP Router (bisa IP public jika router accessible dari internet)
  'ROUTER1@|@admin',                         // Username router
  'ROUTER1#|#YOUR_PASSWORD_BASE64',          // Password Base64 encoded
  'ROUTER1%My Router Name',                   // Nama router
  'ROUTER1^mydomain.com',                    // Domain
  'ROUTER1&Rp',                              // Currency
  'ROUTER1*10',                              // Currency position
  'ROUTER1(1',                               // Expiry mode
  'ROUTER1)',                                // 
  'ROUTER1=10',                              // Expiry days
  'ROUTER1@!@enable'                         // Status: enable
);
```

**Cara Encode Password ke Base64:**

```bash
# Di VPS, jalankan:
echo -n 'your_password' | base64

# Atau gunakan PHP:
php -r "echo base64_encode('your_password');"
```

### 4.3 Set Permission Config File

```bash
sudo chmod 644 /var/www/mikpay/include/config.php
sudo chown www-data:www-data /var/www/mikpay/include/config.php
```

## 🔒 Langkah 5: Setup SSL/HTTPS (Opsional tapi Disarankan)

### 5.1 Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 5.2 Generate SSL Certificate

```bash
# Ganti your-domain.com dengan domain Anda
sudo certbot --nginx -d your-domain.com

# Ikuti instruksi di layar
# Certbot akan otomatis mengkonfigurasi Nginx untuk HTTPS
```

### 5.3 Auto-renewal SSL

Certbot otomatis setup auto-renewal. Test dengan:

```bash
sudo certbot renew --dry-run
```

## 🔥 Langkah 6: Konfigurasi Firewall

### 6.1 Setup UFW (Uncomplicated Firewall)

```bash
# Allow SSH (penting! jangan skip ini)
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Cek status
sudo ufw status
```

## ✅ Langkah 7: Verifikasi Instalasi

### 7.1 Test Akses Web

Buka browser dan akses:
```
http://your-vps-ip
atau
http://your-domain.com
```

### 7.2 Login Default

- Username: `mikpay`
- Password: `mikpay`

**⚠️ PENTING: Segera ganti password setelah login pertama!**

### 7.3 Test Koneksi Router

1. Login ke aplikasi
2. Pilih router dari dropdown
3. Pastikan dashboard muncul dengan data dari router

## 🔄 Langkah 8: Setup Auto-Update (Opsional)

Buat script untuk auto-update dari GitHub:

```bash
sudo nano /usr/local/bin/mikpay-update.sh
```

Salin script berikut:

```bash
#!/bin/bash
cd /var/www/mikpay
sudo -u www-data git pull origin main
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod -R 775 /var/www/mikpay/include
sudo systemctl reload nginx
echo "MIKPAY updated successfully!"
```

Beri permission execute:

```bash
sudo chmod +x /usr/local/bin/mikpay-update.sh
```

Jalankan update:
```bash
sudo /usr/local/bin/mikpay-update.sh
```

## 🛠️ Troubleshooting

### Error 502 Bad Gateway

**Penyebab:** PHP-FPM tidak running atau socket salah

**Solusi:**
```bash
# Cek status PHP-FPM
sudo systemctl status php7.4-fpm

# Restart PHP-FPM
sudo systemctl restart php7.4-fpm

# Cek socket path di nginx config
ls -la /var/run/php/php7.4-fpm.sock
```

### Error 403 Forbidden

**Penyebab:** Permission atau ownership salah

**Solusi:**
```bash
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod -R 775 /var/www/mikpay/include
```

### Tidak Bisa Connect ke Router

**Penyebab:** Router tidak accessible dari VPS

**Solusi:**
1. Pastikan router IP accessible dari VPS:
   ```bash
   telnet ROUTER_IP 8728
   ```

2. Jika router di jaringan lokal, setup VPN atau port forwarding

3. Pastikan API MikroTik enabled di router:
   ```
   /ip service enable api
   /ip service set api port=8728
   ```

### File JSON Tidak Terbuat

**Penyebab:** Folder include tidak writable

**Solusi:**
```bash
sudo chmod -R 775 /var/www/mikpay/include
sudo chown -R www-data:www-data /var/www/mikpay/include
```

### Nginx Error: Permission Denied

**Penyebab:** SELinux atau AppArmor blocking

**Solusi:**
```bash
# Untuk AppArmor (jika ada)
sudo aa-complain /usr/sbin/nginx

# Atau disable AppArmor untuk Nginx
sudo ln -s /etc/apparmor.d/usr.sbin.nginx /etc/apparmor.d/disable/
sudo apparmor_parser -R /etc/apparmor.d/usr.sbin.nginx
```

## 📊 Monitoring & Maintenance

### Cek Log Files

```bash
# Nginx access log
sudo tail -f /var/log/nginx/mikpay-access.log

# Nginx error log
sudo tail -f /var/log/nginx/mikpay-error.log

# PHP-FPM log
sudo tail -f /var/log/php7.4-fpm.log
```

### Backup Data

Buat script backup:

```bash
sudo nano /usr/local/bin/mikpay-backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/root/backups/mikpay"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup include folder (berisi data JSON)
tar -czf $BACKUP_DIR/mikpay_$DATE.tar.gz /var/www/mikpay/include/*.json /var/www/mikpay/include/config.php

# Hapus backup lebih dari 7 hari
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/mikpay_$DATE.tar.gz"
```

Beri permission:
```bash
sudo chmod +x /usr/local/bin/mikpay-backup.sh
```

Setup cron untuk auto-backup (setiap hari jam 2 pagi):
```bash
sudo crontab -e
# Tambahkan:
0 2 * * * /usr/local/bin/mikpay-backup.sh
```

## 🎯 Checklist Final

Setelah deploy, pastikan:

- [ ] Aplikasi bisa diakses via browser
- [ ] Bisa login dengan default credentials
- [ ] Bisa connect ke router
- [ ] Dashboard menampilkan data
- [ ] File JSON terbuat otomatis di folder include/
- [ ] SSL/HTTPS aktif (jika pakai domain)
- [ ] Firewall configured
- [ ] Password admin sudah diganti
- [ ] Backup script sudah setup

## 📞 Support

Jika ada masalah, cek:
1. Log files (Nginx, PHP-FPM)
2. File permissions
3. Router connectivity
4. Firewall rules

---

**Selamat! MIKPAY sudah berjalan di VPS Anda! 🎉**
