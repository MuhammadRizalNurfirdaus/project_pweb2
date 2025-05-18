<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\proses_hapus_riwayat.php

require_once __DIR__ . '/../config/config.php';

// Pastikan komponen yang dibutuhkan sudah dimuat
if (!function_exists('require_login') || !function_exists('get_current_user_id') || !function_exists('verify_csrf_token') || !function_exists('redirect') || !function_exists('set_flash_message')) {
    error_log("FATAL proses_hapus_riwayat.php: Fungsi penting hilang.");
    exit("Kesalahan konfigurasi sistem. (ERR_FUNC_MISSING_PHR)");
}
if (!class_exists('PemesananTiketController') || !method_exists('PemesananTiketController', 'handleSoftDeleteByUser')) {
    error_log("KRITIS proses_hapus_riwayat.php: PemesananTiketController atau metode handleSoftDeleteByUser tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Tidak dapat memproses permintaan hapus riwayat (PTC_NF_PHR).');
    redirect('user/riwayat_pemesanan.php');
    exit;
}

require_login();
$current_user_id = get_current_user_id();
$redirect_url = USER_URL . 'riwayat_pemesanan.php';

if (is_post() && isset($_POST['confirm_soft_delete'])) {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Permintaan tidak valid: Token keamanan tidak cocok.');
        redirect($redirect_url);
        exit;
    }

    $pemesanan_id = isset($_POST['pemesanan_id_to_soft_delete']) ? filter_var($_POST['pemesanan_id_to_soft_delete'], FILTER_VALIDATE_INT) : null;

    if (!$pemesanan_id || $pemesanan_id <= 0) {
        set_flash_message('danger', 'ID pemesanan tidak valid untuk dihapus dari riwayat.');
        redirect($redirect_url);
        exit;
    }

    // Panggil controller untuk melakukan soft delete
    if (PemesananTiketController::handleSoftDeleteByUser($pemesanan_id, $current_user_id)) {
        // Pesan sukses sudah diset oleh controller
    } else {
        // Pesan error sudah diset oleh controller
    }
    redirect($redirect_url);
    exit;
} else {
    set_flash_message('warning', 'Aksi tidak valid atau tidak dikonfirmasi.');
    redirect($redirect_url);
    exit;
}
