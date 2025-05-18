<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\proses_tambah_wisata.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/wisata/proses_tambah_wisata.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses dan sesi sudah dimulai
require_admin(); // Fungsi ini dari auth_helpers.php via config.php

// 3. Pastikan Controller dan Model Wisata sudah siap
if (!class_exists('WisataController') || !method_exists('WisataController', 'create')) {
    error_log("KRITIS proses_tambah_wisata.php: WisataController atau metode create tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen untuk menambah destinasi tidak tersedia.');
    redirect(ADMIN_URL . 'wisata/tambah_wisata.php');
    exit;
}
// Model Wisata diasumsikan sudah di-init oleh config.php

$redirect_url_form = ADMIN_URL . 'wisata/tambah_wisata.php';
$session_form_data_key = 'flash_form_data_tambah_wisata';

if (is_post() && isset($_POST['submit_tambah_wisata'])) { // Pastikan tombol submit punya name="submit_tambah_wisata"
    // 4. Validasi CSRF Token
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Permintaan tidak valid: Token CSRF tidak cocok atau hilang.');
        redirect($redirect_url_form);
        exit;
    }

    // Ambil data dari form menggunakan helper input()
    $nama_wisata_input = input('nama_wisata', '', 'POST'); // Input name di form harus 'nama_wisata'
    $deskripsi_input = input('deskripsi', '', 'POST');
    $lokasi_input = input('lokasi', '', 'POST');

    // Simpan input ke session untuk repopulasi jika ada error
    $_SESSION[$session_form_data_key] = [
        'nama_wisata' => $nama_wisata_input, // Simpan dengan key yang sama dengan input name
        'deskripsi' => $deskripsi_input,
        'lokasi' => $lokasi_input
    ];

    $gambar_final_name_to_save_in_db = null;
    $upload_error_message = null;
    $target_file_upload_absolute_path = '';

    // 5. Validasi Input Teks Dasar
    if (empty($nama_wisata_input) || empty($deskripsi_input) || empty($lokasi_input)) {
        set_flash_message('danger', 'Nama Destinasi, Deskripsi, dan Lokasi wajib diisi.');
        redirect($redirect_url_form);
        exit;
    }

    // 6. Handle File Upload (WAJIB untuk tambah wisata)
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        if (!defined('UPLOADS_WISATA_PATH') || !is_writable(UPLOADS_WISATA_PATH)) {
            $upload_error_message = 'Konfigurasi direktori unggah wisata bermasalah atau tidak dapat ditulis.';
            error_log("Error Upload Tambah Wisata: UPLOADS_WISATA_PATH tidak valid. Path: " . (defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH : "Belum terdefinisi"));
        } else {
            $target_dir_for_upload = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $file_tmp_name = $_FILES['gambar']['tmp_name'];
            $file_original_name = $_FILES['gambar']['name'];
            $file_size = $_FILES['gambar']['size'];
            $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));

            $allowed_extensions = ["jpg", "png", "jpeg", "gif", "webp"];

            if (!in_array($file_ext, $allowed_extensions)) {
                $upload_error_message = 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF, WEBP.';
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                $upload_error_message = 'Ukuran file terlalu besar. Maksimal 5MB.';
            } else {
                $check_image = @getimagesize($file_tmp_name);
                if ($check_image === false) {
                    $upload_error_message = 'File yang diunggah bukan format gambar yang valid.';
                } else {
                    $gambar_final_name_to_save_in_db = "wisata_" . uniqid() . '_' . time() . '.' . $file_ext;
                    $target_file_upload_absolute_path = $target_dir_for_upload . $gambar_final_name_to_save_in_db;

                    if (!move_uploaded_file($file_tmp_name, $target_file_upload_absolute_path)) {
                        $upload_error_message = 'Gagal memindahkan file gambar. Pastikan folder tujuan writable.';
                        error_log("ERROR: Gagal move_uploaded_file ke: " . $target_file_upload_absolute_path);
                        $gambar_final_name_to_save_in_db = null; // Reset jika gagal
                    }
                }
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE) {
        // Ada error lain saat upload selain tidak ada file
        $php_upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "File melebihi upload_max_filesize.",
            UPLOAD_ERR_FORM_SIZE  => "File melebihi MAX_FILE_SIZE.",
            UPLOAD_ERR_PARTIAL    => "File hanya terunggah sebagian.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Gagal menulis file ke disk.",
            UPLOAD_ERR_EXTENSION  => "Ekstensi PHP menghentikan unggahan.",
        ];
        $error_code_upload = $_FILES['gambar']['error'];
        $upload_error_message = $php_upload_errors[$error_code_upload] ?? "Terjadi kesalahan tidak diketahui saat unggah (Kode: {$error_code_upload}).";
    } else {
        // Tidak ada file yang diunggah (UPLOAD_ERR_NO_FILE atau tidak ada $_FILES['gambar'])
        $upload_error_message = 'Gambar utama untuk destinasi wajib diunggah.';
    }


    // 7. Penyimpanan ke Database jika tidak ada error sebelumnya
    if (empty($upload_error_message) && !empty($gambar_final_name_to_save_in_db)) {
        $data_to_create_in_db = [
            'nama' => $nama_wisata_input, // Model Wisata menggunakan kolom 'nama'
            'deskripsi' => $deskripsi_input,
            'lokasi' => $lokasi_input,
            'gambar' => $gambar_final_name_to_save_in_db
        ];

        // Panggil WisataController::create dengan data dan nama file gambar
        // Asumsi WisataController::create akan memanggil Model Wisata::create
        $new_wisata_id = WisataController::create($data_to_create_in_db, $gambar_final_name_to_save_in_db);

        if ($new_wisata_id && is_int($new_wisata_id)) {
            unset($_SESSION[$session_form_data_key]); // Hapus data form dari session jika sukses
            set_flash_message('success', 'Destinasi wisata "' . e($nama_wisata_input) . '" berhasil ditambahkan!');
            redirect(ADMIN_URL . 'wisata/kelola_wisata.php'); // Redirect ke halaman kelola
            exit;
        } else {
            // Jika penyimpanan ke DB gagal, hapus file yang mungkin sudah terunggah
            if (!empty($target_file_upload_absolute_path) && file_exists($target_file_upload_absolute_path)) {
                @unlink($target_file_upload_absolute_path);
                error_log("INFO: Rollback upload file di proses_tambah_wisata.php (karena gagal insert DB): " . $target_file_upload_absolute_path);
            }
            $db_error_msg = (class_exists('Wisata') && method_exists('Wisata', 'getLastError')) ? Wisata::getLastError() : 'Tidak diketahui.';
            $error_message_to_display = is_string($new_wisata_id) ? $new_wisata_id : ('Gagal menambahkan destinasi wisata ke database. ' . e($db_error_msg));
            set_flash_message('danger', $error_message_to_display);
        }
    } else {
        // Jika ada error upload, set flash message jika belum diset
        if (!isset($_SESSION['flash_message']) && !empty($upload_error_message)) {
            set_flash_message('danger', $upload_error_message);
        }
    }

    // Jika sampai di sini, berarti ada error, redirect kembali ke form tambah
    redirect($redirect_url_form);
    exit;
} else {
    // Jika bukan metode POST atau tombol submit tidak ditekan
    set_flash_message('warning', 'Akses tidak sah atau form tidak dikirim dengan benar.');
    redirect($redirect_url_form);
    exit;
}
