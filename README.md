# MIKPAY - MikroTik Management System

Sistem manajemen untuk MikroTik Router yang mencakup Hotspot Management, PPP/PPPoE Management, Billing System, dan fitur-fitur lengkap lainnya.

## 🚀 Fitur Utama

- **Dashboard** - Statistik dan monitoring real-time
- **Hotspot Management** - Manajemen user, profile, dan aktifitas hotspot
- **PPP/PPPoE Management** - Manajemen PPP secrets, profiles, dan koneksi aktif
- **Billing System** - Sistem tagihan WiFi dengan invoice dan laporan
- **Voucher System** - Generate dan print voucher
- **WhatsApp Integration** - Integrasi dengan Fonnte API untuk reminder otomatis
- **Multi-Router Support** - Kelola multiple router dalam satu aplikasi
- **Reporting** - Laporan penjualan, keuangan, dan aktivitas user
- **Subscription System** - Sistem langganan dengan berbagai paket

## 📋 Requirements

- PHP 7.4 atau lebih tinggi
- Web Server (Apache/Nginx)
- MikroTik Router dengan API enabled
- MySQL (opsional, untuk beberapa fitur)

## 🔧 Instalasi

### 1. Clone atau Download Repository

```bash
git clone https://github.com/yourusername/mikpay.git
cd mikpay
```

### 2. Konfigurasi Router

Copy file template konfigurasi:

```bash
cp include/config.php.example include/config.php
```

Edit file `include/config.php` dan tambahkan konfigurasi router Anda:

```php
$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',
  'ROUTER1@|@admin',
  'ROUTER1#|#YOUR_PASSWORD_BASE64',
  'ROUTER1%My Router Name',
  'ROUTER1^mydomain.com',
  'ROUTER1&Rp',
  'ROUTER1*10',
  'ROUTER1(1',
  'ROUTER1)',
  'ROUTER1=10',
  'ROUTER1@!@enable'
);
```

**Catatan:** Password harus di-encode dalam Base64. Anda bisa menggunakan tool online atau PHP:
```php
echo base64_encode('your_password');
```

### 3. Set Permissions

Pastikan folder `include/` writable untuk menyimpan data:

```bash
chmod 755 include/
chmod 644 include/*.php
```

### 4. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/mikpay/admin.php?id=login
```

**Default Login:**
- Username: `mikpay`
- Password: `mikpay`

**⚠️ PENTING:** Ganti password default setelah login pertama kali!

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

### 3. WhatsApp API (Opsional)

Jika ingin menggunakan fitur reminder otomatis:
1. Daftar di [Fonnte](https://fonnte.com)
2. Dapatkan API key
3. Masukkan di Settings > WhatsApp API

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
├── settings/               # Settings pages
├── css/                   # Stylesheets
├── js/                    # JavaScript files
├── img/                   # Images & logos
└── lang/                  # Language files
```

## 🔐 Keamanan

1. **Ganti Password Default** - Segera ganti password admin setelah instalasi
2. **File Permissions** - Pastikan file konfigurasi tidak accessible langsung
3. **HTTPS** - Gunakan HTTPS untuk production
4. **Firewall** - Batasi akses ke aplikasi dari IP tertentu jika perlu

## 📝 Data Storage

Aplikasi menyimpan data dalam file JSON di folder `include/`:
- `business_settings.json` - Pengaturan bisnis
- `subscription.json` - Status langganan
- `billing_customers.json` - Data pelanggan billing
- `billing_payments.json` - Data pembayaran
- `fonnte_settings.json` - Pengaturan WhatsApp API

**Catatan:** File-file ini tidak di-commit ke Git karena berisi data user.

## 🆘 Troubleshooting

### Tidak bisa connect ke router
- Pastikan API MikroTik enabled (port 8728)
- Cek firewall/router settings
- Pastikan IP dan port benar

### Error permission denied
- Pastikan folder `include/` writable
- Cek file permissions

### Logo tidak muncul
- Upload logo di folder `img/`
- Format: `logo-{SESSION_NAME}.png` untuk logo per router
- Atau `logo.png` untuk logo default

## 📄 License

GPL v2 - Lihat file LICENSE untuk detail lengkap

## 👨‍💻 Author

Muhammad Andi

## 🤝 Contributing

Contributions are welcome! Silakan buat issue atau pull request.

## 📞 Support

Untuk support dan pertanyaan:
- Email: [your-email]
- WhatsApp: [your-whatsapp]

---

**Selamat menggunakan MIKPAY! 🎉**
