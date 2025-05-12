<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\proses_edit_alat.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/SewaAlatController.php'; // Menggunakan SewaAlatController

// require_admin(); // Pastikan admin sudah login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi ID dari POST
    if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT) || (int)$_POST['id'] <= 0) {
        set_flash_message('danger', 'ID Alat Sewa tidak valid untuk pembaruan.');
        // Redirect ke kelola jika ID tidak ada atau tidak valid
        redirect('admin/alat_sewa/kelola_alat.php');
    }
    $id_alat = (int)$_POST['id'];

    // Ambil data dari form
    $nama_item = input('nama_item');
    $kategori_alat = input('kategori_alat');
    $deskripsi = input('deskripsi');
    $harga_sewa = input('harga_sewa');
    $durasi_harga_sewa = input('durasi_harga_sewa');
    $satuan_durasi_harga = input('satuan_durasi_harga');
    $stok_tersedia = input('stok_tersedia');
    $kondisi_alat = input('kondisi_alat');
    $gambar_lama = input('gambar_lama'); // Nama file gambar lama
    $gambar_action = input('gambar_action', 'keep'); // keep, remove, change

    // Data untuk dikirim ke controller (tanpa gambar dulu)
    $data_alat_update = [
        'id' => $id_alat,
        'nama_item' => $nama_item,
        'kategori_alat' => $kategori_alat,
        'deskripsi' => $deskripsi,
        'harga_sewa' => $harga_sewa,
        'durasi_harga_sewa' => $durasi_harga_sewa,
        'satuan_durasi_harga' => $satuan_durasi_harga,
        'stok_tersedia' => $stok_tersedia,
        'kondisi_alat' => $kondisi_alat
    ];

    // Simpan input ke session untuk repopulasi jika ada error redirect dari Controller/Model
    // (termasuk ID agar form edit bisa di-load kembali dengan benar)
    $_SESSION['form_data_alat_sewa_edit'] = $data_alat_update;


    // --- Handle File Upload untuk gambar_alat_baru (jika aksi 'change') ---
    $gambar_baru_final_name = null; // Nama file baru yang akan disimpan jika berhasil upload
    $uploadOk = 1; // Anggap upload OK kecuali ada masalah

    if ($gambar_action === 'change') {
        if (isset($_FILES['gambar_alat_baru']) && $_FILES['gambar_alat_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_alat_baru']['name'])) {
            $target_dir_upload_absolute = defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH . '/' : __DIR__ . "/../../public/uploads/alat_sewa/";

            // Pastikan direktori ada (seharusnya sudah ditangani di config.php)
            if (!is_dir($target_dir_upload_absolute)) {
                if (!@mkdir($target_dir_upload_absolute, 0775, true) && !is_dir($target_dir_upload_absolute)) {
                    set_flash_message('danger', "Gagal membuat direktori unggah untuk gambar alat: " . e($target_dir_upload_absolute));
                    $uploadOk = 0;
                }
            }

            if ($uploadOk && is_dir($target_dir_upload_absolute) && is_writable($target_dir_upload_absolute)) {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_alat_baru"]["name"], PATHINFO_EXTENSION));
                $gambar_baru_final_name = "alat_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload_absolute = $target_dir_upload_absolute . $gambar_baru_final_name;

                $check = @getimagesize($_FILES["gambar_alat_baru"]["tmp_name"]);
                if ($check === false) {
                    set_flash_message('danger', 'File baru yang diunggah bukan format gambar yang valid.');
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_alat_baru"]["size"] > 2000000) { // Maks 2MB
                    set_flash_message('danger', 'Ukuran file gambar baru terlalu besar (maksimal 2MB).');
                    $uploadOk = 0;
                }
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    set_flash_message('danger', 'Format file gambar baru tidak diizinkan (JPG, PNG, JPEG, GIF, WEBP).');
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    if (!move_uploaded_file($_FILES["gambar_alat_baru"]["tmp_name"], $target_file_upload_absolute)) {
                        set_flash_message('danger', 'Gagal memindahkan file gambar baru yang diunggah.');
                        error_log("ERROR: Gagal move_uploaded_file (gambar_alat_baru) ke: " . $target_file_upload_absolute . ". Error PHP: " . ($_FILES['gambar_alat_baru']['error'] ?? 'Tidak ada info'));
                        $uploadOk = 0;
                    }
                }
            } elseif ($uploadOk) {
                set_flash_message('danger', 'Direktori unggah gambar alat tidak dapat ditulis atau tidak ditemukan.');
                $uploadOk = 0;
            }

            if ($uploadOk == 0) {
                $gambar_baru_final_name = null; // Jangan gunakan nama file jika upload gagal
            }
        } else { // Jika aksi 'change' tapi tidak ada file baru atau ada error upload awal
            set_flash_message('warning', 'Anda memilih untuk mengganti gambar, tetapi tidak ada file gambar baru yang valid diunggah. Gambar tidak akan diubah.');
            $gambar_action = 'keep'; // Anggap seperti 'keep' jika upload gagal
            $uploadOk = 1; // Set uploadOk kembali ke 1 agar proses update data teks tetap jalan
        }
    } // Akhir dari if ($gambar_action === 'change')

    // Tentukan nama file gambar yang akan dikirim ke Controller berdasarkan aksi
    $nama_gambar_untuk_controller = null;
    if ($gambar_action === 'keep') {
        $nama_gambar_untuk_controller = null; // Kirim null jika tidak ada perubahan pada gambar
    } elseif ($gambar_action === 'remove') {
        $nama_gambar_untuk_controller = "REMOVE_IMAGE";
    } elseif ($gambar_action === 'change' && $uploadOk == 1 && !empty($gambar_baru_final_name)) {
        $nama_gambar_untuk_controller = $gambar_baru_final_name;
    }
    // Jika aksi 'change' tapi uploadOk = 0, $nama_gambar_untuk_controller akan tetap null (seperti 'keep')

    // Lanjutkan ke Controller untuk update data teks dan mungkin gambar
    // Validasi teks utama akan dilakukan oleh Controller
    if (SewaAlatController::update($data_alat_update, $nama_gambar_untuk_controller, $gambar_lama)) {
        unset($_SESSION['form_data_alat_sewa_edit']);
        set_flash_message('success', 'Data alat sewa "' . e($nama_item) . '" berhasil diperbarui.');
        redirect('admin/alat_sewa/kelola_alat.php');
    } else {
        // Pesan error seharusnya sudah di-set oleh Controller atau Model.
        // Jika tidak ada (misalnya error upload sudah set pesan dan update DB tidak dijalankan),
        // atau jika Controller gagal validasi dan set pesan.
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal memperbarui data alat sewa. Silakan periksa kembali data yang Anda masukkan.');
        }
        // Jika gambar baru sudah terlanjur diunggah tapi update DB gagal, hapus gambar baru tersebut
        if ($gambar_action === 'change' && $uploadOk == 1 && !empty($gambar_baru_final_name) && !empty($target_file_upload_absolute) && file_exists($target_file_upload_absolute)) {
            // Ini hanya terjadi jika Controller::update mengembalikan false SETELAH gambar diproses di sini
            @unlink($target_file_upload_absolute);
            error_log("INFO: Rollback upload file (gambar_alat_baru) di proses_edit_alat.php karena gagal update DB: " . $target_file_upload_absolute);
        }
        redirect('admin/alat_sewa/edit_alat.php?id=' . $id_alat); // Kembali ke form edit
    }
} else {
    set_flash_message('danger', 'Akses tidak sah.');
    redirect('admin/alat_sewa/kelola_alat.php');
}
