# 🛡️ Sistem Pengaduan Bullying Anonim

Sistem web untuk pelaporan bullying secara anonim dengan komunikasi dua arah antara pelapor dan konselor. Dibangun dengan PHP murni dan MySQL.

## 📋 Fitur Utama

### Untuk Siswa (Pelapor Anonim)
- ✅ **Laporan 100% Anonim** - Tidak perlu login atau memberikan identitas
- 📝 **Form Pelaporan Lengkap** - Kategori, kronologi, bukti, tingkat urgensi
- 📎 **Upload Bukti** - Foto, video, dokumen (maks 10MB)
- 🔐 **Kode Tracking & PIN** - Untuk cek status dan komunikasi
- 💬 **Chat Anonim** - Komunikasi dua arah dengan konselor
- 🚪 **Quick Exit Button** - Keluar cepat dengan tombol ESC
- 💾 **Auto-save Draft** - Draft tersimpan otomatis di browser

### Untuk Admin/Konselor
- 📊 **Dashboard Statistik** - Overview laporan dan notifikasi
- 📋 **Manajemen Laporan** - Lihat, filter, dan proses laporan
- 💬 **Balas Pesan Anonim** - Komunikasi dengan pelapor
- 📈 **Update Status** - Tracking progress penanganan
- 👥 **Assignment** - Assign laporan ke staf BK tertentu
- 📜 **Audit Trail** - Log semua aktivitas admin
- 🔔 **Notifikasi Real-time** - Alert untuk laporan darurat

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

2. **Buat Database MySQL**
   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Buat database baru dengan nama `pengaduan_bullying`
   - Import file `database/pengaduan_bullying.sql`

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
   - Folder `uploads/` akan dibuat otomatis di luar webroot
   - Pastikan PHP memiliki permission write

5. **Akses Aplikasi**
   - Frontend (Siswa): http://localhost/pengaduan_bullying/public/
   - Admin Panel: http://localhost/pengaduan_bullying/admin/

## 🔑 Kredensial Default

### Admin Login
- **Username:** admin
- **Password:** Admin123!
- **Email:** admin@sekolah.id

> ⚠️ **PENTING:** Segera ganti password default setelah instalasi!

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
- `report_messages` - Pesan chat anonim
- `report_attachments` - File bukti
- `admin_users` - Data admin
- `audit_log` - Log aktivitas
- `notifications` - Notifikasi admin
- `rate_limit` - Anti spam

## 🤝 Kontribusi

Sistem ini open source dan menerima kontribusi untuk:
- Bug fixes
- Fitur baru
- Dokumentasi
- Testing
- UI/UX improvements

## 📝 Lisensi

MIT License - Bebas digunakan dan dimodifikasi

## 🆘 Support & Kontak

Untuk bantuan teknis atau pertanyaan:
- Buat issue di repository
- Email: yogariski290508@gmail.com
- Dokumentasi: /public/info.php

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

---
**Versi:** 1.0.0  
**Tanggal Rilis:** November 2025  
**PHP Version:** 8.x  
**MySQL Version:** 5.7+
