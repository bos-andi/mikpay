# 🤖 MIKPAY Cron Jobs

Script-script untuk menjalankan tugas otomatis di background (tanpa perlu login).

## 📋 Daftar Script

### 1. `fonnte-auto-send.php`
Script untuk mengirim reminder WhatsApp otomatis via Fonnte API.

**Fitur:**
- ✅ Auto-send reminder H-3 (3 hari sebelum jatuh tempo)
- ✅ Auto-send reminder H-0 (hari ini jatuh tempo)
- ✅ Auto-send reminder Overdue (sudah lewat jatuh tempo)
- ✅ Anti-spam protection (rate limiting & delay)
- ✅ Logging lengkap

## 🚀 Setup Cron Job

### Langkah 1: Set Permission

```bash
cd /var/www/mikpay
chmod +x cron/fonnte-auto-send.php
chmod 755 cron/
```

### Langkah 2: Test Script Manual

Test script dulu sebelum setup cron:

```bash
cd /var/www/mikpay
php cron/fonnte-auto-send.php
```

Jika berhasil, akan muncul log seperti:
```
[2026-01-31 10:00:00] === Fonnte Auto-Send Reminder Started ===
[2026-01-31 10:00:00] Fonnte aktif. Memulai proses auto-send...
[2026-01-31 10:00:00] Mengecek reminder H-3...
...
```

### Langkah 3: Setup Cron Job

Edit crontab:

```bash
sudo crontab -e
```

**Pilihan 1: Jalankan setiap jam (recommended)**
```cron
# Fonnte auto-send reminder (setiap jam)
0 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

**Pilihan 2: Jalankan setiap 30 menit (lebih sering)**
```cron
# Fonnte auto-send reminder (setiap 30 menit)
*/30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

**Pilihan 3: Jalankan setiap hari jam 8 pagi dan 6 sore**
```cron
# Fonnte auto-send reminder (2x sehari)
0 8,18 * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

### Langkah 4: Cek Log

```bash
# Log cron execution
tail -f /var/log/mikpay-fonnte-cron.log

# Log script detail
tail -f /var/www/mikpay/cron/fonnte-auto-send.log

# Error log
tail -f /var/www/mikpay/cron/fonnte-auto-send-error.log
```

## ⚙️ Konfigurasi Auto-Send

1. **Login ke aplikasi MIKPAY**
2. **Settings > WhatsApp API (Fonnte)**
3. **Aktifkan auto-send:**
   - ✅ Auto Send H-3 (3 hari sebelum jatuh tempo)
   - ✅ Auto Send H-0 (Hari ini jatuh tempo)
   - ✅ Auto Send Overdue (Sudah lewat jatuh tempo)

4. **Atur Anti-Spam (opsional):**
   - Delay antar pesan: 15-30 detik
   - Batas per jam: 10 pesan
   - Batas per hari: 50 pesan

## 📊 Monitoring

### Cek Status Cron

```bash
# Cek apakah cron job aktif
sudo crontab -l

# Cek log cron system
grep CRON /var/log/syslog | tail -20
```

### Test Manual

```bash
# Test script
cd /var/www/mikpay
php cron/fonnte-auto-send.php

# Cek hasil
cat cron/fonnte-auto-send.log | tail -20
```

### Cek Quota Fonnte

Akses via browser:
```
http://your-domain.com/?id=fonnte&session=ROUTER1
```

Atau cek log:
```bash
tail -f /var/www/mikpay/cron/fonnte-auto-send.log
```

## 🔧 Troubleshooting

### Cron tidak jalan

**Cek:**
```bash
# Cek apakah cron service running
sudo systemctl status cron

# Restart cron
sudo systemctl restart cron

# Cek log cron
grep CRON /var/log/syslog | tail -20
```

### Script error "Permission denied"

**Solusi:**
```bash
sudo chmod +x /var/www/mikpay/cron/fonnte-auto-send.php
sudo chown www-data:www-data /var/www/mikpay/cron/
```

### Script error "Fonnte tidak aktif"

**Solusi:**
1. Pastikan Fonnte sudah dikonfigurasi di Settings > WhatsApp API
2. Pastikan API Token valid
3. Test koneksi Fonnte di settings

### Script error "No phone number"

**Solusi:**
1. Pastikan customer sudah di-set nomor teleponnya
2. Di menu Billing, edit customer dan isi nomor telepon

### Log file tidak terbuat

**Solusi:**
```bash
# Buat folder dan set permission
sudo mkdir -p /var/www/mikpay/cron
sudo chmod 755 /var/www/mikpay/cron
sudo chown www-data:www-data /var/www/mikpay/cron
```

## 📝 Catatan Penting

1. **Cron job berjalan di background** - Tidak perlu login ke aplikasi
2. **Rate limiting aktif** - Script akan otomatis delay antar pesan untuk menghindari spam
3. **Logging lengkap** - Semua aktivitas tercatat di log file
4. **Quota tracking** - Script akan skip jika quota habis

## 🎯 Best Practices

1. **Jalankan setiap jam** - Cukup untuk reminder harian
2. **Monitor log** - Cek log secara berkala untuk memastikan berjalan
3. **Set quota limit** - Atur batas pengiriman per jam/hari di settings
4. **Test dulu** - Test script manual sebelum setup cron

---

**Selamat! Fonnte auto-send sudah berjalan di background! 🎉**
