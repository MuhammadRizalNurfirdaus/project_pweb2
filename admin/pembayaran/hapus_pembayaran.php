<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\hapus_pembayaran.php

// LANGKAH 1: Sertakan config.php
if (!file_exists(__DIR__ . '/../../config/config.php')) {
    http_response_code(503);
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] FATAL: Gagal memuat config.php dari admin/pembayaran/hapus_pembayaran.php");
    exit("Kesalahan konfigurasi server.");
}
require_once __DIR__ . '/../../config/config.php';

// LANGKAH 2: Otentikasi Admin
try {
    if (!function_exists('require_admin')) {
        throw new Exception("Fungsi require_admin() tidak ditemukan.");
    }
    require_admin();
} catch (Exception $e) {
    error_log("FATAL ERROR di hapus_pembayaran.php: Exception saat require_admin(): " . $e->getMessage());
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak. Sesi tidak valid atau Anda belum login sebagai admin.');
    if (function_exists('redirect') && defined('AUTH_URL')) redirect(AUTH_URL . 'login.php');
    else exit('Akses ditolak.');
}

// LANGKAH 3: Pastikan Model Pembayaran ada
if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'delete') || !method_exists('Pembayaran', 'getLastError')) {
    error_log("KRITIS hapus_pembayaran.php: Model Pembayaran atau metode delete/getLastError tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data pembayaran tidak dapat dimuat (ERR_PM_NF_DEL).');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'pembayaran/kelola_pembayaran.php');
    else exit("Kesalahan sistem.");
}


$redirect_url = defined('ADMIN_URL') ? ADMIN_URL . 'pembayaran/kelola_pembayaran.php' : (defined('BASE_URL') ? BASE_URL : '/');

// LANGKAH 4: Proses Penghapusan
if (is_post() && isset($_POST['confirm_delete_pembayaran'])) {
    if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid: Token keamanan tidak cocok.');
        if (function_exists('redirect')) redirect($redirect_url);
        exit;
    }

    $pembayaran_id_to_delete = isset($_POST['pembayaran_id_to_delete']) ? filter_var($_POST['pembayaran_id_to_delete'], FILTER_VALIDATE_INT) : null;

    if (!$pembayaran_id_to_delete || $pembayaran_id_to_delete <= 0) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pembayaran tidak valid atau tidak disertakan untuk dihapus.');
        if (function_exists('redirect')) redirect($redirect_url);
        exit;
    }

    // Untuk keamanan tambahan, Anda bisa mengambil data pembayaran dulu
    // dan memastikan bahwa admin memang berhak menghapusnya atau ada kondisi tertentu
    $pembayaranInfo = Pembayaran::findById($pembayaran_id_to_delete);
    if (!$pembayaranInfo) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Pembayaran yang akan dihapus tidak ditemukan.');
        if (function_exists('redirect')) redirect($redirect_url);
        exit;
    }
    if ($pembayaranInfo['status_pembayaran'] === 'success') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Pembayaran yang sudah berhasil tidak dapat dihapus.');
        if (function_exists('redirect')) redirect($redirect_url);
        exit;
    }


    $delete_result = Pembayaran::delete($pembayaran_id_to_delete);

    if ($delete_result) {
        if (function_exists('set_flash_message')) set_flash_message('success', 'Data pembayaran ID #' . htmlspecialchars($pembayaran_id_to_delete) . ' berhasil dihapus.');
        $current_user_id_log = function_exists('get_current_user_id') ? get_current_user_id() : 'N/A';
        error_log("Admin (ID: " . $current_user_id_log . ") menghapus pembayaran ID: " . $pembayaran_id_to_delete);
    } else {
        $error_from_model = Pembayaran::getLastError();
        $pesan_error_flash = 'Gagal menghapus data pembayaran ID #' . htmlspecialchars($pembayaran_id_to_delete) . '.';
        if ($error_from_model && strpos(strtolower($error_from_model), 'tidak ada error') === false && strpos(strtolower($error_from_model), 'belum diinisialisasi') === false) {
            $pesan_error_flash .= ' Detail Sistem: ' . htmlspecialchars($error_from_model);
        } else {
            $pesan_error_flash .= ' Silakan coba lagi atau hubungi administrator.';
        }
        if (function_exists('set_flash_message')) set_flash_message('danger', $pesan_error_flash);
        error_log("Gagal menghapus pembayaran ID: " . $pembayaran_id_to_delete . ". Error Model: " . $error_from_model);
    }

    if (function_exists('redirect')) redirect($redirect_url);
    exit;
} else {
    if (function_exists('set_flash_message')) set_flash_message('warning', 'Aksi penghapusan tidak valid atau tidak dikonfirmasi.');
    if (function_exists('redirect')) redirect($redirect_url);
    exit;
}
