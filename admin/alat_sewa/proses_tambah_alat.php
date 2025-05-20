<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\proses_tambah_alat.php

require_once __DIR__ . '/../../config/config.php';

// Pastikan admin sudah login dan fungsi ini tersedia dari auth_helpers.php
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di proses_tambah_alat.php: Fungsi require_admin() tidak ditemukan.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia.");
}
require_admin();

// Pastikan Controller ada (config.php seharusnya sudah memuatnya)
if (!class_exists('SewaAlatController')) {
    error_log("FATAL ERROR di proses_tambah_alat.php: Kelas SewaAlatController tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen utama tidak dapat dimuat.');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}
if (!method_exists('SewaAlatController', 'handleCreateAlat')) {
    error_log("FATAL ERROR di proses_tambah_alat.php: Metode SewaAlatController::handleCreateAlat() tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi tambah alat sewa tidak tersedia.');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF Token
    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true, 'POST')) {
        set_flash_message('danger', 'Permintaan tidak valid atau token keamanan salah/kadaluarsa. Silakan coba lagi dari form.');
        error_log("Proses Tambah Alat Sewa - Kegagalan Verifikasi CSRF.");
        redirect(ADMIN_URL . 'alat_sewa/tambah_alat.php');
        exit;
    }

    // Ambil data dari form menggunakan fungsi input()
    $data_form_input = [
        'nama_item' => input('nama_item'),
        'kategori_alat' => input('kategori_alat'),
        'deskripsi' => input('deskripsi'),
        'harga_sewa' => input('harga_sewa'),
        'durasi_harga_sewa' => input('durasi_harga_sewa'),
        'satuan_durasi_harga' => input('satuan_durasi_harga'),
        'stok_tersedia' => input('stok_tersedia'),
        'kondisi_alat' => input('kondisi_alat')
    ];

    // Simpan input ke session untuk repopulasi jika ada error
    $_SESSION['form_data_alat_sewa'] = $data_form_input;

    // Ambil data file gambar
    $file_data_gambar = null;
    if (isset($_FILES['gambar_alat']) && is_array($_FILES['gambar_alat']) && $_FILES['gambar_alat']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_data_gambar = $_FILES['gambar_alat'];
    }

    // Panggil SewaAlatController untuk membuat data baru
    // Controller akan melakukan validasi input yang lebih mendalam dan penanganan file.
    $result = SewaAlatController::handleCreateAlat($data_form_input, $file_data_gambar);

    if (is_int($result) && $result > 0) {
        unset($_SESSION['form_data_alat_sewa']);
        set_flash_message('success', 'Alat sewa "' . e($data_form_input['nama_item']) . '" berhasil ditambahkan dengan ID: ' . $result . '.');
        redirect(ADMIN_URL . 'alat_sewa/kelola_alat.php');
        exit; // Pastikan exit setelah redirect
    } else {
        // Gagal. $result kemungkinan adalah string kode error dari Controller.
        // Controller diharapkan sudah mengatur flash message.
        // Jika tidak, set pesan umum.
        if (!isset($_SESSION['flash_message'])) {
            $error_message_display = 'Gagal menambahkan alat sewa. ';
            if (is_string($result)) {
                switch ($result) {
                    case 'missing_nama':
                        $error_message_display .= 'Nama item wajib diisi.';
                        break;
                    case 'invalid_harga_min_1':
                        $error_message_display .= 'Harga sewa minimal Rp 1.';
                        break;
                    case 'invalid_durasi_min_1':
                        $error_message_display .= 'Durasi harga sewa minimal 1 (kecuali untuk Peminjaman).';
                        break;
                    case 'invalid_stok_min_1':
                        $error_message_display .= 'Stok tersedia minimal 1 unit.';
                        break;
                    case 'invalid_satuan_durasi':
                        $error_message_display .= 'Satuan durasi harga tidak valid.';
                        break;
                    case 'invalid_kondisi':
                        $error_message_display .= 'Kondisi alat tidak valid.';
                        break;
                    case 'upload_failed':
                        $error_message_display .= 'Terjadi masalah saat mengunggah gambar.';
                        break;
                    case 'db_create_failed':
                        $error_message_display .= 'Gagal menyimpan data ke database.';
                        break;
                    case 'system_error_model_unavailable':
                        $error_message_display = 'Kesalahan sistem: Komponen data tidak tersedia.';
                        break;
                    default:
                        $error_message_display .= 'Terjadi kesalahan yang tidak diketahui (' . e($result) . ').';
                        break;
                }
            } else {
                $error_message_display .= 'Silakan periksa kembali data yang Anda masukkan atau hubungi administrator.';
            }
            set_flash_message('danger', $error_message_display);
        }
        // Controller sudah menangani rollback upload file jika insert DB gagal.
        redirect(ADMIN_URL . 'alat_sewa/tambah_alat.php');
        exit; // Pastikan exit setelah redirect
    }
} else {
    // Jika bukan metode POST, redirect
    set_flash_message('danger', 'Akses tidak sah ke halaman proses.');
    redirect(ADMIN_URL . 'alat_sewa/tambah_alat.php');
    exit; // Pastikan exit setelah redirect
}
