# 🚀 Script Deployment MIKPAY untuk VPS

Script-script ini memudahkan deployment dan maintenance MIKPAY di VPS.

## 📁 File Script

### 1. `setup-vps.sh`
Script untuk setup awal MIKPAY di VPS baru.

**Cara penggunaan:**
```bash
# Download script
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/setup-vps.sh

# Beri permission execute
chmod +x setup-vps.sh

# Jalankan sebagai root
sudo ./setup-vps.sh
```

**Apa yang dilakukan script:**
- Update system packages
- Install PHP 7.4 dan ekstensi yang diperlukan
- Install Nginx
- Clone repository dari GitHub
- Set permissions yang benar
- Setup konfigurasi Nginx
- Enable dan start services
- Setup firewall (UFW)

### 2. `update-vps.sh`
Script untuk update MIKPAY ke versi terbaru dari GitHub.

**Cara penggunaan:**
```bash
# Download script
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/update-vps.sh

# Beri permission execute
chmod +x update-vps.sh

# Jalankan sebagai root
sudo ./update-vps.sh
```

**Apa yang dilakukan script:**
- Backup config.php
- Pull update terbaru dari GitHub
- Set permissions
- Restore config.php jika perlu
- Reload Nginx

### 3. `backup-vps.sh`
Script untuk backup data MIKPAY.

**Cara penggunaan:**
```bash
# Download script
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/backup-vps.sh

# Beri permission execute
chmod +x backup-vps.sh

# Jalankan sebagai root
sudo ./backup-vps.sh
```

**Apa yang dilakukan script:**
- Backup semua file JSON dan config.php
- Simpan di `/root/backups/mikpay/`
- Hapus backup lebih dari 7 hari
- Tampilkan list backup yang ada

## ⚙️ Setup Auto-Update dengan Cron

Untuk auto-update setiap hari jam 2 pagi:

```bash
sudo crontab -e
```

Tambahkan:
```cron
0 2 * * * /usr/local/bin/mikpay-update.sh
```

## 💾 Setup Auto-Backup dengan Cron

Untuk auto-backup setiap hari jam 3 pagi:

```bash
sudo crontab -e
```

Tambahkan:
```cron
0 3 * * * /usr/local/bin/mikpay-backup.sh
```

## 📝 Manual Installation Scripts

Jika ingin install script ke `/usr/local/bin/`:

```bash
# Copy scripts
sudo cp setup-vps.sh /usr/local/bin/mikpay-setup
sudo cp update-vps.sh /usr/local/bin/mikpay-update
sudo cp backup-vps.sh /usr/local/bin/mikpay-backup

# Set permissions
sudo chmod +x /usr/local/bin/mikpay-*
```

Kemudian bisa dipanggil langsung:
```bash
sudo mikpay-update
sudo mikpay-backup
```

## ⚠️ Catatan Penting

1. **Selalu backup sebelum update!**
   ```bash
   sudo ./backup-vps.sh
   sudo ./update-vps.sh
   ```

2. **Jangan hapus config.php!**
   - Script update akan backup otomatis
   - Tapi lebih baik manual backup dulu

3. **Cek log setelah update:**
   ```bash
   sudo tail -f /var/log/nginx/mikpay-error.log
   ```

4. **Test aplikasi setelah update:**
   - Buka browser dan cek apakah aplikasi masih berjalan
   - Test login dan koneksi router

## 🔧 Troubleshooting

### Script tidak bisa dijalankan
```bash
chmod +x script-name.sh
```

### Permission denied
Pastikan menjalankan dengan `sudo`:
```bash
sudo ./script-name.sh
```

### Git pull error
Pastikan folder `/var/www/mikpay` adalah git repository:
```bash
cd /var/www/mikpay
git remote -v
```

### Backup folder tidak ada
Script akan membuat folder otomatis, tapi jika error:
```bash
sudo mkdir -p /root/backups/mikpay
sudo chmod 755 /root/backups/mikpay
```

---

**Selamat menggunakan script deployment! 🎉**
