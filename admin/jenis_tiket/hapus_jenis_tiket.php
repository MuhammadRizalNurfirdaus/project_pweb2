<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\hapus_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di hapus_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di hapus_jenis_tiket.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// 3. Pastikan JenisTiketController dan metode yang dibutuhkan ada
if (
    !class_exists('JenisTiketController') ||
    !method_exists('JenisTiketController', 'delete') ||
    !method_exists('JenisTiketController', 'getById')
) {
    error_log("FATAL ERROR di hapus_jenis_tiket.php: JenisTiketController atau metode penting tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
    if (defined('ADMIN_URL') && function_exists('redirect')) redirect(ADMIN_URL . 'dashboard.php');
    else exit('Kesalahan sistem fatal.');
}

$redirect_url_kelola = ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php';

// 4. Hanya proses jika metode GET (sesuai link di kelola_jenis_tiket.php)
if (!is_get()) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    if (function_exists('redirect')) redirect($redirect_url_kelola);
    exit;
}

// 5. Validasi CSRF Token dari URL
// Menggunakan 'csrf_token' sebagai nama default dari generate_csrf_token()
// Parameter kedua true akan meng-unset token setelah verifikasi berhasil.
if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true, 'GET')) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan coba lagi dari halaman Kelola Jenis Tiket.');
    error_log("Hapus Jenis Tiket - Kegagalan Verifikasi CSRF. Sesi CSRF: " . ($_SESSION['csrf_token'] ?? 'TIDAK ADA') . ", GET CSRF: " . ($_GET['csrf_token'] ?? 'TIDAK ADA'));
    if (function_exists('redirect')) redirect($redirect_url_kelola);
    exit;
}
error_log("Hapus Jenis Tiket - CSRF Token berhasil diverifikasi.");


// 6. Ambil dan Validasi ID Jenis Tiket dari URL
$id_jenis_tiket_to_delete = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if ($id_jenis_tiket_to_delete === false || $id_jenis_tiket_to_delete <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('warning', 'Permintaan tidak valid: ID jenis tiket tidak ditemukan atau formatnya salah.');
    if (function_exists('redirect')) redirect($redirect_url_kelola);
    exit;
}

// 7. Ambil nama untuk pesan flash sebelum dihapus
$nama_display_untuk_pesan = "ID #" . $id_jenis_tiket_to_delete; // Default
$jenis_tiket_info = JenisTiketController::getById($id_jenis_tiket_to_delete);
if ($jenis_tiket_info && isset($jenis_tiket_info['nama_layanan_display'])) {
    $nama_display_untuk_pesan = e($jenis_tiket_info['nama_layanan_display'] . ' (' . ($jenis_tiket_info['tipe_hari'] ?? '') . ')');
} elseif (!$jenis_tiket_info) {
    // Jika item sudah tidak ada sebelum mencoba delete, berikan pesan yang sesuai
    set_flash_message('warning', "Jenis tiket dengan ID #{$id_jenis_tiket_to_delete} tidak ditemukan atau mungkin sudah dihapus sebelumnya.");
    redirect($redirect_url_kelola);
    exit;
}


// 8. Proses Penghapusan melalui Controller
$delete_result = JenisTiketController::delete($id_jenis_tiket_to_delete); // Controller akan memanggil Model

if ($delete_result === true) {
    set_flash_message('success', 'Jenis tiket "' . $nama_display_untuk_pesan . '" berhasil dihapus.');
    error_log("Admin (ID: " . (get_current_user_id() ?? 'N/A') . ") menghapus jenis tiket ID: {$id_jenis_tiket_to_delete} ({$nama_display_untuk_pesan})");
} else {
    // Controller diharapkan sudah menyetel flash message jika gagal karena alasan spesifik (misal, foreign key)
    // Jika controller mengembalikan string kode error, kita bisa menampilkannya
    if (!isset($_SESSION['flash_message'])) { // Hanya set jika controller belum
        $error_message = 'Gagal menghapus jenis tiket "' . $nama_display_untuk_pesan . '".';
        if (is_string($delete_result)) { // Jika controller mengembalikan kode error
            // Anda bisa menambahkan switch case di sini untuk pesan yang lebih spesifik berdasarkan $delete_result
            if ($delete_result === 'item_not_found_on_delete') {
                $error_message = 'Jenis tiket "' . $nama_display_untuk_pesan . '" tidak ditemukan saat mencoba menghapus.';
            } else {
                $error_message .= ' Alasan: ' . e($delete_result);
            }
        } elseif (class_exists('JenisTiket') && method_exists('JenisTiket', 'getLastError') && ($modelError = JenisTiket::getLastError()) && strpos(strtolower($modelError), 'tidak ada error') === false) {
            $error_message .= ' Detail Sistem: ' . e($modelError);
        } else {
            $error_message .= ' Terjadi kesalahan internal.';
        }
        set_flash_message('danger', $error_message);
    }
    error_log("Gagal menghapus jenis tiket ID: {$id_jenis_tiket_to_delete}. Hasil dari controller: " . print_r($delete_result, true));
}

redirect($redirect_url_kelola);
// exit; // redirect() sudah memiliki exit
