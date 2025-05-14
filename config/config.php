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
        if (isset($file) && isset($line)) {
            $error_message_session .= " Output dimulai dari file: {$file} pada baris: {$line}.";
        }
        error_log($error_message_session);
        exit("Kesalahan kritis: Tidak dapat memulai sesi aplikasi. Silakan hubungi administrator. Detail Internal: " . htmlspecialchars($error_message_session));
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
        $base_path_relative = ($script_directory === '/' || $script_directory === '\\') ? '' : $script_directory;
    }
    // error_log("INFO config.php: Menggunakan SCRIPT_NAME untuk base_path_relative. Hasil: '" . $base_path_relative . "' dari script_dir: '" . $script_directory . "'");
} else {
    error_log("PERINGATAN config.php: Tidak dapat menentukan base_path_relative otomatis (DOCUMENT_ROOT & SCRIPT_NAME tidak membantu). BASE_URL mungkin tidak akurat. Pertimbangkan set manual untuk CLI atau lingkungan non-standar.");
}

$base_path_relative = '/' . trim(str_replace('//', '/', $base_path_relative), '/');
if ($base_path_relative === '/') $base_path_relative = '';

$base_url = $protocol . $host . $base_path_relative . "/";

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
// error_log("INFO config.php: BASE_URL di-set ke: " . BASE_URL);


// 3. Pengaturan Lingkungan, Zona Waktu, dan Error Reporting
date_default_timezone_set("Asia/Jakarta");

if (!defined('IS_DEVELOPMENT')) {
    define('IS_DEVELOPMENT', (in_array($host, ['localhost', '127.0.0.1']) || strpos($host, '.test') !== false || strpos($host, '.local') !== false));
}

if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
$logs_dir_path = dirname(__DIR__) . '/logs';
if (!is_dir($logs_dir_path)) {
    if (!@mkdir($logs_dir_path, 0775, true) && !is_dir($logs_dir_path)) {
        error_log("KRITIKAL config.php: Gagal membuat direktori logs di {$logs_dir_path}. Periksa izin pada folder induk: " . dirname($logs_dir_path));
    } else {
        @chmod($logs_dir_path, 0775);
        error_log("INFO config.php: Direktori logs berhasil dibuat di {$logs_dir_path}.");
    }
}
$error_log_filename = IS_DEVELOPMENT ? 'php_error_dev.log' : 'php_error_prod.log';
$error_log_path = $logs_dir_path . '/' . $error_log_filename;
if (is_writable($logs_dir_path) || (file_exists($error_log_path) && is_writable($error_log_path)) || (!file_exists($error_log_path) && is_writable(dirname($error_log_path)))) {
    ini_set('error_log', $error_log_path);
    // error_log("INFO config.php: Error logging di-set ke: " . $error_log_path);
} else {
    error_log("KRITIKAL config.php: Direktori logs {$logs_dir_path} atau file log {$error_log_path} tidak dapat ditulis. Error PHP mungkin tidak tercatat.");
}


// 4. Definisi Konstanta Path Aplikasi
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
if (file_exists(INCLUDES_PATH . '/helpers.php')) require_once INCLUDES_PATH . '/helpers.php';
else error_log("KRITIS config.php: helpers.php tidak ditemukan.");
if (file_exists(INCLUDES_PATH . '/flash_message.php')) require_once INCLUDES_PATH . '/flash_message.php';
else error_log("KRITIS config.php: flash_message.php tidak ditemukan.");
if (file_exists(INCLUDES_PATH . '/auth_helpers.php')) require_once INCLUDES_PATH . '/auth_helpers.php';
else error_log("KRITIS config.php: auth_helpers.php tidak ditemukan.");


// 6. Koneksi Database
if (!file_exists(CONFIG_PATH . "/Koneksi.php")) {
    error_log("FATAL config.php: File Koneksi.php tidak ditemukan.");
    exit("Kesalahan kritis: Komponen koneksi database tidak ditemukan.");
}
require_once CONFIG_PATH . "/Koneksi.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    $db_error_message = "Variabel koneksi (\$conn) tidak terdefinisi atau bukan instance mysqli setelah include Koneksi.php.";
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    $display_error_detail = IS_DEVELOPMENT ? "<br><small style='color:gray;font-family:monospace;'>Detail Teknis: " . htmlspecialchars($db_error_message) . "</small>" : "";
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database (KNF)." . $display_error_detail);
}
if ($conn->connect_error) {
    $db_error_message = "Koneksi ke database gagal. Error MySQLi: (" . $conn->connect_errno . ") " . $conn->connect_error;
    error_log("FATAL config.php: " . $db_error_message);
    http_response_code(503);
    $display_error_detail = IS_DEVELOPMENT ? "<br><small style='color:gray;font-family:monospace;'>Detail Teknis: " . htmlspecialchars($db_error_message) . "</small>" : "";
    exit("Maaf, situs sedang mengalami gangguan teknis pada layanan database (KCE)." . $display_error_detail);
}
if (!$conn->set_charset("utf8mb4")) {
    error_log("PERINGATAN config.php: Gagal mengatur charset koneksi ke utf8mb4. Error: " . $conn->error);
}
error_log("INFO config.php: Koneksi database berhasil dan charset di-set ke utf8mb4.");


// 7. Memuat SEMUA Model dan Menginisialisasi Koneksi Database serta Path Upload untuk Model
error_log("INFO config.php: Memulai proses inisialisasi model.");
$model_files = glob(MODELS_PATH . '/*.php');

if ($model_files === false || empty($model_files)) {
    error_log("PERINGATAN config.php: Tidak ada file model ditemukan di '" . MODELS_PATH . "' atau path tidak valid.");
} else {
    foreach ($model_files as $model_file) {
        if (is_file($model_file)) {
            require_once $model_file;
            $class_name = basename($model_file, '.php');

            if (class_exists($class_name)) {
                $model_initialized_successfully = false;

                if (method_exists($class_name, 'init')) {
                    $upload_path_to_pass = null;
                    $init_requires_upload_path = false;

                    $models_needing_upload_path = [
                        'User'     => defined('UPLOADS_PROFIL_PATH') ? UPLOADS_PROFIL_PATH : null,
                        'SewaAlat' => defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH : null,
                        'Artikel'  => defined('UPLOADS_ARTIKEL_PATH') ? UPLOADS_ARTIKEL_PATH : null,
                        'Galeri'   => defined('UPLOADS_GALERI_PATH') ? UPLOADS_GALERI_PATH : null,
                        'Wisata'   => defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH : null,
                        // 'BuktiPembayaran' => defined('UPLOADS_BUKTI_PEMBAYARAN_PATH') ? UPLOADS_BUKTI_PEMBAYARAN_PATH : null,
                    ];

                    if (array_key_exists($class_name, $models_needing_upload_path)) {
                        if ($models_needing_upload_path[$class_name] !== null) {
                            $upload_path_to_pass = $models_needing_upload_path[$class_name];
                            $init_requires_upload_path = true;
                        } else {
                            error_log("PERINGATAN config.php: Konstanta path upload untuk Model {$class_name} tidak terdefinisi, padahal diharapkan.");
                        }
                    }

                    try {
                        $reflectionMethod = new ReflectionMethod($class_name, 'init');
                        $actual_params_count = $reflectionMethod->getNumberOfParameters();

                        if ($init_requires_upload_path && $upload_path_to_pass !== null) {
                            if ($actual_params_count === 2) {
                                $class_name::init($conn, $upload_path_to_pass);
                                $model_initialized_successfully = true;
                                error_log("INFO config.php: Model {$class_name} diinisialisasi dengan init(conn, path).");
                            } else {
                                error_log("PERINGATAN config.php: Model {$class_name} memerlukan path upload, tetapi metode init() memiliki {$actual_params_count} parameter (diharapkan 2). Akan dicoba setDbConnection.");
                            }
                        } elseif (!$init_requires_upload_path && $actual_params_count === 1) {
                            $paramsReflection = $reflectionMethod->getParameters();
                            if ($paramsReflection[0]->getType() && $paramsReflection[0]->getType()->getName() === 'mysqli') {
                                $class_name::init($conn);
                                $model_initialized_successfully = true;
                                error_log("INFO config.php: Model {$class_name} diinisialisasi dengan init(conn).");
                            } else {
                                error_log("PERINGATAN config.php: Metode init(conn) pada Model {$class_name} parameternya bukan mysqli. Akan dicoba setDbConnection.");
                            }
                        } elseif ($actual_params_count === 0) {
                            $class_name::init();
                            $model_initialized_successfully = true;
                            error_log("INFO config.php: Model {$class_name} diinisialisasi dengan init() tanpa parameter.");
                        }
                    } catch (ReflectionException $e) {
                        error_log("KESALAHAN config.php: ReflectionException saat memeriksa metode init() pada Model {$class_name}: " . $e->getMessage());
                    }
                }

                if (!$model_initialized_successfully && method_exists($class_name, 'setDbConnection')) {
                    try {
                        $reflectionSetDb = new ReflectionMethod($class_name, 'setDbConnection');
                        if ($reflectionSetDb->getNumberOfParameters() === 1) {
                            $paramsSetDb = $reflectionSetDb->getParameters();
                            if ($paramsSetDb[0]->getType() && $paramsSetDb[0]->getType()->getName() === 'mysqli') {
                                $class_name::setDbConnection($conn);
                                $model_initialized_successfully = true;
                                error_log("INFO config.php: Model {$class_name} diinisialisasi dengan setDbConnection(conn).");
                            } else {
                                error_log("PERINGATAN config.php: Metode setDbConnection() pada Model {$class_name} memiliki 1 parameter, tetapi bukan tipe mysqli.");
                            }
                        } else {
                            error_log("PERINGATAN config.php: Metode setDbConnection() pada Model {$class_name} tidak memiliki 1 parameter seperti yang diharapkan.");
                        }
                    } catch (ReflectionException $e) {
                        error_log("KESALAHAN config.php: ReflectionException saat memeriksa metode setDbConnection() pada Model {$class_name}: " . $e->getMessage());
                    }
                }

                if (!$model_initialized_successfully) {
                    error_log("PERINGATAN config.php: Model {$class_name} tidak memiliki metode init() atau setDbConnection() yang cocok untuk inisialisasi database otomatis, atau parameter tidak cocok.");
                }
            } else {
                error_log("PERINGATAN config.php: Kelas {$class_name} tidak ditemukan setelah require file model '{$model_file}'. Pastikan nama file dan nama kelas sama persis (case-sensitive).");
            }
        } else {
            error_log("PERINGATAN config.php: Path '{$model_file}' bukan file yang valid di direktori models.");
        }
    }
    error_log("INFO config.php: Selesai proses inisialisasi model.");
}


// 7.b. Memuat SEMUA Controller 
error_log("INFO config.php: Memulai proses pemuatan semua Controller.");
if (defined('CONTROLLERS_PATH') && is_dir(CONTROLLERS_PATH)) {
    $controller_files = glob(CONTROLLERS_PATH . '/*.php');
    if ($controller_files === false || empty($controller_files)) {
        error_log("PERINGATAN config.php: Tidak ada file controller ditemukan di '" . CONTROLLERS_PATH . "' atau path tidak valid.");
    } else {
        foreach ($controller_files as $controller_file) {
            if (is_file($controller_file)) {
                require_once $controller_file;
                error_log("INFO config.php: Controller dimuat: " . basename($controller_file));
            } else {
                error_log("PERINGATAN config.php: Path controller '{$controller_file}' dari glob bukan file yang valid.");
            }
        }
    }
} else {
    error_log("PERINGATAN config.php: CONTROLLERS_PATH tidak terdefinisi atau bukan direktori.");
}
error_log("INFO config.php: Selesai proses pemuatan semua Controller.");


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
            error_log("PERINGATAN config.php: Gagal membuat dir {$path}.");
        } else {
            @chmod($path, 0775);
            error_log("INFO config.php: Dir {$path} dibuat.");
        }
    } elseif (!is_writable($path)) {
        error_log("PERINGATAN config.php: Dir {$path} tidak writable.");
    }
}

// 9. Definisi Konstanta URL Lainnya
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . 'admin/');
if (!defined('USER_URL')) define('USER_URL', BASE_URL . 'user/');
if (!defined('AUTH_URL')) define('AUTH_URL', BASE_URL . 'auth/');
if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . 'public/');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', ASSETS_URL . 'uploads/');
if (!defined('UPLOADS_PROFIL_URL')) define('UPLOADS_PROFIL_URL', UPLOADS_URL . 'profil/');


// 10. Pengaturan Global Situs Lainnya
if (!defined('NAMA_SITUS')) define('NAMA_SITUS', 'Lembah Cilengkrang');
if (!defined('EMAIL_ADMIN')) define('EMAIL_ADMIN', 'admin@cilengkrang.info');
if (!defined('DEFAULT_ITEMS_PER_PAGE')) define('DEFAULT_ITEMS_PER_PAGE', 10);

error_log("INFO config.php: Konfigurasi selesai dimuat. BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'TIDAK TERDEFINISI'));
// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.
