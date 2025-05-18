<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\tambah_foto.php

// 1. Sertakan konfigurasi utama
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/tambah_foto.php");
    exit("Terjadi kesalahan kritis pada server.");
}

// 2. Otentikasi Admin
require_admin(); // Fungsi ini dari auth_helpers.php via config.php

// 3. Set judul halaman
$pageTitle = "Tambah Foto ke Galeri";

// 4. Sertakan header admin
// Path yang BENAR adalah relatif terhadap ROOT_PATH atau menggunakan VIEWS_PATH
$header_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : ROOT_PATH . '/template/header_admin.php';
if (!file_exists($header_admin_path)) {
    error_log("FATAL: Gagal memuat template header admin dari admin/galeri/tambah_foto.php. Path: " . $header_admin_path);
    exit("Error Kritis: Komponen tampilan tidak dapat dimuat.");
}
require_once $header_admin_path; // display_flash_message() akan dipanggil di sini jika ada di header

// Inisialisasi variabel untuk repopulasi form
$session_form_data_key = 'flash_form_data_tambah_foto';
$keterangan_input = '';

if (isset($_SESSION[$session_form_data_key])) {
    $keterangan_input = e($_SESSION[$session_form_data_key]['keterangan'] ?? '');
    unset($_SESSION[$session_form_data_key]); // Hapus setelah digunakan
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'galeri/kelola_galeri.php') ?>"><i class="fas fa-images"></i> Kelola Galeri</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Foto</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-camera-retro me-2"></i>Tambah Foto Baru ke Galeri</h5>
        <a href="<?= e(ADMIN_URL . 'galeri/kelola_galeri.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <?php // Jika display_flash_message() belum ada di header, panggil di sini
        // if (function_exists('display_flash_message')) {
        //     echo display_flash_message();
        // }
        ?>
        <p class="text-muted">Unggah gambar untuk ditampilkan di galeri foto situs wisata.</p>
        <form method="post" enctype="multipart/form-data" action="<?= e(ADMIN_URL . 'galeri/proses_tambah_foto.php') ?>" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan Foto <span class="text-danger">*</span></label>
                <input type="text" id="keterangan" name="keterangan" class="form-control" value="<?= $keterangan_input ?>" required placeholder="Misal: Pemandangan Curug di Pagi Hari">
                <div class="invalid-feedback">
                    Keterangan foto wajib diisi.
                </div>
            </div>
            <div class="mb-3">
                <label for="nama_file" class="form-label">Pilih File Gambar <span class="text-danger">*</span></label>
                <input type="file" id="nama_file" name="nama_file" class="form-control" required accept="image/jpeg, image/png, image/gif, image/webp">
                <small class="form-text text-muted">Format yang diizinkan: JPG, PNG, JPEG, GIF, WEBP. Ukuran maksimal: 5MB.</small>
                <div class="invalid-feedback">
                    Silakan pilih file gambar.
                </div>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Foto
                </button>
                <a href="<?= e(ADMIN_URL . 'galeri/kelola_galeri.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// 3. Sertakan footer admin
$footer_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer_admin.php' : ROOT_PATH . '/template/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("ERROR: Gagal memuat template footer admin dari admin/galeri/tambah_foto.php. Path: " . $footer_admin_path);
} else {
    include_once $footer_admin_path;
}
?>