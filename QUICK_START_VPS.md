# ⚡ Quick Start - Deploy MIKPAY ke VPS

Panduan cepat untuk deploy MIKPAY ke VPS dalam 5 menit!

## 🚀 Cara Cepat (Otomatis)

### 1. Login ke VPS via SSH

```bash
ssh root@your-vps-ip
```

### 2. Jalankan Script Setup Otomatis

```bash
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/setup-vps.sh
chmod +x setup-vps.sh
sudo ./setup-vps.sh
```

Script akan:
- ✅ Install semua dependencies (PHP, Nginx, Git)
- ✅ Clone repository dari GitHub
- ✅ Setup konfigurasi Nginx
- ✅ Set permissions yang benar
- ✅ Enable dan start services
- ✅ Setup firewall

### 3. Konfigurasi Router

Edit file config:
```bash
nano /var/www/mikpay/include/config.php
```

Tambahkan router Anda (lihat contoh di file).

**Encode password ke Base64:**
```bash
echo -n 'your_password' | base64
```

### 4. Akses Aplikasi

Buka browser:
```
http://your-vps-ip
```

**Login default:**
- Username: `mikpay`
- Password: `mikpay`

⚠️ **Ganti password segera setelah login!**

## 📝 Update Aplikasi

Untuk update ke versi terbaru:

```bash
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/update-vps.sh
chmod +x update-vps.sh
sudo ./update-vps.sh
```

## 💾 Backup Data

Untuk backup data:

```bash
wget https://raw.githubusercontent.com/bos-andi/mikpay/main/deploy/backup-vps.sh
chmod +x backup-vps.sh
sudo ./backup-vps.sh
```

## 🔒 Setup SSL/HTTPS (Opsional)

Jika punya domain:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## ✅ Checklist

Setelah deploy, pastikan:
- [ ] Aplikasi bisa diakses
- [ ] Bisa login
- [ ] Bisa connect ke router
- [ ] Password sudah diganti
- [ ] SSL aktif (jika pakai domain)

## 📚 Dokumentasi Lengkap

Untuk panduan detail, lihat:
- **[DEPLOY_VPS.md](DEPLOY_VPS.md)** - Panduan lengkap step-by-step
- **[deploy/README.md](deploy/README.md)** - Dokumentasi script deployment

## 🆘 Troubleshooting

### Error 502 Bad Gateway
```bash
sudo systemctl restart php7.4-fpm
sudo systemctl restart nginx
```

### Permission Error
```bash
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 775 /var/www/mikpay/include
```

### Tidak Bisa Connect Router
- Pastikan router IP accessible dari VPS
- Cek firewall router
- Pastikan API MikroTik enabled (port 8728)

---

**Selamat! MIKPAY sudah berjalan di VPS Anda! 🎉**
