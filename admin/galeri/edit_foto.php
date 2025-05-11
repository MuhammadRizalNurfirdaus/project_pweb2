<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\edit_foto.php

// 1. Sertakan konfigurasi dan Controller/Model yang relevan
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php");
    exit("Server Error");
}
// Anda memerlukan GaleriController atau Model Galeri di sini
// Pastikan class dan method yang dipanggil sudah ada dan berfungsi.
// Contoh jika menggunakan GaleriController:
if (!@require_once __DIR__ . '/../../controllers/GaleriController.php') { // ATAU models/Galeri.php
    http_response_code(500);
    error_log("FATAL: Gagal memuat GaleriController.php");
    exit("Server Error (Controller)");
}

// 2. Sertakan header admin
$page_title = "Edit Foto Galeri";
if (!@include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php");
    exit("Server Error (Header)");
}

$error_message = '';
$foto_galeri_data = null;
$keterangan_form = '';
$current_nama_file_db = ''; // Nama file yang tersimpan di DB
$id_foto = null;

// Validasi dan ambil ID foto dari URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_foto = (int)$_GET['id'];

    // Ambil data foto dari database berdasarkan $id_foto
    // GANTI INI DENGAN PEMANGGILAN METHOD MODEL/CONTROLLER ANDA YANG SEBENARNYA
    if (class_exists('GaleriController') && method_exists('GaleriController', 'getById')) { // Atau cek Model Galeri
        $foto_galeri_data = GaleriController::getById($id_foto);
    } else {
        // Data dummy jika controller/model belum siap sepenuhnya (HAPUS INI DI PRODUKSI)
        if ($id_foto == 11) {
            $foto_galeri_data = ['id' => 11, 'nama_file' => 'lembah_cilengkrang.jpg', 'keterangan' => 'Pemandangan Lembah Cilengkrang dari atas.'];
        } elseif ($id_foto == 13) {
            $foto_galeri_data = ['id' => 13, 'nama_file' => 'puncak.jpg', 'keterangan' => 'Menikmati sunrise di Puncak Cilengkrang.'];
        }
        // error_log("Peringatan: GaleriController::getById() tidak ditemukan, menggunakan data dummy.");
    }


    if ($foto_galeri_data) {
        $keterangan_form = $foto_galeri_data['keterangan'];
        $current_nama_file_db = $foto_galeri_data['nama_file'];
    } else {
        set_flash_message('danger', 'Foto dengan ID tersebut tidak ditemukan.');
        redirect('admin/galeri/kelola_galeri.php');
    }
} else {
    set_flash_message('danger', 'ID foto tidak valid atau tidak disediakan.');
    redirect('admin/galeri/kelola_galeri.php');
}

// Proses form jika ada POST request
if (is_post() && $id_foto) {
    $keterangan_form_update = trim(input('keterangan'));
    $gambar_action = input('gambar_action', 'keep');
    $new_image_filename_to_save = null; // Akan diisi jika ada file baru yang valid
    $old_image_to_delete_on_success = null; // Akan diisi jika gambar lama perlu dihapus

    if (empty($keterangan_form_update) && $gambar_action === 'keep' && !isset($_FILES['gambar_baru']['name']) || (isset($_FILES['gambar_baru']['name']) && empty($_FILES['gambar_baru']['name']))) {
        $error_message = "Setidaknya ubah keterangan atau pilih tindakan untuk gambar.";
    } else {

        // Logika penanganan upload gambar baru atau penghapusan gambar
        if ($gambar_action === 'remove' && !empty($current_nama_file_db)) {
            $new_image_filename_to_save = "REMOVE_IMAGE"; // Signal khusus untuk menghapus
            $old_image_to_delete_on_success = $current_nama_file_db;
        } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {

            $target_dir_upload = __DIR__ . "/../../public/img/"; // Folder untuk gambar galeri
            if (!is_dir($target_dir_upload)) {
                if (!mkdir($target_dir_upload, 0775, true) && !is_dir($target_dir_upload)) {
                    $error_message = "Gagal membuat direktori unggah: " . $target_dir_upload;
                }
            }

            if (empty($error_message)) { // Lanjutkan jika direktori OK
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                $new_image_filename_to_save = "galeri_" . uniqid() . '.' . $imageFileType;
                $target_file_upload = $target_dir_upload . $new_image_filename_to_save;
                $uploadOk = 1;

                $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
                if ($check === false) {
                    $error_message = "File baru yang diunggah bukan gambar.";
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_baru"]["size"] > 5000000) { // Maks 5MB
                    $error_message = "Ukuran file baru terlalu besar (maksimal 5MB).";
                    $uploadOk = 0;
                }
                $allowed_formats = ["jpg", "png", "jpeg", "gif"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    $error_message = "Format file baru tidak diizinkan (hanya JPG, PNG, JPEG, GIF).";
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    if (move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                        // File baru berhasil diunggah
                        if (!empty($current_nama_file_db)) {
                            $old_image_to_delete_on_success = $current_nama_file_db; // Tandai file lama untuk dihapus setelah DB update
                        }
                    } else {
                        $error_message = "Gagal memindahkan file gambar baru.";
                        $new_image_filename_to_save = null;
                    }
                } else {
                    $new_image_filename_to_save = null;
                }
            }
        } elseif ($gambar_action === 'change' && (!isset($_FILES['gambar_baru']) || $_FILES['gambar_baru']['error'] == UPLOAD_ERR_NO_FILE || empty($_FILES['gambar_baru']['name']))) {
            $error_message = "Anda memilih untuk mengganti gambar, tetapi tidak ada file baru yang diunggah.";
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message = "Terjadi kesalahan saat mengunggah file baru. Kode Error: " . $_FILES['gambar_baru']['error'];
        }

        // Jika tidak ada error validasi atau upload sejauh ini
        if (empty($error_message)) {
            // Tentukan nama file akhir untuk disimpan ke DB
            $final_filename_for_db = $current_nama_file_db; // Defaultnya adalah gambar lama
            if ($new_image_filename_to_save === "REMOVE_IMAGE") {
                $final_filename_for_db = null; // Hapus nama file dari DB
            } elseif ($new_image_filename_to_save !== null) {
                $final_filename_for_db = $new_image_filename_to_save; // Gunakan nama file baru
            }

            // Panggil method update dari GaleriController atau Model Galeri Anda
            // GANTI INI DENGAN PEMANGGILAN METHOD ANDA YANG SEBENARNYA
            // Contoh jika menggunakan GaleriController::update($id, $keterangan, $nama_file_untuk_db, $nama_file_lama_untuk_dihapus_fisik)
            $update_success = false;
            if (class_exists('GaleriController') && method_exists('GaleriController', 'update')) {
                $update_success = GaleriController::update($id_foto, $keterangan_form_update, $final_filename_for_db, $old_image_to_delete_on_success);
            } else {
                // Simulasi sukses jika controller/model belum ada (HAPUS INI DI PRODUKSI)
                $update_success = true;
                error_log("Peringatan: GaleriController::update() tidak ditemukan, update disimulasikan.");
                if ($old_image_to_delete_on_success && $old_image_to_delete_on_success !== $final_filename_for_db) {
                    $path_to_delete = __DIR__ . "/../../public/img/" . $old_image_to_delete_on_success;
                    if (file_exists($path_to_delete)) @unlink($path_to_delete);
                    error_log("Simulasi: Menghapus file lama: " . $path_to_delete);
                }
            }

            if ($update_success) {
                set_flash_message('success', 'Foto galeri berhasil diperbarui!');
                redirect('admin/galeri/kelola_galeri.php');
            } else {
                $error_message = "Gagal memperbarui foto galeri di database.";
                // Jika update DB gagal TAPI gambar baru sudah terlanjur diupload, hapus gambar baru tersebut
                if ($new_image_filename_to_save && $new_image_filename_to_save !== "REMOVE_IMAGE" && isset($target_file_upload) && file_exists($target_file_upload)) {
                    @unlink($target_file_upload);
                    error_log("Rollback: Menghapus gambar baru yang gagal diupdate ke DB: " . $target_file_upload);
                }
            }
        }
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/galeri/kelola_galeri.php"><i class="fas fa-images"></i> Kelola Galeri</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Foto</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Foto Galeri: <?= e($foto_galeri_data['keterangan'] ?: 'Tanpa Keterangan') ?></h5>
        <a href="<?= $base_url ?>admin/galeri/kelola_galeri.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><?= e($error_message) ?></div>
        <?php endif; ?>
        <?php
        if (function_exists('display_flash_message')) {
            echo display_flash_message();
        }
        ?>

        <?php if ($id_foto && $foto_galeri_data): ?>
            <form action="<?= e($base_url . 'admin/galeri/edit_foto.php?id=' . $id_foto) ?>" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">Foto Saat Ini:</label>
                    <div>
                        <?php if (!empty($current_nama_file_db) && file_exists(__DIR__ . '/../../public/img/' . $current_nama_file_db)): ?>
                            <img src="<?= $base_url ?>public/img/<?= e($current_nama_file_db) ?>"
                                alt="Foto saat ini: <?= e($keterangan_form) ?>"
                                class="img-thumbnail mb-2"
                                style="max-width: 300px; max-height: 250px; object-fit: cover; border: 1px solid var(--admin-border-color);">
                        <?php elseif (!empty($current_nama_file_db)): ?>
                            <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> File gambar "<?= e($current_nama_file_db) ?>" tidak ditemukan di server.</p>
                            <p class="text-muted small">Pastikan file gambar ada di folder `public/img/`.</p>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada gambar untuk item galeri ini.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan Foto</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= e($keterangan_form) ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="gambar_action" class="form-label fw-bold">Tindakan untuk Gambar:</label>
                    <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUpload(this.value)" style="max-width: 300px;">
                        <option value="keep" selected>Pertahankan Gambar Saat Ini</option>
                        <option value="change">Ganti dengan Gambar Baru</option>
                        <?php if (!empty($current_nama_file_db)): // Hanya tampilkan opsi hapus jika ada gambar 
                        ?>
                            <option value="remove">Hapus Gambar Saat Ini</option>
                        <?php endif; ?>
                    </select>

                    <div id="new-image-upload-section" style="display:none;" class="mt-3 p-3 border rounded bg-light">
                        <label for="gambar_baru" class="form-label">Pilih File Gambar Baru:</label>
                        <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif">
                        <small class="form-text text-muted">Format: JPG, PNG, JPEG, GIF. Ukuran maks: 5MB.</small>
                    </div>
                </div>

                <hr>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                    <a href="<?= $base_url ?>admin/galeri/kelola_galeri.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Batal
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Data foto tidak dapat dimuat atau ID tidak valid.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleNewImageUpload(action) {
        const newImageSection = document.getElementById('new-image-upload-section');
        const gambarBaruInput = document.getElementById('gambar_baru');
        if (action === 'change') {
            newImageSection.style.display = 'block';
            gambarBaruInput.required = true;
        } else {
            newImageSection.style.display = 'none';
            gambarBaruInput.required = false;
            gambarBaruInput.value = '';
        }
    }
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        if (gambarActionSelect) {
            toggleNewImageUpload(gambarActionSelect.value);
        }
    });
</script>

<?php
// 3. Sertakan footer admin
if (!@include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/galeri/edit_foto.php.");
}
?>