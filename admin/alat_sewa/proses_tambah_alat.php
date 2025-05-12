<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\proses_tambah_alat.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/SewaAlatController.php'; // Menggunakan SewaAlatController

// require_admin(); // Pastikan admin sudah login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_item = input('nama_item');
    $kategori_alat = input('kategori_alat'); // Opsional
    $deskripsi = input('deskripsi');         // Opsional
    $harga_sewa = input('harga_sewa');
    $durasi_harga_sewa = input('durasi_harga_sewa');
    $satuan_durasi_harga = input('satuan_durasi_harga');
    $stok_tersedia = input('stok_tersedia');
    $kondisi_alat = input('kondisi_alat');

    // Data untuk dikirim ke controller (tanpa gambar dulu)
    $data_alat = [
        'nama_item' => $nama_item,
        'kategori_alat' => $kategori_alat,
        'deskripsi' => $deskripsi,
        'harga_sewa' => $harga_sewa, // Controller akan validasi dan konversi ke int
        'durasi_harga_sewa' => $durasi_harga_sewa, // Controller akan validasi dan konversi ke int
        'satuan_durasi_harga' => $satuan_durasi_harga,
        'stok_tersedia' => $stok_tersedia, // Controller akan validasi dan konversi ke int
        'kondisi_alat' => $kondisi_alat
    ];

    // Simpan input ke session untuk repopulasi jika ada error redirect dari Controller/Model
    $_SESSION['form_data_alat_sewa'] = $data_alat;


    // --- Handle File Upload untuk gambar_alat (Opsional) ---
    $gambar_final_name_to_save_in_db = null;
    $uploadOk = 1; // Anggap upload OK kecuali ada masalah

    if (isset($_FILES['gambar_alat']) && $_FILES['gambar_alat']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_alat']['name'])) {
        // Pastikan path upload konsisten dengan yang ada di Model SewaAlat.php
        $target_dir_upload_absolute = defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH . '/' : __DIR__ . "/../../public/uploads/alat_sewa/";

        // Buat direktori jika belum ada (seharusnya sudah ditangani di config.php, tapi cek lagi tidak masalah)
        if (!is_dir($target_dir_upload_absolute)) {
            if (!@mkdir($target_dir_upload_absolute, 0775, true) && !is_dir($target_dir_upload_absolute)) {
                set_flash_message('danger', "Gagal membuat direktori unggah untuk gambar alat: " . e($target_dir_upload_absolute));
                error_log("FATAL: Gagal membuat direktori unggah di proses_tambah_alat.php: " . $target_dir_upload_absolute);
                $uploadOk = 0; // Gagalkan proses jika direktori tidak bisa dibuat
            }
        }

        if ($uploadOk && is_dir($target_dir_upload_absolute) && is_writable($target_dir_upload_absolute)) {
            $imageFileType = strtolower(pathinfo($_FILES["gambar_alat"]["name"], PATHINFO_EXTENSION));
            $gambar_final_name_to_save_in_db = "alat_" . uniqid() . '_' . time() . '.' . $imageFileType;
            $target_file_upload_absolute = $target_dir_upload_absolute . $gambar_final_name_to_save_in_db;

            $check = @getimagesize($_FILES["gambar_alat"]["tmp_name"]);
            if ($check === false) {
                set_flash_message('danger', 'File yang diunggah untuk gambar alat bukan format gambar yang valid.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar_alat"]["size"] > 2000000) { // Batas 2MB untuk gambar alat
                set_flash_message('danger', 'Ukuran file gambar alat terlalu besar (maksimal 2MB).');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
            if (!in_array($imageFileType, $allowed_formats)) {
                set_flash_message('danger', 'Format file gambar alat tidak diizinkan (JPG, PNG, JPEG, GIF, WEBP).');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar_alat"]["tmp_name"], $target_file_upload_absolute)) {
                    set_flash_message('danger', 'Gagal memindahkan file gambar alat yang diunggah.');
                    error_log("ERROR: Gagal move_uploaded_file (gambar_alat) ke: " . $target_file_upload_absolute . ". Error PHP: " . ($_FILES['gambar_alat']['error'] ?? 'Tidak ada info'));
                    $uploadOk = 0;
                }
            }
        } elseif ($uploadOk) { // Jika direktori tidak writable atau tidak ada, dan uploadOk masih 1
            set_flash_message('danger', 'Direktori unggah gambar alat tidak dapat ditulis atau tidak ditemukan.');
            error_log("ERROR: Direktori unggah gambar alat tidak writable/ditemukan: " . $target_dir_upload_absolute);
            $uploadOk = 0;
        }

        if ($uploadOk == 0) { // Jika ada error upload, set nama file gambar ke null
            $gambar_final_name_to_save_in_db = null;
        }
    } // Tidak ada error jika file gambar tidak diunggah (opsional)

    // Lanjutkan hanya jika tidak ada error fatal dari pembuatan direktori
    // Validasi teks utama sudah dilakukan oleh Controller
    // Jika uploadOk = 0 karena error validasi gambar, pesan flash sudah di-set, Controller akan return false

    // Panggil SewaAlatController untuk membuat data baru
    // Parameter kedua adalah nama file gambar yang sudah diproses (bisa null jika tidak ada upload atau error)
    $new_alat_id = SewaAlatController::create($data_alat, $gambar_final_name_to_save_in_db);

    if ($new_alat_id) {
        unset($_SESSION['form_data_alat_sewa']); // Hapus data form dari session jika berhasil
        set_flash_message('success', 'Alat sewa "' . e($nama_item) . '" berhasil ditambahkan dengan ID: ' . $new_alat_id . '.');
        redirect('admin/alat_sewa/kelola_alat.php');
    } else {
        // Pesan error spesifik seharusnya sudah di-set oleh Controller jika validasi gagal,
        // atau jika Model gagal menyimpan.
        // Jika tidak ada flash message (error dari Model tanpa set flash di Controller), set pesan umum.
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menambahkan alat sewa. Silakan periksa kembali data yang Anda masukkan atau log server.');
        }
        // Jika upload file berhasil tapi simpan ke DB gagal, hapus file yang terlanjur diupload
        if ($uploadOk == 1 && !empty($gambar_final_name_to_save_in_db) && !empty($target_file_upload_absolute) && file_exists($target_file_upload_absolute)) {
            @unlink($target_file_upload_absolute);
            error_log("INFO: Rollback upload file (gambar_alat) di proses_tambah_alat.php karena gagal insert DB: " . $target_file_upload_absolute);
        }
        redirect('admin/alat_sewa/tambah_alat.php'); // Kembali ke form tambah
    }
} else {
    // Jika bukan POST, redirect
    set_flash_message('danger', 'Akses tidak sah.');
    redirect('admin/alat_sewa/tambah_alat.php');
}
