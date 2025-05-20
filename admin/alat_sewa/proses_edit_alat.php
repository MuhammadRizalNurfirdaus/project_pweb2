<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\proses_edit_alat.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    error_log("FATAL ERROR di proses_edit_alat.php: Gagal memuat config.php. Path: " . __DIR__ . '/../../config/config.php');
    http_response_code(503);
    exit("Terjadi kesalahan konfigurasi server yang kritis. Silakan hubungi administrator. (Error Code: CFG_LOAD_FAIL)");
}

// 2. Pastikan hanya admin yang bisa akses
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di proses_edit_alat.php: Fungsi require_admin() tidak ditemukan. Periksa pemuatan helpers.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia. (Error Code: ADM_AUTH_NF)");
}
require_admin();

// 3. Pastikan SewaAlatController dan metode yang dibutuhkan ada
if (!class_exists('SewaAlatController')) {
    error_log("FATAL ERROR di proses_edit_alat.php: Kelas SewaAlatController tidak ditemukan setelah config.php dimuat.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen utama untuk alat sewa tidak dapat dimuat (SAC_NF).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}
if (!method_exists('SewaAlatController', 'handleUpdateAlat')) {
    error_log("FATAL ERROR di proses_edit_alat.php: Metode SewaAlatController::handleUpdateAlat() tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi update alat sewa tidak tersedia (SAC_MTH_NF).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}
if (!defined('SewaAlat::ALLOWED_DURATION_UNITS') || !defined('SewaAlat::ALLOWED_CONDITIONS')) {
    error_log("FATAL ERROR di proses_edit_alat.php: Konstanta dari Model SewaAlat tidak terdefinisi.");
    set_flash_message('danger', 'Kesalahan sistem: Konfigurasi data alat sewa tidak lengkap (SAM_CONST_NF).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

$redirect_url_kelola = ADMIN_URL . 'alat_sewa/kelola_alat.php';

if (!is_post() || !isset($_POST['submit_edit_alat'])) {
    set_flash_message('danger', 'Akses tidak sah atau form tidak dikirim dengan benar.');
    redirect($redirect_url_kelola);
    exit;
}

$id_alat = input('id_alat', 0, 'POST');
$id_alat = filter_var($id_alat, FILTER_VALIDATE_INT);

if (!$id_alat || $id_alat <= 0) {
    set_flash_message('danger', 'ID Alat Sewa tidak valid atau tidak ditemukan untuk pembaruan.');
    redirect($redirect_url_kelola);
    exit;
}
$redirect_url_form_edit = ADMIN_URL . 'alat_sewa/edit_alat.php?id=' . $id_alat;

if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true, 'POST')) {
    set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan coba lagi dari form.');
    error_log("Proses Edit Alat Sewa - Kegagalan Verifikasi CSRF untuk ID alat: " . $id_alat);
    redirect($redirect_url_form_edit);
    exit;
}

$nama_item = input('nama_item', '', 'POST');
$kategori_alat = input('kategori_alat', null, 'POST');
$deskripsi = input('deskripsi', null, 'POST');
$harga_sewa_input = input('harga_sewa', '', 'POST');
$durasi_harga_sewa_input = input('durasi_harga_sewa', '', 'POST');
$satuan_durasi_harga = input('satuan_durasi_harga', '', 'POST');
$stok_tersedia_input = input('stok_tersedia', '', 'POST');
$kondisi_alat = input('kondisi_alat', '', 'POST');
$gambar_lama_db = input('gambar_lama_db', null, 'POST');
$gambar_action = input('gambar_action', 'keep', 'POST');

$session_form_data_key = 'flash_form_data_edit_alat_sewa_' . $id_alat;
$_SESSION[$session_form_data_key] = $_POST;

// 9. Validasi Input Dasar Server-Side (Konsisten dengan Controller)
$errors = [];
if (empty($nama_item)) {
    $errors[] = "Nama item alat wajib diisi.";
}
// Perbaikan Validasi: Harga, Durasi, Stok minimal 1
if ($harga_sewa_input === '' || !is_numeric($harga_sewa_input) || (float)$harga_sewa_input < 1) {
    $errors[] = "Harga sewa wajib diisi (minimal Rp 1).";
}
if ($satuan_durasi_harga !== 'Peminjaman' && ($durasi_harga_sewa_input === '' || !is_numeric($durasi_harga_sewa_input) || (int)$durasi_harga_sewa_input < 1)) {
    $errors[] = "Durasi harga sewa wajib diisi dengan angka positif minimal 1 (kecuali untuk satuan 'Peminjaman').";
}
if (empty($satuan_durasi_harga) || !in_array($satuan_durasi_harga, SewaAlat::ALLOWED_DURATION_UNITS)) {
    $errors[] = "Satuan durasi harga tidak valid.";
}
if ($stok_tersedia_input === '' || !is_numeric($stok_tersedia_input) || (int)$stok_tersedia_input < 1) {
    $errors[] = "Stok tersedia wajib diisi (minimal 1 unit).";
}
if (empty($kondisi_alat) || !in_array($kondisi_alat, SewaAlat::ALLOWED_CONDITIONS)) {
    $errors[] = "Kondisi alat tidak valid.";
}

if (!empty($errors)) {
    set_flash_message('danger', implode("<br>", array_map('e', $errors)));
    redirect($redirect_url_form_edit);
    exit;
}

$file_data_baru_untuk_controller = null;
if ($gambar_action === 'change') {
    if (isset($_FILES['gambar_alat_baru']) && is_array($_FILES['gambar_alat_baru']) && $_FILES['gambar_alat_baru']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_data_baru_untuk_controller = $_FILES['gambar_alat_baru'];
    } elseif (!isset($_FILES['gambar_alat_baru']) || empty($_FILES['gambar_alat_baru']['name'])) {
        set_flash_message('danger', "Anda memilih untuk mengganti gambar, tetapi tidak ada file baru yang dipilih.");
        redirect($redirect_url_form_edit);
        exit;
    }
}

$data_to_controller = [
    'id' => $id_alat,
    'nama_item' => $nama_item,
    'kategori_alat' => $kategori_alat,
    'deskripsi' => $deskripsi,
    'harga_sewa' => $harga_sewa_input,
    'durasi_harga_sewa' => $durasi_harga_sewa_input,
    'satuan_durasi_harga' => $satuan_durasi_harga,
    'stok_tersedia' => $stok_tersedia_input,
    'kondisi_alat' => $kondisi_alat,
    'gambar_action' => $gambar_action,
    'gambar_lama_db' => $gambar_lama_db,
];

$update_result = SewaAlatController::handleUpdateAlat($data_to_controller, $file_data_baru_untuk_controller);

if ($update_result === true) {
    unset($_SESSION[$session_form_data_key]);
    set_flash_message('success', 'Data alat sewa "' . e($nama_item) . '" berhasil diperbarui.');
    redirect($redirect_url_kelola);
    exit;
} else {
    if (!isset($_SESSION['flash_message'])) {
        $error_message_display_from_script = 'Gagal memperbarui data alat sewa. ';
        if (is_string($update_result)) {
            switch ($update_result) {
                case 'missing_nama':
                    $error_message_display_from_script .= 'Nama item wajib diisi.';
                    break;
                case 'invalid_harga_min_1':
                    $error_message_display_from_script .= 'Harga sewa minimal Rp 1.';
                    break; // Disesuaikan
                case 'invalid_durasi_min_1':
                    $error_message_display_from_script .= 'Durasi harga sewa minimal 1.';
                    break; // Disesuaikan
                case 'invalid_stok_min_1':
                    $error_message_display_from_script .= 'Stok tersedia minimal 1 unit.';
                    break; // Disesuaikan
                case 'invalid_satuan_durasi':
                    $error_message_display_from_script .= 'Satuan durasi harga tidak valid.';
                    break;
                case 'invalid_kondisi':
                    $error_message_display_from_script .= 'Kondisi alat tidak valid.';
                    break;
                case 'upload_failed':
                    $error_message_display_from_script .= 'Terjadi masalah saat mengunggah gambar baru.';
                    break;
                case 'db_update_failed':
                    $error_message_display_from_script .= 'Gagal menyimpan perubahan ke database.';
                    break;
                case 'invalid_id':
                    $error_message_display_from_script .= 'ID alat tidak valid.';
                    break;
                case 'item_not_found':
                    $error_message_display_from_script .= 'Data alat tidak ditemukan.';
                    break;
                case 'system_error_model_unavailable':
                    $error_message_display_from_script = 'Kesalahan sistem: Komponen data tidak tersedia.';
                    break;
                default:
                    $error_message_display_from_script .= 'Terjadi kesalahan yang tidak diketahui (' . e($update_result) . ').';
                    break;
            }
        } else {
            $error_message_display_from_script .= 'Silakan periksa kembali data yang Anda masukkan atau hubungi administrator.';
        }
        set_flash_message('danger', $error_message_display_from_script);
    }
    redirect($redirect_url_form_edit);
    exit;
}
