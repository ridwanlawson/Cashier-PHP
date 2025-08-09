
# Kasir Digital - Sistem Point of Sale (POS)

![Kasir Digital](https://img.shields.io/badge/PHP-8.2-blue) ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1-purple) ![SQLite](https://img.shields.io/badge/SQLite-3-green)

Aplikasi Kasir Digital adalah sistem Point of Sale (POS) yang dibuat dengan PHP dan SQLite. Aplikasi ini memungkinkan Anda untuk mengelola produk, melakukan transaksi penjualan, tracking inventory, dan manajemen user dengan antarmuka yang modern dan responsif.

## 🌟 Fitur Utama

### 📊 Dashboard
- **Statistik Real-time**: Total produk, transaksi harian, omzet harian
- **Monitoring**: Produk dengan stok menipis
- **Riwayat**: Transaksi terbaru dan analisis bulanan

### 🛍️ Manajemen Produk
- ✅ CRUD (Create, Read, Update, Delete) produk
- ✅ Kategori produk
- ✅ Barcode unik untuk setiap produk
- ✅ Tracking stok real-time
- ✅ DataTables dengan fitur export (Excel, PDF, CSV)

### 💰 Sistem Kasir
- ✅ Pencarian produk cepat (nama atau barcode)
- ✅ Scanner barcode support
- ✅ Keranjang belanja interaktif
- ✅ Perhitungan otomatis total dan kembalian
- ✅ Print struk pembayaran
- ✅ Validasi stok

### 📦 Manajemen Inventory
- ✅ Pencatatan barang masuk
- ✅ History perubahan stok
- ✅ Perhitungan harga jual otomatis dengan margin
- ✅ Keterangan untuk setiap transaksi stok

### 📜 Riwayat Transaksi
- ✅ Log lengkap semua transaksi
- ✅ Detail item per transaksi
- ✅ Reprint struk pembayaran
- ✅ Export data transaksi

### 👥 Manajemen User (Admin)
- ✅ Multi-level user (Admin & Kasir)
- ✅ Autentikasi aman
- ✅ Role-based access control

### 🎫 Manajemen Member
- ✅ Pendaftaran member baru
- ✅ Pencarian member cepat
- ✅ Sistem poin member
- ✅ Integrasi dengan transaksi kasir
- ✅ CRUD lengkap untuk data member

### 💳 Fitur Kasir Lanjutan
- ✅ Integrasi member dalam transaksi
- ✅ Pencatatan metode pembayaran
- ✅ Sistem Hold/Resume transaksi
- ✅ Cetak struk dengan data kasir dan member
- ✅ Perhitungan diskon per item

### 🎨 UI/UX Modern
- ✅ Dark/Light mode toggle
- ✅ Responsive design (Mobile & Desktop)
- ✅ Modern gradient design
- ✅ Smooth animations
- ✅ Select2 untuk dropdown yang lebih user-friendly

## 🚀 Teknologi yang Digunakan

- **Backend**: PHP 8.2+
- **Database**: SQLite 3
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Framework CSS**: Bootstrap 5.1
- **Libraries**: 
  - DataTables (Table management)
  - Select2 (Enhanced select dropdowns)
  - Font Awesome (Icons)
  - jQuery (DOM manipulation)

## 📋 Requirements

- PHP 8.0 atau lebih tinggi
- SQLite extension untuk PHP
- PDO extension untuk PHP
- Web browser modern (Chrome, Firefox, Safari, Edge)

## 🛠️ Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/<username>/Cashier-PHP.git
cd Cashier-PHP
```

### 2. Setup Database
Database SQLite akan dibuat otomatis saat pertama kali dijalankan. Struktur tabel akan dibuat secara otomatis.

### 3. Install Sample Data (Opsional)
```bash
php install_sample_data.php
```

### 4. Jalankan Aplikasi
```bash
php -S localhost:8000
```

### 5. Buka Browser
Akses aplikasi di: `http://localhost:8000`

## 👤 Default Login

### Admin
- **Username**: `admin`
- **Password**: `password`

### Kasir
- **Username**: `kasir`
- **Password**: `password`

## 📁 Struktur Project

```
Cashier-PHP/
├── api/                    # API endpoints
│   ├── products.php       # API untuk produk
│   ├── transactions.php   # API untuk transaksi
│   ├── inventory.php      # API untuk inventory
│   └── users.php         # API untuk user management
├── assets/
│   └── script.js         # JavaScript utama
├── config/
│   └── database.php      # Konfigurasi database
├── auth.php              # Sistem autentikasi
├── index.php             # Halaman utama aplikasi
├── login.php             # Halaman login
├── logout.php            # Logout handler
├── install_sample_data.php # Install data contoh
└── README.md
```

## 🔧 Konfigurasi

### Database
Database SQLite akan dibuat otomatis di `api/kasir_digital.db`. Tidak perlu konfigurasi tambahan.

### Themes
Aplikasi mendukung dark mode dan light mode. Setting akan tersimpan di localStorage browser.

## 📱 Fitur Mobile

- Responsive design untuk semua ukuran layar
- Mobile-first navigation
- Touch-friendly interface
- Optimized untuk tablet kasir

## 🔒 Keamanan

- **Session Management**: Secure session handling
- **SQL Injection Protection**: Prepared statements
- **XSS Protection**: Input sanitization
- **Role-based Access**: Admin/Kasir permission levels

## 📊 Export Features

- **Excel Export**: Data produk dan transaksi
- **PDF Export**: Laporan dan struk
- **CSV Export**: Data untuk analisis eksternal
- **Print**: Struk pembayaran thermal printer ready

## 🎯 Penggunaan

### Untuk Kasir
1. Login dengan akun kasir
2. Pilih menu "Kasir"
3. Cari produk atau scan barcode
4. Tambahkan ke keranjang
5. Input jumlah pembayaran
6. Proses transaksi dan print struk

### Untuk Admin
1. Login dengan akun admin
2. Kelola produk di menu "Kelola Produk"
3. Tambah stok barang di menu "Barang Masuk"
4. Monitor penjualan di "Dashboard"
5. Kelola user di menu "Kelola User"

## 🚧 Development

### Menambah Fitur Baru
1. Buat API endpoint di folder `api/`
2. Tambah fungsi JavaScript di `assets/script.js`
3. Update UI di `index.php`

### Database Schema
Tables akan dibuat otomatis:
- `products` - Data produk
- `transactions` - Header transaksi (dengan kasir dan member)
- `transaction_items` - Detail item transaksi (dengan diskon)
- `users` - Data pengguna
- `members` - Data member dengan sistem poin
- `held_transactions` - Transaksi yang ditahan
- `inventory_log` - Log perubahan stok
- `app_settings` - Pengaturan aplikasi

## 🧪 Running Tests

1. Install dependencies:
   ```bash
   composer install
   ```
2. Run the test suite:
   ```bash
   vendor/bin/phpunit
   ```

## 🐛 Troubleshooting

### Database Issues
- Pastikan folder `api/` writable
- Check PHP SQLite extension enabled

### Permission Issues
- Set proper file permissions (755 untuk folder, 644 untuk file)
- Pastikan web server dapat menulis ke folder `api/`

## 📝 Changelog

### Version 1.0.0
- Initial release
- Basic POS functionality
- Dark/Light mode
- Mobile responsive
- Select2 integration

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

## 📞 Support

- **Documentation**: README.md
- **Issues**: GitHub Issues
- **Email**: support@kasirdigital.com

## 🙏 Acknowledgments

- Bootstrap team untuk framework CSS
- DataTables untuk table management
- Select2 untuk enhanced dropdowns
- Font Awesome untuk icons
- PHP community untuk dokumentasi

---

**Made with ❤️ in Indonesia**
