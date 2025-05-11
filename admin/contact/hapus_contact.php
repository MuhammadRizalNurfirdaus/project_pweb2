<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\contact\hapus_contact.php

// 1. Sertakan konfigurasi utama dan Controller
// Pastikan path ini benar dari lokasi file saat ini (admin/contact/)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/ContactController.php';

// 2. Proteksi Akses Admin (Sangat Penting)
// Pastikan fungsi require_admin() sudah didefinisikan di auth_helpers.php dan berfungsi
// atau lakukan pengecekan session secara manual jika belum ada helper.
if (function_exists('require_admin')) {
    require_admin(); // Memastikan hanya admin yang bisa mengakses
} else {
    // Fallback manual jika require_admin() tidak ada (sebaiknya ada)
    if (!isset($_SESSION['is_loggedin']) || $_SESSION['is_loggedin'] !== true || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        set_flash_message('danger', 'Akses ditolak. Anda harus login sebagai admin untuk melakukan tindakan ini.');
        // Arahkan ke halaman login admin, pastikan $base_url sudah benar dari config.php
        $login_url = isset($base_url) ? $base_url . 'auth/login.php' : '../../auth/login.php'; // Fallback path
        redirect($login_url);
    }
}


// 3. Validasi ID pesan dari parameter GET
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) && (int)$_GET['id'] > 0) {
    $id_pesan_kontak = (int)$_GET['id'];

    // 4. Panggil method delete dari ContactController
    // ContactController akan memanggil Contact::delete() dari Model
    if (ContactController::delete($id_pesan_kontak)) {
        set_flash_message('success', 'Pesan kontak (ID: ' . $id_pesan_kontak . ') berhasil dihapus.');
    } else {
        // Pesan error yang lebih spesifik bisa berasal dari log Controller/Model
        // atau jika delete mengembalikan false karena ID tidak ditemukan
        set_flash_message('danger', 'Gagal menghapus pesan kontak (ID: ' . $id_pesan_kontak . '). Pesan mungkin sudah dihapus atau terjadi kesalahan internal.');
        error_log("Gagal menghapus pesan kontak ID: " . $id_pesan_kontak . " dari admin/contact/hapus_contact.php");
    }
} else {
    // Jika ID tidak ada atau tidak valid
    set_flash_message('warning', 'Permintaan tidak valid atau ID pesan kontak tidak ditemukan/tidak valid.');
    error_log("Permintaan hapus kontak tidak valid. ID: " . ($_GET['id'] ?? 'Tidak ada ID'));
}

// 5. Redirect kembali ke halaman kelola pesan kontak
// Pastikan $base_url sudah benar dari config.php
$redirect_page = isset($base_url) ? $base_url . 'admin/contact/kelola_contact.php' : '../contact/kelola_contact.php'; // Fallback path
redirect($redirect_page);
