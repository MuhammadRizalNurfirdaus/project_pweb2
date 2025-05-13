<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\hapus_pemesanan.php

// 1. Muat Konfigurasi Utama dan Pemeriksaan Dasar
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di hapus_pemesanan.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan.");
}

// 2. Otentikasi Admin - SANGAT PENTING!
// Pastikan admin yang login. Fungsi require_admin() dari auth_helpers.php
try {
    require_admin();
} catch (Exception $e) { // Menangkap exception jika require_admin() melemparnya
    error_log("ERROR saat otentikasi admin di hapus_pemesanan.php: " . $e->getMessage());
    set_flash_message('danger', 'Akses ditolak. Anda harus login sebagai admin.');
    redirect(AUTH_URL . '/login.php'); // Menggunakan AUTH_URL dari config
    exit;
}

// 3. Muat Controller PemesananTiket
// Controller PemesananTiketController sudah dimuat oleh config.php
if (!class_exists('PemesananTiketController')) {
    error_log("FATAL ERROR di hapus_pemesanan.php: Class PemesananTiketController tidak ditemukan setelah config.php dimuat.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket tidak ditemukan.');
    redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
    exit;
}

// 4. Validasi Metode Request dan ID
// Idealnya, hapus menggunakan metode POST dengan CSRF token untuk keamanan.
// Jika menggunakan GET, pastikan ada konfirmasi di sisi klien (misal, JavaScript confirm).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Jika Anda menggunakan link GET dengan konfirmasi JavaScript, Anda bisa membiarkan ini.
    // Tapi untuk keamanan lebih, validasi request method.
    // Jika Anda mengubahnya menjadi form POST, baris ini penting.
    // Untuk saat ini, kita asumsikan link GET dari tabel.
    // set_flash_message('warning', 'Aksi hapus harus melalui metode POST yang aman.');
    // redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
    // exit;
}

// Ambil ID dari POST (jika form hapus menggunakan POST) atau GET (jika link langsung)
// Karena form di kelola_pemesanan.php menggunakan POST, kita utamakan POST
$id_pemesanan = null;
if (isset($_POST['id_pemesanan']) && isset($_POST['hapus_pemesanan_submit'])) { // Dari form di kelola_pemesanan.php
    $id_pemesanan = filter_var($_POST['id_pemesanan'], FILTER_VALIDATE_INT);
} elseif (isset($_GET['id'])) { // Fallback jika ada yang memanggil via GET (kurang aman)
    $id_pemesanan = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    // Jika via GET, pastikan ada mekanisme CSRF sederhana jika memungkinkan, atau minimal konfirmasi.
    // Untuk saat ini, kita proses saja.
}


if (!$id_pemesanan || $id_pemesanan <= 0) {
    set_flash_message('danger', 'ID Pemesanan tidak valid atau tidak ditemukan untuk dihapus.');
    redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
    exit;
}

// 5. Panggil Metode Delete dari Controller
$deleteBerhasil = false;
if (method_exists('PemesananTiketController', 'deletePemesananById')) { // Pastikan nama method benar
    try {
        $deleteBerhasil = PemesananTiketController::delete($id_pemesanan);
        if ($deleteBerhasil) {
            set_flash_message('success', 'Pemesanan tiket (ID: ' . e($id_pemesanan) . ') berhasil dihapus beserta data terkait.');
            $admin_user_id = $_SESSION['user_id'] ?? 'UNKNOWN_ADMIN';
            error_log("ADMIN ACTION: Pemesanan tiket ID {$id_pemesanan} dihapus oleh admin ID {$admin_user_id}");
        } else {
            // Jika controller mengembalikan false, controller/model mungkin sudah set flash message atau log error
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menghapus pemesanan tiket (ID: ' . e($id_pemesanan) . '). Operasi di controller tidak berhasil.');
            }
        }
    } catch (Exception $e) {
        error_log("EXCEPTION saat menghapus pemesanan (ID: {$id_pemesanan}): " . $e->getMessage());
        set_flash_message('danger', 'Terjadi kesalahan teknis saat menghapus pemesanan: ' . e($e->getMessage()));
    }
} else {
    set_flash_message('danger', 'Kesalahan sistem: Fungsi untuk menghapus pemesanan tidak tersedia.');
    error_log("FATAL ERROR di hapus_pemesanan.php: Method 'deletePemesananById' tidak ditemukan di PemesananTiketController.");
}

// 6. Redirect kembali ke halaman kelola pemesanan tiket
redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
exit;
