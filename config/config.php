<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

/**
 * File Konfigurasi Utama Aplikasi Cilengkrang Web Wisata
 */

// --- LANGKAH DEBUGGING AWAL YANG SANGAT PENTING ---
// Aktifkan ini HANYA saat debugging untuk melihat error PHP langsung di browser.
// Komentari atau hapus ini untuk produksi.
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
// --- AKHIR LANGKAH DEBUGGING AWAL ---

// 1. Mulai Session (HARUS menjadi baris pertama yang dieksekusi sebelum output apapun)
if (session_status() == PHP_SESSION_NONE) {
    if (!headers_sent($php_file, $php_line)) { // Variabel $file dan $line diganti agar tidak konflik
        session_start();
    } else {
        $error_message_session = "FATAL ERROR di config.php: Tidak dapat memulai session karena headers sudah terkirim.";
        if (isset($php_file) && isset($php_line)) {
            $error_message_session .= " Output dimulai dari file: {$php_file} pada baris: {$php_line}.";
        }
        error_log($error_message_session);
        // Di tahap ini, menampilkan pesan HTML mungkin tidak efektif jika errornya sangat awal.
        // Mengandalkan log server adalah yang terbaik.
        exit("Kesalahan kritis pada aplikasi. Silakan hubungi administrator. (Error Code: SESS_INIT_FAIL)");
    }
}

// 2. Pengaturan Base URL Dinamis
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback untuk CLI atau jika HTTP_HOST tidak ada

// Logika penentuan base_path_relative yang lebih sederhana dan umum
$script_name = $_SERVER['SCRIPT_NAME'] ?? ''; // e.g., /Cilengkrang-Web-Wisata/config/config.php or /index.php
$script_path_parts = explode('/', dirname(str_replace('\\', '/', $script_name)));

// Asumsi: Nama folder proyek adalah 'Cilengkrang-Web-Wisata' (sesuaikan jika berbeda)
// atau folder di mana file config.php ini berada adalah 'config' di dalam root proyek.
$project_folder_name = basename(dirname(__DIR__)); // e.g., Cilengkrang-Web-Wisata

$base_path_relative = '';
$path_segments = [];
foreach ($script_path_parts as $segment) {
    if (empty($segment)) continue;
    $path_segments[] = $segment;
    if ($segment === $project_folder_name) {
        break; // Berhenti jika sudah menemukan nama folder proyek
    }
}
if (!empty($path_segments)) {
    $base_path_relative = '/' . implode('/', $path_segments);
}
// Jika script ada di root dan nama folder proyek adalah nama domain/subdomain, base path adalah ''
if (count($path_segments) === 1 && $path_segments[0] === $project_folder_name && $_SERVER['HTTP_HOST'] === $project_folder_name) {
    $base_path_relative = '';
}


$base_path_relative = rtrim(str_replace('//', '/', $base_path_relative), '/');

$base_url = rtrim($protocol . $host . $base_path_relative, '/') . "/";

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
// error_log("INFO config.php: BASE_URL di-set ke: " . BASE_URL);


// 3. Pengaturan Lingkungan, Zona Waktu, dan Error Reporting
date_default_timezone_set("Asia/Jakarta");

if (!defined('IS_DEVELOPMENT')) {
    define('IS_DEVELOPMENT', (in_array($host, ['localhost', '127.0.0.1']) || strpos($host, '.test') !== false || strpos($host, '.local') !== false));
}

// Pengaturan error reporting dipindahkan ke atas agar error saat parsing file ini juga bisa terlihat jika display_errors diaktifkan di awal.
// Namun, untuk masalah loading, error log server adalah yang utama.
if (IS_DEVELOPMENT) {
    if (ini_get('display_errors') !== '1') ini_set('display_errors', 1); // Pastikan aktif jika IS_DEVELOPMENT
    if (error_reporting() !== E_ALL) error_reporting(E_ALL);
} else {
    if (ini_get('display_errors') !== '0') ini_set('display_errors', 0);
    if (error_reporting() !== (E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING)) {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
    }
}

ini_set('log_errors', 1);
$logs_dir_path = dirname(__DIR__) . '/logs';
if (!is_dir($logs_dir_path)) {
    if (!@mkdir($logs_dir_path, 0775, true) && !is_dir($logs_dir_path)) {
        error_log("KRITIKAL config.php: Gagal membuat direktori logs di {$logs_dir_path}. Periksa izin pada folder induk: " . dirname($logs_dir_path));
    } else {
        @chmod($logs_dir_path, 0775);
        // error_log("INFO config.php: Direktori logs berhasil dibuat di {$logs_dir_path}.");
    }
}
$error_log_filename = IS_DEVELOPMENT ? 'php_error_dev.log' : 'php_error_prod.log';
$error_log_path = $logs_dir_path . '/' . $error_log_filename;

// Coba set error_log path, jika gagal, PHP akan menggunakan default server.
if (is_writable($logs_dir_path) || (file_exists($error_log_path) && is_writable($error_log_path)) || (!file_exists($error_log_path) && is_writable(dirname($error_log_path)))) {
    ini_set('error_log', $error_log_path);
    // error_log("INFO config.php: Error logging di-set ke: " . $error_log_path . " (Saat startup config)");
} else {
    error_log("KRITIKAL config.php: Direktori logs {$logs_dir_path} atau file log {$error_log_path} tidak dapat ditulis. Error PHP mungkin tidak tercatat ke file custom, akan menggunakan default server.");
}


// 4. Definisi Konstanta Path Aplikasi Fisik
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
if (!defined('UPLOADS_GALERI_PATH')) define('UPLOADS_GALERI_PATH', UPLOADS_PATH . '/galeri');
if (!defined('UPLOADS_PROFIL_PATH')) define('UPLOADS_PROFIL_PATH', UPLOADS_PATH . '/profil');


// 5. Memuat File Helper
$helper_files_to_load = [
    INCLUDES_PATH . '/helpers.php',
    INCLUDES_PATH . '/flash_message.php',
    INCLUDES_PATH . '/auth_helpers.php'
];
foreach ($helper_files_to_load as $helper_file) {
    if (file_exists($helper_file)) {
        require_once $helper_file;
    } else {
        $err_msg_helper = "KRITIS config.php: File helper '{$helper_file}' tidak ditemukan.";
        error_log($err_msg_helper);
        // Pertimbangkan exit jika helper kritis tidak ada, tergantung seberapa penting helper tersebut
        // exit("Kesalahan konfigurasi: Komponen penting aplikasi hilang (" . basename($helper_file) . ").");
    }
}


// 6. Koneksi Database
if (!file_exists(CONFIG_PATH . "/Koneksi.php")) {
    error_log("FATAL config.php: File Koneksi.php tidak ditemukan.");
    exit("Kesalahan kritis: Komponen koneksi database tidak ditemukan.");
}
require_once CONFIG_PATH . "/Koneksi.php"; // File ini seharusnya mendefinisikan variabel $conn

if (!isset($conn) || !($conn instanceof mysqli)) {
    $db_error_message = "Variabel koneksi (\$conn) tidak terdefinisi atau bukan instance mysqli setelah include Koneksi.php.";
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    $display_error_detail = IS_DEVELOPMENT ? "<br><small style='color:gray;font-family:monospace;'>Detail Teknis: " . htmlspecialchars($db_error_message) . "</small>" : "";
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database (KNF)." . $display_error_detail);
}
if ($conn->connect_error) {
    $db_error_message = "Koneksi ke database gagal. Error MySQLi: ({$conn->connect_errno}) {$conn->connect_error}";
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    $display_error_detail = IS_DEVELOPMENT ? "<br><small style='color:gray;font-family:monospace;'>Detail Teknis: " . htmlspecialchars($db_error_message) . "</small>" : "";
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database (KCE)." . $display_error_detail);
}
if (!$conn->set_charset("utf8mb4")) {
    error_log("PERINGATAN config.php: Gagal mengatur charset koneksi ke utf8mb4. Error: " . $conn->error);
}
// error_log("INFO config.php: Koneksi database berhasil dan charset di-set ke utf8mb4. Host: " . $conn->host_info);


// 7. Memuat SEMUA Model dan Menginisialisasi Koneksi Database serta Path Upload untuk Model
// error_log("INFO config.php: Memulai proses pemuatan dan inisialisasi Model.");
$model_files = glob(MODELS_PATH . '/*.php');

if ($model_files === false || empty($model_files)) {
    error_log("KRITIKAL config.php: Tidak ada file model ditemukan di '" . MODELS_PATH . "' atau path tidak dapat dibaca.");
} else {
    foreach ($model_files as $model_file) {
        if (is_file($model_file)) {
            // error_log("DEBUG config.php: Mencoba memuat model: " . $model_file); // Aktifkan untuk debugging file mana yang error
            require_once $model_file; // Jika ada error di sini, skrip akan berhenti
            // error_log("DEBUG config.php: BERHASIL memuat model: " . $model_file);

            $class_name = basename($model_file, '.php');

            if (class_exists($class_name, false)) {
                $model_initialized_successfully = false;
                if (method_exists($class_name, 'init')) {
                    $upload_path_for_this_model = null; // Default
                    if ($class_name === 'SewaAlat' && defined('UPLOADS_ALAT_SEWA_PATH')) $upload_path_for_this_model = UPLOADS_ALAT_SEWA_PATH;
                    elseif ($class_name === 'User' && defined('UPLOADS_PROFIL_PATH')) $upload_path_for_this_model = UPLOADS_PROFIL_PATH;
                    // Tambahkan model lain yang butuh path upload di sini
                    // elseif ($class_name === 'Artikel' && defined('UPLOADS_ARTIKEL_PATH')) $upload_path_for_this_model = UPLOADS_ARTIKEL_PATH;

                    try {
                        $reflectionMethod = new ReflectionMethod($class_name, 'init');
                        $num_params = $reflectionMethod->getNumberOfParameters();

                        if ($num_params === 2 && $upload_path_for_this_model !== null) {
                            $class_name::init($conn, $upload_path_for_this_model);
                            $model_initialized_successfully = true;
                        } elseif ($num_params === 1) {
                            $class_name::init($conn);
                            $model_initialized_successfully = true;
                        } elseif ($num_params === 0) {
                            $class_name::init();
                            $model_initialized_successfully = true;
                        }
                        // if ($model_initialized_successfully) error_log("INFO config.php: Model {$class_name} diinisialisasi dengan init().");

                    } catch (ReflectionException $e) { /* Log error jika perlu */
                    }
                }

                if (!$model_initialized_successfully && method_exists($class_name, 'setDbConnection')) {
                    try {
                        $reflectionSetDb = new ReflectionMethod($class_name, 'setDbConnection');
                        if ($reflectionSetDb->getNumberOfParameters() === 1) {
                            $class_name::setDbConnection($conn);
                            $model_initialized_successfully = true;
                            // error_log("INFO config.php: Model {$class_name} diinisialisasi dengan setDbConnection(conn).");
                        }
                    } catch (ReflectionException $e) { /* Log error jika perlu */
                    }
                }

                if (!$model_initialized_successfully) {
                    error_log("KRITIKAL config.php: Model {$class_name} TIDAK DAPAT diinisialisasi. Tidak ada metode init() atau setDbConnection() yang cocok.");
                }
            } else {
                error_log("KRITIKAL config.php: Kelas {$class_name} tidak ditemukan setelah require file model '{$model_file}'. Periksa nama file vs nama kelas.");
            }
        }
    }
}
// error_log("INFO config.php: Selesai proses inisialisasi Model.");


// 7.b. Memuat SEMUA Controller (Opsional di config, bisa per halaman)
// error_log("INFO config.php: Memulai proses pemuatan semua Controller.");
if (defined('CONTROLLERS_PATH') && is_dir(CONTROLLERS_PATH)) {
    $controller_files = glob(CONTROLLERS_PATH . '/*.php');
    if ($controller_files !== false && !empty($controller_files)) {
        foreach ($controller_files as $controller_file) {
            if (is_file($controller_file)) {
                require_once $controller_file;
            }
        }
    }
}
// error_log("INFO config.php: Selesai proses pemuatan semua Controller.");


// 8. Otomatis Membuat Direktori Uploads
$upload_paths_to_create_if_not_exist = [
    UPLOADS_PATH,
    UPLOADS_WISATA_PATH,
    UPLOADS_ARTIKEL_PATH,
    UPLOADS_ALAT_SEWA_PATH,
    UPLOADS_BUKTI_PEMBAYARAN_PATH,
    UPLOADS_GALERI_PATH,
    defined('UPLOADS_PROFIL_PATH') ? UPLOADS_PROFIL_PATH : null
];
foreach (array_filter($upload_paths_to_create_if_not_exist) as $path) {
    if (!is_dir($path)) {
        if (!@mkdir($path, 0775, true) && !is_dir($path)) {
            error_log("PERINGATAN config.php: Gagal membuat direktori upload {$path}. Periksa izin.");
        } else {
            @chmod($path, 0775);
        }
    }
}

// 9. Definisi Konstanta URL Lainnya
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . 'admin/');
if (!defined('USER_URL')) define('USER_URL', BASE_URL . 'user/');
if (!defined('AUTH_URL')) define('AUTH_URL', BASE_URL . 'auth/');
if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . 'public/');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', ASSETS_URL . 'uploads/');

if (!defined('UPLOADS_WISATA_URL')) define('UPLOADS_WISATA_URL', UPLOADS_URL . 'wisata/');
if (!defined('UPLOADS_ARTIKEL_URL')) define('UPLOADS_ARTIKEL_URL', UPLOADS_URL . 'artikel/');
if (!defined('UPLOADS_ALAT_SEWA_URL')) define('UPLOADS_ALAT_SEWA_URL', UPLOADS_URL . 'alat_sewa/');
if (!defined('UPLOADS_BUKTI_PEMBAYARAN_URL')) define('UPLOADS_BUKTI_PEMBAYARAN_URL', UPLOADS_URL . 'bukti_pembayaran/');
if (!defined('UPLOADS_GALERI_URL')) define('UPLOADS_GALERI_URL', UPLOADS_URL . 'galeri/');
if (!defined('UPLOADS_PROFIL_URL')) define('UPLOADS_PROFIL_URL', UPLOADS_URL . 'profil/');


// 10. Pengaturan Global Situs Lainnya
if (!defined('NAMA_SITUS')) define('NAMA_SITUS', 'Lembah Cilengkrang');
if (!defined('EMAIL_ADMIN')) define('EMAIL_ADMIN', 'admin@example.com'); // Ganti dengan email admin sebenarnya
if (!defined('DEFAULT_ITEMS_PER_PAGE')) define('DEFAULT_ITEMS_PER_PAGE', 10);

// error_log("INFO config.php: Konfigurasi selesai dimuat. BASE_URL: " . BASE_URL);
// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.