# 📅 Schedule Pengiriman Fonnte WhatsApp

Panduan lengkap untuk mengatur jadwal pengiriman reminder WhatsApp via Fonnte.

## 🎯 Fitur Schedule

Dengan fitur schedule, Anda bisa mengatur:
- ✅ **Waktu pengiriman** untuk setiap jenis reminder (H-3, H-0, Overdue)
- ✅ **Hari pengiriman** (Senin-Minggu)
- ✅ **Interval cek** (berapa menit sekali script mengecek jadwal)

## ⚙️ Cara Setup

### 1. Aktifkan Schedule di Settings

1. **Login ke aplikasi**
2. **Settings > WhatsApp API (Fonnte)**
3. **Scroll ke "Jadwal Pengiriman"**
4. **Aktifkan "Aktifkan Jadwal"**
5. **Atur waktu pengiriman:**
   - **H-3:** Waktu untuk kirim reminder 3 hari sebelum jatuh tempo (default: 09:00)
   - **Hari H:** Waktu untuk kirim reminder hari ini jatuh tempo (default: 08:00)
   - **Overdue:** Waktu untuk kirim reminder sudah lewat jatuh tempo (default: 10:00)
6. **Pilih hari pengiriman:**
   - Centang hari yang ingin aktif (Senin-Minggu)
   - Default: Semua hari aktif
7. **Atur interval cek:**
   - Rekomendasi: 30 menit
   - Minimal: 5 menit
   - Maksimal: 60 menit
8. **Klik "Simpan"**

### 2. Setup Cron Job

Berdasarkan interval yang Anda set, atur cron job:

#### Jika Interval 30 Menit:
```bash
sudo crontab -e
```

Tambahkan:
```cron
# Jalankan setiap 30 menit
*/30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

#### Jika Interval 15 Menit:
```cron
# Jalankan setiap 15 menit
*/15 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

#### Jika Interval 60 Menit (1 jam):
```cron
# Jalankan setiap jam
0 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

## 📋 Contoh Konfigurasi

### Contoh 1: Pengiriman Pagi (Senin-Jumat)

**Settings:**
- H-3: 09:00
- Hari H: 08:00
- Overdue: 10:00
- Hari: Senin, Selasa, Rabu, Kamis, Jumat
- Interval: 30 menit

**Cron:**
```cron
*/30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

### Contoh 2: Pengiriman Siang (Semua Hari)

**Settings:**
- H-3: 13:00
- Hari H: 12:00
- Overdue: 14:00
- Hari: Semua hari (Senin-Minggu)
- Interval: 30 menit

**Cron:**
```cron
*/30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

### Contoh 3: Pengiriman 2x Sehari

**Settings:**
- H-3: 09:00 dan 15:00 (gunakan 2 cron job terpisah)
- Hari H: 08:00 dan 14:00
- Overdue: 10:00 dan 16:00
- Hari: Semua hari
- Interval: 30 menit

**Cron (untuk pagi):**
```cron
*/30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
```

## 🔧 Cara Kerja

1. **Cron job berjalan** sesuai interval yang diatur (contoh: setiap 30 menit)
2. **Script mengecek:**
   - Apakah hari ini dalam jadwal pengiriman?
   - Apakah waktu saat ini sesuai dengan jadwal (toleransi ±5 menit)?
3. **Jika sesuai, kirim reminder:**
   - H-3: Jika ada customer yang 3 hari lagi jatuh tempo
   - Hari H: Jika ada customer yang hari ini jatuh tempo
   - Overdue: Jika ada customer yang sudah lewat jatuh tempo
4. **Log semua aktivitas** ke file log

## ⏰ Toleransi Waktu

Script memiliki toleransi **±5 menit** untuk waktu pengiriman. Artinya:
- Jika jadwal H-3: 09:00
- Script akan kirim jika cron berjalan antara **08:55 - 09:05**

Ini memastikan reminder tetap terkirim meskipun cron job sedikit terlambat.

## 📊 Monitoring

### Cek Log

```bash
# Log cron execution
tail -f /var/log/mikpay-fonnte-cron.log

# Log detail script
tail -f /var/www/mikpay/cron/fonnte-auto-send.log
```

### Cek Status

Di log, Anda akan melihat:
```
[2026-01-31 09:00:00] === Fonnte Auto-Send Reminder Started ===
[2026-01-31 09:00:00] Fonnte aktif. Memulai proses auto-send...
[2026-01-31 09:00:00] Mengecek reminder H-3...
[2026-01-31 09:00:01] SUCCESS H-3: customer1 (081234567890)
```

## 🎯 Best Practices

1. **Interval 30 menit** - Cukup untuk sebagian besar kasus
2. **Waktu pagi (08:00-10:00)** - Waktu yang baik untuk reminder
3. **Hindari jam sibuk** - Jangan set di jam 00:00-06:00 (kecuali perlu)
4. **Test dulu** - Test dengan waktu dekat untuk memastikan berjalan
5. **Monitor log** - Cek log secara berkala untuk memastikan berjalan

## 🔄 Disable Schedule

Jika ingin disable schedule (kirim setiap kali cron berjalan):

1. **Settings > WhatsApp API**
2. **Uncheck "Aktifkan Jadwal"**
3. **Simpan**

Dengan schedule disabled, reminder akan dikirim setiap kali cron job berjalan (jika ada customer yang perlu reminder).

## 🆘 Troubleshooting

### Reminder tidak terkirim sesuai jadwal

**Cek:**
1. Apakah schedule enabled?
2. Apakah hari ini dalam jadwal?
3. Apakah waktu saat ini sesuai jadwal (±5 menit)?
4. Apakah cron job berjalan sesuai interval?

**Solusi:**
```bash
# Cek cron job
sudo crontab -l

# Test script manual
cd /var/www/mikpay
php cron/fonnte-auto-send.php

# Cek log
tail -50 /var/www/mikpay/cron/fonnte-auto-send.log
```

### Cron job tidak berjalan

**Cek:**
```bash
# Cek status cron
sudo systemctl status cron

# Cek log cron
grep CRON /var/log/syslog | tail -20
```

---

**Selamat! Schedule pengiriman sudah dikonfigurasi! 🎉**
