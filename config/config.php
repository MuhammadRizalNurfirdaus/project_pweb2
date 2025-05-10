<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Base URL Setup ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Nama folder proyek Anda di htdocs
$project_folder_name = 'Cilengkrang-Web-Wisata'; // PASTIKAN INI SESUAI NAMA FOLDER ANDA

// Logika untuk mendapatkan base path yang lebih robust
$document_root_norm = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$script_filename_norm = str_replace('\\', '/', __DIR__); // __DIR__ dari config.php adalah C:/xampp/htdocs/Cilengkrang-Web-Wisata/config

// Hapus document root dari script filename untuk mendapatkan path relatif dari web root
$relative_path_from_web_root = str_replace($document_root_norm, '', $script_filename_norm);
// Dari path config, naik satu level untuk mendapatkan base path proyek
$base_path = dirname($relative_path_from_web_root);

// Bersihkan jika base_path adalah root ('/' atau '\')
if ($base_path === '/' || $base_path === '\\') {
    $base_path = '';
}

$base_url = $protocol . $host . $base_path . "/";


// --- Other Global Settings ---
date_default_timezone_set("Asia/Jakarta");
error_reporting(E_ALL);
ini_set('display_errors', 1); // Untuk development. Set ke 0 di produksi dan log error.

// --- Database Connection ---
require_once __DIR__ . "/Koneksi.php"; // $conn menjadi tersedia secara global

// --- Include Helper Files ---
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/flash_message.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
