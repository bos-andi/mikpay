# 🛠️ Troubleshooting CSV Import/Export

Panduan untuk mengatasi masalah download template dan import CSV di VPS.

## 🔍 Masalah Umum

### Error: "Unexpected end of JSON input"

**Penyebab:**
- Response dari server tidak valid JSON
- Error PHP yang tidak tertangani
- Path file tidak benar
- Permission issue

**Solusi:**
1. **Cek error log:**
   ```bash
   sudo tail -50 /var/log/nginx/mikpay-error.log
   sudo tail -50 /var/log/php7.4-fpm.log
   ```

2. **Cek permission file:**
   ```bash
   ls -la /var/www/mikpay/ppp/import-excel.php
   ls -la /var/www/mikpay/ppp/export-excel-template.php
   # Harus: -rw-r--r-- atau -rw-rw-r--
   ```

3. **Test file langsung:**
   ```bash
   # Test export (harus download file)
   curl -b cookies.txt "http://your-domain.com/ppp/export-excel-template.php?session=ROUTER1"
   
   # Test import (harus return JSON)
   curl -X POST -F "excel_file=@test.csv" -F "session=ROUTER1" "http://your-domain.com/ppp/import-excel.php"
   ```

### Error: "Session expired"

**Penyebab:**
- Session PHP tidak ter-set dengan benar
- Cookie tidak terkirim

**Solusi:**
1. **Cek session path:**
   ```bash
   # Cek php.ini
   php -i | grep session.save_path
   
   # Pastikan folder writable
   sudo chmod 777 /var/lib/php/sessions
   # atau
   sudo chown www-data:www-data /var/lib/php/sessions
   ```

2. **Cek cookie settings di browser:**
   - Pastikan cookie tidak di-block
   - Cek di Developer Tools > Application > Cookies

### Error: "File tidak ditemukan"

**Penyebab:**
- File upload tidak terkirim
- POST data tidak ter-parse

**Solusi:**
1. **Cek upload_max_filesize:**
   ```bash
   php -i | grep upload_max_filesize
   # Harus minimal 5M
   ```

2. **Cek post_max_size:**
   ```bash
   php -i | grep post_max_size
   # Harus lebih besar dari upload_max_filesize
   ```

3. **Edit php.ini:**
   ```bash
   sudo nano /etc/php/7.4/fpm/php.ini
   # Set:
   upload_max_filesize = 10M
   post_max_size = 12M
   
   # Restart PHP-FPM
   sudo systemctl restart php7.4-fpm
   ```

### Error: "Tidak bisa terhubung ke router"

**Penyebab:**
- Router tidak accessible dari VPS
- API MikroTik tidak enabled

**Solusi:**
1. **Test koneksi router:**
   ```bash
   telnet ROUTER_IP 8728
   ```

2. **Cek firewall:**
   ```bash
   # Di router, pastikan API enabled
   /ip service enable api
   /ip service set api port=8728
   ```

### Error: "Permission denied"

**Penyebab:**
- File tidak writable
- Folder tidak writable

**Solusi:**
```bash
# Set permission
sudo chown -R www-data:www-data /var/www/mikpay
sudo chmod -R 755 /var/www/mikpay
sudo chmod -R 775 /var/www/mikpay/include
```

## ✅ Checklist Perbaikan

Setelah update, pastikan:

- [ ] File permission benar
- [ ] PHP upload settings cukup besar
- [ ] Session path writable
- [ ] Router accessible dari VPS
- [ ] Error log tidak ada error baru
- [ ] Test download template berhasil
- [ ] Test import CSV berhasil

## 🧪 Test Manual

### Test Download Template

1. **Via browser:**
   - Login ke aplikasi
   - Menu PPP > Billing
   - Klik "Template CSV"
   - File harus ter-download

2. **Via curl:**
   ```bash
   # Simpan cookie dulu dari browser
   curl -b cookies.txt -c cookies.txt "http://your-domain.com/admin.php?id=login"
   
   # Download template
   curl -b cookies.txt "http://your-domain.com/ppp/export-excel-template.php?session=ROUTER1" -o template.csv
   ```

### Test Import CSV

1. **Siapkan file test:**
   ```csv
   Username PPPoE,Nama Pelanggan,No HP / WhatsApp,Tanggal Jatuh Tempo,Tarif Bulanan (Rp),Catatan
   testuser,Test User,081234567890,15,100000,Test
   ```

2. **Test via curl:**
   ```bash
   curl -X POST \
     -b cookies.txt \
     -F "excel_file=@test.csv" \
     -F "session=ROUTER1" \
     "http://your-domain.com/ppp/import-excel.php"
   ```

3. **Response harus JSON:**
   ```json
   {
     "success": true,
     "message": "Import selesai! ...",
     "stats": {
       "success": 1,
       "skipped": 0,
       "errors": 0
     }
   }
   ```

## 🔧 Konfigurasi PHP untuk VPS

Edit `/etc/php/7.4/fpm/php.ini`:

```ini
; Upload settings
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20

; Memory
memory_limit = 256M

; Execution time
max_execution_time = 300
max_input_time = 300

; Session
session.save_path = "/var/lib/php/sessions"
session.gc_maxlifetime = 1440
```

Restart PHP-FPM:
```bash
sudo systemctl restart php7.4-fpm
```

## 📝 Log Files

Cek log untuk debugging:

```bash
# Nginx error log
sudo tail -f /var/log/nginx/mikpay-error.log

# PHP-FPM error log
sudo tail -f /var/log/php7.4-fpm.log

# PHP error log umum
sudo tail -f /var/log/php_errors.log
```

## 🆘 Jika Masih Bermasalah

1. **Enable error reporting sementara:**
   Edit `ppp/import-excel.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. **Cek response langsung:**
   ```bash
   curl -v -X POST \
     -F "excel_file=@test.csv" \
     -F "session=ROUTER1" \
     "http://your-domain.com/ppp/import-excel.php"
   ```

3. **Cek browser console:**
   - Buka Developer Tools (F12)
   - Tab Network
   - Lihat response dari import-excel.php

---

**Semoga masalahnya teratasi! 🎉**
