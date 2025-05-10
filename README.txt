Cilengkrang Web Wisata
======================

Selamat datang di repositori kode untuk Website Pariwisata Cilengkrang. Proyek ini bertujuan untuk menyediakan platform digital yang informatif dan interaktif bagi calon pengunjung Destinasi Wisata Alam Cilengkrang.

Fitur Utama
-----------
- Informasi Destinasi: Detail mengenai berbagai spot wisata di Cilengkrang (Air Panas, Curug, Hutan Pinus, Area Camping, dll.).
- Galeri Foto & Video: Menampilkan keindahan visual Cilengkrang.
- Artikel & Berita: Informasi terkini, tips perjalanan, dan cerita menarik seputar Cilengkrang.
- Sistem Pemesanan Tiket: (Jika diimplementasikan) Memungkinkan pengunjung memesan tiket secara online.
- Formulir Kontak & Feedback: Sarana komunikasi bagi pengunjung untuk bertanya atau memberikan masukan.
- Sistem Autentikasi:
    - Registrasi dan Login untuk Pengguna.
    - Panel Admin untuk pengelolaan konten dan data.
- Panel Admin:
    - CRUD (Create, Read, Update, Delete) untuk data Wisata.
    - CRUD untuk Artikel.
    - Manajemen Galeri Foto/Video.
    - Pengelolaan data Booking (jika ada).
    - Melihat Pesan Kontak dan Feedback.
    - (Opsional) Manajemen Pengguna.
- Desain Responsif: Tampilan yang optimal di berbagai perangkat (desktop, tablet, mobile).

Struktur Folder Utama
---------------------
/Cilengkrang-Web-Wisata
|-- admin/             # File dan logika untuk panel admin
|-- auth/              # Skrip untuk autentikasi (login, register, logout)
|-- config/            # File konfigurasi (database, base URL, helpers)
|-- controllers/       # (Jika menggunakan pola MVC) Logika bisnis aplikasi
|-- includes/          # Fungsi-fungsi helper global
|-- models/            # Interaksi dengan database (logika data)
|-- public/            # Aset yang dapat diakses publik (CSS, JS, gambar, font)
|   |-- css/
|   |-- img/
|   |-- js/
|-- template/          # File template header dan footer (publik & admin)
|-- user/              # Halaman untuk pengguna yang sudah login (non-admin)
|-- wisata/            # Halaman-halaman publik terkait informasi wisata
|-- .htaccess          # Konfigurasi server Apache (untuk routing)
|-- index.php          # Titik masuk utama aplikasi (front controller/landing page)
|-- README.md          # Dokumentasi ini
|-- README.txt         # Versi teks dari README

Panduan Instalasi & Setup Lokal
-------------------------------
1. Clone Repositori:
   git clone [URL_REPOSITORY_ANDA] Cilengkrang-Web-Wisata
   cd Cilengkrang-Web-Wisata
2. Server Lokal: Pastikan Anda memiliki server lokal seperti XAMPP, WAMP, atau MAMP yang sudah terinstal dan berjalan (Apache, MySQL, PHP).
3. Database:
   - Buat database baru di MySQL/phpMyAdmin (misalnya, `cilengkrang_web_wisata`).
   - Import file SQL `database/cilengkrang_db.sql` (jika Anda menyediakannya) ke database yang baru dibuat. Jika tidak ada, buat tabel secara manual sesuai skema yang dibutuhkan (lihat `models/` untuk referensi kolom).
4. Konfigurasi Database:
   - Buka file `config/Koneksi.php`.
   - Sesuaikan `$host`, `$db_user`, `$db_pass`, dan `$db_name` dengan pengaturan database MySQL Anda.
5. Konfigurasi Base URL (Otomatis):
   - File `config/config.php` mencoba mendeteksi `$base_url` secara otomatis. Pastikan nama folder proyek Anda (`Cilengkrang-Web-Wisata`) benar di variabel `$project_folder_name` dalam file tersebut jika Anda menjalankannya di subdirektori (misalnya `http://localhost/Cilengkrang-Web-Wisata/`).
6. Akses Web:
   - Akses proyek melalui browser Anda: `http://localhost/Cilengkrang-Web-Wisata/` (atau path yang sesuai).

Teknologi yang Digunakan (Contoh)
--------------------------------
- PHP (Procedural dengan elemen OOP)
- MySQL / MariaDB
- HTML5
- CSS3 (dengan Bootstrap 5 untuk layout dasar)
- JavaScript (untuk interaktivitas sisi klien)
- Apache (Web Server via XAMPP/WAMP/MAMP)

Kontribusi
----------
Jika Anda ingin berkontribusi pada proyek ini, silakan buat *fork* dan ajukan *pull request*.

Kontak
------
Untuk pertanyaan atau kendala terkait proyek ini, silakan hubungi: admin@cilengkrangwisata.com (ganti dengan email kontak Anda yang sebenarnya).

---