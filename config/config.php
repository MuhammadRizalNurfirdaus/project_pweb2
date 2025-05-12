<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

// Selalu mulai session di paling atas file konfigurasi utama
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Pengaturan Base URL ---
// Protokol (http atau https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";

// Host (misalnya, localhost atau domain.com)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback untuk CLI

// Mendapatkan path skrip tempat config.php berada, relatif terhadap document root web server
// Contoh: Jika URL adalah http://localhost/Cilengkrang-Web-Wisata/admin/dashboard.php
// dan config.php dipanggil dari admin/dashboard.php (melalui __DIR__ . '/../config/config.php')
// maka SCRIPT_NAME mungkin /Cilengkrang-Web-Wisata/admin/dashboard.php
// dirname($_SERVER['SCRIPT_NAME']) akan memberi /Cilengkrang-Web-Wisata/admin
// dirname(dirname($_SERVER['SCRIPT_NAME'])) akan memberi /Cilengkrang-Web-Wisata (ini yang kita mau sebagai base path)

// Logika yang lebih sederhana dan umum untuk base_url:
// Asumsi bahwa folder 'config' berada satu level di bawah root folder proyek Anda.
// __DIR__ adalah C:\xampp\htdocs\Cilengkrang-Web-Wisata\config
// dirname(__DIR__) adalah C:\xampp\htdocs\Cilengkrang-Web-Wisata (ROOT_PATH proyek)
$project_root_on_disk = dirname(__DIR__); // Path absolut di disk ke folder proyek

// Menghilangkan DOCUMENT_ROOT dari $project_root_on_disk untuk mendapatkan base path relatif dari web root
$document_root_norm = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$project_root_on_disk_norm = str_replace('\\', '/', $project_root_on_disk);

$base_path_relative = str_replace($document_root_norm, '', $project_root_on_disk_norm);
// Pastikan base_path_relative selalu diawali slash jika tidak kosong, dan hilangkan slash ganda
$base_path_relative = '/' . trim(str_replace('//', '/', $base_path_relative), '/');
if ($base_path_relative === '/') { // Jika proyek ada di root web server
    $base_path_relative = '';
}

// $base_url selalu diakhiri dengan satu slash
$base_url = $protocol . $host . $base_path_relative . "/";


// --- Pengaturan Zona Waktu dan Error Reporting ---
date_default_timezone_set("Asia/Jakarta");

// Untuk Development: Tampilkan semua error dan log ke file
error_reporting(E_ALL);
ini_set('display_errors', 1); // Tampilkan error di browser
ini_set('log_errors', 1);    // Juga log error ke file
// Simpan log error di root proyek (C:\xampp\htdocs\Cilengkrang-Web-Wisata\php_error.log)
ini_set('error_log', dirname(__DIR__) . '/php_error.log'); // Menggunakan dirname(__DIR__) untuk path root proyek
// Pastikan file php_error.log atau direktori root proyek writable oleh server web.

// Untuk Produksi (contoh, aktifkan saat deploy):
// error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', '/var/log/myapp_php_error.log'); // Path absolut yang aman di server produksi


// --- Koneksi Database ---
// Memuat file Koneksi.php yang akan mendefinisikan variabel $conn global
require_once __DIR__ . "/Koneksi.php";

// Pengecekan koneksi database yang lebih robust
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_error_message = "Koneksi ke database gagal.";
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_error) {
        $db_error_message .= " Error MySQLi: " . $conn->connect_error;
    } elseif (function_exists('mysqli_connect_error') && mysqli_connect_error()) {
        // Fallback jika $conn bukan objek mysqli tapi ada error global
        $db_error_message .= " Error Global MySQLi: " . mysqli_connect_error();
    } else {
        $db_error_message .= " Objek koneksi tidak valid atau tidak tersedia.";
    }
    error_log("FATAL: " . $db_error_message . " (di config.php)");
    http_response_code(503); // Service Unavailable
    // Tampilkan pesan yang lebih ramah di produksi tanpa detail error teknis
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database. Silakan coba beberapa saat lagi.");
}


// --- Memuat File Helper ---
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/flash_message.php';
require_once __DIR__ . '/../includes/auth_helpers.php';


// --- Definisi Konstanta Path (Ini sudah baik) ---
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__)); // C:/xampp/htdocs/Cilengkrang-Web-Wisata
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . '/public');
}
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
}
if (!defined('UPLOADS_WISATA_PATH')) {
    define('UPLOADS_WISATA_PATH', UPLOADS_PATH . '/wisata');
}
if (!defined('UPLOADS_ARTIKEL_PATH')) {
    define('UPLOADS_ARTIKEL_PATH', UPLOADS_PATH . '/artikel');
}
// TAMBAHKAN KONSTANTA UNTUK UPLOAD ALAT SEWA
if (!defined('UPLOADS_ALAT_SEWA_PATH')) {
    define('UPLOADS_ALAT_SEWA_PATH', UPLOADS_PATH . '/alat_sewa');
}


// Otomatis membuat direktori uploads jika belum ada
$upload_paths_to_check = [
    UPLOADS_PATH, // Direktori uploads utama
    UPLOADS_WISATA_PATH,
    UPLOADS_ARTIKEL_PATH,
    UPLOADS_ALAT_SEWA_PATH // Tambahkan path baru
];

foreach ($upload_paths_to_check as $path) {
    if (defined(strtoupper(basename($path)) . '_PATH') && !is_dir($path)) { // Cek jika konstanta pathnya ada
        // @mkdir untuk menekan warning jika direktori sudah ada (meskipun is_dir harusnya menangani)
        // true untuk recursive creation
        if (!@mkdir($path, 0775, true) && !is_dir($path)) { // Cek lagi jika mkdir gagal tapi direktori belum ada
            error_log("Peringatan di config.php: Gagal membuat direktori " . $path . ". Periksa izin folder induk.");
        }
    } elseif (defined(strtoupper(basename($path)) . '_PATH') && is_dir($path) && !is_writable($path)) {
        error_log("Peringatan di config.php: Direktori " . $path . " ada tetapi tidak writable. Periksa izin folder.");
    }
}

// Pengaturan global lainnya bisa ditambahkan di sini
// define('NAMA_SITUS', 'Lembah Cilengkrang');
// define('EMAIL_ADMIN', 'admin@example.com');

// Tidak perlu tag PHP penutup jika ini adalah akhir file dan hanya berisi kode PHP
// 
