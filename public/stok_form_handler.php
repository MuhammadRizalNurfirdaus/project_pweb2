<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\public\stok_form_handler.php

// Aktifkan error reporting untuk debugging AJAX
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Set header JSON di awal

// Default response
$response = ['success' => false, 'message' => 'Permintaan tidak valid atau error server awal.', 'available' => 0];

// Path ke config harus benar dari direktori 'public'
$configFile = __DIR__ . '/../config/config.php'; // <-- PERBAIKAN PATH
if (!file_exists($configFile)) {
    $response['message'] = 'File konfigurasi utama tidak ditemukan pada path yang diharapkan.';
    error_log("stok_form_handler.php: Gagal memuat config. Path: " . $configFile);
    echo json_encode($response);
    exit;
}
require_once $configFile;

// Array model yang dibutuhkan dan path-nya
$required_models = [
    'JenisTiket' => __DIR__ . '/../models/JenisTiket.php', // <-- PERBAIKAN PATH
    'SewaAlat' => __DIR__ . '/../models/SewaAlat.php',         // <-- PERBAIKAN PATH
    'JadwalKetersediaanTiket' => __DIR__ . '/../models/JadwalKetersediaanTiket.php' // <-- PERBAIKAN PATH
];

foreach ($required_models as $model_name => $model_path) {
    if (!file_exists($model_path)) {
        $response['message'] = "File model {$model_name} tidak ditemukan di {$model_path}.";
        error_log("stok_form_handler.php: Gagal memuat model {$model_name}. Path: " . $model_path);
        echo json_encode($response);
        exit;
    }
    require_once $model_path;
}

// Pastikan koneksi $conn ada dan model diinisialisasi
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $response['message'] = 'Koneksi database gagal atau tidak diinisialisasi dari config.';
    if (isset($conn) && $conn->connect_error) $response['message'] .= ' Error: ' . $conn->connect_error;
    error_log("stok_form_handler.php: Koneksi database gagal. Conn: " . print_r($conn, true));
    echo json_encode($response);
    exit;
}

// Inisialisasi model dengan koneksi
// Pastikan model memiliki metode setDbConnection atau init yang sesuai
if (class_exists('JenisTiket') && method_exists('JenisTiket', 'setDbConnection')) {
    JenisTiket::setDbConnection($conn);
}
if (class_exists('SewaAlat') && method_exists('SewaAlat', 'init')) {
    if (!defined('UPLOADS_ALAT_SEWA_PATH')) { // Pastikan konstanta ini ada di config.php
        $response['message'] = 'Konstanta UPLOADS_ALAT_SEWA_PATH tidak terdefinisi.';
        error_log("stok_form_handler.php: UPLOADS_ALAT_SEWA_PATH tidak terdefinisi.");
        echo json_encode($response);
        exit;
    }
    SewaAlat::init($conn, UPLOADS_ALAT_SEWA_PATH);
}
if (class_exists('JadwalKetersediaanTiket') && method_exists('JadwalKetersediaanTiket', 'setDbConnection')) {
    JadwalKetersediaanTiket::setDbConnection($conn);
}


$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action === 'cek_kuota_tiket' && isset($_POST['jenis_tiket_id']) && isset($_POST['tanggal_kunjungan']) && isset($_POST['jumlah_diminta'])) {
    $jenis_tiket_id = (int)$_POST['jenis_tiket_id'];
    $tanggal_kunjungan = trim($_POST['tanggal_kunjungan']);
    $jumlah_diminta = (int)$_POST['jumlah_diminta'];
    $dtValid = DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan);

    if ($jenis_tiket_id > 0 && !empty($tanggal_kunjungan) && $dtValid && $dtValid->format('Y-m-d') === $tanggal_kunjungan && $jumlah_diminta > 0) {
        if (!class_exists('JadwalKetersediaanTiket') || !method_exists('JadwalKetersediaanTiket', 'getActiveKetersediaan') || !class_exists('JenisTiket') || !method_exists('JenisTiket', 'findById')) {
            $response['message'] = 'Model yang dibutuhkan untuk cek kuota tidak tersedia atau metode tidak ditemukan.';
            error_log("stok_form_handler.php: Model JadwalKetersediaanTiket/JenisTiket atau metodenya tidak ada.");
        } else {
            $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan);
            if ($ketersediaan && isset($ketersediaan['jumlah_saat_ini_tersedia'])) {
                if ((int)$ketersediaan['jumlah_saat_ini_tersedia'] >= $jumlah_diminta) {
                    $response = ['success' => true, 'message' => 'Kuota tersedia.', 'available' => (int)$ketersediaan['jumlah_saat_ini_tersedia']];
                } else {
                    $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id);
                    $nama_tiket = isset($jenisTiketInfo['nama_layanan_display']) ? e($jenisTiketInfo['nama_layanan_display']) : 'Tiket';
                    $tanggal_formatted = function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($tanggal_kunjungan) : $tanggal_kunjungan;
                    $response = [
                        'success' => false,
                        'message' => 'Kuota tiket "' . $nama_tiket . '" untuk tanggal ' . e($tanggal_formatted) . ' tidak mencukupi (tersisa: ' . (int)$ketersediaan['jumlah_saat_ini_tersedia'] . ', diminta: ' . $jumlah_diminta . ').',
                        'available' => (int)$ketersediaan['jumlah_saat_ini_tersedia']
                    ];
                }
            } else {
                $response['message'] = 'Jadwal ketersediaan tidak ditemukan untuk tiket dan tanggal tersebut.';
            }
        }
    } else {
        $response['message'] = 'Parameter tidak lengkap atau format tanggal salah untuk cek kuota tiket.';
    }
} elseif ($action === 'cek_stok_alat' && isset($_POST['sewa_alat_id']) && isset($_POST['jumlah_diminta'])) {
    $sewa_alat_id = (int)$_POST['sewa_alat_id'];
    $jumlah_diminta = (int)$_POST['jumlah_diminta'];

    if ($sewa_alat_id > 0 && $jumlah_diminta > 0) {
        if (!class_exists('SewaAlat') || !method_exists('SewaAlat', 'getById')) {
            $response['message'] = 'Model yang dibutuhkan untuk cek stok tidak tersedia atau metode tidak ditemukan.';
            error_log("stok_form_handler.php: Model SewaAlat atau metodenya tidak ada.");
        } else {
            $alatInfo = SewaAlat::getById($sewa_alat_id);
            if ($alatInfo && isset($alatInfo['stok_tersedia'])) {
                if ((int)$alatInfo['stok_tersedia'] >= $jumlah_diminta) {
                    $response = ['success' => true, 'message' => 'Stok alat tersedia.', 'available' => (int)$alatInfo['stok_tersedia']];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Stok alat "' . e($alatInfo['nama_item'] ?? 'Alat') . '" tidak mencukupi (tersisa: ' . (int)$alatInfo['stok_tersedia'] . ', diminta: ' . $jumlah_diminta . ').',
                        'available' => (int)$alatInfo['stok_tersedia']
                    ];
                }
            } else {
                $response['message'] = 'Informasi alat sewa tidak ditemukan.';
            }
        }
    } else {
        $response['message'] = 'Parameter tidak lengkap untuk cek stok alat.';
    }
}

// Pastikan tidak ada output lain sebelum ini
echo json_encode($response);
exit;
