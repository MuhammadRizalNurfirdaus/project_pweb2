<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\tambah_wisata.php

// 1. Sertakan konfigurasi utama
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/wisata/tambah_wisata.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}

// 2. Sertakan header admin
$page_title = "Tambah Destinasi Wisata Baru";
if (!@include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/wisata/tambah_wisata.php.");
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat.</div>";
    exit;
}

// Inisialisasi variabel untuk pre-fill form
$nama_input_val = '';
$deskripsi_input_val = '';
$lokasi_input_val = '';

if (isset($_SESSION['flash_form_data_wisata'])) {
    $nama_input_val = e($_SESSION['flash_form_data_wisata']['nama'] ?? '');
    $deskripsi_input_val = e($_SESSION['flash_form_data_wisata']['deskripsi'] ?? '');
    $lokasi_input_val = e($_SESSION['flash_form_data_wisata']['lokasi'] ?? '');
    // Jangan unset di sini, biarkan sampai proses berhasil atau session expired
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/wisata/kelola_wisata.php"><i class="fas fa-map-marked-alt"></i> Kelola Wisata</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Wisata</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-map-pin me-2"></i>Tambah Destinasi Wisata Baru</h5>
        <a href="<?= $base_url ?>admin/wisata/kelola_wisata.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Wisata
        </a>
    </div>
    <div class="card-body">
        <?php
        if (function_exists('display_flash_message')) {
            echo display_flash_message();
        }
        ?>
        <p class="text-muted">Isikan detail destinasi wisata agar pengunjung semakin mudah menemukan keindahan Cilengkrang.</p>

        <form action="<?= $base_url ?>admin/wisata/proses_tambah_wisata.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Wisata</label>
                <input type="text" id="nama" name="nama" class="form-control" value="<?= $nama_input_val ?>" required placeholder="Contoh: Curug Cilengkrang">
                <div class="invalid-feedback">Nama destinasi wisata wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5" required placeholder="Jelaskan tentang destinasi wisata ini..."><?= $deskripsi_input_val ?></textarea>
                <div class="invalid-feedback">Deskripsi wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="lokasi" class="form-label">Lokasi</label>
                <input type="text" id="lokasi" name="lokasi" class="form-control" value="<?= $lokasi_input_val ?>" required placeholder="Contoh: Area Pemandian Utama, Desa Pajambon">
                <div class="invalid-feedback">Lokasi wajib diisi.</div>
            </div>

            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Utama Destinasi</label>
                <input type="file" id="gambar" name="gambar" class="form-control" required accept="image/jpeg, image/png, image/gif">
                <small class="form-text text-muted">Format yang diizinkan: JPG, PNG, GIF. Ukuran maksimal: 5MB.</small>
                <div class="invalid-feedback">Silakan pilih file gambar utama.</div>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Simpan Destinasi Wisata
                </button>
                <a href="<?= $base_url ?>admin/wisata/kelola_wisata.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
if (!@include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/wisata/tambah_wisata.php.");
}
?>