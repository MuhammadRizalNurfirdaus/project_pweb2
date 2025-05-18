<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\tambah_wisata.php

// 1. Sertakan konfigurasi utama (HARUS PALING ATAS)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503); // Service Unavailable
    error_log("FATAL: Gagal memuat config.php dari admin/wisata/tambah_wisata.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}

// 2. Otentikasi Admin
// require_admin() akan redirect jika tidak login, dan sudah dimuat via config.php -> auth_helpers.php
if (function_exists('require_admin')) {
    require_admin();
} else {
    // Fallback darurat jika require_admin tidak ada (seharusnya tidak terjadi)
    error_log("FATAL ERROR di tambah_wisata.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// 3. Set judul halaman (SEBELUM INCLUDE HEADER)
$pageTitle = "Tambah Destinasi Wisata Baru";

// 4. Sertakan header admin
// ROOT_PATH dan VIEWS_PATH sudah didefinisikan di config.php
$header_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : ROOT_PATH . '/template/header_admin.php';
if (!file_exists($header_admin_path)) {
    error_log("FATAL: Gagal memuat template header admin dari admin/wisata/tambah_wisata.php. Path: " . $header_admin_path);
    exit("Error Kritis: Komponen tampilan tidak dapat dimuat.");
}
require_once $header_admin_path; // display_flash_message() akan dipanggil di sini

// Inisialisasi variabel untuk pre-fill form
$session_form_data_key = 'flash_form_data_tambah_wisata'; // Konsisten dengan proses_tambah_wisata.php
$nama_input_val = '';
$deskripsi_input_val = '';
$lokasi_input_val = '';

// Ambil data dari session untuk repopulate jika ada (setelah redirect dari proses_tambah_wisata.php karena error)
if (isset($_SESSION[$session_form_data_key]) && is_array($_SESSION[$session_form_data_key])) {
    // PERBAIKAN: Gunakan key 'nama_wisata' jika itu yang disimpan di proses
    $nama_input_val = e($_SESSION[$session_form_data_key]['nama_wisata'] ?? '');
    $deskripsi_input_val = e($_SESSION[$session_form_data_key]['deskripsi'] ?? '');
    $lokasi_input_val = e($_SESSION[$session_form_data_key]['lokasi'] ?? '');
    unset($_SESSION[$session_form_data_key]); // Hapus setelah digunakan
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'wisata/kelola_wisata.php') ?>"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Destinasi</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Tambah Destinasi Wisata Baru</h1>
    <a href="<?= e(ADMIN_URL . 'wisata/kelola_wisata.php') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Destinasi
    </a>
</div>


<div class="card shadow-sm">
    <div class="card-header bg-light"> <?php // Menghapus d-flex dll. agar lebih standar 
                                        ?>
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-map-pin me-2"></i>Formulir Tambah Destinasi</h5>
    </div>
    <div class="card-body">
        <?php // Pesan flash sudah ditampilkan oleh header_admin.php 
        ?>
        <p class="text-muted">Isikan detail destinasi wisata untuk menarik lebih banyak pengunjung.</p>

        <form action="<?= e(ADMIN_URL . 'wisata/proses_tambah_wisata.php') ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

            <div class="mb-3">
                <?php // PERBAIKAN: name="nama_wisata" agar konsisten dengan proses_tambah_wisata.php 
                ?>
                <label for="nama_wisata" class="form-label">Nama Destinasi <span class="text-danger">*</span></label>
                <input type="text" id="nama_wisata" name="nama_wisata" class="form-control form-control-lg" value="<?= $nama_input_val ?>" required placeholder="Contoh: Curug Cilengkrang Indah">
                <div class="invalid-feedback">Nama destinasi wisata wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                <textarea id="deskripsi" name="deskripsi" class="form-control form-control-lg" rows="5" required placeholder="Jelaskan tentang keunikan dan fasilitas destinasi wisata ini..."><?= $deskripsi_input_val ?></textarea>
                <div class="invalid-feedback">Deskripsi wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="lokasi" class="form-label">Lokasi/Alamat <span class="text-danger">*</span></label>
                <input type="text" id="lokasi" name="lokasi" class="form-control form-control-lg" value="<?= $lokasi_input_val ?>" required placeholder="Contoh: Desa Pajambon, Kuningan, Jawa Barat">
                <div class="invalid-feedback">Lokasi wajib diisi.</div>
            </div>

            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Utama Destinasi <span class="text-danger">*</span></label>
                <input type="file" id="gambar" name="gambar" class="form-control form-control-lg" required accept="image/jpeg, image/png, image/gif, image/webp">
                <small class="form-text text-muted">Format yang diizinkan: JPG, PNG, JPEG, GIF, WEBP. Ukuran maksimal: 5MB.</small>
                <div class="invalid-feedback">Silakan pilih file gambar utama destinasi.</div>
            </div>
            <hr>
            <div class="mt-3">
                <?php // PERBAIKAN: Tambahkan name pada tombol submit 
                ?>
                <button type="submit" name="submit_tambah_wisata" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Simpan Destinasi Wisata
                </button>
                <a href="<?= e(ADMIN_URL . 'wisata/kelola_wisata.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$footer_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer_admin.php' : ROOT_PATH . '/template/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("ERROR: Gagal memuat template footer admin dari admin/wisata/tambah_wisata.php. Path: " . $footer_admin_path);
} else {
    include_once $footer_admin_path;
}
?>