<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pengaturan\proses_pengaturan.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_pengaturan.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Pastikan Controller dan Model ada
if (!class_exists('PengaturanController') || !method_exists('PengaturanController', 'updatePengaturanSitus')) {
    error_log("FATAL ERROR di proses_pengaturan.php: PengaturanController atau metode updatePengaturanSitus tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen pengaturan tidak dapat diproses.');
    redirect(ADMIN_URL . 'pengaturan/umum.php');
    exit;
}
if (!class_exists('PengaturanSitus') || !method_exists('PengaturanSitus', 'getUploadDir')) { // Untuk path upload
    error_log("FATAL ERROR di proses_pengaturan.php: Model PengaturanSitus atau getUploadDir tidak ada.");
    // ... (error handling)
}


// 4. Hanya proses jika metode POST dan tombol submit ditekan
if (!is_post() || !isset($_POST['submit_pengaturan_umum'])) {
    set_flash_message('danger', 'Akses tidak sah atau form tidak dikirim dengan benar.');
    redirect(ADMIN_URL . 'pengaturan/umum.php');
    exit;
}

// 5. Validasi CSRF Token
if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token_pengaturan', true, 'POST')) {
    set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan coba lagi.');
    error_log("Proses Pengaturan Umum - Kegagalan Verifikasi CSRF.");
    redirect(ADMIN_URL . 'pengaturan/umum.php');
    exit;
}

// 6. Ambil data dari form
$data_form = $_POST; // Controller akan melakukan trim dan validasi lebih lanjut

// 7. Ambil data file jika ada
$file_logo_data = (isset($_FILES['logo_situs_file']) && $_FILES['logo_situs_file']['error'] != UPLOAD_ERR_NO_FILE) ? $_FILES['logo_situs_file'] : null;
$file_favicon_data = (isset($_FILES['favicon_situs_file']) && $_FILES['favicon_situs_file']['error'] != UPLOAD_ERR_NO_FILE) ? $_FILES['favicon_situs_file'] : null;

// 8. Simpan data ke session untuk repopulasi jika ada error dari Controller
$_SESSION['flash_form_data_pengaturan_umum'] = $data_form;


// 9. Panggil Controller untuk update pengaturan
$update_result = PengaturanController::updatePengaturanSitus($data_form, $file_logo_data, $file_favicon_data);

if ($update_result === true) {
    unset($_SESSION['flash_form_data_pengaturan_umum']);
    set_flash_message('success', 'Pengaturan umum situs berhasil diperbarui.');
    error_log("ADMIN ACTION: Pengaturan umum situs diperbarui oleh Admin ID: " . get_current_user_id());
} else {
    // Controller seharusnya sudah set flash message jika ada error spesifik (kode error string)
    if (!isset($_SESSION['flash_message'])) {
        $error_message_display = 'Gagal memperbarui pengaturan umum. ';
        if (is_string($update_result)) {
            switch ($update_result) {
                case 'missing_nama_situs':
                    $error_message_display .= 'Nama situs wajib diisi.';
                    break;
                case 'invalid_email_kontak':
                    $error_message_display .= 'Format email kontak tidak valid.';
                    break;
                case 'upload_logo_failed':
                    $error_message_display .= 'Gagal mengunggah file logo baru.';
                    break;
                case 'invalid_logo_file':
                    $error_message_display .= 'File logo tidak valid (format atau ukuran).';
                    break;
                case 'upload_favicon_failed':
                    $error_message_display .= 'Gagal mengunggah file favicon baru.';
                    break;
                case 'invalid_favicon_file':
                    $error_message_display .= 'File favicon tidak valid (format atau ukuran).';
                    break;
                case 'db_update_failed':
                    $error_message_display .= 'Gagal menyimpan data ke database.';
                    break;
                case 'system_error_model_unavailable':
                    $error_message_display = 'Kesalahan sistem: Komponen data pengaturan tidak siap.';
                    break;
                default:
                    $error_message_display .= 'Terjadi kesalahan: ' . e($update_result);
                    break;
            }
        } else {
            $error_message_display .= 'Terjadi kesalahan internal.';
        }
        set_flash_message('danger', $error_message_display);
    }
}

redirect(ADMIN_URL . 'pengaturan/umum.php');
exit;
