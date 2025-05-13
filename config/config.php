<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

/**
 * File Konfigurasi Utama Aplikasi Cilengkrang Web Wisata
 * 
 * Mengatur base URL, zona waktu, error reporting, koneksi database,
 * memuat helper, memuat dan menginisialisasi model, dan mendefinisikan konstanta.
 */

// 1. Mulai Session (HARUS menjadi baris pertama yang dieksekusi sebelum output apapun)
if (session_status() == PHP_SESSION_NONE) {
    if (!headers_sent($file, $line)) {
        session_start();
    } else {
        $error_message_session = "FATAL ERROR di config.php: Tidak dapat memulai session karena headers sudah terkirim.";
        $error_message_session .= " Output dimulai dari file: {$file} pada baris: {$line}.";
        error_log($error_message_session);
        exit("Kesalahan kritis: Tidak dapat memulai sesi aplikasi. Detail: " . htmlspecialchars($error_message_session));
    }
}

// 2. Pengaturan Base URL Dinamis
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$project_root_on_disk_norm = str_replace('\\', '/', dirname(__DIR__));
$document_root_norm = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';

$base_path_relative = '';
if (!empty($document_root_norm) && strpos($project_root_on_disk_norm, $document_root_norm) === 0) {
    $base_path_relative = substr($project_root_on_disk_norm, strlen($document_root_norm));
} elseif (isset($_SERVER['SCRIPT_NAME'])) {
    $script_directory = dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
    $project_dir_name = basename($project_root_on_disk_norm);
    if (strpos($script_directory, '/' . $project_dir_name) !== false) {
        $base_path_relative = substr($script_directory, 0, strpos($script_directory, '/' . $project_dir_name) + strlen('/' . $project_dir_name));
    } elseif (in_array($script_directory, ['/', '\\', ''], true) && $project_dir_name !== '') {
        $base_path_relative = '/' . $project_dir_name;
    } else {
        $base_path_relative = $script_directory;
    }
    error_log("Peringatan config.php: Menggunakan SCRIPT_NAME untuk base_path_relative. Hasil: '" . $base_path_relative . "' dari script_dir: '" . $script_directory . "'");
} else {
    error_log("Peringatan config.php: Tidak dapat menentukan base_path_relative otomatis. BASE_URL mungkin tidak akurat.");
}

$base_path_relative = '/' . trim(str_replace('//', '/', $base_path_relative), '/');
if ($base_path_relative === '/') $base_path_relative = '';

$base_url = $protocol . $host . $base_path_relative . "/"; // Pastikan diakhiri slash

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}

// 3. Pengaturan Lingkungan, Zona Waktu, dan Error Reporting
date_default_timezone_set("Asia/Jakarta");

if (!defined('IS_DEVELOPMENT')) {
    define('IS_DEVELOPMENT', (in_array($host, ['localhost', '127.0.0.1']) || strpos($host, '.test') !== false || strpos($host, '.local') !== false));
}

if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
$logs_dir_path = dirname(__DIR__) . '/logs';
if (!is_dir($logs_dir_path)) {
    if (!@mkdir($logs_dir_path, 0775, true) && !is_dir($logs_dir_path)) {
        error_log("KRITIKAL config.php: Gagal membuat direktori logs di {$logs_dir_path}.");
    } else {
        @chmod($logs_dir_path, 0775);
    }
}
$error_log_filename = IS_DEVELOPMENT ? 'php_error_dev.log' : 'php_error_prod.log';
ini_set('error_log', $logs_dir_path . '/' . $error_log_filename);


// 4. Definisi Konstanta Path Aplikasi (SEBELUM memuat model dan helper)
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', ROOT_PATH . '/config');
if (!defined('CONTROLLERS_PATH')) define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
if (!defined('MODELS_PATH')) define('MODELS_PATH', ROOT_PATH . '/models');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', ROOT_PATH . '/template');
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', ROOT_PATH . '/includes');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . '/public');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

if (!defined('UPLOADS_WISATA_PATH')) define('UPLOADS_WISATA_PATH', UPLOADS_PATH . '/wisata');
if (!defined('UPLOADS_ARTIKEL_PATH')) define('UPLOADS_ARTIKEL_PATH', UPLOADS_PATH . '/artikel');
if (!defined('UPLOADS_ALAT_SEWA_PATH')) define('UPLOADS_ALAT_SEWA_PATH', UPLOADS_PATH . '/alat_sewa');
if (!defined('UPLOADS_BUKTI_PEMBAYARAN_PATH')) define('UPLOADS_BUKTI_PEMBAYARAN_PATH', UPLOADS_PATH . '/bukti_pembayaran');


// 5. Memuat File Helper
require_once INCLUDES_PATH . '/helpers.php';
require_once INCLUDES_PATH . '/flash_message.php';
require_once INCLUDES_PATH . '/auth_helpers.php';


// 6. Koneksi Database
require_once CONFIG_PATH . "/Koneksi.php"; // Menggunakan konstanta CONFIG_PATH

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_error_message = "Koneksi ke database gagal.";
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_error) {
        $db_error_message .= " Error MySQLi: (" . $conn->connect_errno . ") " . $conn->connect_error;
    } elseif (function_exists('mysqli_connect_error') && mysqli_connect_error()) {
        $db_error_message .= " Error Global MySQLi: (" . mysqli_connect_errno() . ") " . mysqli_connect_error();
    } else {
        $db_error_message .= " Variabel koneksi (\$conn) tidak terdefinisi/valid setelah include Koneksi.php.";
    }
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    $display_error_detail = IS_DEVELOPMENT ? "<br><small style='color:gray;font-family:monospace;'>Detail: " . htmlspecialchars($db_error_message) . "</small>" : "";
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database. Silakan coba beberapa saat lagi." . $display_error_detail);
}

// 7. Memuat SEMUA Model dan Menginisialisasi Koneksi Database untuk Model
// Ini adalah langkah penting jika model Anda menggunakan metode statis setDbConnection() atau init().
$model_files = glob(MODELS_PATH . '/*.php');
foreach ($model_files as $model_file) {
    require_once $model_file;
    $class_name = basename($model_file, '.php');
    if (class_exists($class_name)) {
        if (method_exists($class_name, 'setDbConnection')) {
            $class_name::setDbConnection($conn);
        } elseif (method_exists($class_name, 'init') && $class_name === 'SewaAlat' && defined('UPLOADS_ALAT_SEWA_PATH')) {
            // Kasus khusus untuk SewaAlat yang menggunakan init()
            $class_name::init($conn, UPLOADS_ALAT_SEWA_PATH);
        }
    }
}


// 8. Otomatis Membuat Direktori Uploads Jika Belum Ada
// (Path sudah didefinisikan di atas, pastikan konstanta UPLOADS_ALAT_SEWA_PATH dan UPLOADS_BUKTI_PEMBAYARAN_PATH ada jika digunakan di sini)
$upload_paths_to_create_if_not_exist = [
    UPLOADS_PATH,
    UPLOADS_WISATA_PATH,
    UPLOADS_ARTIKEL_PATH,
    UPLOADS_ALAT_SEWA_PATH,
    UPLOADS_BUKTI_PEMBAYARAN_PATH
];

foreach ($upload_paths_to_create_if_not_exist as $path) {
    if (defined('UPLOADS_PATH') && !empty($path)) { // Pastikan path tidak kosong
        if (!is_dir($path)) {
            if (!@mkdir($path, 0775, true) && !is_dir($path)) {
                error_log("Peringatan config.php: Gagal membuat direktori {$path}. Periksa izin folder induk: " . dirname($path));
            } else {
                @chmod($path, 0775);
                error_log("Info config.php: Direktori {$path} berhasil dibuat.");
            }
        } elseif (!is_writable($path)) {
            error_log("Peringatan config.php: Direktori {$path} ada tetapi tidak writable. Periksa izin folder.");
        }
    }
}

// 9. Definisi Konstanta URL Lainnya (BASE_URL sudah memiliki trailing slash)
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . 'admin');
if (!defined('USER_URL')) define('USER_URL', BASE_URL . 'user');
if (!defined('AUTH_URL')) define('AUTH_URL', BASE_URL . 'auth');
if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . 'public');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', ASSETS_URL . '/uploads');


// 10. Pengaturan Global Situs Lainnya
if (!defined('NAMA_SITUS')) define('NAMA_SITUS', 'Lembah Cilengkrang');
if (!defined('EMAIL_ADMIN')) define('EMAIL_ADMIN', 'admin@example.com');
if (!defined('DEFAULT_ITEMS_PER_PAGE')) define('DEFAULT_ITEMS_PER_PAGE', 10);

// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.