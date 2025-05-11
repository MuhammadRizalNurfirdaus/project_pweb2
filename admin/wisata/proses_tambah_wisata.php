<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\proses_tambah_wisata.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/WisataController.php';

// require_admin(); // Pastikan hanya admin yang bisa akses

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_wisata_dari_form_saat_ini = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $deskripsi_input_saat_ini = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $lokasi_input_saat_ini = isset($_POST['lokasi']) ? trim($_POST['lokasi']) : '';

    $_SESSION['flash_form_data_wisata'] = [
        'nama' => $nama_wisata_dari_form_saat_ini,
        'deskripsi' => $deskripsi_input_saat_ini,
        'lokasi' => $lokasi_input_saat_ini
    ];

    $gambar_final_name_to_save_in_db = null;
    $uploadOk = 1;
    $target_file_upload_absolute = '';

    if (empty($nama_wisata_dari_form_saat_ini) || empty($deskripsi_input_saat_ini) || empty($lokasi_input_saat_ini)) {
        // Untuk pesan error, e() tetap penting untuk keseluruhan pesan
        $nama_display_error = e($nama_wisata_dari_form_saat_ini); // Escape dulu untuk dimasukkan ke string pesan
        set_flash_message('danger', 'Semua field (Nama Destinasi: "' . $nama_display_error . '", Deskripsi, Lokasi) wajib diisi.');
        redirect('admin/wisata/tambah_wisata.php');
    }

    // --- Handle File Upload (WAJIB) ---
    // (Kode Handle File Upload tetap sama seperti versi terakhir yang sudah baik,
    //  pastikan ia menggunakan e($nama_wisata_dari_form_saat_ini) untuk pesan error terkait nama destinasi)
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $target_dir_upload_absolute = __DIR__ . "/../../public/uploads/wisata/";

        if (!is_dir($target_dir_upload_absolute)) {
            if (!@mkdir($target_dir_upload_absolute, 0775, true) && !is_dir($target_dir_upload_absolute)) {
                set_flash_message('danger', "Gagal membuat direktori unggah: " . e($target_dir_upload_absolute) . ". Periksa izin folder.");
                error_log("FATAL: Gagal membuat direktori unggah di proses_tambah_wisata.php: " . $target_dir_upload_absolute);
                redirect('admin/wisata/tambah_wisata.php');
            }
        }

        if (is_dir($target_dir_upload_absolute) && is_writable($target_dir_upload_absolute)) {
            $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
            $gambar_final_name_to_save_in_db = "wisata_" . uniqid() . '_' . time() . '.' . $imageFileType;
            $target_file_upload_absolute = $target_dir_upload_absolute . $gambar_final_name_to_save_in_db;

            $check = @getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check === false) {
                set_flash_message('danger', 'File yang diunggah untuk destinasi "' . e($nama_wisata_dari_form_saat_ini) . '" bukan format gambar yang valid.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar"]["size"] > 5000000) { // Maks 5MB
                set_flash_message('danger', 'Ukuran file untuk destinasi "' . e($nama_wisata_dari_form_saat_ini) . '" terlalu besar (maksimal 5MB).');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
            if (!in_array($imageFileType, $allowed_formats)) {
                set_flash_message('danger', 'Format file untuk destinasi "' . e($nama_wisata_dari_form_saat_ini) . '" tidak diizinkan (hanya JPG, PNG, JPEG, GIF, WEBP).');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file_upload_absolute)) {
                    set_flash_message('danger', 'Gagal memindahkan file gambar untuk destinasi "' . e($nama_wisata_dari_form_saat_ini) . '". Pastikan folder tujuan writable.');
                    error_log("ERROR: Gagal move_uploaded_file dari " . $_FILES["gambar"]["tmp_name"] . " ke: " . $target_file_upload_absolute . ". Error PHP: " . ($_FILES['gambar']['error'] ?? 'Tidak ada info error PHP'));
                    $uploadOk = 0;
                    $gambar_final_name_to_save_in_db = null;
                }
            } else {
                $gambar_final_name_to_save_in_db = null;
            }
        } else {
            set_flash_message('danger', 'Direktori unggah tidak dapat ditulis atau tidak ditemukan: ' . e($target_dir_upload_absolute));
            error_log("ERROR: Direktori unggah tidak dapat ditulis atau tidak ditemukan di proses_tambah_wisata.php: " . $target_dir_upload_absolute);
            $uploadOk = 0;
        }
    } else {
        $php_upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "File melebihi upload_max_filesize di php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "File melebihi MAX_FILE_SIZE di form HTML.",
            UPLOAD_ERR_PARTIAL    => "File hanya terunggah sebagian.",
            UPLOAD_ERR_NO_FILE    => "Tidak ada file yang diunggah.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder di server.",
            UPLOAD_ERR_CANT_WRITE => "Gagal menulis file ke disk di server.",
            UPLOAD_ERR_EXTENSION  => "Ekstensi PHP menghentikan unggahan file.",
        ];
        $error_code = $_FILES['gambar']['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message_upload = $php_upload_errors[$error_code] ?? "Terjadi kesalahan tidak diketahui saat unggah.";
        set_flash_message('danger', 'Gambar utama untuk destinasi "' . e($nama_wisata_dari_form_saat_ini) . '" wajib diunggah. Masalah: ' . $error_message_upload);
        $uploadOk = 0;
    }

    if ($uploadOk == 1 && !empty($gambar_final_name_to_save_in_db)) {
        $data_to_create = [
            'nama_wisata' => $nama_wisata_dari_form_saat_ini,
            'deskripsi' => $deskripsi_input_saat_ini,
            'lokasi' => $lokasi_input_saat_ini,
        ];

        $new_wisata_id = WisataController::create($data_to_create, $gambar_final_name_to_save_in_db);

        if ($new_wisata_id) {
            unset($_SESSION['flash_form_data_wisata']);

            // PERBAIKAN UNTUK TAMPILAN '&' DI PESAN FLASH SUKSES
            // Kita ingin nama tampil apa adanya di pesan, bukan dengan &
            // Nama mentah dari form adalah $nama_wisata_dari_form_saat_ini
            // Fungsi e() akan mengubah '&' menjadi '&'
            // Untuk pesan flash, kita bisa menampilkannya "mentah" atau melakukan decode setelah e()
            // Opsi 1: Tampilkan mentah (kurang aman jika nama bisa diinput sembarangan oleh user di front-end lain)
            // $nama_untuk_pesan = $nama_wisata_dari_form_saat_ini;
            // Opsi 2: Escape dulu, lalu decode khusus untuk & (lebih terkontrol)
            $nama_untuk_pesan = htmlspecialchars_decode(e($nama_wisata_dari_form_saat_ini), ENT_QUOTES);

            set_flash_message('success', 'Destinasi wisata "' . $nama_untuk_pesan . '" berhasil ditambahkan!');
            redirect('admin/wisata/kelola_wisata.php');
        } else {
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menambahkan destinasi wisata "' . e($nama_wisata_dari_form_saat_ini) . '" ke database. Silakan periksa log server.');
            }
            if (!empty($target_file_upload_absolute) && file_exists($target_file_upload_absolute)) {
                @unlink($target_file_upload_absolute);
                error_log("INFO: Rollback upload file di proses_tambah_wisata.php (karena gagal insert DB): " . $target_file_upload_absolute);
            }
            redirect('admin/wisata/tambah_wisata.php');
        }
    } else {
        if ($uploadOk == 0 && !isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Terjadi kesalahan pada proses unggah gambar untuk destinasi "' . e($nama_wisata_dari_form_saat_ini) . '" atau data tidak lengkap.');
        }
        redirect('admin/wisata/tambah_wisata.php');
    }
} else {
    set_flash_message('info', 'Akses tidak valid.');
    redirect('admin/wisata/tambah_wisata.php');
}
