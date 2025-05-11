<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\edit_wisata.php

require_once __DIR__ . '/../../config/config.php'; // Diperbaiki
require_once __DIR__ . '/../../controllers/WisataController.php'; // Diperbaiki
// require_admin(); // Pastikan hanya admin yang bisa akses

$page_title = "Edit Destinasi Wisata";

// Inisialisasi variabel
$wisata_data = null;
$error_message = '';
// $success_message = ''; // Tidak digunakan secara langsung saat ini

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', 'ID Destinasi Wisata tidak valid.');
    redirect('admin/wisata/kelola_wisata.php');
}
$id = (int)$_GET['id'];

$wisata_data = WisataController::getById($id);

if (!$wisata_data) {
    set_flash_message('danger', 'Data destinasi wisata tidak ditemukan.');
    redirect('admin/wisata/kelola_wisata.php');
}

// Model getById() sudah mengalias 'nama' dari DB menjadi 'nama_wisata' di array PHP
$nama_wisata_form = $wisata_data['nama_wisata'] ?? '';
$deskripsi_form = $wisata_data['deskripsi'] ?? '';
$lokasi_form = $wisata_data['lokasi'] ?? '';
$current_gambar_filename = $wisata_data['gambar'] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_wisata_form = input('nama_wisata');
    $deskripsi_form = input('deskripsi');
    $lokasi_form = input('lokasi');
    $gambar_action = input('gambar_action', 'keep');
    $gambar_new_name_to_save = null;

    if (empty($nama_wisata_form) || empty($deskripsi_form)) {
        $error_message = "Nama Wisata dan Deskripsi wajib diisi.";
    } else {
        $upload_dir = __DIR__ . "/../../public/uploads/wisata/"; // Diperbaiki
        if (!is_dir($upload_dir)) {
            // Mencoba membuat direktori jika belum ada
            if (!mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) { // Tambahkan pengecekan kedua setelah mkdir
                $error_message = "Gagal membuat direktori unggah: " . $upload_dir;
                error_log("Gagal membuat direktori unggah: " . $upload_dir);
            }
        }

        // Lanjutkan hanya jika tidak ada error pembuatan direktori
        if (empty($error_message)) {
            if ($gambar_action === 'remove') {
                $gambar_new_name_to_save = "REMOVE_IMAGE";
            } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                $gambar_new_name_to_save = "wisata_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload = $upload_dir . $gambar_new_name_to_save;
                $uploadOk = 1;

                $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
                if ($check === false) {
                    $error_message = "File baru yang diunggah bukan gambar.";
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_baru"]["size"] > 5000000) { // Maks 5MB
                    $error_message = "Ukuran file gambar baru terlalu besar (maksimal 5MB).";
                    $uploadOk = 0;
                }
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    $error_message = "Format file gambar baru tidak diizinkan (JPG, PNG, JPEG, GIF, WEBP).";
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    if (!move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                        $error_message = "Gagal mengunggah file gambar baru. Pastikan folder 'public/uploads/wisata/' writable.";
                        error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                        $gambar_new_name_to_save = null;
                    }
                } else {
                    $gambar_new_name_to_save = null;
                }
            } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_OK) {
                $error_message = "Terjadi kesalahan saat mengunggah file gambar baru. Kode Error: " . $_FILES['gambar_baru']['error'];
            }
        }


        if (empty($error_message)) {
            $data_to_update = [
                'id' => $id,
                'nama_wisata' => $nama_wisata_form,
                'deskripsi' => $deskripsi_form,
                'lokasi' => $lokasi_form
            ];

            if (WisataController::update($data_to_update, $gambar_new_name_to_save, $current_gambar_filename)) {
                set_flash_message('success', 'Data destinasi wisata berhasil diperbarui!');
                redirect('admin/wisata/kelola_wisata.php');
            } else {
                $error_message = "Gagal memperbarui data destinasi wisata di database.";
                // Jika update DB gagal TAPI gambar baru sudah terlanjur diupload, hapus gambar baru tersebut
                if ($gambar_new_name_to_save && $gambar_new_name_to_save !== "REMOVE_IMAGE" && isset($target_file_upload) && file_exists($target_file_upload)) {
                    @unlink($target_file_upload);
                    error_log("Rollback edit_wisata: Menghapus gambar baru yang gagal diupdate ke DB: " . $target_file_upload);
                }
            }
        }
    }
}

include_once __DIR__ . '/../../template/header_admin.php'; // Diperbaiki
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/wisata/kelola_wisata.php"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Destinasi: <?= e($wisata_data['nama_wisata'] ?? 'Tidak Diketahui') ?></li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Destinasi Wisata</h5>
        <a href="<?= $base_url ?>admin/wisata/kelola_wisata.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Destinasi
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><?= e($error_message) ?></div>
        <?php endif; ?>
        <?php if (function_exists('display_flash_message')) {
            echo display_flash_message();
        } ?>

        <form action="<?= $base_url ?>admin/wisata/edit_wisata.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="nama_wisata" class="form-label">Nama Destinasi</label>
                        <input type="text" class="form-control form-control-lg" id="nama_wisata" name="nama_wisata" value="<?= e($nama_wisata_form) ?>" required>
                        <div class="invalid-feedback">Nama destinasi wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="10" required><?= e($deskripsi_form) ?></textarea>
                        <div class="invalid-feedback">Deskripsi wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi/Alamat</label>
                        <textarea class="form-control" id="lokasi" name="lokasi" rows="4"><?= e($lokasi_form) ?></textarea>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Gambar Destinasi</div>
                        <div class="card-body text-center">
                            <?php if (!empty($current_gambar_filename) && file_exists(__DIR__ . "/../../public/uploads/wisata/" . $current_gambar_filename)): // Diperbaiki 
                            ?>
                                <img src="<?= $base_url ?>public/uploads/wisata/<?= e($current_gambar_filename) ?>?t=<?= time() ?>" alt="Gambar Saat Ini: <?= e($nama_wisata_form) ?>" class="img-fluid img-thumbnail mb-2" style="max-height: 200px; object-fit: cover;">
                                <p class="small text-muted mb-1">Saat Ini: <?= e($current_gambar_filename) ?></p>
                            <?php else: ?>
                                <i class="fas fa-image fa-5x text-muted mb-2"></i>
                                <p class="text-muted">Belum ada gambar.</p>
                            <?php endif; ?>

                            <label for="gambar_action" class="form-label mt-2">Tindakan untuk Gambar:</label>
                            <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUploadWisata(this.value)">
                                <option value="keep" selected>Pertahankan Gambar</option>
                                <?php if (!empty($current_gambar_filename)): ?>
                                    <option value="remove">Hapus Gambar Saat Ini</option>
                                <?php endif; ?>
                                <option value="change">Ganti dengan Gambar Baru</option>
                            </select>

                            <div id="new-image-upload-section-wisata" style="display:none;" class="mt-2">
                                <label for="gambar_baru" class="form-label">Pilih Gambar Baru:</label>
                                <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif, image/webp">
                                <small class="form-text text-muted">Format: JPG, PNG, GIF, WEBP. Maks 5MB.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <a href="<?= $base_url ?>admin/wisata/kelola_wisata.php" class="btn btn-secondary me-2">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
include_once __DIR__ . '/../../template/footer_admin.php'; // Diperbaiki
?>

<script>
    function toggleNewImageUploadWisata(action) {
        const newImageSection = document.getElementById('new-image-upload-section-wisata');
        const gambarBaruInput = document.getElementById('gambar_baru');
        if (action === 'change') {
            newImageSection.style.display = 'block';
        } else {
            newImageSection.style.display = 'none';
            gambarBaruInput.value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        if (gambarActionSelect) {
            toggleNewImageUploadWisata(gambarActionSelect.value);
        }
    });

    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>