# 📱 Fonnte WhatsApp API di VPS

Panduan lengkap untuk menggunakan fitur Fonnte WhatsApp API di VPS.

## 🎯 Fitur Fonnte

Fonnte memungkinkan Anda mengirim reminder WhatsApp otomatis ke pelanggan untuk:
- ✅ Reminder tagihan H-3 (3 hari sebelum jatuh tempo)
- ✅ Reminder tagihan H-0 (hari ini jatuh tempo)
- ✅ Reminder Overdue (sudah lewat jatuh tempo)
- ✅ Manual send dari menu Billing

## ⚙️ Setup Awal

### 1. Daftar di Fonnte

1. Kunjungi [https://fonnte.com](https://fonnte.com)
2. Daftar dan dapatkan API Token
3. Top up saldo (jika diperlukan)

### 2. Konfigurasi di MIKPAY

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

## 🤖 Auto-Send di Background (Tanpa Login)

**YA!** Fonnte bisa berjalan di background tanpa perlu login dengan menggunakan **Cron Job**.

### Setup Cron Job

#### Langkah 1: Test Script

```bash
cd /var/www/mikpay
php cron/fonnte-auto-send.php
```

#### Langkah 2: Setup Cron

```bash
sudo crontab -e
```

Tambahkan:
```cron
# Jalankan setiap jam
0 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

#### Langkah 3: Aktifkan Auto-Send di Settings

1. Login ke aplikasi
2. Settings > WhatsApp API
3. Aktifkan:
   - ✅ Auto Send H-3
   - ✅ Auto Send H-0
   - ✅ Auto Send Overdue

### Cara Kerja

1. **Cron job berjalan setiap jam** (atau sesuai schedule yang Anda set)
2. **Script mengecek customer** yang perlu reminder:
   - H-3: 3 hari sebelum jatuh tempo
   - H-0: Hari ini jatuh tempo
   - Overdue: Sudah lewat jatuh tempo
3. **Kirim WhatsApp otomatis** ke nomor yang sudah di-set
4. **Log semua aktivitas** ke file log

## 📋 Manual Send (Saat Login)

Selain auto-send, Anda juga bisa kirim manual:

1. **Menu PPP > Billing**
2. **Cari customer** yang ingin dikirim reminder
3. **Klik tombol WhatsApp** (ikon hijau)
4. **Pilih template** (Reminder, Due Today, atau Overdue)
5. **Klik "Kirim via Fonnte"**

## 🔒 Anti-Spam Protection

Fonnte memiliki fitur anti-spam built-in:

### Rate Limiting
- **Delay antar pesan:** 15-30 detik (random)
- **Batas per jam:** 10 pesan (default)
- **Batas per hari:** 50 pesan (default)

### Message Randomization
- **Random greeting:** Variasi salam
- **Random closing:** Variasi penutup
- **Unique ID:** Setiap pesan memiliki ID unik

### Konfigurasi Anti-Spam

Di Settings > WhatsApp API:
- ✅ Aktifkan Anti-Spam
- Atur delay min/max (detik)
- Atur batas per jam/hari
- ✅ Aktifkan randomize message

## 📊 Monitoring

### Cek Log Auto-Send

```bash
# Log cron execution
tail -f /var/log/mikpay-fonnte-cron.log

# Log detail script
tail -f /var/www/mikpay/cron/fonnte-auto-send.log

# Error log
tail -f /var/www/mikpay/cron/fonnte-auto-send-error.log
```

### Cek Quota

1. **Settings > WhatsApp API**
2. **Lihat "Remaining Quota"**
   - Hourly remaining
   - Daily remaining
   - Last sent

### Cek Log Pengiriman

1. **Settings > WhatsApp API**
2. **Scroll ke "Message Logs"**
3. **Lihat history pengiriman**

## 🛠️ Troubleshooting

### Auto-send tidak jalan

**Cek:**
```bash
# Cek cron job
sudo crontab -l

# Test script manual
cd /var/www/mikpay
php cron/fonnte-auto-send.php

# Cek log
tail -f /var/log/mikpay-fonnte-cron.log
```

### Error "Fonnte tidak aktif"

**Solusi:**
1. Pastikan Fonnte sudah diaktifkan di Settings
2. Pastikan API Token valid
3. Test koneksi di Settings

### Error "No phone number"

**Solusi:**
1. Edit customer di menu Billing
2. Isi nomor telepon customer
3. Format: 08xxx atau 628xxx

### Quota habis

**Solusi:**
1. Cek quota di Settings > WhatsApp API
2. Tunggu reset (per jam atau per hari)
3. Atau top up saldo di Fonnte

### Pesan tidak terkirim

**Cek:**
1. Log di Settings > WhatsApp API > Message Logs
2. Cek error message
3. Pastikan nomor telepon benar
4. Pastikan saldo Fonnte cukup

## 📝 Checklist Setup

- [ ] Daftar di Fonnte dan dapatkan API Token
- [ ] Konfigurasi Fonnte di Settings > WhatsApp API
- [ ] Test koneksi Fonnte
- [ ] Set nomor telepon customer di menu Billing
- [ ] Aktifkan auto-send (H-3, H-0, Overdue)
- [ ] Setup cron job untuk auto-send
- [ ] Test script manual
- [ ] Monitor log untuk memastikan berjalan

## 🎯 Best Practices

1. **Set quota limit** - Atur batas pengiriman per jam/hari
2. **Monitor log** - Cek log secara berkala
3. **Test dulu** - Test manual sebelum aktifkan auto-send
4. **Update template** - Sesuaikan template pesan dengan kebutuhan
5. **Backup data** - Backup file `fonnte_settings.json` secara berkala

## 📞 Support

Jika ada masalah:
1. Cek log file
2. Test koneksi Fonnte
3. Cek quota dan saldo Fonnte
4. Lihat dokumentasi di [cron/README.md](cron/README.md)

---

**Fonnte siap digunakan! 🎉**
