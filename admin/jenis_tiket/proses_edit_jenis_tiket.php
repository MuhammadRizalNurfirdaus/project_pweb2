<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\proses_edit_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_edit_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan JenisTiketController
$controllerPath = CONTROLLERS_PATH . '/JenisTiketController.php';
if (!class_exists('JenisTiketController')) {
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        error_log("FATAL ERROR di proses_edit_jenis_tiket.php: File JenisTiketController.php tidak ditemukan di " . $controllerPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}

// 4. Hanya proses jika metode POST
if (!is_post()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

// 5. Validasi CSRF Token
if (!function_exists('verify_csrf_token') || !verify_csrf_token(null, true)) { // verifikasi dan unset token
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi.');
    // Redirect kembali ke halaman sebelumnya jika ada, atau ke kelola
    redirect($_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

// 6. Validasi dan Ambil ID Jenis Tiket dari POST
if (!isset($_POST['id_jenis_tiket']) || !filter_var($_POST['id_jenis_tiket'], FILTER_VALIDATE_INT) || (int)$_POST['id_jenis_tiket'] <= 0) {
    set_flash_message('danger', 'ID Jenis Tiket tidak valid untuk pembaruan.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}
$id_jenis_tiket = (int)$_POST['id_jenis_tiket'];

// 7. Ambil data lain dari form menggunakan fungsi input()
$nama_layanan = input('nama_layanan_display', '', 'post');
$tipe_hari = input('tipe_hari', '', 'post');
$harga_input = input('harga', '', 'post'); // Ambil sebagai string untuk validasi
$deskripsi = input('deskripsi', null, 'post');
$wisata_id_input = input('wisata_id', null, 'post');
$aktif = isset($_POST['aktif']) && $_POST['aktif'] == '1' ? 1 : 0;

// 8. Simpan data ke session untuk repopulasi jika ada error redirect
$session_form_data_key = 'flash_form_data_edit_jenis_tiket_' . $id_jenis_tiket; // Key unik
$_SESSION[$session_form_data_key] = [
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => $harga_input,
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => $wisata_id_input
    // Tidak perlu menyimpan 'id_jenis_tiket' di sini karena sudah ada di URL redirect
];

// 9. Validasi Input Dasar di Sini
if (empty($nama_layanan) || empty($tipe_hari) || $harga_input === '' || !is_numeric($harga_input) || (float)$harga_input < 0) {
    set_flash_message('danger', 'Nama layanan, tipe hari, dan harga (angka valid non-negatif) wajib diisi.');
    redirect(ADMIN_URL . '/jenis_tiket/edit_jenis_tiket.php?id=' . $id_jenis_tiket);
    exit;
}
// Anda bisa menambahkan validasi lain di sini, misal untuk $tipe_hari terhadap ALLOWED_TIPE_HARI dari Model

// 10. Siapkan data untuk dikirim ke Controller
$data_to_controller = [
    'id' => $id_jenis_tiket, // Sertakan ID untuk update
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => (float)$harga_input,
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => !empty($wisata_id_input) ? (int)$wisata_id_input : null
];

// 11. Panggil metode update dari JenisTiketController
if (!method_exists('JenisTiketController', 'update')) {
    error_log("FATAL ERROR di proses_edit_jenis_tiket.php: Metode JenisTiketController::update() tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi pembaruan jenis tiket tidak tersedia.');
    redirect(ADMIN_URL . '/jenis_tiket/edit_jenis_tiket.php?id=' . $id_jenis_tiket);
    exit;
}

$update_result = JenisTiketController::update($data_to_controller);

if ($update_result === true) {
    unset($_SESSION[$session_form_data_key]); // Hapus data form dari session jika berhasil
    set_flash_message('success', 'Jenis tiket "' . e($nama_layanan) . ' - ' . e($tipe_hari) . '" berhasil diperbarui.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
} elseif (is_string($update_result)) { // Jika Controller mengembalikan string kode error (misal 'duplicate')
    // Pesan flash kemungkinan sudah di-set oleh Controller jika ia mengembalikan string error
    if (!isset($_SESSION['flash_message'])) {
        $error_message = 'Gagal memperbarui jenis tiket: ';
        if ($update_result === 'duplicate') {
            $error_message .= 'Kombinasi nama layanan, tipe hari, dan destinasi sudah ada untuk entri lain.';
        } else {
            $error_message .= 'Terjadi kesalahan validasi (' . e($update_result) . ').';
        }
        set_flash_message('danger', $error_message);
    }
    redirect(ADMIN_URL . '/jenis_tiket/edit_jenis_tiket.php?id=' . $id_jenis_tiket);
} else { // Jika Controller mengembalikan false (error umum/DB)
    // Pesan error kemungkinan sudah di-set oleh Controller atau Model
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal memperbarui jenis tiket karena kesalahan internal. Silakan coba lagi.');
    }
    redirect(ADMIN_URL . '/jenis_tiket/edit_jenis_tiket.php?id=' . $id_jenis_tiket);
}
exit; // Pastikan exit setelah semua kemungkinan redirect
