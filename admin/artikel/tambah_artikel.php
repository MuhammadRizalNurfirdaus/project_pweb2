<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\tambah_artikel.php

// 1. Include Config and Model
// Path dari admin/artikel/ ke config/ adalah ../../config/
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/tambah_artikel.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}
// Path dari admin/artikel/ ke models/ adalah ../../models/
if (!@require_once __DIR__ . '/../../models/Artikel.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat models/Artikel.php dari admin/artikel/tambah_artikel.php");
    exit("Terjadi kesalahan kritis pada server (model tidak termuat). Mohon coba lagi nanti.");
}

// 2. Sertakan header admin
// Path yang BENAR dari admin/artikel/tambah_artikel.php ke template/header_admin.php adalah '../../template/header_admin.php'
$page_title = "Tambah Artikel Baru"; // Set judul halaman sebelum include header
if (!@include_once __DIR__ . '/../../template/header_admin.php') { // PERBAIKAN PATH
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/artikel/tambah_artikel.php. Path yang dicoba: " . __DIR__ . '/../../template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file ('../../template/header_admin.php').</div>";
    exit;
}

// Initialize variables for form pre-filling and error messages
$judul_input_val = ''; // Gunakan nama variabel berbeda untuk value di form
$isi_input_val = '';

// Repopulate form from session if validation failed on previous attempt and redirected
if (isset($_SESSION['flash_form_data_artikel'])) { // Gunakan key session yang unik
    $judul_input_val = e($_SESSION['flash_form_data_artikel']['judul'] ?? '');
    $isi_input_val = e($_SESSION['flash_form_data_artikel']['isi'] ?? '');
    unset($_SESSION['flash_form_data_artikel']); // Clear after use
}

// 3. Process POST Request (if form submitted)
if (is_post()) {
    $judul_from_post = input('judul');
    $isi_from_post = input('isi');
    $gambar_filename = null;

    // Store input in session for repopulation in case of redirect
    $_SESSION['flash_form_data_artikel'] = [
        'judul' => $judul_from_post,
        'isi' => $isi_from_post,
    ];

    // --- Handle File Upload for 'gambar' ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $target_dir_upload = __DIR__ . "/../../public/uploads/artikel/";
        if (!is_dir($target_dir_upload)) {
            if (!mkdir($target_dir_upload, 0775, true) && !is_dir($target_dir_upload)) {
                set_flash_message('danger', 'Gagal membuat direktori unggah: ' . $target_dir_upload . '. Periksa izin folder.');
                redirect('admin/artikel/tambah_artikel.php'); // Redirect agar flash message tampil
            }
        }

        if (is_dir($target_dir_upload) && is_writable($target_dir_upload)) {
            $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
            $gambar_filename = "artikel_" . uniqid() . '.' . $imageFileType;
            $target_file_upload = $target_dir_upload . $gambar_filename;
            $uploadOk = 1;

            $check = @getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check === false) {
                set_flash_message('danger', 'File yang diunggah bukan format gambar yang valid.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar"]["size"] > 2000000) {
                set_flash_message('danger', 'Ukuran file terlalu besar (maksimal 2MB).');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($imageFileType, $allowed_formats)) {
                set_flash_message('danger', 'Format file tidak diizinkan (hanya JPG, JPEG, PNG & GIF).');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file_upload)) {
                    set_flash_message('danger', 'Maaf, terjadi error saat mengunggah file Anda. Pastikan folder writable.');
                    error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                    $gambar_filename = null;
                }
            } else {
                $gambar_filename = null;
            }
        } else {
            set_flash_message('danger', 'Direktori unggah tidak dapat ditulis atau tidak ada: ' . $target_dir_upload);
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] != UPLOAD_ERR_OK) {
        set_flash_message('danger', 'Terjadi kesalahan saat mengunggah gambar. Kode Error: ' . $_FILES['gambar']['error']);
    }
    // --- End File Upload Handling ---


    // Validate other inputs (only proceed if no critical upload error has set a flash message already)
    // Cek flash message berdasarkan tipenya, karena mungkin ada pesan sukses dari operasi lain
    $has_upload_error = false;
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'danger') {
        $has_upload_error = true;
    }

    if (!$has_upload_error) {
        if (empty($judul_from_post) || empty($isi_from_post)) {
            set_flash_message('danger', 'Judul dan Isi artikel wajib diisi.');
        } else {
            if (isset($conn) && $conn && class_exists('Artikel')) {
                $artikel_model = new Artikel($conn);

                $artikel_model->judul = $judul_from_post;
                $artikel_model->isi = $isi_from_post;
                $artikel_model->gambar = $gambar_filename;

                if ($artikel_model->create()) { // Asumsi method create() ada di model Artikel
                    unset($_SESSION['flash_form_data_artikel']); // Hapus data form dari session jika berhasil
                    set_flash_message('success', 'Artikel berhasil ditambahkan!');
                    redirect('admin/artikel/kelola_artikel.php');
                } else {
                    set_flash_message('danger', 'Gagal menambahkan artikel ke database.');
                    if ($gambar_filename && isset($target_file_upload) && file_exists($target_file_upload)) {
                        @unlink($target_file_upload);
                        error_log("Rollback: Menghapus file gambar yang gagal disimpan ke DB: " . $target_file_upload);
                    }
                }
            } else {
                set_flash_message('danger', 'Koneksi database atau Model Artikel tidak tersedia.');
                error_log("Koneksi DB atau Model Artikel tidak ada saat tambah artikel.");
            }
        }
    }

    // Jika ada error validasi dan tidak redirect di atas, redirect ke halaman yang sama
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'danger') {
        redirect('admin/artikel/tambah_artikel.php');
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/artikel/kelola_artikel.php"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Artikel</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-plus-square me-2"></i>Tambah Artikel Baru</h5>
        <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Artikel
        </a>
    </div>
    <div class="card-body">
        <?php
        // Display Flash Message (jika belum ada di header_admin.php, atau jika ingin spesifik di sini)
        if (function_exists('display_flash_message')) {
            echo display_flash_message();
        }
        ?>
        <p class="text-muted">Isi semua field yang ditandai dengan <span class="text-danger">*</span>.</p>
        <form action="<?= e($base_url . 'admin/artikel/tambah_artikel.php') ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= $judul_input_val ?>" required>
                <div class="invalid-feedback">Judul artikel wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= $isi_input_val ?></textarea>
                <div class="invalid-feedback">Isi artikel wajib diisi.</div>
                <!-- Pertimbangkan untuk menggunakan WYSIWYG Editor seperti TinyMCE atau CKEditor di sini -->
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Artikel <span class="text-muted">(Opsional)</span></label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/png, image/jpeg, image/gif">
                <small class="form-text text-muted">Format yang diizinkan: JPG, PNG, GIF. Ukuran maksimal: 2MB.</small>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Simpan Artikel
                </button>
                <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// 5. Include Admin Footer
// Path yang BENAR dari admin/artikel/tambah_artikel.php ke template/footer_admin.php adalah '../../template/footer_admin.php'
if (!@include_once __DIR__ . '/../../template/footer_admin.php') { // PERBAIKAN PATH
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/artikel/tambah_artikel.php. Path yang dicoba: " . __DIR__ . '/../../template/footer_admin.php');
}
?>