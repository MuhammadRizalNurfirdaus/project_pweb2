<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\hapus_pemesanan_sewa.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari hapus_pemesanan_sewa.php");
    exit("Server Error.");
}

// 2. Otentikasi Admin
require_admin(); // Pastikan fungsi ini ada dan berfungsi dari auth_helpers.php

// 3. Sertakan Controller yang diperlukan
if (!require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat PemesananSewaAlatController.php dari hapus_pemesanan_sewa.php");
    set_flash_message('danger', 'Kesalahan sistem: Komponen tidak dapat dimuat.');
    redirect('admin/pemesanan_sewa/kelola_pemesanan_sewa.php');
}

$pemesanan_sewa_id = null;
$redirect_url = ADMIN_URL . '/pemesanan_sewa/kelola_pemesanan_sewa.php'; // URL default untuk redirect

// Prioritaskan ID dari POST (lebih aman)
if (is_post() && isset($_POST['id_pemesanan_sewa'])) {
    $pemesanan_sewa_id = filter_var($_POST['id_pemesanan_sewa'], FILTER_VALIDATE_INT);
} elseif (is_get() && isset($_GET['id'])) {
    // Jika dari GET, ini biasanya untuk link hapus langsung (kurang aman tanpa konfirmasi tambahan/CSRF)
    // Pertimbangkan untuk menambahkan langkah konfirmasi jika menggunakan GET
    $pemesanan_sewa_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    // Jika menggunakan GET, Anda mungkin ingin menambahkan token CSRF untuk keamanan
}


if (!$pemesanan_sewa_id || $pemesanan_sewa_id <= 0) {
    set_flash_message('danger', 'ID Pemesanan Sewa tidak valid atau tidak diberikan.');
    redirect($redirect_url);
}

// Lakukan proses penghapusan menggunakan Controller
if (class_exists('PemesananSewaAlatController') && method_exists('PemesananSewaAlatController', 'deletePemesananSewa')) {
    try {
        if (PemesananSewaAlatController::deletePemesananSewa($pemesanan_sewa_id)) {
            // Pesan sukses sudah di-set di dalam controller jika Anda mengikuti contoh sebelumnya
            // Jika tidak, set di sini:
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('success', 'Pemesanan sewa alat dengan ID ' . e($pemesanan_sewa_id) . ' berhasil dihapus.');
            }
        } else {
            // Pesan error kemungkinan sudah di-set oleh controller
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menghapus pemesanan sewa alat. Mungkin data tidak ditemukan atau terjadi kesalahan lain.');
            }
        }
    } catch (Throwable $e) {
        error_log("Error di hapus_pemesanan_sewa.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        set_flash_message('danger', 'Terjadi kesalahan tak terduga saat menghapus pemesanan sewa.');
    }
} else {
    set_flash_message('danger', 'Kesalahan sistem: Fungsi penghapusan tidak tersedia.');
    error_log("Controller PemesananSewaAlatController atau metode deletePemesananSewa tidak ditemukan.");
}

// Redirect kembali ke halaman kelola
redirect($redirect_url);
