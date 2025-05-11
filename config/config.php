<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

// Mulai session jika belum ada (penting untuk flash messages dan login)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Pengaturan Base URL ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback jika HTTP_HOST tidak diset (misalnya, untuk CLI)

// Nama folder proyek Anda di htdocs. Kosongkan jika proyek ada di root web server.
$project_folder_name = 'Cilengkrang-Web-Wisata'; // PASTIKAN INI SESUAI DENGAN NAMA FOLDER PROYEK ANDA

// Mendapatkan base path proyek secara dinamis
$script_path_directory = dirname($_SERVER['SCRIPT_NAME']); // Mendapatkan path direktori dari skrip yang sedang diakses

// Normalisasi untuk menghapus nama file jika SCRIPT_NAME menyertakannya, atau jika kita di /config/
if (basename($script_path_directory) === 'config' && strpos($_SERVER['SCRIPT_NAME'], '/config/config.php') !== false) {
    $project_root_path_relative_to_web_root = dirname($script_path_directory);
} else if (!empty($project_folder_name) && strpos($script_path_directory, '/' . $project_folder_name) === 0) {
    // Jika script ada di dalam folder proyek, ambil path hingga nama folder proyek
    $project_root_path_relative_to_web_root = '/' . $project_folder_name;
} else {
    // Fallback jika di root atau struktur tidak terduga (misalnya, jika config.php ada di root)
    $project_root_path_relative_to_web_root = '';
    // Cek jika script_path_directory adalah root dan tidak mengandung nama folder proyek
    if ($script_path_directory === '/' || $script_path_directory === '\\') {
        // Jika kita sudah di root dan tidak ada project folder name yang cocok, mungkin project_folder_name harus kosong.
    } else if (basename(dirname($script_path_directory)) === $project_folder_name && basename($script_path_directory) !== $project_folder_name) {
        // Jika kita satu level di dalam project folder (misal /Cilengkrang-Web-Wisata/admin)
        $project_root_path_relative_to_web_root = '/' . $project_folder_name;
    }
}
// Menghilangkan duplikasi slash dan memastikan diakhiri dengan satu slash
$base_url = $protocol . $host . rtrim(str_replace('//', '/', $project_root_path_relative_to_web_root), '/') . "/";


// --- Pengaturan Zona Waktu dan Error Reporting ---
date_default_timezone_set("Asia/Jakarta");

// Untuk Development: Tampilkan semua error dan log ke file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log'); // Simpan log error di root proyek

// Untuk Produksi (contoh, aktifkan saat deploy):
// error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', '/var/log/php_error.log'); // Path absolut di server produksi


// --- Koneksi Database ---
// Memuat file Koneksi.php yang akan mendefinisikan variabel $conn
require_once __DIR__ . "/Koneksi.php"; // $conn akan tersedia secara global dari file ini

// Pengecekan koneksi database dasar
if (!isset($conn) || $conn === null || ($conn instanceof mysqli && $conn->connect_error)) {
    $db_error_message = "Koneksi ke database gagal.";
    if ($conn instanceof mysqli && $conn->connect_error) {
        $db_error_message .= " Error: " . $conn->connect_error;
    } elseif (function_exists('mysqli_connect_error') && mysqli_connect_error()) {
        $db_error_message .= " Error: " . mysqli_connect_error();
    }
    error_log("FATAL: " . $db_error_message . " (di config.php)");
    http_response_code(503); // Service Unavailable
    // Tampilkan pesan yang lebih ramah di produksi
    exit("Maaf, situs sedang mengalami masalah teknis terkait database. Silakan coba lagi nanti.");
}


// --- Memuat File Helper ---
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/flash_message.php';
require_once __DIR__ . '/../includes/auth_helpers.php';


// --- Definisi Konstanta Path (Opsional tapi berguna) ---
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__)); // Path ke C:/xampp/htdocs/Cilengkrang-Web-Wisata
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
// ... tambahkan path lain jika perlu

// Otomatis membuat direktori uploads jika belum ada (dasar)
if (defined('UPLOADS_WISATA_PATH') && !is_dir(UPLOADS_WISATA_PATH)) {
    if (!@mkdir(UPLOADS_WISATA_PATH, 0775, true) && !is_dir(UPLOADS_WISATA_PATH)) {
        error_log("Peringatan di config.php: Gagal membuat direktori " . UPLOADS_WISATA_PATH . ". Periksa izin folder.");
    }
}
if (defined('UPLOADS_ARTIKEL_PATH') && !is_dir(UPLOADS_ARTIKEL_PATH)) {
    if (!@mkdir(UPLOADS_ARTIKEL_PATH, 0775, true) && !is_dir(UPLOADS_ARTIKEL_PATH)) {
        error_log("Peringatan di config.php: Gagal membuat direktori " . UPLOADS_ARTIKEL_PATH . ". Periksa izin folder.");
    }
}

// Anda bisa menambahkan pengaturan global lainnya di sini
// Misalnya, kunci API, pengaturan email, dll.

// Tidak disarankan menggunakan tag PHP penutup jika file ini murni berisi kode PHP
// 
