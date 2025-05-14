<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\hapus_user.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di hapus_user.php: Gagal memuat config.php.");
    $errorMessage = (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) ? "Gagal memuat config.php." : "Kesalahan konfigurasi server.";
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan. " . $errorMessage);
}

// 2. Pastikan hanya admin yang bisa akses
require_admin(); // Fungsi ini akan redirect jika bukan admin

// 3. Sertakan Model User
// Diasumsikan User::setDbConnection($conn) sudah dipanggil di config.php
if (!class_exists('User')) {
    $userModelPath = MODELS_PATH . '/User.php'; // MODELS_PATH dari config.php
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
        // Jika User::setDbConnection belum dipanggil di config.php, dan $conn tersedia:
        // if (isset($conn) && $conn instanceof mysqli && method_exists('User', 'setDbConnection')) {
        //     User::setDbConnection($conn);
        // }
    } else {
        error_log("FATAL ERROR di hapus_user.php: Model User.php tidak ditemukan di " . $userModelPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
        exit;
    }
}

// 4. Hanya proses jika metode GET (karena link hapus biasanya GET dengan token CSRF)
if (!is_get()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 5. Validasi CSRF Token dari URL
// Diasumsikan verify_csrf_token() menerima token dan opsi untuk unset
// Menggunakan 'true' sebagai argumen kedua untuk meng-unset token setelah verifikasi
if (!isset($_GET['csrf_token']) || !function_exists('verify_csrf_token') || !verify_csrf_token($_GET['csrf_token'], true)) {
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi dari halaman Kelola Pengguna.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 6. Ambil dan Validasi ID Pengguna
$user_id_to_delete = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if ($user_id_to_delete <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid atau tidak disertakan untuk dihapus.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 7. Proteksi Tambahan
if ($user_id_to_delete === 1) {
    set_flash_message('warning', 'Admin utama (ID 1) tidak dapat dihapus dari sistem.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

$current_admin_id = function_exists('get_current_user_id') ? get_current_user_id() : null;
if ($current_admin_id !== null && $current_admin_id === $user_id_to_delete) {
    set_flash_message('warning', 'Anda tidak dapat menghapus akun Anda sendiri.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 8. Proses Penghapusan Pengguna
$result = false;
$user_being_deleted_info = null; // Untuk logging atau pesan flash nama pengguna

if (method_exists('User', 'delete')) {
    // Opsional: Ambil info user sebelum dihapus untuk pesan yang lebih baik
    if (method_exists('User', 'findById')) {
        $user_being_deleted_info = User::findById($user_id_to_delete);
    }
    try {
        $result = User::delete($user_id_to_delete);
    } catch (Exception $e) {
        error_log("Exception saat User::delete() untuk ID {$user_id_to_delete}: " . $e->getMessage());
        set_flash_message('danger', 'Terjadi kesalahan teknis saat mencoba menghapus pengguna.');
        // $result akan tetap false
    }
} else {
    error_log("FATAL ERROR di hapus_user.php: Metode User::delete() tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi penghapusan pengguna tidak tersedia.');
}


$nama_pengguna_yang_dihapus = $user_being_deleted_info ? ($user_being_deleted_info['nama_lengkap'] ?? $user_being_deleted_info['nama'] ?? "ID: {$user_id_to_delete}") : "Pengguna ID: {$user_id_to_delete}";

if ($result) {
    set_flash_message('success', 'Pengguna "' . htmlspecialchars($nama_pengguna_yang_dihapus) . '" berhasil dihapus.');
    error_log("ADMIN ACTION: Pengguna {$nama_pengguna_yang_dihapus} (ID: {$user_id_to_delete}) dihapus oleh Admin ID: {$current_admin_id}.");
} else {
    // Jika User::delete() mengembalikan false dan belum ada flash message (misalnya dari proteksi di model)
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal menghapus pengguna "' . htmlspecialchars($nama_pengguna_yang_dihapus) . '". Pengguna mungkin tidak ditemukan atau masih terkait dengan data lain.');
    }
    // Pesan error spesifik dari database seharusnya sudah di-log oleh Model User::delete() jika ada masalah DB
    error_log("Info di hapus_user.php: Percobaan hapus pengguna {$nama_pengguna_yang_dihapus} (ID: {$user_id_to_delete}) mengembalikan " . print_r($result, true) . " dari User::delete(). DB Error: " . (method_exists('User', 'getLastError') ? User::getLastError() : 'N/A'));
}

redirect(ADMIN_URL . '/users/kelola_users.php');
// exit; // redirect() sudah memiliki exit