<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\proses_tambah_wisata.php

// 1. Sertakan konfigurasi utama
// Path dari admin/wisata/proses_tambah_wisata.php ke config/ adalah ../../config/
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/wisata/proses_tambah_wisata.php");
    exit("Terjadi kesalahan kritis pada server.");
}

if (is_post()) {
    $nama = trim(input('nama'));
    $deskripsi = trim(input('deskripsi'));
    $lokasi = trim(input('lokasi')); // Sekarang wajib

    // Simpan input ke session untuk repopulasi jika ada error dan redirect
    $_SESSION['flash_form_data_wisata'] = [
        'nama' => $nama,
        'deskripsi' => $deskripsi,
        'lokasi' => $lokasi
    ];

    $gambar_final_name = null;
    $uploadOk = 1;
    $target_file_upload = '';

    // Validasi input wajib dari sisi server (selain gambar yang divalidasi di bawah)
    if (empty($nama) || empty($deskripsi) || empty($lokasi)) {
        set_flash_message('danger', 'Semua field (Nama, Deskripsi, Lokasi) wajib diisi.');
        redirect('admin/wisata/tambah_wisata.php');
    }

    // --- Handle File Upload (SEKARANG WAJIB) ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $target_dir_upload = __DIR__ . "/../../public/img/"; // Pastikan folder ini ada dan writable
        if (!is_dir($target_dir_upload)) {
            if (!mkdir($target_dir_upload, 0775, true) && !is_dir($target_dir_upload)) {
                set_flash_message('danger', "Gagal membuat direktori unggah: " . $target_dir_upload . ". Periksa izin folder.");
                redirect('admin/wisata/tambah_wisata.php');
            }
        }

        if (is_dir($target_dir_upload) && is_writable($target_dir_upload)) {
            $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
            $gambar_final_name = "wisata_" . uniqid() . '.' . $imageFileType;
            $target_file_upload = $target_dir_upload . $gambar_final_name;

            $check = @getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check === false) {
                set_flash_message('danger', 'File yang diunggah bukan format gambar yang valid.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar"]["size"] > 5000000) { // Maks 5MB
                set_flash_message('danger', 'Ukuran file terlalu besar (maksimal 5MB).');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($imageFileType, $allowed_formats)) {
                set_flash_message('danger', 'Format file tidak diizinkan (hanya JPG, PNG, JPEG, GIF).');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file_upload)) {
                    set_flash_message('danger', 'Gagal memindahkan file gambar yang diunggah. Pastikan folder writable.');
                    error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                    $uploadOk = 0; // Tandai gagal upload
                }
            }
        } else {
            set_flash_message('danger', 'Direktori unggah tidak dapat ditulis.');
            $uploadOk = 0;
        }
    } else { // Tidak ada file yang diunggah atau ada error upload awal
        set_flash_message('danger', 'Gambar utama destinasi wajib diunggah. Error: ' . ($_FILES['gambar']['error'] ?? 'Tidak ada file dipilih'));
        $uploadOk = 0;
    }
    // --- End File Upload Handling ---


    if ($uploadOk == 1 && !empty($gambar_final_name)) { // Hanya lanjut jika upload berhasil dan nama file ada
        // Kolom 'harga' sudah dihapus dari tabel dan query
        $sql = "INSERT INTO wisata (nama, deskripsi, gambar, lokasi) VALUES (?, ?, ?, ?)";
        // Asumsi kolom 'created_at' akan otomatis diisi oleh database (DEFAULT CURRENT_TIMESTAMP)

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $deskripsi, $gambar_final_name, $lokasi);

            if (mysqli_stmt_execute($stmt)) {
                unset($_SESSION['flash_form_data_wisata']);
                set_flash_message('success', 'Destinasi wisata berhasil ditambahkan!');
                redirect('admin/wisata/kelola_wisata.php');
            } else {
                set_flash_message('danger', 'Gagal menambahkan data wisata ke database: ' . mysqli_stmt_error($stmt));
                // Hapus file yang sudah terlanjur diupload jika insert DB gagal
                if (!empty($target_file_upload) && file_exists($target_file_upload)) {
                    @unlink($target_file_upload);
                }
                redirect('admin/wisata/tambah_wisata.php');
            }
            mysqli_stmt_close($stmt);
        } else {
            set_flash_message('danger', 'Gagal mempersiapkan statement database: ' . mysqli_error($conn));
            if (!empty($target_file_upload) && file_exists($target_file_upload)) {
                @unlink($target_file_upload);
            }
            redirect('admin/wisata/tambah_wisata.php');
        }
    } else { // Jika $uploadOk = 0 (ada error upload file)
        // Flash message seharusnya sudah di-set di blok upload
        redirect('admin/wisata/tambah_wisata.php');
    }
} else {
    // Jika bukan POST, redirect ke halaman tambah
    redirect('admin/wisata/tambah_wisata.php');
}
