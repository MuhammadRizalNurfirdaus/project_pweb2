<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\edit_artikel.php

// 1. Sertakan konfigurasi dan controller
// Path dari admin/artikel/ ke config/ adalah ../../config/
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/edit_artikel.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}
// Path dari admin/artikel/ ke controllers/ adalah ../../controllers/
if (!@require_once __DIR__ . '/../../controllers/ArtikelController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat ArtikelController.php dari admin/artikel/edit_artikel.php");
    exit("Terjadi kesalahan kritis pada server (controller tidak termuat). Mohon coba lagi nanti.");
}

// 2. Sertakan header admin
// Path yang BENAR dari admin/artikel/edit_artikel.php ke template/header_admin.php adalah '../../template/header_admin.php'
$page_title = "Edit Artikel"; // Set judul halaman sebelum include header
if (!@include_once __DIR__ . '/../../template/header_admin.php') { // PERBAIKAN PATH DI SINI
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/artikel/edit_artikel.php. Path yang dicoba: " . __DIR__ . '/../../template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file ('../../template/header_admin.php').</div>";
    exit;
}

// Inisialisasi variabel pesan dan data artikel
$error_message = '';
$success_message = ''; // Tidak digunakan langsung di sini, tapi bisa untuk masa depan
$artikel = null;
$current_gambar_filename = ''; // Hanya nama file gambar saat ini

// Validasi ID artikel dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', 'ID Artikel tidak valid.');
    redirect('admin/artikel/kelola_artikel.php'); // Redirect menggunakan helper
}

$id = (int)$_GET['id'];
$artikel_data = ArtikelController::getById($id); // Ambil data artikel sebagai array

if (!$artikel_data) {
    set_flash_message('danger', 'Artikel tidak ditemukan.');
    redirect('admin/artikel/kelola_artikel.php');
}

// Set variabel form dari data artikel yang ada
$judul_form = $artikel_data['judul'];
$isi_form = $artikel_data['isi'];
$current_gambar_filename = $artikel_data['gambar'];


// Proses form jika ada POST request
if (is_post()) { // Menggunakan helper is_post()
    $judul_form = trim(input('judul')); // Menggunakan helper input()
    $isi_form = trim(input('isi'));
    $gambar_new_name_to_save = null;
    $gambar_action = input('gambar_action', 'keep'); // Default 'keep'

    // Validasi input dasar
    if (empty($judul_form) || empty($isi_form)) {
        $error_message = "Judul dan Isi artikel wajib diisi.";
    } else {
        // Logika penanganan upload gambar baru atau penghapusan gambar
        if ($gambar_action === 'remove') {
            $gambar_new_name_to_save = "REMOVE"; // Signal ke controller untuk menghapus gambar
        } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {

            $target_dir_upload = __DIR__ . "/../../public/uploads/artikel/"; // Path absolut ke folder upload
            if (!is_dir($target_dir_upload)) {
                mkdir($target_dir_upload, 0775, true); // Buat direktori jika belum ada
            }

            $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
            $gambar_new_name_to_save = "artikel_" . uniqid() . '.' . $imageFileType; // Nama file unik
            $target_file_upload = $target_dir_upload . $gambar_new_name_to_save;
            $uploadOk = 1;

            // Validasi file gambar
            $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
            if ($check === false) {
                $error_message = "File baru yang diunggah bukan gambar.";
                $uploadOk = 0;
            }
            if ($_FILES["gambar_baru"]["size"] > 2000000) { // Maks 2MB
                $error_message = "Ukuran file baru terlalu besar (maksimal 2MB).";
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($imageFileType, $allowed_formats)) {
                $error_message = "Format file baru tidak diizinkan (hanya JPG, PNG, JPEG, GIF).";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                    $error_message = "Gagal mengunggah file gambar baru. Pastikan folder uploads/artikel/ writable.";
                    error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                    $gambar_new_name_to_save = null; // Batalkan jika gagal upload
                }
            } else {
                $gambar_new_name_to_save = null; // Batalkan jika validasi gagal
            }
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_OK) {
            // Ada error lain saat upload, selain tidak ada file yang diupload
            $error_message = "Terjadi kesalahan saat mengunggah file baru. Kode Error: " . $_FILES['gambar_baru']['error'];
        }

        // Jika tidak ada error validasi atau upload sejauh ini
        if (empty($error_message)) {
            // Panggil ArtikelController::update
            // Parameter terakhir adalah nama file gambar lama, untuk dihapus oleh controller jika gambar baru diupload atau gambar dihapus
            if (ArtikelController::update($id, $judul_form, $isi_form, $gambar_new_name_to_save, $current_gambar_filename)) {
                set_flash_message('success', 'Artikel berhasil diperbarui!');
                redirect('admin/artikel/kelola_artikel.php');
            } else {
                $error_message = "Gagal memperbarui artikel di database.";
                // Jika update DB gagal TAPI gambar baru sudah terlanjur diupload, hapus gambar baru tersebut
                if ($gambar_new_name_to_save && $gambar_new_name_to_save !== "REMOVE" && isset($target_file_upload) && file_exists($target_file_upload)) {
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
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/artikel/kelola_artikel.php"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Artikel</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Artikel: <?= e($artikel_data['judul']) ?></h5>
        <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Artikel
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><?= e($error_message) ?></div>
        <?php endif; ?>

        <form action="<?= e($base_url . 'admin/artikel/edit_artikel.php?id=' . $id) ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= e($judul_form) ?>" required>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= e($isi_form) ?></textarea>
                <!-- Pertimbangkan untuk menggunakan WYSIWYG Editor seperti TinyMCE atau CKEditor di sini -->
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Gambar Artikel</label>
                <?php if (!empty($current_gambar_filename)): ?>
                    <div class="mb-2">
                        <p class="mb-1 small text-muted">Gambar Saat Ini:</p>
                        <img src="<?= $base_url ?>public/uploads/artikel/<?= e($current_gambar_filename) ?>" alt="Gambar Saat Ini: <?= e($artikel_data['judul']) ?>" class="img-thumbnail mb-2" style="max-width: 250px; max-height: 200px; object-fit: cover;">
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-2">Tidak ada gambar saat ini untuk artikel ini.</p>
                <?php endif; ?>

                <label for="gambar_action" class="form-label">Tindakan untuk Gambar:</label>
                <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUpload(this.value)" style="max-width: 300px;">
                    <option value="keep" selected>Pertahankan Gambar Saat Ini</option>
                    <option value="remove" <?= (empty($current_gambar_filename)) ? 'disabled' : '' ?>>Hapus Gambar Saat Ini</option>
                    <option value="change">Ganti dengan Gambar Baru</option>
                </select>

                <div id="new-image-upload-section" style="display:none;" class="mt-3 p-3 border rounded bg-light">
                    <label for="gambar_baru" class="form-label">Pilih Gambar Baru:</label>
                    <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif">
                    <small class="form-text text-muted">Format yang diizinkan: JPG, PNG, JPEG, GIF. Ukuran maksimal: 2MB.</small>
                </div>
            </div>

            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
                <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleNewImageUpload(action) {
        const newImageSection = document.getElementById('new-image-upload-section');
        const gambarBaruInput = document.getElementById('gambar_baru');
        if (action === 'change') {
            newImageSection.style.display = 'block';
            gambarBaruInput.required = true; // Jadikan wajib jika memilih ganti gambar
        } else {
            newImageSection.style.display = 'none';
            gambarBaruInput.required = false; // Tidak wajib jika tidak ganti
            gambarBaruInput.value = ''; // Bersihkan pilihan file jika disembunyikan
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
// Path yang BENAR dari admin/artikel/edit_artikel.php ke template/footer_admin.php adalah '../../template/footer_admin.php'
if (!@include_once __DIR__ . '/../../template/footer_admin.php') { // PERBAIKAN PATH DI SINI
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/artikel/edit_artikel.php. Path yang dicoba: " . __DIR__ . '/../../template/footer_admin.php');
}
?>