<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\hapus_wisata.php

// 1. Sertakan konfigurasi utama (HARUS PALING ATAS)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/wisata/hapus_wisata.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Otentikasi Admin
// Fungsi require_admin() dari auth_helpers.php (via config.php) akan menangani redirect jika tidak admin
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di hapus_wisata.php: Fungsi require_admin() tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
    if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
    else exit('Akses ditolak.');
}

// 3. Pastikan Model Wisata dan metode yang dibutuhkan ada
if (!class_exists('Wisata') || !method_exists('Wisata', 'delete') || !method_exists('Wisata', 'findById')) {
    error_log("KRITIS hapus_wisata.php: Model Wisata atau metode yang dibutuhkan tidak tersedia.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data wisata tidak dapat dioperasikan.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'wisata/kelola_wisata.php');
    else exit("Kesalahan sistem.");
}

$redirect_url = ADMIN_URL . 'wisata/kelola_wisata.php';

// 4. Validasi ID dan CSRF Token dari GET Parameter
if (!isset($_GET['id']) || !isset($_GET['csrf_token'])) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau parameter tidak lengkap.');
    if (function_exists('redirect')) redirect($redirect_url);
    exit;
}

$id_wisata = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$id_wisata || $id_wisata <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Destinasi Wisata tidak valid.');
    if (function_exists('redirect')) redirect($redirect_url);
    exit;
}

$wisata_info_nama = "ID #" . $id_wisata; // Default message
$data_wisata_lama = Wisata::findById($id_wisata); // Ambil data untuk pesan
if ($data_wisata_lama && isset($data_wisata_lama['nama'])) {
    $wisata_info_nama = e($data_wisata_lama['nama']);
}


if (Wisata::delete($id_wisata)) {
    if (function_exists('set_flash_message')) set_flash_message('success', "Destinasi wisata '{$wisata_info_nama}' berhasil dihapus.");
    error_log("Admin (ID: " . (get_current_user_id() ?? 'N/A') . ") menghapus wisata ID: {$id_wisata} ({$wisata_info_nama})");
} else {
    $error_model_msg = method_exists('Wisata', 'getLastError') ? Wisata::getLastError() : 'Tidak ada detail error dari model.';
    if (function_exists('set_flash_message')) set_flash_message('danger', "Gagal menghapus destinasi wisata '{$wisata_info_nama}'. " . e($error_model_msg));
    error_log("Gagal menghapus wisata ID: {$id_wisata}. Error Model: " . $error_model_msg);
}

if (function_exists('redirect')) redirect($redirect_url);
exit;
