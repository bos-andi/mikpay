# 🔧 Cara Memperbaiki Tampilan yang Berbeda di VPS

Jika tampilan di VPS berbeda dengan di local, ikuti langkah-langkah berikut:

## 📋 Langkah 1: Update Kode dari GitHub

Masuk ke VPS dan jalankan update script:

```bash
# Masuk ke VPS via SSH
ssh user@your-vps-ip

# Masuk ke direktori aplikasi
cd /var/www/mikpay

# Pull update terbaru dari GitHub
sudo git pull origin main

# Atau gunakan update script
sudo bash deploy/update-vps.sh
```

## 📋 Langkah 2: Pastikan File logo.png Ada

```bash
# Cek apakah logo.png ada
ls -la /var/www/mikpay/img/logo.png

# Jika tidak ada, copy dari local atau download
# File logo.png harus ada di folder img/
```

## 📋 Langkah 3: Clear Cache Browser

Di browser, lakukan hard refresh:
- **Windows/Linux**: `Ctrl + Shift + R` atau `Ctrl + F5`
- **Mac**: `Cmd + Shift + R`

Atau clear cache browser:
- Chrome: Settings → Privacy → Clear browsing data
- Firefox: Settings → Privacy → Clear Data

## 📋 Langkah 4: Clear Cache Server (Jika Menggunakan Cache)

Jika menggunakan cache server seperti Redis atau Memcached:

```bash
# Redis
sudo redis-cli FLUSHALL

# Memcached
sudo service memcached restart
```

## 📋 Langkah 5: Set Permissions yang Benar

```bash
cd /var/www/mikpay

# Set ownership
sudo chown -R www-data:www-data /var/www/mikpay

# Set permissions
sudo chmod -R 755 /var/www/mikpay
sudo chmod -R 775 /var/www/mikpay/include
sudo chmod -R 775 /var/www/mikpay/img
```

## 📋 Langkah 6: Restart Web Server

```bash
# Nginx
sudo systemctl restart nginx

# PHP-FPM
sudo systemctl restart php7.4-fpm
# atau
sudo systemctl restart php8.0-fpm
# atau sesuai versi PHP yang digunakan
```

## 📋 Langkah 7: Cek File CSS/JS

Pastikan file CSS dan JS ter-update:

```bash
# Cek versi CSS
cat /var/www/mikpay/css/dashboard-custom.css | head -20

# Cek apakah ada perubahan terbaru
cd /var/www/mikpay
git log --oneline -5
```

## 🔍 Troubleshooting

### Masalah: Logo tidak muncul
**Solusi:**
```bash
# Pastikan file logo.png ada
ls -la /var/www/mikpay/img/logo.png

# Jika tidak ada, copy dari local
scp img/logo.png user@vps-ip:/var/www/mikpay/img/
```

### Masalah: CSS tidak ter-update
**Solusi:**
- File CSS menggunakan cache buster `?v=<?= time() ?>`
- Hard refresh browser (Ctrl+Shift+R)
- Cek apakah file CSS ter-update di server

### Masalah: Tampilan masih berbeda
**Solusi:**
1. Cek versi PHP di VPS vs Local
2. Cek apakah ada custom CSS/JS di VPS
3. Cek error log:
   ```bash
   sudo tail -f /var/log/nginx/mikpay-error.log
   ```

## ✅ Checklist

- [ ] Git pull sudah dilakukan
- [ ] File logo.png ada di `/var/www/mikpay/img/`
- [ ] Permissions sudah benar
- [ ] Web server sudah di-restart
- [ ] Browser cache sudah di-clear
- [ ] Hard refresh sudah dilakukan

## 📞 Jika Masih Bermasalah

1. Cek error log Nginx:
   ```bash
   sudo tail -50 /var/log/nginx/mikpay-error.log
   ```

2. Cek PHP error log:
   ```bash
   sudo tail -50 /var/log/php7.4-fpm.log
   ```

3. Bandingkan file di local vs VPS:
   ```bash
   # Di VPS
   md5sum /var/www/mikpay/css/dashboard-custom.css
   
   # Di local
   md5sum css/dashboard-custom.css
   ```

---

**Catatan:** Pastikan semua perubahan sudah di-commit dan di-push ke GitHub sebelum melakukan update di VPS.
