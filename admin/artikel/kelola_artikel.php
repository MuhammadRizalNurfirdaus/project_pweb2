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

// 3. Sertakan Model Artikel
// Diasumsikan config.php sudah memuat Artikel.php dan memanggil Artikel::init($conn, UPLOADS_ARTIKEL_PATH);
if (!class_exists('Artikel')) {
    error_log("FATAL ERROR di kelola_artikel.php: Kelas Model Artikel tidak ditemukan setelah config dimuat.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data artikel inti tidak dapat dimuat.');
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
    if (method_exists('Artikel', 'getAll')) {
        $artikel_list = Artikel::getAll('created_at DESC'); // Langsung dari Model, urutkan terbaru dulu

        if ($artikel_list === false) { // Jika Model mengembalikan false karena error query
            $artikel_list = []; // Pastikan array untuk view
            $db_error_detail = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Tidak ada detail error.';
            $error_artikel = "Gagal mengambil data artikel dari database. " . $db_error_detail;
            error_log("Error di kelola_artikel.php saat Artikel::getAll(): " . $error_artikel);
        } elseif (empty($artikel_list) && !$error_artikel) {
            // Tidak ada error, tapi data memang kosong di database
            // Pesan "Belum ada artikel" akan ditampilkan oleh HTML di bawah.
            // error_log("Info di kelola_artikel.php: Tidak ada artikel ditemukan di database."); // Opsional logging
        }
    } else {
        $error_artikel = "Metode Artikel::getAll() tidak ditemukan di Model.";
        error_log("FATAL ERROR di kelola_artikel.php: Metode Artikel::getAll() tidak ada.");
        set_flash_message('danger', 'Kesalahan sistem: Fungsi pengambilan data artikel tidak tersedia.');
        // Tidak redirect agar admin tahu ada masalah kode, tapi tabel akan kosong.
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
                                <td><?= e(excerpt($artikel['isi'] ?? '', 100)) ?></td>
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
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus artikel \" <?= e(addslashes($artikel['judul'])) ?>\"? Tindakan ini tidak dapat diurungkan.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
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
require_once ROOT_PATH . '/template/footer_admin.php';
?>