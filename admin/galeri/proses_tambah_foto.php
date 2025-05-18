<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\proses_tambah_foto.php

// 1. Sertakan config.php pertama kali
// Ini akan memuat koneksi DB, helper, konstanta, dan model (termasuk Galeri.php dan init-nya)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/proses_tambah_foto.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses dan sesi sudah dimulai
require_admin(); // Fungsi ini dari auth_helpers.php via config.php

// 3. Pastikan Model Galeri sudah siap
if (!class_exists('Galeri') || !method_exists('Galeri', 'create')) {
    error_log("KRITIS proses_tambah_foto.php: Model Galeri atau metode create tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen untuk menambah foto tidak tersedia.');
    redirect(ADMIN_URL . 'galeri/tambah_foto.php');
    exit;
}

// Key untuk menyimpan data form di session jika ada error
$session_form_data_key = 'flash_form_data_tambah_foto';

if (is_post()) {
    // 4. Validasi CSRF Token
    if (!verify_csrf_token()) { // Asumsi verify_csrf_token() dari helpers.php
        set_flash_message('danger', 'Permintaan tidak valid: Token CSRF tidak cocok atau hilang.');
        redirect(ADMIN_URL . 'galeri/tambah_foto.php');
        exit;
    }

    $keterangan = input('keterangan', '', 'POST'); // Menggunakan helper input()
    $nama_file_final = null;
    $upload_error = false;
    $upload_path_target_file = '';

    // Simpan input ke session untuk repopulasi JIKA ada error di bawah
    $_SESSION[$session_form_data_key] = ['keterangan' => $keterangan];

    // 5. Penanganan File Upload yang Lebih Baik
    if (isset($_FILES['nama_file']) && $_FILES['nama_file']['error'] == UPLOAD_ERR_OK && !empty($_FILES['nama_file']['name'])) {
        if (!defined('UPLOADS_GALERI_PATH') || !is_writable(UPLOADS_GALERI_PATH)) {
            set_flash_message('danger', 'Konfigurasi direktori unggah galeri bermasalah atau tidak dapat ditulis.');
            error_log("Error Upload: UPLOADS_GALERI_PATH tidak terdefinisi atau tidak writable. Path: " . (defined('UPLOADS_GALERI_PATH') ? UPLOADS_GALERI_PATH : "Belum terdefinisi"));
            $upload_error = true;
        } else {
            $file_tmp_name = $_FILES['nama_file']['tmp_name'];
            $file_original_name = $_FILES['nama_file']['name'];
            $file_size = $_FILES['nama_file']['size'];
            $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));

            $allowed_extensions = ["jpg", "jpeg", "png", "gif", "webp"];

            if (!in_array($file_ext, $allowed_extensions)) {
                set_flash_message('danger', 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF, WEBP.');
                $upload_error = true;
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                set_flash_message('danger', 'Ukuran file terlalu besar. Maksimal 5MB.');
                $upload_error = true;
            } else {
                $check = @getimagesize($file_tmp_name);
                if ($check === false) {
                    set_flash_message('danger', 'File yang diunggah bukan format gambar yang valid.');
                    $upload_error = true;
                } else {
                    $nama_file_final = "galeri_" . uniqid() . '_' . time() . '.' . $file_ext;
                    $upload_path_target_file = rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nama_file_final;

                    if (!move_uploaded_file($file_tmp_name, $upload_path_target_file)) {
                        set_flash_message('danger', 'Gagal memindahkan file yang diunggah. Periksa izin folder.');
                        error_log("Gagal move_uploaded_file ke: " . $upload_path_target_file . " Error PHP: " . ($_FILES['nama_file']['error'] ?? 'Tidak ada info error'));
                        $nama_file_final = null; // Reset jika gagal
                        $upload_error = true;
                    }
                }
            }
        }
    } elseif (isset($_FILES['nama_file']) && $_FILES['nama_file']['error'] != UPLOAD_ERR_NO_FILE) {
        set_flash_message('danger', 'Terjadi kesalahan saat mengunggah gambar. Kode Error: ' . $_FILES['nama_file']['error']);
        $upload_error = true;
    } else { // Tidak ada file yang diunggah, ini wajib
        set_flash_message('danger', 'File gambar wajib diunggah.');
        $upload_error = true;
    }

    // 6. Validasi Input Lain dan Penyimpanan ke Database
    if (!$upload_error) { // Hanya lanjut jika tidak ada error upload
        if (empty($keterangan)) { // nama_file_final sudah divalidasi wajib ada oleh logika upload
            set_flash_message('danger', 'Keterangan foto wajib diisi.');
        } else {
            $data_to_save = [
                'nama_file' => $nama_file_final,
                'keterangan' => $keterangan
                // Kolom 'uploaded_at' akan diisi NOW() oleh Model Galeri::create()
            ];

            $new_galeri_id = Galeri::create($data_to_save);

            if ($new_galeri_id) {
                unset($_SESSION[$session_form_data_key]); // Hapus data form dari session jika sukses
                set_flash_message('success', 'Foto baru berhasil ditambahkan ke galeri!');
                redirect(ADMIN_URL . 'galeri/kelola_galeri.php'); // Redirect ke halaman kelola
                // exit sudah ada di redirect()
            } else {
                // Jika penyimpanan ke DB gagal, hapus file yang mungkin sudah terunggah
                if ($nama_file_final && !empty($upload_path_target_file) && file_exists($upload_path_target_file)) {
                    @unlink($upload_path_target_file);
                    error_log("Rollback Tambah Foto: Menghapus file {$upload_path_target_file} karena gagal simpan DB.");
                }
                $db_error = Galeri::getLastError();
                set_flash_message('danger', 'Gagal menyimpan data ke database. ' . ($db_error ?: ''));
            }
        }
    }

    // Jika ada pesan error (dari upload atau validasi lain), redirect kembali ke form tambah
    // Pesan flash sudah diset di atas
    redirect(ADMIN_URL . 'galeri/tambah_foto.php');
    exit;
} else {
    // Jika bukan metode POST, redirect ke halaman tambah (atau kelola)
    set_flash_message('warning', 'Akses tidak sah ke halaman proses.');
    redirect(ADMIN_URL . 'galeri/tambah_foto.php');
    exit;
}
