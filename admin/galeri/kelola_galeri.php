<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\kelola_galeri.php

// 1. Sertakan konfigurasi
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_galeri.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Controller atau Model Galeri
// Diasumsikan config.php sudah memuat Galeri.php (Model) dan GaleriController.php (Controller)
// serta sudah memanggil Galeri::init($conn, UPLOADS_GALERI_PATH)
$data_source_available = false;
$is_using_controller = false;

if (class_exists('GaleriController') && method_exists('GaleriController', 'getAllForAdmin')) {
    $data_source_available = true;
    $is_using_controller = true;
} elseif (class_exists('Galeri') && method_exists('Galeri', 'getAll')) {
    $data_source_available = true;
}

if (!$data_source_available) {
    error_log("FATAL ERROR di kelola_galeri.php: Tidak dapat memuat GaleriController atau Model Galeri untuk mengambil data.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data galeri tidak dapat dimuat.');
    redirect(ADMIN_URL . '/dashboard.php');
    exit;
}

// 4. Set judul halaman
$pageTitle = "Kelola Galeri Foto"; // Diubah dari $page_title

// 5. Sertakan header admin
// header_admin.php akan memanggil display_flash_message()
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Ambil semua data galeri
$foto_list = [];
$error_galeri = null;

try {
    if ($is_using_controller) {
        $foto_list = GaleriController::getAllForAdmin();
    } else {
        $foto_list = Galeri::getAll('uploaded_at DESC'); // Urutkan terbaru dulu
    }

    if ($foto_list === false) { // Jika Model/Controller mengembalikan false karena error
        $foto_list = [];
        $db_error_detail = '';
        if (class_exists('Galeri') && method_exists('Galeri', 'getLastError')) {
            $db_error_detail = Galeri::getLastError();
        }
        $error_galeri = "Gagal mengambil data galeri." . (!empty($db_error_detail) ? " Detail: " . $db_error_detail : "");
        error_log("Error di kelola_galeri.php saat mengambil foto: " . $error_galeri);
    }
} catch (Exception $e) {
    $error_galeri = "Terjadi exception saat mengambil data galeri: " . $e->getMessage();
    error_log("Error di kelola_galeri.php saat mengambil foto (Exception): " . $e->getMessage());
    $foto_list = [];
}

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-images"></i> Kelola Galeri Foto</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Kelola Galeri Foto</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e(ADMIN_URL . '/galeri/tambah_foto.php') ?>" class="btn btn-success"> <!-- Dihilangkan btn-sm shadow-sm fa-sm text-white-50 -->
            <i class="fas fa-plus me-1"></i> Tambah Foto Baru
        </a>
    </div>
</div>

<?php // Pesan flash sudah ditampilkan di header_admin.php 
?>

<?php if ($error_galeri): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_galeri) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-th-large me-2"></i>Daftar Foto di Galeri</h6>
    </div>
    <div class="card-body">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php if (!empty($foto_list)): ?>
                <?php foreach ($foto_list as $foto): ?>
                    <div class="col">
                        <div class="card h-100 item-galeri-admin shadow-sm">
                            <div class="item-galeri-img-container">
                                <?php if (!empty($foto['nama_file']) && file_exists(UPLOADS_GALERI_PATH . '/' . $foto['nama_file'])): ?>
                                    <img src="<?= e(BASE_URL . 'public/uploads/galeri/' . $foto['nama_file']) ?>"
                                        class="card-img-top"
                                        alt="<?= e($foto['keterangan'] ?: 'Foto Galeri') ?>"
                                        style="height: 200px; object-fit: cover;">
                                <?php elseif (!empty($foto['nama_file'])): ?>
                                    <div class="text-center p-3" style="height: 200px; background-color: #f0f0f0; display:flex; align-items:center; justify-content:center;">
                                        <small class="text-danger"><i class="fas fa-image"></i> File tidak ditemukan</small>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-3" style="height: 200px; background-color: #f8f9fa; display:flex; align-items:center; justify-content:center;">
                                        <small class="text-muted">Tidak ada gambar</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <?php if (!empty($foto['keterangan'])): ?>
                                    <p class="card-text small flex-grow-1" style="min-height: 40px; overflow-y: auto; max-height: 60px;"><?= nl2br(e($foto['keterangan'])) ?></p>
                                <?php else: ?>
                                    <p class="card-text small flex-grow-1 text-muted fst-italic" style="min-height: 40px;">Tanpa keterangan</p>
                                <?php endif; ?>
                                <small class="text-muted d-block mb-2">ID: <?= e($foto['id']) ?></small>
                                <small class="text-muted d-block mb-2">File: <?= e(excerpt($foto['nama_file'] ?? '', 20)) ?></small>
                                <small class="text-muted d-block mb-2">Upload: <?= e(formatTanggalIndonesia($foto['uploaded_at'] ?? null, false, true)) ?></small>
                            </div>
                            <div class="card-footer text-center bg-light">
                                <a href="<?= e(ADMIN_URL . '/galeri/edit_foto.php?id=' . $foto['id']) ?>" class="btn btn-outline-warning btn-sm me-1" title="Edit Foto">
                                    <i class="fas fa-edit fa-xs"></i> Edit
                                </a>
                                <a href="<?= e(ADMIN_URL . '/galeri/hapus_foto.php?id=' . $foto['id']) ?>&csrf_token=<?= e(generate_csrf_token()) // Pastikan fungsi ini ada 
                                                                                                                        ?>"
                                    class="btn btn-outline-danger btn-sm" title="Hapus Foto"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus foto ini: \'<?= e(addslashes($foto['keterangan'] ?: $foto['nama_file'])) ?>\'?');">
                                    <i class="fas fa-trash-alt fa-xs"></i> Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <p class="mb-1 lead">Belum ada foto di galeri.</p>
                        <p class="mb-0">Anda bisa mulai dengan <a href="<?= e(ADMIN_URL . '/galeri/tambah_foto.php') ?>" class="alert-link">menambahkan foto baru</a>.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .item-galeri-admin .card-img-top {
        border-bottom: 1px solid var(--admin-border-color, #dee2e6);
        transition: transform 0.3s ease, filter 0.3s ease;
    }

    .item-galeri-admin:hover .card-img-top {
        transform: scale(1.03);
        filter: brightness(0.9);
    }

    .item-galeri-img-container {
        overflow: hidden;
        border-top-left-radius: var(--bs-card-inner-border-radius);
        border-top-right-radius: var(--bs-card-inner-border-radius);
    }

    .card-footer.bg-light {
        /* Pastikan footer card juga mengikuti tema */
        background-color: var(--admin-card-header-bg) !important;
    }
</style>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>