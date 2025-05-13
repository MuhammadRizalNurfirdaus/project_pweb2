<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\kelola_artikel.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_artikel.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Controller atau Model Artikel
$artikel_controller_loaded = false;
if (class_exists('ArtikelController') && method_exists('ArtikelController', 'getAllForAdmin')) {
    $artikel_controller_loaded = true;
} else {
    $artikelControllerPath = CONTROLLERS_PATH . '/ArtikelController.php';
    if (file_exists($artikelControllerPath)) {
        require_once $artikelControllerPath;
        if (class_exists('ArtikelController') && method_exists('ArtikelController', 'getAllForAdmin')) {
            $artikel_controller_loaded = true;
        }
    }
}

$artikel_model_loaded_for_get_all = false;
if (!$artikel_controller_loaded) {
    if (class_exists('Artikel') && method_exists('Artikel', 'getAll')) {
        $artikel_model_loaded_for_get_all = true;
    } else {
        $artikelModelPath = MODELS_PATH . '/Artikel.php';
        if (file_exists($artikelModelPath)) {
            require_once $artikelModelPath;
            if (class_exists('Artikel') && method_exists('Artikel', 'getAll')) {
                $artikel_model_loaded_for_get_all = true;
            }
        }
    }
}

if (!$artikel_controller_loaded && !$artikel_model_loaded_for_get_all) {
    error_log("FATAL ERROR di kelola_artikel.php: Tidak dapat memuat ArtikelController atau Model Artikel untuk mengambil data.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data artikel tidak dapat dimuat.');
    redirect(ADMIN_URL . '/dashboard.php');
    exit;
}

// 4. Set judul halaman
$pageTitle = "Kelola Artikel";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Ambil semua data artikel
$artikel_list = [];
$error_artikel = null;

try {
    if ($artikel_controller_loaded) {
        $artikel_list = ArtikelController::getAllForAdmin();
    } elseif ($artikel_model_loaded_for_get_all) {
        $artikel_list = Artikel::getAll('created_at DESC');
    }

    if ($artikel_list === false) {
        $artikel_list = [];
        $db_error_detail = '';
        if ($artikel_model_loaded_for_get_all && method_exists('Artikel', 'getLastError')) {
            $db_error_detail = Artikel::getLastError();
        }
        $error_artikel = "Gagal mengambil data artikel dari database." . (!empty($db_error_detail) ? " Detail: " . $db_error_detail : "");
        error_log("Error di kelola_artikel.php saat mengambil artikel: " . $error_artikel);
    }
} catch (Exception $e) {
    $error_artikel = "Terjadi exception saat mengambil data artikel: " . $e->getMessage();
    error_log("Error di kelola_artikel.php saat mengambil artikel (Exception): " . $e->getMessage());
    $artikel_list = [];
}

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-newspaper"></i> Kelola Daftar Artikel</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Kelola Daftar Artikel</h1>
    <a href="<?= e(ADMIN_URL . '/artikel/tambah_artikel.php') ?>" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Tambah Artikel Baru
    </a>
</div>

<?php // display_flash_message() sudah dipanggil di header_admin.php 
?>

<?php if ($error_artikel): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_artikel) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Artikel Tersedia</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTableArtikel" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th style="width: 10%;">ID</th>
                        <th>Judul</th>
                        <th>Isi (Ringkasan)</th>
                        <th style="width: 15%;">Gambar</th>
                        <th style="width: 15%;">Tanggal Publikasi</th>
                        <th style="width: 15%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($artikel_list)): ?>
                        <?php $nomor = 1; ?>
                        <?php foreach ($artikel_list as $artikel): ?>
                            <tr>
                                <td><?= $nomor++ ?></td>
                                <td><?= e($artikel['id']) ?></td>
                                <td><?= e($artikel['judul']) ?></td>
                                <td><?= e(excerpt($artikel['isi'] ?? '', 100)) // Menampilkan ringkasan isi 
                                    ?></td>
                                <td>
                                    <?php if (!empty($artikel['gambar'])): ?>
                                        <img src="<?= e(BASE_URL . 'public/uploads/artikel/' . $artikel['gambar']) ?>"
                                            alt="Gambar <?= e($artikel['judul']) ?>"
                                            class="img-thumbnail"
                                            style="max-height: 70px; max-width: 100px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Tidak ada gambar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(formatTanggalIndonesia($artikel['created_at'] ?? null, true, true)) ?></td>
                                <td class="text-center">
                                    <a href="<?= e(ADMIN_URL . '/artikel/edit_artikel.php?id=' . $artikel['id']) ?>" class="btn btn-warning btn-sm my-1" title="Edit Artikel">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= e(ADMIN_URL . '/artikel/hapus_artikel.php?id=' . $artikel['id']) ?>&csrf_token=<?= e(generate_csrf_token()) ?>"
                                        class="btn btn-danger btn-sm my-1" title="Hapus Artikel"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus artikel \" <?= e(addslashes($artikel['judul'])) // addslashes untuk javascript confirm 
                                                                                                                ?>\"? Tindakan ini tidak dapat diurungkan.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4"> <?php // PERBAIKAN: Colspan disesuaikan 
                                                                        ?>
                                <p class="mb-2">Belum ada artikel yang ditambahkan.</p>
                                <a href="<?= e(ADMIN_URL . '/artikel/tambah_artikel.php') ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Buat Artikel Pertama
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Komentar untuk DataTables.js jika Anda ingin menggunakannya nanti
/*
$additional_js_admin = [
    ASSETS_URL . '/vendor/datatables/jquery.dataTables.min.js', // Ganti path jika perlu
    ASSETS_URL . '/vendor/datatables/dataTables.bootstrap5.min.js',
    ASSETS_URL . '/js/admin-datatables-artikel.js' // Buat file JS kustom untuk inisialisasi DataTable Artikel
];
// Anda juga perlu memuat CSS DataTables di header_admin.php
// <link href="<?= ASSETS_URL ?>/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet">
*/
?>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>