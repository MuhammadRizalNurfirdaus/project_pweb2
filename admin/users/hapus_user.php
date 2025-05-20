<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\hapus_user.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di hapus_user.php: Gagal memuat config.php.");
    $errorMessage = (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) ? "Gagal memuat config.php." : "Kesalahan konfigurasi server.";
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan. " . $errorMessage);
}

// 2. Pastikan hanya admin yang bisa akses
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di hapus_user.php: Fungsi require_admin() tidak ditemukan.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia.");
}
require_admin();

// 3. Pastikan Model User dan metode yang diperlukan ada
// config.php seharusnya sudah memuat Model User dan menginisialisasi koneksi DB nya
if (
    !class_exists('User') ||
    !method_exists('User', 'delete') ||
    !method_exists('User', 'findById') ||
    !method_exists('User', 'getLastError')
) {
    error_log("FATAL ERROR di hapus_user.php: Model User atau metode penting (delete, findById, getLastError) tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat (MUSR_DEL_NF_HAPUS).');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 4. Hanya proses jika metode GET (karena link hapus biasanya GET dengan token CSRF)
if (!is_get()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 5. Validasi CSRF Token dari URL
// Nama parameter token di URL harus 'csrf_token' (sesuai dengan yang di-generate di kelola_users.php)
$csrf_token_name_in_url = 'csrf_token';
if (!function_exists('verify_csrf_token') || !verify_csrf_token($csrf_token_name_in_url, true, 'GET')) {
    set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan coba lagi dari halaman Kelola Pengguna.');
    error_log("Hapus User - Kegagalan Verifikasi CSRF (GET). Token yang diterima dari URL via '{$csrf_token_name_in_url}': " . ($_GET[$csrf_token_name_in_url] ?? 'TIDAK ADA'));
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 6. Ambil dan Validasi ID Pengguna
$user_id_to_delete = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if ($user_id_to_delete <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid atau tidak disertakan untuk dihapus.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 7. Proteksi Tambahan
if ($user_id_to_delete === 1) { // Asumsi ID 1 adalah admin utama
    set_flash_message('warning', 'Admin utama (ID 1) tidak dapat dihapus dari sistem.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

$current_admin_id = get_current_user_id(); // Fungsi dari auth_helpers.php
if ($current_admin_id !== null && $current_admin_id === $user_id_to_delete) {
    set_flash_message('warning', 'Anda tidak dapat menghapus akun Anda sendiri dari sistem.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 8. Proses Penghapusan Pengguna
$result_delete = false;
$user_info_for_message = "Pengguna dengan ID: {$user_id_to_delete}"; // Fallback nama

// Ambil info user sebelum dihapus untuk pesan yang lebih baik dan logging
$user_being_deleted_data = User::findById($user_id_to_delete);
if ($user_being_deleted_data) {
    $user_info_for_message = e($user_being_deleted_data['nama_lengkap'] ?? $user_being_deleted_data['nama'] ?? "ID: {$user_id_to_delete}");
} else {
    // Jika pengguna tidak ditemukan sebelum mencoba hapus, kemungkinan sudah dihapus atau ID salah
    set_flash_message('warning', 'Pengguna yang akan dihapus (ID: ' . e($user_id_to_delete) . ') tidak ditemukan.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

try {
    $result_delete = User::delete($user_id_to_delete); // Model User::delete() akan menangani penghapusan foto profil
} catch (Exception $e) {
    error_log("Exception saat User::delete() untuk ID {$user_id_to_delete}: " . $e->getMessage());
    set_flash_message('danger', 'Terjadi kesalahan teknis saat mencoba menghapus pengguna: ' . e($e->getMessage()));
    // $result_delete akan tetap false
}


if ($result_delete === true) {
    set_flash_message('success', 'Pengguna "' . $user_info_for_message . '" berhasil dihapus dari sistem.');
    error_log("ADMIN ACTION: Pengguna {$user_info_for_message} (ID: {$user_id_to_delete}) telah dihapus oleh Admin ID: {$current_admin_id}.");
} else {
    // Jika User::delete() mengembalikan false dan belum ada flash message (misalnya dari proteksi di model atau exception)
    if (!isset($_SESSION['flash_message'])) {
        $model_error = User::getLastError(); // Ambil pesan error dari Model
        $error_detail_msg = ' Gagal menghapus pengguna.';
        if ($model_error && strpos(strtolower($model_error), 'tidak dapat dihapus karena masih terkait') !== false) {
            // Pesan spesifik jika Model mencegah delete karena FK (jika Anda implementasikan ini di Model::delete)
            $error_detail_msg = ' ' . e($model_error);
        } elseif ($model_error) {
            $error_detail_msg .= ' Detail Sistem: ' . e($model_error);
        } else {
            $error_detail_msg .= ' Pengguna mungkin sudah dihapus atau terjadi kesalahan tidak diketahui.';
        }
        set_flash_message('danger', 'Gagal menghapus pengguna "' . $user_info_for_message . '".' . $error_detail_msg);
    }
    // Error spesifik dari database (jika ada) seharusnya sudah di-log oleh Model User::delete()
    error_log("Info di hapus_user.php: Percobaan hapus pengguna {$user_info_for_message} (ID: {$user_id_to_delete}) gagal. Hasil User::delete(): " . print_r($result_delete, true) . ". DB Error dari Model: " . (User::getLastError() ?? 'N/A'));
}

redirect(ADMIN_URL . 'users/kelola_users.php');
// exit; // redirect() di helpers.php Anda seharusnya sudah memiliki exit