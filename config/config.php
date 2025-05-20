<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

/**
 * File Konfigurasi Utama Aplikasi Cilengkrang Web Wisata
 */

// --- PENGATURAN ERROR REPORTING & DEBUGGING ---
if (isset($_GET['debug_errors_on'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    echo "<p style='color:red; font-weight:bold;'>PERINGATAN: Display errors diaktifkan via URL!</p>";
} else {
    // Untuk development, Anda bisa aktifkan ini secara default
    // ini_set('display_errors', 1); 
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
}

// 1. MULAI SESSION
if (session_status() == PHP_SESSION_NONE) {
    if (!headers_sent($php_file_sess, $php_line_sess)) {
        session_start();
    } else {
        $error_message_session = "FATAL ERROR di config.php: Tidak dapat memulai session karena headers sudah terkirim.";
        if (isset($php_file_sess) && isset($php_line_sess)) {
            $error_message_session .= " Output dimulai dari file: {$php_file_sess} pada baris: {$php_line_sess}.";
        }
        error_log($error_message_session);
        exit("Kesalahan kritis pada aplikasi. Silakan hubungi administrator. (Error Code: SESS_INIT_FAIL)");
    }
}

// 2. PENGATURAN BASE URL DINAMIS
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$config_dir_path = str_replace('\\', '/', __DIR__);

$base_path_relative = '';
if (!empty($document_root) && strpos($config_dir_path, $document_root) === 0) {
    $base_path_relative = substr($config_dir_path, strlen($document_root));
    $base_path_relative = dirname($base_path_relative);
} else {
    $script_name_for_fallback = $_SERVER['SCRIPT_NAME'] ?? '';
    $script_path_parts = explode('/', dirname(str_replace('\\', '/', $script_name_for_fallback)));
    $project_folder_name = basename(dirname(__DIR__));
    $path_segments = [];
    foreach ($script_path_parts as $segment) {
        if (empty($segment) && empty($path_segments)) continue;
        $path_segments[] = $segment;
        if ($segment === $project_folder_name) break;
    }
    $base_path_relative = '/' . implode('/', $path_segments);
    if (count($path_segments) === 1 && $path_segments[0] === $project_folder_name && $host === $project_folder_name) {
        $base_path_relative = '';
    }
}
$base_path_relative = rtrim(str_replace('//', '/', $base_path_relative), '/');
$base_url = rtrim($protocol . $host . $base_path_relative, '/') . "/";

if (!defined('BASE_URL')) define('BASE_URL', $base_url);
// error_log("INFO config.php: BASE_URL di-set ke: " . BASE_URL);

// 3. PENGATURAN LINGKUNGAN, ZONA WAKTU, DAN ERROR REPORTING (LANJUTAN)
date_default_timezone_set("Asia/Jakarta");

if (!defined('IS_DEVELOPMENT')) {
    define('IS_DEVELOPMENT', (in_array($host, ['localhost', '127.0.0.1']) || strpos($host, '.test') !== false || strpos($host, '.local') !== false || (isset($_GET['debug_errors_on']))));
}

if (IS_DEVELOPMENT) {
    if (ini_get('display_errors') != '1') ini_set('display_errors', 1);
    if (error_reporting() !== E_ALL) error_reporting(E_ALL);
} else {
    if (ini_get('display_errors') != '0') ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
}

ini_set('log_errors', 1);
$logs_dir_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logs_dir_path)) {
    if (!@mkdir($logs_dir_path, 0775, true) && !is_dir($logs_dir_path)) {
        error_log("KRITIKAL config.php: Gagal membuat direktori logs di {$logs_dir_path}.");
    } else {
        @chmod($logs_dir_path, 0775);
    }
}
$error_log_filename = IS_DEVELOPMENT ? 'php_error_dev.log' : 'php_error_prod.log';
$error_log_path = $logs_dir_path . DIRECTORY_SEPARATOR . $error_log_filename;

if (is_writable($logs_dir_path) || (file_exists($error_log_path) && is_writable($error_log_path)) || (!file_exists($error_log_path) && is_writable(dirname($error_log_path)))) {
    ini_set('error_log', $error_log_path);
} else {
    error_log("KRITIKAL config.php: Direktori logs {$logs_dir_path} atau file log {$error_log_path} tidak dapat ditulis.");
}

// 4. DEFINISI KONSTANTA PATH APLIKASI FISIK
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config');
if (!defined('CONTROLLERS_PATH')) define('CONTROLLERS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'controllers');
if (!defined('MODELS_PATH')) define('MODELS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'models');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'template');
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'includes');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads');

if (!defined('UPLOADS_WISATA_PATH')) define('UPLOADS_WISATA_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'wisata');
if (!defined('UPLOADS_ARTIKEL_PATH')) define('UPLOADS_ARTIKEL_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'artikel');
if (!defined('UPLOADS_ALAT_SEWA_PATH')) define('UPLOADS_ALAT_SEWA_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'alat_sewa');
if (!defined('UPLOADS_BUKTI_PEMBAYARAN_PATH')) define('UPLOADS_BUKTI_PEMBAYARAN_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'bukti_pembayaran');
if (!defined('UPLOADS_GALERI_PATH')) define('UPLOADS_GALERI_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'galeri');
if (!defined('UPLOADS_PROFIL_PATH')) define('UPLOADS_PROFIL_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'profil');
if (!defined('UPLOADS_SITUS_PATH')) define('UPLOADS_SITUS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'situs');

// 5. MEMUAT FILE HELPER
$helper_files_to_load = [
    INCLUDES_PATH . DIRECTORY_SEPARATOR . 'helpers.php',
    INCLUDES_PATH . DIRECTORY_SEPARATOR . 'flash_message.php',
    INCLUDES_PATH . DIRECTORY_SEPARATOR . 'auth_helpers.php'
];
foreach ($helper_files_to_load as $helper_file) {
    if (file_exists($helper_file)) {
        require_once $helper_file;
    } else {
        error_log("KRITIS config.php: File helper '{$helper_file}' tidak ditemukan.");
    }
}

// 6. KONEKSI DATABASE
if (!file_exists(CONFIG_PATH . DIRECTORY_SEPARATOR . "Koneksi.php")) {
    error_log("FATAL config.php: File Koneksi.php tidak ditemukan.");
    exit("Kesalahan kritis: Komponen koneksi database tidak ditemukan.");
}
require_once CONFIG_PATH . DIRECTORY_SEPARATOR . "Koneksi.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    $db_error_message = "Variabel koneksi (\$conn) tidak terdefinisi atau bukan instance mysqli setelah include Koneksi.php.";
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database (KNF_CFG).");
}
if ($conn->connect_error) {
    $db_error_message = "Koneksi ke database gagal. Error MySQLi: ({$conn->connect_errno}) {$conn->connect_error}";
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database (KCE_CFG).");
}
if (!$conn->set_charset("utf8mb4")) {
    error_log("PERINGATAN config.php: Gagal mengatur charset koneksi ke utf8mb4. Error: " . $conn->error);
}

// 7. MEMUAT SEMUA MODEL DAN MENGINISIALISASI
// error_log("INFO config.php: Memulai pemuatan dan inisialisasi Model.");
$model_files = glob(MODELS_PATH . DIRECTORY_SEPARATOR . '*.php');
if ($model_files === false || empty($model_files)) {
    error_log("KRITIKAL config.php: Tidak ada file model ditemukan di '" . MODELS_PATH . "' atau path tidak dapat dibaca.");
} else {
    foreach ($model_files as $model_file) {
        if (is_file($model_file)) {
            require_once $model_file;
            $class_name = basename($model_file, '.php');
            if (class_exists($class_name, false)) {
                $initialized = false;
                if (method_exists($class_name, 'init')) {
                    try {
                        $reflectionMethod = new ReflectionMethod($class_name, 'init');
                        $numParams = $reflectionMethod->getNumberOfParameters();
                        $params_to_pass = [];
                        if ($numParams >= 1) $params_to_pass[] = $conn;
                        if ($numParams === 2) {
                            $upload_path_for_this_model = null;
                            switch ($class_name) {
                                case 'Artikel':
                                    $upload_path_for_this_model = defined('UPLOADS_ARTIKEL_PATH') ? UPLOADS_ARTIKEL_PATH : null;
                                    break;
                                case 'SewaAlat':
                                    $upload_path_for_this_model = defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH : null;
                                    break;
                                case 'User':
                                    $upload_path_for_this_model = defined('UPLOADS_PROFIL_PATH') ? UPLOADS_PROFIL_PATH : null;
                                    break;
                                case 'Galeri':
                                    $upload_path_for_this_model = defined('UPLOADS_GALERI_PATH') ? UPLOADS_GALERI_PATH : null;
                                    break;
                                case 'Wisata':
                                    $upload_path_for_this_model = defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH : null;
                                    break;
                                case 'PengaturanSitus':
                                    $upload_path_for_this_model = defined('UPLOADS_SITUS_PATH') ? UPLOADS_SITUS_PATH : (defined('UPLOADS_PATH') ? UPLOADS_PATH : null);
                                    break;
                            }
                            if ($upload_path_for_this_model !== null) {
                                $params_to_pass[] = $upload_path_for_this_model;
                            } elseif (!$reflectionMethod->getParameters()[1]->isOptional()) {
                                error_log("KRITIKAL config.php: Model {$class_name}::init() wajib 2 parameter tapi upload path tidak ada atau tidak terdefinisi. Inisialisasi dilewati.");
                                continue;
                            }
                        }
                        if (count($params_to_pass) === $numParams || ($numParams === 1 && count($params_to_pass) === 1)) {
                            $class_name::init(...$params_to_pass);
                            $initialized = true;
                        } else if ($numParams === 2 && count($params_to_pass) === 1 && $reflectionMethod->getParameters()[1]->isOptional()) {
                            $class_name::init($params_to_pass[0]);
                            $initialized = true;
                        } else {
                            error_log("PERINGATAN config.php: Model {$class_name}::init() tidak dipanggil karena jumlah parameter tidak cocok (Diharapkan {$numParams}, Disediakan " . count($params_to_pass) . ").");
                        }
                    } catch (ReflectionException $e) {
                        error_log("ERROR config.php: ReflectionException saat init model {$class_name}: " . $e->getMessage());
                    }
                }
                if (!$initialized && method_exists($class_name, 'setDbConnection')) {
                    try {
                        $reflectionSetDb = new ReflectionMethod($class_name, 'setDbConnection');
                        if ($reflectionSetDb->getNumberOfParameters() === 1) {
                            $class_name::setDbConnection($conn);
                            $initialized = true;
                        }
                    } catch (ReflectionException $e) {
                        error_log("ERROR config.php: ReflectionException saat setDbConnection model {$class_name}: " . $e->getMessage());
                    }
                }
                if (!$initialized) error_log("KRITIKAL config.php: Model {$class_name} tidak dapat diinisialisasi (koneksi DB/path).");
            } else {
                error_log("KRITIKAL config.php: Kelas {$class_name} tidak ditemukan setelah require '{$model_file}'.");
            }
        }
    }
}
// error_log("INFO config.php: Selesai proses inisialisasi Model.");

// 7.b. MEMUAT SEMUA CONTROLLER
// error_log("INFO config.php: Memulai pemuatan semua Controller.");
if (defined('CONTROLLERS_PATH') && is_dir(CONTROLLERS_PATH)) {
    $controller_files = glob(CONTROLLERS_PATH . DIRECTORY_SEPARATOR . '*.php');
    if ($controller_files !== false && !empty($controller_files)) {
        foreach ($controller_files as $controller_file) {
            if (is_file($controller_file)) {
                require_once $controller_file;
            }
        }
    }
}
// error_log("INFO config.php: Selesai proses pemuatan semua Controller.");

// 8. OTOMATIS MEMBUAT DIREKTORI UPLOADS
$upload_paths_to_create = [
    UPLOADS_PATH,
    UPLOADS_WISATA_PATH,
    UPLOADS_ARTIKEL_PATH,
    UPLOADS_ALAT_SEWA_PATH,
    UPLOADS_BUKTI_PEMBAYARAN_PATH,
    UPLOADS_GALERI_PATH,
    UPLOADS_PROFIL_PATH,
    UPLOADS_SITUS_PATH
];
foreach (array_filter($upload_paths_to_create) as $path) {
    if ($path && !is_dir($path)) {
        if (!@mkdir($path, 0775, true) && !is_dir($path)) {
            error_log("PERINGATAN config.php: Gagal membuat direktori upload '{$path}'. Periksa izin.");
        } else {
            @chmod($path, 0775);
        }
    }
}

// 9. DEFINISI KONSTANTA URL
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
if (!defined('UPLOADS_SITUS_URL')) define('UPLOADS_SITUS_URL', UPLOADS_URL . 'situs/');

if (!defined('ERROR_PAGE_URL')) {
    define('ERROR_PAGE_URL', BASE_URL . 'error.php'); // Buat file error.php di root proyek Anda
}


// 10. PENGATURAN GLOBAL SITUS LAINNYA
if (!defined('NAMA_SITUS')) define('NAMA_SITUS', 'Lembah Cilengkrang');
if (!defined('PENGANTAR_SINGKAT_SITUS')) define('PENGANTAR_SINGKAT_SITUS', "Lembah Cilengkrang terletak di Pajambon, Kramatmulya, Kuningan, Jawa Barat, sekitar 30km dari pusat kota Kuningan. Destinasi ini menawarkan keindahan air terjun menawan, relaksasi di pemandian air panas alami, dan kesegaran udara pegunungan.");
if (!defined('EMAIL_ADMIN')) define('EMAIL_ADMIN', 'admin@example.com');
if (!defined('DEFAULT_ITEMS_PER_PAGE')) define('DEFAULT_ITEMS_PER_PAGE', 10);
if (!defined('THEME_COLOR_PRIMARY')) define('THEME_COLOR_PRIMARY', '#28a745'); // Warna tema hijau


// error_log("INFO config.php: Konfigurasi selesai dimuat. IS_DEVELOPMENT: " . (IS_DEVELOPMENT ? 'true' : 'false'));
