<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\tambah_artikel.php

// 1. Include Config (memuat $conn, helpers termasuk is_post, input, display_flash_message, e, redirect)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/tambah_artikel.php");
    exit("Kesalahan konfigurasi server. Tidak dapat memuat file penting.");
}

// 2. Panggil fungsi otentikasi admin
require_admin();

// 3. Sertakan Model Artikel (yang seharusnya sekarang statis)
if (!require_once __DIR__ . '/../../models/Artikel.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat models/Artikel.php dari admin/artikel/tambah_artikel.php");
    set_flash_message('danger', 'Kesalahan sistem: Model Artikel tidak dapat dimuat.');
    redirect('admin/dashboard.php');
}

// 4. Set judul halaman dan sertakan header admin
$pageTitle = "Tambah Artikel Baru";
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/artikel/tambah_artikel.php.");
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px;'>Error Kritis: Gagal memuat komponen header halaman admin.</p>";
    // exit;
}

// Inisialisasi variabel untuk form pre-filling
$judul_input_val = '';
$isi_input_val = '';

if (isset($_SESSION['flash_form_data_artikel'])) {
    $judul_input_val = e($_SESSION['flash_form_data_artikel']['judul'] ?? '');
    $isi_input_val = e($_SESSION['flash_form_data_artikel']['isi'] ?? ''); // Jangan e() jika isi bisa HTML
    // Jika isi artikel Anda menggunakan editor WYSIWYG dan boleh HTML, JANGAN GUNAKAN e() di sini untuk $isi_input_val
    // $isi_input_val = $_SESSION['flash_form_data_artikel']['isi'] ?? ''; 
    unset($_SESSION['flash_form_data_artikel']);
}

// 5. Proses POST Request
if (is_post()) { // Menggunakan fungsi helper is_post()
    $judul_from_post = input('judul'); // Menggunakan fungsi helper input()
    $isi_from_post = input('isi');     // Menggunakan fungsi helper input()
    $gambar_filename = null;

    $_SESSION['flash_form_data_artikel'] = [
        'judul' => $judul_from_post,
        'isi' => $isi_from_post,
    ];

    // --- Handle File Upload ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        // Pastikan konstanta UPLOADS_ARTIKEL_PATH sudah didefinisikan di config.php
        if (!defined('UPLOADS_ARTIKEL_PATH')) {
            set_flash_message('danger', 'Konfigurasi direktori unggah artikel tidak ditemukan.');
            redirect('admin/artikel/tambah_artikel.php');
        }
        $target_dir_upload = UPLOADS_ARTIKEL_PATH . DIRECTORY_SEPARATOR; // Tambahkan separator

        if (!is_dir($target_dir_upload)) {
            if (!mkdir($target_dir_upload, 0775, true) && !is_dir($target_dir_upload)) {
                set_flash_message('danger', 'Gagal membuat direktori unggah. Periksa izin folder.');
                redirect('admin/artikel/tambah_artikel.php');
            }
        }

        if (is_dir($target_dir_upload) && is_writable($target_dir_upload)) {
            $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
            $gambar_filename = "artikel_" . uniqid() . '_' . time() . '.' . $imageFileType; // Nama file lebih unik
            $target_file_upload = $target_dir_upload . $gambar_filename;
            $uploadOk = 1;

            $check = @getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check === false) {
                set_flash_message('danger', 'File bukan format gambar.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar"]["size"] > 2000000) {
                set_flash_message('danger', 'Ukuran file maks 2MB.');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($imageFileType, $allowed_formats)) {
                set_flash_message('danger', 'Hanya JPG, JPEG, PNG & GIF.');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file_upload)) {
                    set_flash_message('danger', 'Gagal unggah file. Folder tidak writable?');
                    error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                    $gambar_filename = null;
                }
            } else {
                $gambar_filename = null; // Reset jika upload tidak OK
            }
        } else {
            set_flash_message('danger', 'Direktori unggah tidak dapat ditulis.');
            error_log("Direktori unggah artikel tidak writable: " . $target_dir_upload);
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] != UPLOAD_ERR_OK) {
        set_flash_message('danger', 'Error unggah gambar: ' . $_FILES['gambar']['error']);
    }
    // --- End File Upload Handling ---

    $has_critical_error = false;
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'danger') {
        $has_critical_error = true;
    }

    if (!$has_critical_error) { // Hanya proses jika tidak ada error upload sebelumnya
        if (empty(trim($judul_from_post)) || empty(trim($isi_from_post))) {
            set_flash_message('danger', 'Judul dan Isi artikel wajib diisi.');
        } else {
            if (isset($conn) && $conn instanceof mysqli && class_exists('Artikel') && method_exists('Artikel', 'create')) {
                $data_to_save = [
                    'judul' => $judul_from_post, // Sanitasi sudah di model atau controller
                    'isi' => $isi_from_post,     // Biarkan HTML jika editor WYSIWYG
                    'gambar' => $gambar_filename
                ];

                // PERUBAHAN DI SINI: Panggil metode statis
                $new_artikel_id = Artikel::create($data_to_save);

                if ($new_artikel_id) {
                    unset($_SESSION['flash_form_data_artikel']);
                    set_flash_message('success', 'Artikel berhasil ditambahkan!');
                    redirect('admin/artikel/kelola_artikel.php');
                } else {
                    set_flash_message('danger', 'Gagal menambahkan artikel ke database.');
                    // Hapus file gambar yang sudah terunggah jika penyimpanan DB gagal
                    if ($gambar_filename && isset($target_file_upload) && file_exists($target_file_upload)) {
                        @unlink($target_file_upload);
                        error_log("Rollback Tambah Artikel: Menghapus file gambar karena gagal simpan DB: " . $target_file_upload);
                    }
                }
            } else {
                set_flash_message('danger', 'Kesalahan sistem: Koneksi atau Model Artikel tidak siap.');
                error_log("Koneksi DB atau Model/Metode Artikel tidak ada saat tambah artikel.");
            }
        }
    }

    // Redirect jika ada pesan error flash untuk menampilkannya
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'danger') {
        redirect('admin/artikel/tambah_artikel.php');
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/artikel/kelola_artikel.php"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Artikel</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-plus-square me-2"></i>Tambah Artikel Baru</h5>
        <a href="<?= e(ADMIN_URL) ?>/artikel/kelola_artikel.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>
        <p class="text-muted small">Lengkapi formulir di bawah ini untuk menambahkan artikel baru.</p>
        <form action="<?= e(ADMIN_URL . '/artikel/tambah_artikel.php') ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= $judul_input_val ?>" required>
                <div class="invalid-feedback">Judul artikel wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= $isi_input_val ?></textarea>
                <div class="invalid-feedback">Isi artikel wajib diisi.</div>
                <!-- Anda bisa mengintegrasikan editor WYSIWYG di sini -->
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Artikel <span class="text-muted">(Opsional)</span></label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/png, image/jpeg, image/gif">
                <small class="form-text text-muted">Format: JPG, JPEG, PNG, GIF. Maks: 2MB.</small>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Simpan Artikel
                </button>
                <a href="<?= e(ADMIN_URL) ?>/artikel/kelola_artikel.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Sertakan Admin Footer
if (!include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/artikel/tambah_artikel.php.");
}
?>