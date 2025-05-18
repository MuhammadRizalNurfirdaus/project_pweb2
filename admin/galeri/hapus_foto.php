<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\hapus_foto.php

// 1. Sertakan konfigurasi utama
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/hapus_foto.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Otentikasi Admin
require_admin();

// 3. Pastikan Controller dan Model Galeri ada
$controller_ok = class_exists('GaleriController') && method_exists('GaleriController', 'delete') && method_exists('GaleriController', 'getById');
$model_ok = class_exists('Galeri') && method_exists('Galeri', 'delete') && method_exists('Galeri', 'getLastError') && method_exists('Galeri', 'findById');

if (!$controller_ok || !$model_ok) {
    error_log("FATAL ERROR di hapus_foto.php: Komponen Galeri tidak tersedia.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen untuk menghapus foto tidak tersedia.');
    redirect(ADMIN_URL . 'galeri/kelola_galeri.php');
    exit;
}

$redirect_url = ADMIN_URL . 'galeri/kelola_galeri.php';

// 4. Validasi Metode Request (HANYA GET untuk link konfirmasi JS) dan CSRF Token
if (!isset($_GET['id']) || !isset($_GET['csrf_token'])) {
    set_flash_message('danger', 'Permintaan penghapusan tidak valid atau tidak lengkap.');
    redirect($redirect_url);
    exit;
}

// 5. Validasi ID Foto
$id_foto_to_delete = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$id_foto_to_delete || $id_foto_to_delete <= 0) {
    set_flash_message('danger', 'ID foto tidak valid untuk dihapus.');
    redirect($redirect_url);
    exit;
}

// Opsional: Ambil data foto sebelum dihapus untuk pesan yang lebih informatif
$foto_info_sebelum_hapus = GaleriController::getById($id_foto_to_delete);
$info_untuk_pesan = "ID #" . $id_foto_to_delete; // Default
if ($foto_info_sebelum_hapus) {
    $info_untuk_pesan = e($foto_info_sebelum_hapus['keterangan'] ?: ($foto_info_sebelum_hapus['nama_file'] ?: "ID #" . $id_foto_to_delete));
}


// 6. Proses Penghapusan melalui Controller
$delete_result = GaleriController::delete($id_foto_to_delete);

if ($delete_result === true) {
    set_flash_message('success', 'Foto galeri "' . $info_untuk_pesan . '" berhasil dihapus.');
    error_log("Admin (ID: " . (get_current_user_id() ?? 'N/A') . ") menghapus foto galeri ID: " . $id_foto_to_delete . " (" . $info_untuk_pesan . ")");
} elseif (is_string($delete_result)) {
    $pesan_error_khusus = 'Gagal menghapus foto: ';
    switch ($delete_result) {
        case 'item_not_found_on_delete':
            $pesan_error_khusus .= 'Foto tidak ditemukan.';
            break;
        case 'db_delete_failed':
            $pesan_error_khusus .= 'Gagal menghapus data dari database.';
            break;
        default:
            $pesan_error_khusus .= 'Terjadi kesalahan tidak diketahui.';
            break;
    }
    set_flash_message('danger', $pesan_error_khusus . " (ID: " . $id_foto_to_delete . ")");
} else {
    $error_from_model = class_exists('Galeri') && method_exists('Galeri', 'getLastError') ? Galeri::getLastError() : null;
    $pesan_error_flash = 'Gagal menghapus foto galeri ID #' . e($id_foto_to_delete) . '.';
    if ($error_from_model && strpos(strtolower($error_from_model), 'tidak ada error') === false && strpos(strtolower($error_from_model), 'belum diinisialisasi') === false) {
        $pesan_error_flash .= ' Detail Sistem: ' . e($error_from_model);
    }
    set_flash_message('danger', $pesan_error_flash);
    error_log("Gagal menghapus foto galeri ID: " . $id_foto_to_delete . ". Error Model: " . ($error_from_model ?? 'Tidak ada detail'));
}

redirect($redirect_url);
exit;
