<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\tambah_foto.php

// 1. Sertakan konfigurasi utama
// Path dari admin/galeri/ ke config/ adalah ../../config/
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/tambah_foto.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}
// Anda mungkin juga memerlukan GaleriController atau Model Galeri di sini jika prosesnya digabung
// Untuk saat ini, form action ke proses_tambah_foto.php

// 2. Sertakan header admin
// Path yang BENAR dari admin/galeri/tambah_foto.php ke template/header_admin.php adalah '../../template/header_admin.php'
$page_title = "Tambah Foto ke Galeri"; // Set judul halaman
if (!@include_once __DIR__ . '/../../template/header_admin.php') { // PERBAIKAN PATH
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/galeri/tambah_foto.php. Path yang dicoba: " . __DIR__ . '/../../template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file ('../../template/header_admin.php').</div>";
    exit;
}

// Inisialisasi variabel untuk pre-fill form jika ada error dari proses_tambah_foto.php (jika di-redirect kembali dengan data)
$keterangan_input = '';
if (isset($_SESSION['flash_form_data']['keterangan'])) {
    $keterangan_input = e($_SESSION['flash_form_data']['keterangan']);
    unset($_SESSION['flash_form_data']['keterangan']);
}

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/galeri/kelola_galeri.php"><i class="fas fa-images"></i> Kelola Galeri</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Foto</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-camera-retro me-2"></i>Tambah Foto Baru ke Galeri</h5>
        <a href="<?= $base_url ?>admin/galeri/kelola_galeri.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Galeri
        </a>
    </div>
    <div class="card-body">
        <?php
        // Tampilkan flash message jika ada (misalnya dari proses_tambah_foto.php)
        if (function_exists('display_flash_message')) {
            echo display_flash_message();
        }
        ?>
        <p class="text-muted">Silakan isi detail dan pilih file gambar untuk diunggah ke galeri.</p>
        <form method="post" enctype="multipart/form-data" action="<?= $base_url ?>admin/galeri/proses_tambah_foto.php" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan Foto <span class="text-danger">*</span></label>
                <input type="text" id="keterangan" name="keterangan" class="form-control" value="<?= $keterangan_input ?>" required placeholder="Misal: Pemandangan Curug di Pagi Hari">
                <div class="invalid-feedback">
                    Keterangan foto wajib diisi.
                </div>
            </div>
            <div class="mb-3">
                <label for="nama_file" class="form-label">Pilih File Gambar <span class="text-danger">*</span></label>
                <input type="file" id="nama_file" name="nama_file" class="form-control" required accept="image/jpeg, image/png, image/gif">
                <small class="form-text text-muted">Format yang diizinkan: JPG, PNG, GIF. Ukuran maksimal: 5MB.</small>
                <div class="invalid-feedback">
                    Silakan pilih file gambar.
                </div>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Foto
                </button>
                <a href="<?= $base_url ?>admin/galeri/kelola_galeri.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// 3. Sertakan footer admin
// Path yang BENAR dari admin/galeri/tambah_foto.php ke template/footer_admin.php adalah '../../template/footer_admin.php'
if (!@include_once __DIR__ . '/../../template/footer_admin.php') { // PERBAIKAN PATH
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/galeri/tambah_foto.php. Path yang dicoba: " . __DIR__ . '/../../template/footer_admin.php');
}
?>