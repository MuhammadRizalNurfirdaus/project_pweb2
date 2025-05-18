<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\kelola_galeri.php

// 1. Sertakan config.php
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_galeri.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Validasi ketersediaan Controller atau Model Galeri
$can_use_controller = class_exists('GaleriController') && method_exists('GaleriController', 'getAllForAdmin');
$can_use_model = class_exists('Galeri') && method_exists('Galeri', 'getAll');

if (!$can_use_controller && !$can_use_model) {
    error_log("FATAL ERROR di kelola_galeri.php: Komponen Galeri (Controller/Model) tidak tersedia.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen inti untuk data galeri tidak dapat dimuat.');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// 4. Set judul halaman
$pageTitle = "Kelola Galeri Foto";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php'; // display_flash_message() ada di sini

// 6. Ambil semua data galeri
$foto_list = [];
$error_galeri = null;

try {
    // Prioritaskan Controller jika ada, fallback ke Model langsung
    if ($can_use_controller) {
        $foto_list = GaleriController::getAllForAdmin('uploaded_at DESC');
    } elseif ($can_use_model) {
        $foto_list = Galeri::getAll('uploaded_at DESC');
    } else {
        // Ini seharusnya sudah ditangani oleh pengecekan di atas, tapi sebagai fallback
        throw new Exception("Komponen pengambilan data galeri tidak tersedia.");
    }

    if ($foto_list === false) { // Jika metode mengembalikan false karena error
        $foto_list = []; // Pastikan array untuk view
        $db_error_detail = '';
        if (class_exists('Galeri') && method_exists('Galeri', 'getLastError')) {
            $db_error_detail = Galeri::getLastError();
        } elseif (class_exists('GaleriController') && method_exists('GaleriController', 'getLastError')) { // Jika error dari controller
            // Anda mungkin perlu menambahkan getLastError di Controller atau mengandalkan flash message dari controller
            // Untuk sekarang, kita asumsikan error utama dari Model
        }
        $error_galeri = "Gagal mengambil data galeri." . (!empty($db_error_detail) ? " Detail: " . e($db_error_detail) : " Periksa log server.");
        error_log("Error di kelola_galeri.php saat mengambil foto: " . $error_galeri);
    }
} catch (Exception $e) {
    $error_galeri = "Terjadi exception saat mengambil data galeri: " . e($e->getMessage());
    error_log("Error di kelola_galeri.php (Exception): " . $e->getMessage());
    $foto_list = [];
}

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-images"></i> Kelola Galeri Foto</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Kelola Galeri Foto</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e(ADMIN_URL . 'galeri/tambah_foto.php') ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Tambah Foto Baru
        </a>
    </div>
</div>

<?php
// Pesan flash message sudah ditampilkan oleh header_admin.php
// Tampilkan pesan error spesifik halaman jika ada DAN belum ada flash error global
if ($error_galeri && (!isset($_SESSION['flash_message']) || ($_SESSION['flash_message']['type'] ?? '') !== 'danger')): ?>
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
            <?php if (!empty($foto_list) && is_array($foto_list)): ?>
                <?php foreach ($foto_list as $foto): ?>
                    <div class="col">
                        <div class="card h-100 item-galeri-admin shadow-sm">
                            <div class="item-galeri-img-container">
                                <?php
                                $nama_file_galeri = $foto['nama_file'] ?? '';
                                $gambar_path_fisik = defined('UPLOADS_GALERI_PATH') ? rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nama_file_galeri : '';
                                $gambar_url_display = defined('UPLOADS_GALERI_URL') ? UPLOADS_GALERI_URL . rawurlencode($nama_file_galeri) : BASE_URL . 'public/uploads/galeri/' . rawurlencode($nama_file_galeri);
                                ?>
                                <?php if (!empty($nama_file_galeri) && file_exists($gambar_path_fisik)): ?>
                                    <img src="<?= e($gambar_url_display) ?>"
                                        class="card-img-top"
                                        alt="<?= e($foto['keterangan'] ?: 'Foto Galeri') ?>"
                                        style="height: 200px; object-fit: cover; cursor:pointer;"
                                        onclick="showImageModal('<?= e($gambar_url_display) ?>', '<?= e(addslashes($foto['keterangan'] ?: $nama_file_galeri)) ?>')">
                                <?php elseif (!empty($nama_file_galeri)): ?>
                                    <div class="text-center p-3" style="height: 200px; background-color: #f0f0f0; display:flex; align-items:center; justify-content:center; flex-direction: column;">
                                        <i class="fas fa-image fa-2x text-danger mb-2"></i>
                                        <small class="text-danger">File tidak ditemukan:<br><?= e($nama_file_galeri) ?></small>
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
                                <small class="text-muted d-block mb-1">ID: <?= e($foto['id'] ?? 'N/A') ?></small>
                                <small class="text-muted d-block mb-1" title="<?= e($foto['nama_file'] ?? '') ?>">File: <?= e(function_exists('excerpt') ? excerpt($foto['nama_file'] ?? '', 20) : mb_substr((string)($foto['nama_file'] ?? ''), 0, 20) . '...') ?></small>
                                <small class="text-muted d-block">Upload: <?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($foto['uploaded_at'] ?? null, false, true) : ($foto['uploaded_at'] ?? '-')) ?></small>
                            </div>
                            <div class="card-footer text-center bg-light">
                                <a href="<?= e(ADMIN_URL . 'galeri/edit_foto.php?id=' . ($foto['id'] ?? '')) ?>" class="btn btn-outline-warning btn-sm me-1" title="Edit Foto">
                                    <i class="fas fa-edit fa-xs"></i> Edit
                                </a>
                                <?php // PERUBAHAN: Tombol Hapus Simpel dengan Konfirmasi JS 
                                ?>
                                <a href="<?= e(ADMIN_URL . 'galeri/hapus_foto.php?id=' . ($foto['id'] ?? '')) ?>&csrf_token=<?= e(function_exists('generate_csrf_token') ? generate_csrf_token() : '') ?>"
                                    class="btn btn-outline-danger btn-sm" title="Hapus Foto"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus foto ini: \'<?= e(addslashes($foto['keterangan'] ?: ($foto['nama_file'] ?? 'Item Ini'))) ?>\'? Tindakan ini akan menghapus file gambar secara permanen.');">
                                    <i class="fas fa-trash-alt fa-xs"></i> Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!$error_galeri): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <p class="mb-1 lead">Belum ada foto di galeri.</p>
                        <p class="mb-0">Anda bisa mulai dengan <a href="<?= e(ADMIN_URL . 'galeri/tambah_foto.php') ?>" class="alert-link">menambahkan foto baru</a>.</p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($error_galeri && empty($foto_list)): ?>
                <div class="col-12">
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Gagal memuat data galeri.
                        <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
                            <br><small>Detail: <?= e($error_galeri) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Pratinjau Gambar (jika ingin digunakan) -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalLabel">Pratinjau Gambar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="imagePreviewSrc" class="img-fluid" alt="Pratinjau Gambar">
            </div>
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
        background-color: var(--admin-card-header-bg, #f8f9fa) !important;
    }
</style>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>