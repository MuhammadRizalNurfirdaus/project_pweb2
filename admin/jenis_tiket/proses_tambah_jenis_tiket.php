<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\proses_tambah_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_tambah_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di proses_tambah_jenis_tiket.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// 3. Pastikan JenisTiketController dan metodenya ada
if (!class_exists('JenisTiketController') || !method_exists('JenisTiketController', 'create')) {
    error_log("FATAL ERROR di proses_tambah_jenis_tiket.php: JenisTiketController atau metode create tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
    if (defined('ADMIN_URL') && function_exists('redirect')) redirect(ADMIN_URL . 'dashboard.php');
    else exit('Kesalahan sistem fatal.');
}

$redirect_url_form_tambah = ADMIN_URL . 'jenis_tiket/tambah_jenis_tiket.php';
$redirect_url_kelola = ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php';
$session_form_data_key = 'flash_form_data_tambah_jenis_tiket';

// 4. Hanya proses jika metode POST dan tombol submit ditekan
if (!is_post() || !isset($_POST['submit_tambah_jenis_tiket'])) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses tidak sah atau form tidak dikirim dengan benar.');
    if (function_exists('redirect')) redirect($redirect_url_kelola);
    exit;
}

// 5. Validasi CSRF Token
if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true)) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan muat ulang form dan coba lagi.');
    error_log("Proses Tambah Jenis Tiket - Kegagalan Verifikasi CSRF. Sesi CSRF: " . ($_SESSION['csrf_token'] ?? 'TIDAK ADA') . ", POST CSRF: " . ($_POST['csrf_token'] ?? 'TIDAK ADA'));
    if (function_exists('redirect')) redirect($redirect_url_form_tambah);
    exit;
}

// 6. Ambil data dari form
$nama_layanan = trim(input('nama_layanan_display', '', 'POST'));
$tipe_hari = trim(input('tipe_hari', '', 'POST'));
$harga_input = input('harga', '', 'POST');
$deskripsi = trim(input('deskripsi', null, 'POST'));
$wisata_id_input = input('wisata_id', null, 'POST');
$aktif = (input('aktif', '0', 'POST') == '1') ? 1 : 0;

// 7. Simpan data ke session untuk repopulasi JIKA ada error redirect
$_SESSION[$session_form_data_key] = [
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => $harga_input,
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => $wisata_id_input
];

// 8. Validasi Input Server-Side (sekarang dilakukan di controller)
$errors = []; // Controller akan mengisi ini jika perlu
// ... (validasi dasar bisa tetap di sini atau sepenuhnya di controller)
if (empty($nama_layanan)) {
    $errors[] = "Nama layanan tiket wajib diisi.";
}
$allowed_tipe_hari = (defined('JenisTiket::ALLOWED_TIPE_HARI')) ? JenisTiket::ALLOWED_TIPE_HARI : ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
if (empty($tipe_hari) || !in_array($tipe_hari, $allowed_tipe_hari)) {
    $errors[] = "Tipe hari tidak valid.";
}
if ($harga_input === '' || !is_numeric($harga_input) || (float)$harga_input < 0) {
    $errors[] = "Harga wajib diisi dengan angka non-negatif yang valid.";
}
// Validasi wisata_id bisa lebih detail di controller jika perlu cek ke DB
$wisata_id_final = null;
if (!empty($wisata_id_input)) {
    $wisata_id_val = filter_var($wisata_id_input, FILTER_VALIDATE_INT);
    if ($wisata_id_val && $wisata_id_val > 0) {
        // Pengecekan keberadaan Wisata bisa dilakukan di Controller
        $wisata_id_final = $wisata_id_val;
    } else {
        $errors[] = "Format ID Destinasi Wisata terkait tidak valid.";
    }
}

if (!empty($errors)) {
    if (function_exists('set_flash_message')) set_flash_message('danger', implode("<br>", array_map('e', $errors)));
    if (function_exists('redirect')) redirect($redirect_url_form_tambah);
    exit;
}


// 9. Siapkan data untuk dikirim ke Controller
$data_to_controller = [
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => (float)$harga_input,
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => $wisata_id_final
];

// 10. Panggil metode create dari JenisTiketController
$result = JenisTiketController::create($data_to_controller); // Controller akan set flash message jika ada error validasi internal

if (is_int($result) && $result > 0) {
    unset($_SESSION[$session_form_data_key]);
    if (function_exists('set_flash_message')) set_flash_message('success', 'Jenis tiket "' . e($nama_layanan) . ' (' . e($tipe_hari) . ')" berhasil ditambahkan.');
    if (function_exists('redirect')) redirect($redirect_url_kelola);
    exit;
} else {
    // Jika Controller mengembalikan string error, ia sudah set flash message
    // Jika Controller mengembalikan false, set flash message umum di sini
    if ($result === false && !isset($_SESSION['flash_message'])) {
        $model_error_detail = (class_exists('JenisTiket') && method_exists('JenisTiket', 'getLastError')) ? JenisTiket::getLastError() : null;
        set_flash_message('danger', 'Gagal menambahkan jenis tiket. ' . ($model_error_detail ? e($model_error_detail) : 'Silakan periksa input Anda.'));
    }
    if (function_exists('redirect')) redirect($redirect_url_form_tambah);
    exit;
}
