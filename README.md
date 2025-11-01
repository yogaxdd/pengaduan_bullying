# 🛡️ Sistem Pengaduan Bullying Anonim

Sistem web untuk pelaporan bullying secara anonim dengan komunikasi dua arah antara pelapor dan konselor. Dibangun dengan PHP murni dan MySQL.

## ✨ Fitur Unggulan

### 🎨 Kustomisasi Sekolah
- **Upload Logo Sekolah** - Tampilkan identitas sekolah di semua halaman
- **Background Custom** - Upload foto gedung sekolah sebagai background
- **Nama & Tagline** - Sesuaikan nama dan tagline sekolah
- **Settings Panel** - Kelola semua kustomisasi dari admin panel
- **Support Format** - JPG, PNG, GIF, WEBP

### 💬 Live Chat System
- **Real-time Chat** - Auto-refresh setiap 2 detik tanpa reload halaman
- **Facebook-style Chat Box** - Chat box di pojok kanan bawah (admin)
- **Multi-chat Support** - Admin bisa chat dengan 3 siswa sekaligus
- **Unread Badge** - Notifikasi pesan belum dibaca
- **Report Details** - Info laporan ditampilkan di chat interface

### Untuk Siswa (Pelapor Anonim)
- ✅ **Laporan 100% Anonim** - Tidak perlu login atau memberikan identitas
- 📝 **Form Pelaporan Lengkap** - Kategori, kronologi, bukti, tingkat urgensi
- 📎 **Upload Bukti** - Foto, video, dokumen (maks 10MB)
- 🔐 **Kode Tracking & PIN** - Untuk cek status dan komunikasi
- 💬 **Live Chat Anonim** - Komunikasi real-time dengan konselor
- 🚪 **Quick Exit Button** - Keluar cepat dengan tombol ESC
- 💾 **Auto-save Draft** - Draft tersimpan otomatis di browser

### Untuk Admin/Konselor
- 📊 **Dashboard Statistik** - Overview laporan dan notifikasi
- 📋 **Manajemen Laporan** - Lihat, filter, dan proses laporan
- 💬 **Live Chat Widget** - Chat real-time dengan Facebook-style interface
- 📈 **Update Status** - Tracking progress penanganan
- 👥 **Multi-role Support** - Super Admin dan Staff BK
- 📜 **Audit Trail** - Log semua aktivitas admin
- 🔔 **Notifikasi Real-time** - Alert untuk laporan darurat
- ⚙️ **Settings Panel** - Kustomisasi logo, background, dan info sekolah

## 🚀 Instalasi

### Persyaratan Sistem
- PHP 8.x atau lebih tinggi
- MySQL/MariaDB 5.7+
- Apache/Nginx dengan mod_rewrite
- XAMPP/WAMP/LAMP (untuk development)

### Langkah Instalasi

1. **Clone atau Download Project**
   ```bash
   # Letakkan folder project di htdocs (XAMPP) atau www (WAMP)
   C:\xampp\htdocs\pengaduan_bullying\
   ```

2. **Import Database**
   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Klik tab "Import"
   - Pilih file `database/pengaduan_bullying_fixed.sql`
   - Klik "Go" untuk import
   - Database `pengaduan_bullying` akan otomatis dibuat beserta semua tabelnya

3. **Konfigurasi Database**
   - Edit file `config/database.php`
   - Sesuaikan kredensial database:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'pengaduan_bullying');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Default XAMPP kosong
   ```

4. **Setup Folder Upload**
   - Folder `uploads/` sudah tersedia di root project
   - Pastikan folder memiliki write permission (chmod 755)

5. **Kustomisasi Sekolah (Opsional)**
   - Login sebagai admin
   - Buka menu "Pengaturan"
   - Upload logo sekolah dan foto gedung
   - Ubah nama dan tagline sekolah
   - Simpan pengaturan

6. **Akses Aplikasi**
   - Frontend (Siswa): http://localhost/pengaduan_bullying/public/
   - Admin Panel: http://localhost/pengaduan_bullying/admin/

## 🔑 Kredensial Default

### Admin Login
- **Username:** admin
- **Password:** Admin123!
- **Email:** admin@school.com
- **Role:** Super Admin

> ⚠️ **PENTING:** Segera ganti password default setelah instalasi pertama kali!

## 📁 Struktur Folder

```
pengaduan_bullying/
├── admin/              # Panel admin
│   ├── dashboard.php   # Dashboard utama
│   ├── login.php       # Halaman login admin
│   ├── report_view.php # Detail laporan
│   └── ...
├── config/             # Konfigurasi
│   └── database.php    # Koneksi database
├── database/           # File SQL
│   └── pengaduan_bullying.sql
├── includes/           # File PHP yang di-include
│   └── session.php     # Manajemen session & security
├── public/             # Frontend publik
│   ├── index.php       # Form pelaporan
│   ├── track.php       # Cek status & chat
│   ├── info.php        # Informasi & bantuan
│   └── assets/         # CSS, JS, images
├── uploads/            # File bukti (di luar webroot)
└── README.md           # Dokumentasi ini
```

## 🔒 Keamanan

### Fitur Keamanan Implementasi
- ✅ **Password Hashing** - Menggunakan `password_hash()` PHP
- ✅ **CSRF Protection** - Token di semua form
- ✅ **SQL Injection Prevention** - Prepared statements PDO
- ✅ **XSS Protection** - Input sanitization & output escaping
- ✅ **Rate Limiting** - Batasan submit form & login attempts
- ✅ **Session Security** - Regenerate ID, httponly cookies
- ✅ **File Upload Validation** - Cek MIME type & extension
- ✅ **Secure File Storage** - Di luar webroot dengan .htaccess

### Best Practices
1. Gunakan HTTPS di production
2. Regular backup database
3. Update PHP dan dependencies
4. Monitor audit log secara berkala
5. Edukasi admin tentang phishing

## 💻 Penggunaan

### Alur Pelaporan (Siswa)

1. **Buat Laporan**
   - Akses halaman utama
   - Isi form pelaporan (kategori, deskripsi, dll)
   - Upload bukti jika ada
   - Submit laporan

2. **Simpan Kredensial**
   - Catat/screenshot kode tracking
   - Simpan PIN dengan aman (tidak bisa dipulihkan)

3. **Cek Status**
   - Masuk ke halaman tracking
   - Input kode & PIN
   - Lihat status dan balas pesan admin

### Alur Penanganan (Admin)

1. **Login Admin**
   - Akses `/admin/login.php`
   - Masukkan username & password

2. **Review Laporan**
   - Lihat dashboard untuk laporan baru
   - Klik laporan untuk detail
   - Review kronologi & bukti

3. **Tindak Lanjut**
   - Update status laporan
   - Kirim pesan ke pelapor
   - Assign ke staf lain jika perlu
   - Eskalasi untuk kasus serius

4. **Dokumentasi**
   - Semua aktivitas ter-log otomatis
   - Export laporan jika diperlukan

## 🎨 Customization

### Mengubah Kategori Bullying
Edit di database tabel `categories` atau buat halaman admin untuk CRUD kategori.

### Menambah Admin Baru
```sql
INSERT INTO admin_users (username, email, password_hash, full_name, role) 
VALUES ('username', 'email@sekolah.id', '$2y$10$...', 'Nama Lengkap', 'staff_bk');
```

### Styling/Theme
- Edit CSS di `public/assets/css/style.css` (frontend)
- Edit CSS di `public/assets/css/admin.css` (admin panel)

## 🐛 Troubleshooting

### Database Connection Error
- Cek kredensial di `config/database.php`
- Pastikan MySQL service running
- Cek nama database sudah benar

### File Upload Gagal
- Cek `upload_max_filesize` di php.ini
- Pastikan folder uploads memiliki write permission
- Cek `post_max_size` >= `upload_max_filesize`

### Session Error
- Pastikan `session.save_path` writable
- Clear browser cookies
- Cek tidak ada output sebelum `session_start()`

### Admin Tidak Bisa Login
- Reset password via phpMyAdmin:
```sql
UPDATE admin_users 
SET password_hash = '$2y$10$5g/G2rcByq3Jf4vmXWI.M.Ds1XrgjaotSR5q8JhniXxWJQtV9VMVy' 
WHERE username = 'admin';
-- Password: Admin123!
```

## 📊 Database Schema

### Tabel Utama
- `reports` - Laporan bullying
- `categories` - Kategori kasus
- `report_messages` - Pesan chat anonim (live chat)
- `report_attachments` - File bukti
- `admin_users` - Data admin & konselor
- `system_settings` - Kustomisasi sekolah (logo, background, nama)
- `audit_log` - Log aktivitas admin
- `notifications` - Notifikasi admin
- `rate_limit` - Anti spam & rate limiting

## 🎯 Fitur Teknis

### Live Chat System
- **Polling Interval:** 2 detik untuk real-time experience
- **Multi-chat:** Maksimal 3 chat box bersamaan
- **Auto-scroll:** Smart scroll ke pesan terbaru
- **Minimize/Maximize:** Kontrol chat box dengan mudah
- **AJAX-based:** Kirim & terima pesan tanpa reload

### Security Features
- **CSRF Protection:** Token di semua form
- **SQL Injection Prevention:** Prepared statements PDO
- **XSS Protection:** Input sanitization & output escaping
- **Password Hashing:** bcrypt dengan cost 10
- **Rate Limiting:** Anti spam & brute force
- **Session Security:** Regenerate ID, httponly cookies
- **File Upload Validation:** MIME type & extension check

### Performance
- **Optimized Queries:** Indexed columns untuk fast lookup
- **Lazy Loading:** Load data hanya saat diperlukan
- **Caching:** Browser cache untuk assets
- **Compressed Images:** Support WEBP untuk file size kecil

## 🤝 Kontribusi

Sistem ini open source dan menerima kontribusi untuk:
- Bug fixes
- Fitur baru
- Dokumentasi
- Testing
- UI/UX improvements
- Translasi bahasa

## 📝 Lisensi

MIT License - Bebas digunakan dan dimodifikasi untuk keperluan pendidikan

## 🆘 Support & Kontak

Untuk bantuan teknis atau pertanyaan:
- Buat issue di GitHub repository
- Dokumentasi lengkap: /public/info.php

## 🏆 Credits

Dikembangkan untuk membantu siswa melaporkan bullying dengan aman dan anonim.

---

**Catatan:** Sistem ini adalah tools pendukung. Penanganan bullying tetap memerlukan pendekatan komprehensif dari sekolah, orang tua, dan profesional.

## ⚡ Quick Start

```bash
# 1. Start XAMPP
# 2. Import database
# 3. Akses http://localhost/pengaduan_bullying/public/
# 4. Untuk admin: http://localhost/pengaduan_bullying/admin/
#    Username: admin
#    Password: Admin123!
```

## 📱 Mobile Responsive

Sistem ini fully responsive dan dapat diakses dari:
- Desktop/Laptop
- Tablet
- Smartphone

## 🔄 Update & Maintenance

### Backup Database
```bash
mysqldump -u root -p pengaduan_bullying > backup_$(date +%Y%m%d).sql
```

### Clear Old Logs
```sql
DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM rate_limit WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 1 DAY);
```

### Monitor Performance
- Check slow queries
- Monitor upload folder size
- Review error logs regularly

## 🌟 Changelog

### Version 2.0.0 (Latest)
- ✨ Live chat system dengan auto-refresh 2 detik
- 🎨 Kustomisasi sekolah (logo, background, nama)
- 💬 Facebook-style chat widget untuk admin
- 🔄 Multi-chat support (3 chat bersamaan)
- 📱 Improved mobile responsive design
- 🎯 Professional school-friendly UI
- 🖼️ Support WEBP format untuk images
- ⚡ Performance improvements

### Version 1.0.0
- 📝 Form pelaporan anonim
- 🔐 Tracking code & PIN system
- 📊 Admin dashboard
- 💬 Basic messaging system
- 📎 File upload support

---
**Versi:** 2.0.0  
**Tanggal Update:** November 2024  
**PHP Version:** 8.x+  
**MySQL Version:** 5.7+  
**Browser Support:** Chrome, Firefox, Safari, Edge (latest versions)
