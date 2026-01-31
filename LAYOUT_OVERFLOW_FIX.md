# Layout Overflow Fix - Dokumentasi Perbaikan Global

## 📋 Ringkasan

Solusi CSS global telah diterapkan untuk mencegah overflow horizontal di **SEMUA halaman** aplikasi. Perbaikan ini menggunakan pendekatan CSS murni tanpa JavaScript.

## 🔍 Masalah yang Diperbaiki

1. **Konten melewati viewport horizontal** - Elemen terpotong ke kanan
2. **Layout tidak responsif** - Tidak adaptif untuk berbagai ukuran layar
3. **Container melebihi lebar layar** - Max-width tidak menghitung sidebar
4. **Row/Column overflow** - Flex layout tidak wrap dengan benar
5. **Tabel terpotong** - Tabel melebihi container

## ✅ Solusi yang Diterapkan

### 1. Global Overflow Prevention

**File:** `css/dashboard-custom.css`

**Perbaikan:**
- `html` dan `body` - `overflow-x: hidden` dan `max-width: 100vw`
- `.wrapper` - Mencegah overflow di level teratas
- `#main` - Menghitung sidebar (260px) dengan `calc(100vw - 260px)`
- `.main-container` - Responsive max-width berdasarkan viewport

### 2. Row & Column Layout Fix

**Perbaikan:**
- `.row` - Flex dengan `flex-wrap: wrap` dan margin negatif yang benar
- `.col-*` - `min-width: 0` untuk allow shrink, `box-sizing: border-box`
- Responsive breakpoints:
  - Desktop (≥993px): Column widths normal
  - Tablet (≤992px): Stack ke 100% width
  - Mobile (≤768px): Smaller padding

### 3. Card & Content Containers

**Perbaikan:**
- `.card` - `max-width: 100%` dan `overflow-x: hidden`
- `.card-body` - Proper padding dan box-sizing
- Form elements - `max-width: 100%` untuk semua input

### 4. Table Responsive

**Perbaikan:**
- Desktop: Tabel fit natural tanpa scroll
- Mobile: Horizontal scroll dengan `-webkit-overflow-scrolling: touch`

## 📐 Struktur Layout HTML yang Direkomendasikan

### Struktur Dasar

```html
<div class="wrapper">
    <!-- Sidebar -->
    <div id="sidenav" class="sidenav">...</div>
    
    <!-- Main Content -->
    <div id="main">
        <div class="main-container">
            <!-- Content here -->
        </div>
    </div>
</div>
```

### Layout dengan Row/Column

```html
<div class="main-container">
    <div class="row">
        <!-- Form utama -->
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h3>Title</h3>
                </div>
                <div class="card-body">
                    <!-- Form content -->
                </div>
            </div>
        </div>
        
        <!-- Panel ringkasan -->
        <div class="col-4">
            <div class="card">
                <div class="card-header">
                    <h3>Summary</h3>
                </div>
                <div class="card-body">
                    <!-- Summary content -->
                </div>
            </div>
        </div>
    </div>
</div>
```

### Layout dengan Tabel

```html
<div class="main-container">
    <div class="card">
        <div class="card-header">
            <h3>Data Table</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>...</thead>
                    <tbody>...</tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```

## 🎯 Contoh Implementasi - Halaman Generate User

### Sebelum (Masalah)

```html
<div class="row">
    <div class="col-8">
        <div class="card">
            <!-- Form - bisa overflow -->
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <!-- Summary - bisa terpotong -->
        </div>
    </div>
</div>
```

**Masalah:**
- Row tidak wrap dengan benar
- Column tidak shrink saat space kurang
- Card melebihi container

### Sesudah (Fixed)

```html
<div class="main-container">
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h3>Generate User</h3>
                </div>
                <div class="card-body">
                    <form>
                        <!-- Form content -->
                    </form>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-header">
                    <h3>Last Generate</h3>
                </div>
                <div class="card-body">
                    <!-- Summary content -->
                </div>
            </div>
        </div>
    </div>
</div>
```

**Perbaikan:**
- ✅ Semua container menggunakan `box-sizing: border-box`
- ✅ Row wrap dengan benar di mobile
- ✅ Column stack ke bawah di tablet/mobile
- ✅ Card tidak melebihi container

## 📱 Responsive Breakpoints

### Desktop (≥993px)
- Sidebar: 260px fixed
- Main: `calc(100vw - 260px)`
- Container: `calc(100vw - 260px - 40px)` (minus padding)
- Columns: Normal widths (col-8 = 66.666%, col-4 = 33.333%)
- Tables: Fit natural, no horizontal scroll

### Tablet (769px - 992px)
- Sidebar: Hidden atau collapsed
- Main: `100vw`
- Container: `100vw - 30px` (smaller padding)
- Columns: Stack to 100% width
- Tables: Horizontal scroll if needed

### Mobile (≤768px)
- Sidebar: Hidden
- Main: `100vw`
- Container: `100vw - 30px`
- Columns: Stack to 100% width, smaller padding (5px)
- Tables: Horizontal scroll with touch support

## 🔧 CSS Classes yang Tersedia

### Container Classes
- `.wrapper` - Main wrapper (full width)
- `.main-container` - Content container (responsive max-width)
- `.card` - Card container (100% width, no overflow)
- `.card-body` - Card content (proper padding)

### Layout Classes
- `.row` - Flex row container (wraps on mobile)
- `.col-1` sampai `.col-12` - Column widths (responsive)

### Table Classes
- `.table-responsive` - Table wrapper (scroll on mobile)
- `.table` - Table element (100% width)

## ⚠️ Best Practices

### ✅ DO

1. **Selalu gunakan `.main-container`** untuk wrap content
2. **Gunakan `.row` dan `.col-*`** untuk layout grid
3. **Wrap tabel dengan `.table-responsive`**
4. **Gunakan `box-sizing: border-box`** (sudah otomatis)
5. **Test di berbagai ukuran layar**

### ❌ DON'T

1. **Jangan set fixed width** yang melebihi viewport
2. **Jangan gunakan `min-width` besar** tanpa media query
3. **Jangan skip `.table-responsive`** untuk tabel
4. **Jangan hardcode padding/margin** yang besar
5. **Jangan gunakan `overflow: visible`** di container utama

## 🧪 Testing Checklist

- [ ] Desktop (1920x1080): Semua konten muat tanpa scroll horizontal
- [ ] Desktop (1366x768): Layout tetap proporsional
- [ ] Tablet (768x1024): Columns stack dengan benar
- [ ] Mobile (375x667): Horizontal scroll hanya untuk tabel
- [ ] Sidebar open/close: Layout tidak break
- [ ] Form panjang: Tidak overflow
- [ ] Tabel lebar: Scroll horizontal di mobile, fit di desktop

## 📝 Catatan Teknis

### Box Model
Semua elemen menggunakan `box-sizing: border-box` untuk menghitung width dengan benar termasuk padding dan border.

### Flex Layout
Row menggunakan `flex-wrap: wrap` untuk memastikan columns stack di mobile. Columns menggunakan `min-width: 0` untuk allow shrink.

### Overflow Strategy
- **Desktop**: `overflow-x: hidden` di root, `overflow-x: visible` untuk tabel
- **Mobile**: `overflow-x: auto` untuk tabel dengan smooth scrolling

### Viewport Calculation
- Sidebar width: 260px
- Container padding: 20px (desktop), 15px (mobile)
- Max-width formula: `calc(100vw - sidebar - padding)`

## 🚀 Update di VPS

```bash
cd /var/www/mikpay
git pull origin main
```

Clear browser cache untuk memastikan CSS baru ter-load.

## 📞 Troubleshooting

### Masih ada overflow?
1. Check apakah ada inline style dengan fixed width
2. Pastikan menggunakan `.main-container`
3. Check console untuk CSS conflicts
4. Pastikan tidak ada `!important` yang override

### Layout break di mobile?
1. Pastikan menggunakan `.row` dengan `.col-* classes
2. Check apakah ada `min-width` yang terlalu besar
3. Pastikan `flex-wrap: wrap` aktif

### Tabel masih terpotong?
1. Pastikan wrap dengan `.table-responsive`
2. Check apakah ada `table-layout: fixed` dengan width besar
3. Pastikan menggunakan responsive breakpoints

---

**Last Updated:** 2026-01-31
**Version:** 1.0.0
