<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

/**
 * File Konfigurasi Utama Aplikasi Cilengkrang Web Wisata
 * 
 * Mengatur base URL, zona waktu, error reporting, koneksi database,
 * memuat helper, dan mendefinisikan konstanta path.
 */

// 1. Mulai Session
// Selalu mulai session di paling atas file konfigurasi utama
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Pengaturan Base URL Dinamis
// Protokol (http atau https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";

// Host (misalnya, localhost atau domain.com)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback untuk CLI atau jika tidak diset

// Menentukan Base Path relatif terhadap Document Root
$project_root_on_disk = dirname(__DIR__); // Path absolut di disk ke folder proyek (C:\xampp\htdocs\Cilengkrang-Web-Wisata)
$document_root_norm = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
$project_root_on_disk_norm = str_replace('\\', '/', $project_root_on_disk);

// Hanya hitung base path jika document root ada
$base_path_relative = '';
if (!empty($document_root_norm)) {
    $base_path_relative = str_replace($document_root_norm, '', $project_root_on_disk_norm);
    // Pastikan base_path_relative selalu diawali slash jika tidak kosong, dan hilangkan slash ganda
    $base_path_relative = '/' . trim(str_replace('//', '/', $base_path_relative), '/');
    if ($base_path_relative === '/') { // Jika proyek ada di root web server
        $base_path_relative = '';
    }
} else {
    // Fallback jika document root tidak tersedia (misal: CLI)
    // Anda mungkin perlu mengatur base_path secara manual di sini jika perlu
    error_log("Peringatan: DOCUMENT_ROOT tidak tersedia. Base URL mungkin tidak akurat.");
}


// Variabel $base_url (selalu diakhiri dengan satu slash)
$base_url = $protocol . $host . $base_path_relative . "/";

// Definisikan konstanta BASE_URL untuk kemudahan penggunaan
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}


// 3. Pengaturan Zona Waktu dan Error Reporting
date_default_timezone_set("Asia/Jakarta");

// Pengaturan untuk Development (Tampilkan semua error dan log ke file)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Tampilkan error di browser (nonaktifkan di produksi)
ini_set('log_errors', 1);    // Aktifkan logging error ke file
// Pastikan file ini writable oleh server web
ini_set('error_log', dirname(__DIR__) . '/php_error.log');

// Pengaturan untuk Produksi (Contoh, aktifkan saat deploy)
/*
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/cilengkrang_php_errors.log'); // Path absolut yang aman
*/


// 4. Memuat File Helper TERLEBIH DAHULU
// Memastikan fungsi helper (termasuk otentikasi) tersedia sebelum digunakan di tempat lain
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/flash_message.php';
require_once __DIR__ . '/../includes/auth_helpers.php'; // Memuat fungsi seperti redirect_if_not_admin()


// 5. Koneksi Database
// Memuat file Koneksi.php yang mendefinisikan variabel global $conn
require_once __DIR__ . "/Koneksi.php";

// Pengecekan koneksi database yang robust setelah $conn didefinisikan
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_error_message = "Koneksi ke database gagal.";
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_error) {
        $db_error_message .= " Error MySQLi: " . $conn->connect_error;
    } elseif (function_exists('mysqli_connect_error') && mysqli_connect_error()) {
        // Fallback jika $conn bukan objek mysqli tapi ada error global koneksi
        $db_error_message .= " Error Global MySQLi: " . mysqli_connect_error();
    } else {
        // Jika $conn tidak diset sama sekali oleh Koneksi.php
        $db_error_message .= " Variabel koneksi (\$conn) tidak terdefinisi atau tidak valid.";
    }
    error_log("FATAL: " . $db_error_message . " (di config.php setelah mencoba include Koneksi.php)");
    http_response_code(503); // Service Unavailable
    // Tampilkan pesan yang lebih ramah di produksi
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database. Silakan coba beberapa saat lagi.");
}


// 6. Definisi Konstanta Path
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
} // C:/xampp/htdocs/Cilengkrang-Web-Wisata
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
if (!defined('UPLOADS_ALAT_SEWA_PATH')) {
    define('UPLOADS_ALAT_SEWA_PATH', UPLOADS_PATH . '/alat_sewa');
}
if (!defined('UPLOADS_BUKTI_PEMBAYARAN_PATH')) {
    define('UPLOADS_BUKTI_PEMBAYARAN_PATH', UPLOADS_PATH . '/bukti_pembayaran');
}


// 7. Otomatis Membuat Direktori Uploads Jika Belum Ada
$upload_paths_to_check = [
    // Pastikan kunci array sesuai dengan nama konstanta (tanpa _PATH)
    'UPLOADS' => UPLOADS_PATH,
    'UPLOADS_WISATA' => UPLOADS_WISATA_PATH,
    'UPLOADS_ARTIKEL' => UPLOADS_ARTIKEL_PATH,
    'UPLOADS_ALAT_SEWA' => UPLOADS_ALAT_SEWA_PATH,
    'UPLOADS_BUKTI_PEMBAYARAN' => UPLOADS_BUKTI_PEMBAYARAN_PATH
];

foreach ($upload_paths_to_check as $const_base => $path) {
    $const_name = $const_base . '_PATH'; // Nama konstanta lengkap
    if (defined($const_name) && !is_dir($path)) {
        // Coba buat direktori secara rekursif dengan izin yang sesuai
        if (!@mkdir($path, 0775, true) && !is_dir($path)) { // Cek lagi jika mkdir gagal
            error_log("Peringatan di config.php: Gagal membuat direktori {$path}. Periksa izin folder induk: " . dirname($path));
        } else {
            // Jika berhasil dibuat, coba set izin (opsional, tergantung environment)
            @chmod($path, 0775);
            error_log("Info di config.php: Direktori {$path} berhasil dibuat.");
        }
    } elseif (defined($const_name) && is_dir($path) && !is_writable($path)) {
        // Jika direktori ada tapi tidak writable oleh proses PHP
        error_log("Peringatan di config.php: Direktori {$path} ada tetapi tidak writable. Periksa izin folder.");
    }
}

// 8. Definisi Konstanta URL Lainnya
// Mendefinisikan ADMIN_URL jika belum ada (diambil dari BASE_URL)
if (!defined('ADMIN_URL')) {
    // Pastikan tidak ada slash ganda jika BASE_URL sudah berakhir slash
    define('ADMIN_URL', rtrim(BASE_URL, '/') . '/admin');
}


// Pengaturan global lainnya bisa ditambahkan di sini
define('NAMA_SITUS', 'Lembah Cilengkrang');
define('EMAIL_ADMIN', 'admin@example.com');

// Tidak perlu tag PHP penutup '
