<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\hapus_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di hapus_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan JenisTiketController
// Diasumsikan config.php sudah memuat Controller atau Anda menggunakan autoloader.
$controllerPath = CONTROLLERS_PATH . '/JenisTiketController.php';
if (!class_exists('JenisTiketController')) {
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        error_log("FATAL ERROR di hapus_jenis_tiket.php: File JenisTiketController.php tidak ditemukan di " . $controllerPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}

// 4. Hanya proses jika metode GET (karena link hapus di tabel biasanya GET)
// Namun, pastikan ada proteksi CSRF
if (!is_get()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

// 5. Validasi CSRF Token dari URL
// Diasumsikan verify_csrf_token() menerima token dan opsi untuk unset
if (!isset($_GET['csrf_token']) || !function_exists('verify_csrf_token') || !verify_csrf_token($_GET['csrf_token'], true)) {
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi dari halaman Kelola Jenis Tiket.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

// 6. Ambil dan Validasi ID Jenis Tiket dari URL
$id_jenis_tiket_to_delete = 0; // Default
if (isset($_GET['id'])) {
    $id_jenis_tiket_to_delete = filter_var($_GET['id'], FILTER_VALIDATE_INT);
}

if ($id_jenis_tiket_to_delete === false || $id_jenis_tiket_to_delete <= 0) {
    set_flash_message('warning', 'Permintaan tidak valid: ID jenis tiket tidak ditemukan atau formatnya salah.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

// 7. Proses Penghapusan
$result = false;
$nama_display_untuk_pesan = "ID: " . $id_jenis_tiket_to_delete; // Default nama untuk pesan

// Pastikan metode yang diperlukan ada di Controller
if (!method_exists('JenisTiketController', 'getById') || !method_exists('JenisTiketController', 'delete')) {
    error_log("FATAL ERROR di hapus_jenis_tiket.php: Metode getById atau delete tidak ditemukan di JenisTiketController.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi penghapusan jenis tiket tidak tersedia.');
} else {
    // Ambil nama untuk pesan flash sebelum dihapus (opsional tapi baik untuk UX)
    $jenis_tiket = JenisTiketController::getById($id_jenis_tiket_to_delete);
    if ($jenis_tiket && isset($jenis_tiket['nama_layanan_display']) && isset($jenis_tiket['tipe_hari'])) {
        $nama_display_untuk_pesan = $jenis_tiket['nama_layanan_display'] . ' - ' . $jenis_tiket['tipe_hari'];
    }

    try {
        $result = JenisTiketController::delete($id_jenis_tiket_to_delete);
    } catch (Exception $e) {
        error_log("Exception saat JenisTiketController::delete() untuk ID {$id_jenis_tiket_to_delete}: " . $e->getMessage());
        set_flash_message('danger', 'Terjadi kesalahan teknis saat mencoba menghapus jenis tiket.');
        // $result akan tetap false
    }
}


if ($result === true) {
    set_flash_message('success', 'Jenis tiket "' . e($nama_display_untuk_pesan) . '" berhasil dihapus.');
} else {
    // Jika Controller/Model sudah set flash message (misalnya karena foreign key constraint), biarkan.
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal menghapus jenis tiket "' . e($nama_display_untuk_pesan) . '". Mungkin masih digunakan dalam data pemesanan/jadwal atau ID tidak ditemukan.');
    }
    // Error spesifik dari Model/Controller seharusnya sudah di-log di sana.
    error_log("Info di hapus_jenis_tiket.php: Percobaan hapus jenis tiket {$nama_display_untuk_pesan} (ID: {$id_jenis_tiket_to_delete}) mengembalikan " . print_r($result, true) . ".");
}

redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
// exit; // redirect() sudah memiliki exit