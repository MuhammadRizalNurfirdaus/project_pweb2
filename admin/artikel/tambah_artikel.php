<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\tambah_artikel.php

// 1. Include Config (memuat $conn, helpers termasuk is_post, input, display_flash_message, e, redirect)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/tambah_artikel.php");
    exit("Kesalahan konfigurasi server. Tidak dapat memuat file penting.");
}

// 2. Panggil fungsi otentikasi admin
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di tambah_artikel.php: Fungsi require_admin() tidak ditemukan.");
    // Fallback sederhana jika fungsi tidak ada (seharusnya tidak terjadi)
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
} else {
    require_admin();
}


// 3. Pastikan Model Artikel ada
if (!class_exists('Artikel')) {
    error_log("FATAL ERROR di tambah_artikel.php: Kelas Model Artikel tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data artikel tidak dapat dimuat.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'dashboard.php');
    exit;
}


$judul_input_val = '';
$isi_input_val = '';
$session_form_data_key = 'flash_form_data_artikel_' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest'); // Tambahkan user ID ke key session

// Proses POST Request SEBELUM output HTML apapun
if (is_post()) {
    // DEBUG CSRF
    $sesi_token_saat_post = $_SESSION['csrf_token'] ?? 'TIDAK ADA DI SESI SAAT POST';
    $post_token_saat_post = $_POST['csrf_token'] ?? 'TIDAK ADA DI POST';
    error_log("Tambah Artikel - Proses POST. Sesi CSRF: {$sesi_token_saat_post}, POST CSRF: {$post_token_saat_post}");

    // Validasi CSRF Token
    // Parameter kedua true akan meng-unset token setelah verifikasi berhasil
    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true)) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa.');
        error_log("Kegagalan Verifikasi CSRF. Sesi: {$sesi_token_saat_post}, POST: {$post_token_saat_post}");
        if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'artikel/tambah_artikel.php');
        exit;
    }
    error_log("Tambah Artikel - CSRF Token berhasil diverifikasi.");


    $judul_from_post = input('judul', '', 'POST');
    $isi_from_post = input('isi', '', 'POST');
    $gambar_filename = null;
    $upload_error = false;

    $_SESSION[$session_form_data_key] = ['judul' => $judul_from_post, 'isi' => $isi_from_post];

    // Handle File Upload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        if (!defined('UPLOADS_ARTIKEL_PATH') || !is_writable(UPLOADS_ARTIKEL_PATH)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Konfigurasi direktori unggah artikel bermasalah atau tidak dapat ditulis.');
            error_log("Error Upload: UPLOADS_ARTIKEL_PATH tidak terdefinisi atau tidak writable. Path: " . (defined('UPLOADS_ARTIKEL_PATH') ? UPLOADS_ARTIKEL_PATH : "Belum terdefinisi"));
            $upload_error = true;
        } else {
            $target_dir_upload = rtrim(UPLOADS_ARTIKEL_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            // Tidak perlu @mkdir di sini jika config.php sudah membuatnya. Cukup cek is_dir & is_writable.

            $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
            $gambar_filename = "artikel_" . uniqid() . '_' . time() . '.' . $imageFileType;
            $target_file_upload = $target_dir_upload . $gambar_filename;
            $uploadOk = 1;

            $check = @getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check === false) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'File bukan format gambar yang valid.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar"]["size"] > 3 * 1024 * 1024) { // 3MB
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Ukuran file gambar maksimal 3MB.');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
            if (!in_array($imageFileType, $allowed_formats)) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Hanya format JPG, JPEG, PNG, GIF, WEBP yang diizinkan.');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file_upload)) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal mengunggah file gambar. Periksa izin folder.');
                    error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                    $gambar_filename = null;
                    $upload_error = true;
                }
            } else {
                $gambar_filename = null;
                $upload_error = true;
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] != UPLOAD_ERR_OK) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan saat mengunggah gambar. Kode Error: ' . $_FILES['gambar']['error']);
        $upload_error = true;
    }

    if (!$upload_error) {
        if (empty($judul_from_post) || empty($isi_from_post)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Judul dan Isi artikel wajib diisi.');
        } else {
            if (method_exists('Artikel', 'create')) {
                $data_to_save = [
                    'judul' => $judul_from_post,
                    'isi' => $isi_from_post,
                    'gambar' => $gambar_filename
                ];
                $new_artikel_id = Artikel::create($data_to_save);
                if ($new_artikel_id) {
                    unset($_SESSION[$session_form_data_key]);
                    if (function_exists('set_flash_message')) set_flash_message('success', 'Artikel baru berhasil ditambahkan!');
                    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'artikel/kelola_artikel.php');
                    exit;
                } else {
                    $db_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Tidak diketahui';
                    if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal menambahkan artikel. ' . ($db_error ?: ''));
                    if ($gambar_filename && isset($target_file_upload) && file_exists($target_file_upload)) {
                        @unlink($target_file_upload);
                        error_log("Rollback Tambah Artikel: Menghapus file {$target_file_upload} karena gagal simpan DB.");
                    }
                }
            } else {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Fungsi pembuatan artikel tidak tersedia.');
                error_log("FATAL ERROR: Metode Artikel::create() tidak ditemukan.");
            }
        }
    }
    // Jika ada flash message (error), redirect kembali untuk menampilkan pesan dan repopulasi
    if (isset($_SESSION['flash_message'])) {
        if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'artikel/tambah_artikel.php');
        exit;
    }
}

// Set judul halaman
$pageTitle = "Tambah Artikel Baru";

// Sertakan header admin (SEKARANG SETELAH BLOK POST)
$header_path_tambah = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : (defined('ROOT_PATH') ? ROOT_PATH . '/template/header_admin.php' : null);
if (!$header_path_tambah || !file_exists($header_path_tambah)) {
    error_log("FATAL ERROR di tambah_artikel.php: Path header admin tidak valid. Path: " . ($header_path_tambah ?? 'Tidak terdefinisi'));
    exit("Error kritis: Komponen tampilan header tidak dapat dimuat.");
}
require_once $header_path_tambah;


// Ambil data dari session untuk repopulasi
if (isset($_SESSION[$session_form_data_key])) {
    $input_judul = $_SESSION[$session_form_data_key]['judul'] ?? '';
    $input_isi = $_SESSION[$session_form_data_key]['isi'] ?? '';
    unset($_SESSION[$session_form_data_key]); // Hapus setelah digunakan
} else {
    // Jika tidak ada data di session (misal, load halaman pertama kali)
    $input_judul = $judul_input_val; // Mungkin sudah diisi dari $_POST jika ada error sebelum redirect
    $input_isi = $isi_input_val;
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'artikel/kelola_artikel.php') ?>"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Artikel</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Formulir Tambah Artikel Baru</h6>
    </div>
    <div class="card-body">
        <?php // display_flash_message() sudah dipanggil di header_admin.php 
        ?>
        <form action="<?= e(ADMIN_URL . 'artikel/tambah_artikel.php') ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <!-- DEBUG: Tampilkan token sesi saat form dibuat -->
            <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT && isset($_SESSION['csrf_token'])): ?>
                <!-- Sesi CSRF saat form dibuat: <?= htmlspecialchars($_SESSION['csrf_token']) ?> -->
            <?php endif; ?>


            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= e($input_judul) ?>" required>
                <div class="invalid-feedback">Judul artikel wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= e($input_isi) ?></textarea>
                <div class="invalid-feedback">Isi artikel wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Artikel <span class="text-muted">(Opsional, Maks 3MB)</span></label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/png, image/jpeg, image/gif, image/webp">
                <small class="form-text text-muted">Format yang diizinkan: JPG, JPEG, PNG, GIF, WEBP.</small>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" name="submit_tambah_artikel" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Simpan Artikel
                </button>
                <a href="<?= e(ADMIN_URL . 'artikel/kelola_artikel.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Sertakan Admin Footer
$footer_path_tambah = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer_admin.php' : (defined('ROOT_PATH') ? ROOT_PATH . '/template/footer_admin.php' : null);
if (!$footer_path_tambah || !file_exists($footer_path_tambah)) {
    error_log("FATAL ERROR di tambah_artikel.php: Path footer admin tidak valid. Path: " . ($footer_path_tambah ?? 'Tidak terdefinisi'));
} else {
    require_once $footer_path_tambah;
}
?>
<script>
    // Script untuk validasi form Bootstrap (jika Anda menggunakannya)
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