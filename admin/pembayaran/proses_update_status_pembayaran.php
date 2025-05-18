<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\proses_update_status_pembayaran.php

// Memuat konfigurasi utama
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] FATAL: Gagal memuat config.php dari admin/pembayaran/proses_update_status_pembayaran.php");
    echo "Kesalahan konfigurasi server. Silakan hubungi administrator.";
    exit;
}

// Memastikan admin sudah login
if (!function_exists('require_admin') || !function_exists('is_admin')) {
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] FATAL proses_update_status_pembayaran.php: Fungsi require_admin() atau is_admin() tidak ditemukan.");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem: Komponen autentikasi admin tidak tersedia.');
        redirect(defined('BASE_URL') ? BASE_URL : '/');
    } else {
        echo "Kesalahan autentikasi. Akses ditolak.";
    }
    exit;
}
require_admin();

// Pengecekan PembayaranController
if (!class_exists('PembayaranController')) {
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] KRITIS proses_update_status_pembayaran.php: PembayaranController tidak ditemukan.");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem: Komponen pemrosesan pembayaran tidak dapat dimuat (ERR_PPC_NF_PUS).');
        redirect(defined('ADMIN_URL') ? ADMIN_URL . 'pembayaran/kelola_pembayaran.php' : (defined('BASE_URL') ? BASE_URL : '/'));
    } else {
        echo "Kesalahan sistem: Komponen inti tidak termuat.";
    }
    exit;
}
// Pengecekan Model Pembayaran untuk findById 
if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'findById')) {
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] KRITIS proses_update_status_pembayaran.php: Model Pembayaran atau metode findById tidak ditemukan.");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem: Komponen data pembayaran tidak dapat dimuat (ERR_PM_NF_PUS).');
        redirect(defined('ADMIN_URL') ? ADMIN_URL . 'pembayaran/kelola_pembayaran.php' : (defined('BASE_URL') ? BASE_URL : '/'));
    } else {
        echo "Kesalahan sistem: Komponen data inti tidak termuat.";
    }
    exit;
}


$redirect_url_default = defined('ADMIN_URL') ? ADMIN_URL . 'pembayaran/kelola_pembayaran.php' : (defined('BASE_URL') ? BASE_URL : '/');
$pembayaran_id_from_form_for_redirect = null;

$timestamp_req = date("Y-m-d H:i:s");
error_log("[$timestamp_req] --- Memulai proses_update_status_pembayaran.php ---");
error_log("[$timestamp_req] Metode Request: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
if (is_post()) {
    error_log("[$timestamp_req] Data POST yang diterima: " . print_r($_POST, true));
}

if (is_post() && isset($_POST['submit_update_payment_status'])) {
    error_log("[$timestamp_req] Form POST diterima dengan tombol 'submit_update_payment_status'.");

    if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid: Token keamanan tidak cocok atau hilang.');
        $log_csrf_session = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'Tidak ada di sesi';
        $log_csrf_post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : 'Tidak ada di POST';
        error_log("[$timestamp_req] CSRF Token Mismatch atau fungsi tidak ada. Sesi CSRF: {$log_csrf_session}, POST CSRF: {$log_csrf_post}");
        if (function_exists('redirect')) redirect($redirect_url_default);
        exit;
    }
    error_log("[$timestamp_req] CSRF Token berhasil diverifikasi.");

    $pembayaran_id_from_form = function_exists('input') ? input('pembayaran_id', null, 'POST') : ($_POST['pembayaran_id'] ?? null);
    $pemesanan_id_from_form = function_exists('input') ? input('pemesanan_id', null, 'POST') : ($_POST['pemesanan_id'] ?? null);
    $new_status_pembayaran_from_form = function_exists('input') ? input('new_payment_status', null, 'POST') : ($_POST['new_payment_status'] ?? null);
    $jumlah_dibayar_update_from_form = function_exists('input') ? input('jumlah_dibayar_update', null, 'POST') : ($_POST['jumlah_dibayar_update'] ?? null);
    $custom_redirect_url = function_exists('input') ? input('redirect_url', null, 'POST') : ($_POST['redirect_url'] ?? null);

    $pembayaran_id_from_form_for_redirect = $pembayaran_id_from_form;
    error_log("[$timestamp_req] Data dari form: pembayaran_id='{$pembayaran_id_from_form}', new_status='{$new_status_pembayaran_from_form}', jumlah_dibayar='{$jumlah_dibayar_update_from_form}'");

    $redirect_target = $redirect_url_default;
    if (!empty($custom_redirect_url) && filter_var($custom_redirect_url, FILTER_VALIDATE_URL)) {
        if (defined('BASE_URL') && strpos($custom_redirect_url, BASE_URL) === 0) {
            $redirect_target = $custom_redirect_url;
        } else {
            error_log("[$timestamp_req] Peringatan: custom_redirect_url '{$custom_redirect_url}' tidak valid atau bukan bagian dari BASE_URL. Menggunakan redirect default.");
        }
    } elseif (!empty($pembayaran_id_from_form_for_redirect) && filter_var($pembayaran_id_from_form_for_redirect, FILTER_VALIDATE_INT) && (int)$pembayaran_id_from_form_for_redirect > 0 && defined('ADMIN_URL')) {
        $redirect_target = ADMIN_URL . 'pembayaran/detail_pembayaran.php?id=' . (int)$pembayaran_id_from_form_for_redirect;
    }

    $pembayaran_id = filter_var($pembayaran_id_from_form, FILTER_VALIDATE_INT);
    $new_status = trim((string)$new_status_pembayaran_from_form);

    if (!$pembayaran_id || $pembayaran_id <= 0) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid: ID Pembayaran tidak ada atau tidak valid.');
        error_log("[$timestamp_req] Validasi gagal: ID Pembayaran tidak valid. Diterima: '{$pembayaran_id_from_form}'");
        if (function_exists('redirect')) redirect($redirect_target);
        exit;
    }
    if (empty($new_status)) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid: Status pembayaran baru wajib diisi.');
        error_log("[$timestamp_req] Validasi gagal: Status pembayaran baru kosong.");
        if (function_exists('redirect')) redirect($redirect_target);
        exit;
    }
    error_log("[$timestamp_req] Validasi input dasar lolos. Pembayaran ID: {$pembayaran_id}, Status Baru: {$new_status}");

    $details_to_update = [];
    // SEKARANG INI AKAN BEKERJA DENGAN BENAR:
    $successful_statuses = PembayaranController::getSuccessfulPaymentStatuses();

    if (in_array(strtolower($new_status), $successful_statuses)) {
        error_log("[$timestamp_req] Status baru '{$new_status}' adalah status sukses/lunas. Mengecek 'jumlah_dibayar_update'.");
        if ($jumlah_dibayar_update_from_form !== null && $jumlah_dibayar_update_from_form !== '') {
            $cleaned_jumlah = str_replace(',', '.', $jumlah_dibayar_update_from_form);
            $jumlah_dibayar_float = filter_var($cleaned_jumlah, FILTER_VALIDATE_FLOAT);

            if ($jumlah_dibayar_float === false || $jumlah_dibayar_float < 0) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Jumlah dibayar tidak valid. Harus berupa angka non-negatif (gunakan titik . untuk desimal).');
                error_log("[$timestamp_req] Validasi gagal: Jumlah dibayar tidak valid. Diterima: '{$jumlah_dibayar_update_from_form}', Setelah dibersihkan: '{$cleaned_jumlah}', Hasil filter_var: " . var_export($jumlah_dibayar_float, true));
                if (function_exists('redirect')) redirect($redirect_target);
                exit;
            }
            $details_to_update['jumlah_dibayar'] = $jumlah_dibayar_float;
            error_log("[$timestamp_req] 'jumlah_dibayar' akan diupdate menjadi: {$jumlah_dibayar_float}");

            $currentPembayaranInfo = Pembayaran::findById($pembayaran_id);
            if ($currentPembayaranInfo && empty($currentPembayaranInfo['waktu_pembayaran'])) {
                $details_to_update['waktu_pembayaran'] = date('Y-m-d H:i:s');
                error_log("[$timestamp_req] 'waktu_pembayaran' akan diupdate menjadi NOW() karena status sukses dan waktu pembayaran sebelumnya kosong.");
            } else if ($currentPembayaranInfo && !empty($currentPembayaranInfo['waktu_pembayaran'])) {
                error_log("[$timestamp_req] 'waktu_pembayaran' sudah ada ('{$currentPembayaranInfo['waktu_pembayaran']}'), tidak diubah otomatis meskipun status sukses.");
            }
        } else {
            error_log("[$timestamp_req] 'jumlah_dibayar_update' kosong/null. Jumlah dibayar tidak akan diubah dari form untuk status sukses '{$new_status}'.");
        }
    } else {
        error_log("[$timestamp_req] Status baru '{$new_status}' bukan status sukses/lunas. 'jumlah_dibayar_update' dari form tidak akan diproses.");
    }

    error_log("[$timestamp_req] Memanggil PembayaranController::updateStatusPembayaranDanPemesananTerkait dengan ID: {$pembayaran_id}, Status: {$new_status}, Details: " . print_r($details_to_update, true));

    $update_result = PembayaranController::updateStatusPembayaranDanPemesananTerkait(
        $pembayaran_id,
        $new_status,
        $details_to_update
    );

    if ($update_result) {
        if (function_exists('set_flash_message')) set_flash_message('success', 'Status pembayaran berhasil diperbarui.');
        error_log("[$timestamp_req] Update berhasil untuk pembayaran ID: {$pembayaran_id}");
    } else {
        error_log("[$timestamp_req] Update GAGAL untuk pembayaran ID: {$pembayaran_id}. Flash message yang mungkin sudah diset oleh controller: " . (isset($_SESSION['flash_message']['message']) ? $_SESSION['flash_message']['message'] : 'Tidak ada'));
        if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal memperbarui status pembayaran. Silakan periksa log untuk detail.');
        }
    }
    if (function_exists('redirect')) redirect($redirect_target);
    exit;
} else {
    if (function_exists('set_flash_message')) set_flash_message('warning', 'Permintaan tidak valid atau form tidak lengkap.');
    error_log("[$timestamp_req] Akses tidak valid ke proses_update_status_pembayaran.php (bukan POST atau tombol submit tidak ada).");
    if (function_exists('redirect')) redirect($redirect_url_default);
    exit;
}
