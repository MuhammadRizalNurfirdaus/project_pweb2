<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\proses_edit_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_edit_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di proses_edit_jenis_tiket.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// 3. Pastikan JenisTiketController dan metodenya ada
if (!class_exists('JenisTiketController') || !method_exists('JenisTiketController', 'update')) {
    error_log("FATAL ERROR di proses_edit_jenis_tiket.php: JenisTiketController atau metode update tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
    if (defined('ADMIN_URL') && function_exists('redirect')) redirect(ADMIN_URL . 'dashboard.php');
    else exit('Kesalahan sistem fatal.');
}

// 4. Validasi ID Jenis Tiket dari POST
$id_jenis_tiket = input('id_jenis_tiket', 0, 'POST'); // Ambil ID dari input hidden form
$id_jenis_tiket = filter_var($id_jenis_tiket, FILTER_VALIDATE_INT);

if (!$id_jenis_tiket || $id_jenis_tiket <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Jenis Tiket tidak valid untuk pembaruan.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

$redirect_url_form_edit = ADMIN_URL . 'jenis_tiket/edit_jenis_tiket.php?id=' . $id_jenis_tiket;
$redirect_url_kelola = ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php';
$session_form_data_key = 'flash_form_data_edit_jenis_tiket_' . $id_jenis_tiket;


// 5. Hanya proses jika metode POST dan tombol submit ditekan
if (!is_post() || !isset($_POST['submit_edit_jenis_tiket'])) { // Tombol submit di form HARUS name="submit_edit_jenis_tiket"
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses tidak sah atau form tidak dikirim dengan benar.');
    if (function_exists('redirect')) redirect($redirect_url_kelola);
    exit;
}

// 6. Validasi CSRF Token
if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true)) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan coba lagi.');
    error_log("Proses Edit Jenis Tiket - Kegagalan Verifikasi CSRF.");
    if (function_exists('redirect')) redirect($redirect_url_form_edit);
    exit;
}

// 7. Ambil data dari form
$nama_layanan = trim(input('nama_layanan_display', '', 'POST'));
$tipe_hari = trim(input('tipe_hari', '', 'POST'));
$harga_input = input('harga', '', 'POST');
$deskripsi = trim(input('deskripsi', null, 'POST'));
$wisata_id_input = input('wisata_id', null, 'POST');
$aktif = (input('aktif', '0', 'POST') == '1') ? 1 : 0;

// 8. Simpan data ke session untuk repopulasi JIKA ada error redirect
$_SESSION[$session_form_data_key] = [
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => $harga_input,
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => $wisata_id_input
];

// 9. Siapkan data untuk dikirim ke Controller
// Validasi input detail akan dilakukan di dalam Controller
$data_to_controller = [
    'id' => $id_jenis_tiket,
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => $harga_input, // Controller akan memvalidasi dan konversi ke float
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => $wisata_id_input // Controller akan memvalidasi dan konversi ke int
];

// 10. Panggil metode update dari JenisTiketController
$update_result = JenisTiketController::update($data_to_controller);

if ($update_result === true) { // Controller mengembalikan true jika sukses
    unset($_SESSION[$session_form_data_key]);
    set_flash_message('success', 'Jenis tiket "' . e($nama_layanan) . ' (' . e($tipe_hari) . ')" berhasil diperbarui.');
    redirect($redirect_url_kelola);
    exit;
} else {
    // Jika gagal, Controller seharusnya sudah menyetel flash message dengan error spesifik.
    // Jika Controller mengembalikan false, kita bisa set pesan default jika belum ada.
    if ($update_result === false && !isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal memperbarui jenis tiket karena kesalahan internal. Silakan coba lagi.');
    }
    // Jika Controller mengembalikan string kode error, flash message sudah diset oleh controller
    redirect($redirect_url_form_edit);
    exit;
}
