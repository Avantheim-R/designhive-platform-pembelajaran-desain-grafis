# DesignHIve - Platform Pembelajaran Desain Grafis

DesignHIve adalah platform pembelajaran desain grafis berbasis web yang dirancang untuk memudahkan siswa dalam mempelajari dasar-dasar desain grafis secara terstruktur dan interaktif.

## Fitur Utama

- **Materi Pembelajaran Terstruktur**
  - Pembagian materi berdasarkan BAB dan sub-BAB
  - Konten multimedia (teks, gambar, video)
  - Progress tracking untuk setiap materi

- **Kuis Interaktif**
  - Multiple choice questions
  - Drag and drop exercises
  - Matching questions
  - Hasil dan feedback langsung

- **Forum Diskusi**
  - Diskusi per BAB
  - Sistem komentar
  - Moderasi oleh guru

- **Sistem Penugasan**
  - Upload tugas
  - Penilaian dan feedback
  - Tracking progress

- **Dashboard**
  - Dashboard siswa untuk memantau progress
  - Dashboard guru untuk manajemen konten dan penilaian

## Teknologi yang Digunakan

- PHP 7.4+
- MySQL 5.7+
- HTML5
- Tailwind CSS
- JavaScript
- Font Awesome
- Google Fonts

## Persyaratan Sistem

- Web Server (Apache/Nginx)
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Ekstensi PHP yang diperlukan:
  - PDO
  - PDO MySQL
  - GD Library
  - FileInfo
  - Session

## Instalasi

1. Clone repository ini:
   ```bash
   git clone https://github.com/username/designhive.git
   ```

2. Import skema database:
   ```bash
   mysql -u username -p designhive_db < database_schema.sql
   ```

3. Konfigurasi database:
   - Buka `config/database.php`
   - Sesuaikan kredensial database:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'designhive_db');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

4. Buat direktori yang diperlukan:
   ```bash
   mkdir -p public/uploads/{assignments,certificates}
   mkdir -p logs
   chmod 777 public/uploads logs
   ```

5. Konfigurasi web server:
   - Set document root ke direktori `public`
   - Aktifkan mod_rewrite untuk Apache

## Struktur Direktori

```
designhive/
├── config/             # Konfigurasi aplikasi
├── includes/           # File PHP yang digunakan bersama
├── public/            # Document root
│   ├── api/          # API endpoints
│   ├── Lesson/       # Halaman materi pembelajaran
│   └── uploads/      # File yang diupload
├── logs/             # Log aplikasi
└── resources/        # Asset statis
```

## Penggunaan

### Akun Default

- **Guru**
  - NIS: TEACHER001
  - Password: password123

### Menambahkan Materi Baru

1. Login sebagai guru
2. Akses menu "Kelola Konten"
3. Pilih BAB yang sesuai
4. Klik "Tambah Materi Baru"
5. Isi form dengan konten yang diperlukan

### Manajemen Siswa

1. Login sebagai guru
2. Akses menu "Manajemen Siswa"
3. Tambah/edit/hapus data siswa

## Keamanan

- Semua password di-hash menggunakan bcrypt
- Implementasi CSRF protection
- Validasi input ketat
- Prepared statements untuk query database
- Session management yang aman

## Maintenance

### Backup Database

```bash
mysqldump -u username -p designhive_db > backup.sql
```

### Log Files

Log files tersimpan di direktori `logs/`:
- `error.log`: Error aplikasi
- `access.log`: Access log
- `debug.log`: Debug information (development only)

## Kontribusi

1. Fork repository
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## Lisensi

Distributed under the MIT License. See `LICENSE` for more information.

## Kontak

Project Link: [https://github.com/username/designhive](https://github.com/username/designhive)

## Acknowledgments

- [Tailwind CSS](https://tailwindcss.com)
- [Font Awesome](https://fontawesome.com)
- [Google Fonts](https://fonts.google.com)
