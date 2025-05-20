<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\config.php

/**
 * File Konfigurasi Utama Aplikasi Cilengkrang Web Wisata
 */

// --- PENGATURAN ERROR REPORTING & DEBUGGING ---
// Aktifkan via URL dengan menambahkan ?debug_errors_on=1
if (isset($_GET['debug_errors_on']) && $_GET['debug_errors_on'] == '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // echo "<p style='color:red; font-weight:bold; position:fixed; top:0; left:0; background:white; padding:5px; z-index:9999;'>PERINGATAN: Display errors diaktifkan via URL!</p>";
} else {
    // Default untuk development bisa diatur di php.ini atau uncomment di sini
    // Contoh:
    // if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == 'localhost' || strpos($_SERVER['HTTP_HOST'], '.test') !== false)) {
    //     ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);
    // }
}

// 1. MULAI SESSION (HARUS menjadi baris pertama yang dieksekusi sebelum output apapun)
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
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback untuk CLI
$document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''); // Normalisasi separator
$config_dir_path = str_replace('\\', '/', __DIR__); // Path ke folder 'config' ini

$base_path_relative = '';
// Coba tentukan base path relatif terhadap document root
if (!empty($document_root) && strpos($config_dir_path, $document_root) === 0) {
    $base_path_relative = substr($config_dir_path, strlen($document_root));
    $base_path_relative = dirname($base_path_relative); // Naik satu level dari /config ke /nama_proyek
} else {
    // Fallback jika document_root tidak terdeteksi dengan benar atau tidak cocok
    $script_name_for_fallback = $_SERVER['SCRIPT_NAME'] ?? ''; // /Cilengkrang-Web-Wisata/config/somefile.php atau /index.php
    $path_parts = explode('/', dirname(str_replace('\\', '/', $script_name_for_fallback)));
    $project_folder_name_from_script = basename(dirname(dirname(str_replace('\\', '/', __FILE__)))); // Nama folder proyek dari __FILE__

    $path_segments_build = [];
    foreach ($path_parts as $segment) {
        if (empty($segment) && empty($path_segments_build)) continue; // Abaikan leading slash pertama jika ada
        $path_segments_build[] = $segment;
        if ($segment === $project_folder_name_from_script) { // Berhenti jika sudah menemukan nama folder proyek
            break;
        }
    }
    if (!empty($path_segments_build)) {
        $base_path_relative = '/' . implode('/', $path_segments_build);
    }
    // Khusus jika nama folder proyek adalah nama host (misal, cilengkrang.test) dan ada di root web server
    if (count($path_segments_build) === 1 && $path_segments_build[0] === $project_folder_name_from_script && $host === $project_folder_name_from_script) {
        $base_path_relative = '';
    }
}
$base_path_relative = rtrim(str_replace('//', '/', $base_path_relative), '/'); // Hilangkan duplikasi slash dan trailing slash
$base_url = rtrim($protocol . $host . $base_path_relative, '/') . "/"; // Pastikan diakhiri satu slash

if (!defined('BASE_URL')) define('BASE_URL', $base_url);
// error_log("INFO config.php: BASE_URL di-set ke: " . BASE_URL);

// 3. PENGATURAN LINGKUNGAN, ZONA WAKTU, DAN ERROR REPORTING (LANJUTAN)
date_default_timezone_set("Asia/Jakarta");

if (!defined('IS_DEVELOPMENT')) {
    define('IS_DEVELOPMENT', (
        in_array($host, ['localhost', '127.0.0.1']) ||
        strpos($host, '.test') !== false ||
        strpos($host, '.local') !== false ||
        (isset($_GET['debug_errors_on']) && $_GET['debug_errors_on'] == '1') // Memungkinkan debug via URL
    ));
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
        error_log("KRITIKAL config.php: Gagal membuat direktori logs di {$logs_dir_path}. Periksa izin.");
    } else {
        @chmod($logs_dir_path, 0775);
    }
}
$error_log_filename = IS_DEVELOPMENT ? 'php_error_dev.log' : 'php_error_prod.log';
$error_log_path = $logs_dir_path . DIRECTORY_SEPARATOR . $error_log_filename;

if (is_writable($logs_dir_path) || (file_exists($error_log_path) && is_writable($error_log_path)) || (!file_exists($error_log_path) && is_writable(dirname($error_log_path)))) {
    ini_set('error_log', $error_log_path);
} else {
    error_log("KRITIKAL config.php: Direktori logs {$logs_dir_path} atau file log {$error_log_path} tidak dapat ditulis. Error PHP akan menggunakan default server.");
}

// 4. DEFINISI KONSTANTA PATH APLIKASI FISIK
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config');
if (!defined('CONTROLLERS_PATH')) define('CONTROLLERS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'controllers');
if (!defined('MODELS_PATH')) define('MODELS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'models');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'template'); // Nama folder template Anda
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'includes');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Path spesifik per jenis upload
if (!defined('UPLOADS_WISATA_PATH')) define('UPLOADS_WISATA_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'wisata');
if (!defined('UPLOADS_ARTIKEL_PATH')) define('UPLOADS_ARTIKEL_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'artikel');
if (!defined('UPLOADS_ALAT_SEWA_PATH')) define('UPLOADS_ALAT_SEWA_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'alat_sewa');
if (!defined('UPLOADS_BUKTI_PEMBAYARAN_PATH')) define('UPLOADS_BUKTI_PEMBAYARAN_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'bukti_pembayaran');
if (!defined('UPLOADS_GALERI_PATH')) define('UPLOADS_GALERI_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'galeri');
if (!defined('UPLOADS_PROFIL_PATH')) define('UPLOADS_PROFIL_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'profil');
if (!defined('UPLOADS_SITUS_PATH')) define('UPLOADS_SITUS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'situs'); // Untuk logo, favicon, dll.

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
        error_log("KRITIS config.php: File helper '{$helper_file}' tidak ditemukan. Aplikasi mungkin tidak berfungsi dengan benar.");
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
                        if ($numParams >= 1) $params_to_pass[] = $conn; // Asumsi parameter pertama selalu $conn
                        if ($numParams === 2) {
                            $upload_path_for_this_model = null;
                            // Tentukan path upload berdasarkan nama kelas Model
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
                                error_log("KRITIKAL config.php: Model {$class_name}::init() butuh 2 parameter tapi upload path untuknya tidak terdefinisi/null. Inisialisasi init() dilewati.");
                                continue; // Lanjut ke model berikutnya
                            }
                        }
                        // Panggil init dengan parameter yang sesuai
                        if (count($params_to_pass) === $numParams) {
                            $class_name::init(...$params_to_pass);
                            $initialized = true;
                        } elseif ($numParams === 1 && count($params_to_pass) === 1) { // Jika init hanya butuh $conn
                            $class_name::init($conn);
                            $initialized = true;
                        } elseif ($numParams === 2 && count($params_to_pass) === 1 && $reflectionMethod->getParameters()[1]->isOptional()) { // Jika param kedua opsional
                            $class_name::init($conn);
                            $initialized = true;
                        } else {
                            error_log("PERINGATAN config.php: Model {$class_name}::init() tidak dipanggil karena ketidakcocokan jumlah parameter. Diharapkan:{$numParams}, Disediakan:" . count($params_to_pass) . ".");
                        }
                    } catch (ReflectionException $e) {
                        error_log("ERROR config.php: ReflectionException saat init model {$class_name}: " . $e->getMessage());
                    }
                }
                if (!$initialized && method_exists($class_name, 'setDbConnection')) { // Fallback ke setDbConnection jika init tidak cocok/ada
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
                if (!$initialized) error_log("KRITIKAL config.php: Model {$class_name} tidak dapat diinisialisasi koneksi DB-nya.");
            } else {
                error_log("KRITIKAL config.php: Kelas {$class_name} tidak ditemukan setelah require file model '{$model_file}'.");
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

// 7.c. MENGAMBIL PENGATURAN SITUS GLOBAL SETELAH MODEL DIINISIALISASI
global $pengaturan_situs_global;
$pengaturan_situs_global = [];

// Pastikan Model PengaturanSitus sudah dimuat dan diinisialisasi dengan benar
if (class_exists('PengaturanSitus') && method_exists('PengaturanSitus', 'getPengaturan')) {
    $pengaturan_situs_global = PengaturanSitus::getPengaturan();
}

// Sediakan nilai default jika pengambilan gagal atau tabel `pengaturan_situs` belum ada/kosong
// Ini penting agar situs tidak error jika data pengaturan belum ada.
if (empty($pengaturan_situs_global) || !is_array($pengaturan_situs_global)) {
    $pengaturan_situs_global = [
        'nama_situs' => 'Lembah Cilengkrang (Default)',
        'tagline_situs' => 'Keindahan Alam Tersembunyi yang Menakjubkan',
        'deskripsi_situs' => 'Jelajahi pesona alam Lembah Cilengkrang, destinasi sempurna untuk petualangan dan relaksasi.',
        'email_kontak_situs' => 'info@lembahcilengkrang.com',
        'telepon_kontak_situs' => '0812-3456-7890',
        'alamat_situs' => 'Desa Pajambon, Kramatmulya, Kuningan, Jawa Barat',
        'logo_situs' => null, // Akan menggunakan logo default di template jika null
        'favicon_situs' => null, // Akan menggunakan favicon default di template jika null
        'link_facebook' => 'https://facebook.com/lembahcilengkrang',
        'link_instagram' => 'https://instagram.com/lembahcilengkrang',
        'link_twitter' => 'https://twitter.com/lembahcilengkrang',
        'link_youtube' => null,
        'google_analytics_id' => null,
        'items_per_page' => 10, // Default item per halaman untuk paginasi
        'mode_pemeliharaan' => 0, // 0 = Tidak Aktif, 1 = Aktif
        // Tambahkan default untuk konstanta yang sebelumnya Anda definisikan secara hardcode
        'PENGANTAR_SINGKAT_SITUS' => "Lembah Cilengkrang terletak di Pajambon, Kramatmulya, Kuningan, Jawa Barat, sekitar 30km dari pusat kota Kuningan. Destinasi ini menawarkan keindahan air terjun menawan, relaksasi di pemandian air panas alami, dan kesegaran udara pegunungan.",
        'THEME_COLOR_PRIMARY' => '#28a745', // Warna hijau default Anda
    ];
    error_log("PERINGATAN config.php: Gagal memuat pengaturan situs dari DB atau tabel kosong. Menggunakan pengaturan default dari config.php.");
}

// Anda bisa membuat fungsi helper untuk mengakses pengaturan ini dengan mudah, misalnya:
// function get_setting($key, $default = null) {
//     global $pengaturan_situs_global;
//     return $pengaturan_situs_global[$key] ?? $default;
// }
// Dan panggil di template: e(get_setting('nama_situs', 'Nama Default'))

// 8. OTOMATIS MEMBUAT DIREKTORI UPLOADS (Jika belum ada)
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

// 9. DEFINISI KONSTANTA URL (Selalu gunakan / untuk URL)
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

// Definisikan ERROR_PAGE_URL jika Anda memiliki halaman error khusus
if (!defined('ERROR_PAGE_URL')) {
    define('ERROR_PAGE_URL', BASE_URL . 'error.php'); // Buat file error.php di root proyek Anda jika ingin menggunakannya
}


// 10. PENGATURAN GLOBAL SITUS LAINNYA
if (!defined('NAMA_SITUS')) define('NAMA_SITUS', 'Lembah Cilengkrang');
// Konstanta ini digunakan di index.php
if (!defined('PENGANTAR_SINGKAT_SITUS')) define('PENGANTAR_SINGKAT_SITUS', "Lembah Cilengkrang terletak di Pajambon, Kramatmulya, Kuningan, Jawa Barat, sekitar 30km dari pusat kota Kuningan. Destinasi ini menawarkan keindahan air terjun menawan, relaksasi di pemandian air panas alami, dan kesegaran udara pegunungan.");
if (!defined('EMAIL_ADMIN')) define('EMAIL_ADMIN', 'admin@example.com'); // Ganti dengan email admin sebenarnya
if (!defined('DEFAULT_ITEMS_PER_PAGE')) define('DEFAULT_ITEMS_PER_PAGE', 10);
// Konstanta ini digunakan di index.php untuk warna tema
if (!defined('THEME_COLOR_PRIMARY')) define('THEME_COLOR_PRIMARY', '#28a745'); // Contoh warna hijau


// error_log("INFO config.php: Konfigurasi selesai dimuat. IS_DEVELOPMENT: " . (IS_DEVELOPMENT ? 'true' : 'false'));
