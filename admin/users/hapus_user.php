<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\hapus_user.php

if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Kesalahan konfigurasi server.");
}
require_admin();

if (!class_exists('User')) {
    require_once MODELS_PATH . '/User.php';
}

// Hanya proses jika metode GET dan ada ID
if (!is_get()) {
    set_flash_message('danger', 'Akses tidak sah.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// Validasi CSRF dari URL
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) { // Modifikasi verify_csrf_token untuk menerima token
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}


$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid untuk dihapus.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// PENTING: Jangan biarkan admin utama (misalnya ID 1) dihapus
// Atau jangan biarkan admin menghapus akunnya sendiri
if ($user_id == 1) {
    set_flash_message('danger', 'Admin utama (ID 1) tidak dapat dihapus.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}
if (get_current_user_id() == $user_id) {
    set_flash_message('danger', 'Anda tidak dapat menghapus akun Anda sendiri.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}


$result = User::delete($user_id);

if ($result) {
    set_flash_message('success', 'Pengguna berhasil dihapus.');
} else {
    // User::delete mungkin sudah set flash message jika ada constraint
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal menghapus pengguna. Mungkin pengguna masih terkait dengan data lain atau ID tidak ditemukan.');
    }
    error_log("Gagal hapus user ID {$user_id}, hasil dari User::delete: " . print_r($result, true));
}

redirect(ADMIN_URL . '/users/kelola_users.php');
exit;
