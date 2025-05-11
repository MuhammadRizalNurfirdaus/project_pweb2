<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\kelola_galeri.php

// 1. Sertakan konfigurasi
// Path dari admin/galeri/ ke config/ adalah ../../config/
if (!@require_once __DIR__ . '/../../config/config.php') { // Menggunakan config.php utama
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/kelola_galeri.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}
// Koneksi $conn sudah tersedia dari config.php

// 2. Sertakan header admin
// Path yang BENAR dari admin/galeri/kelola_galeri.php ke template/header_admin.php adalah '../../template/header_admin.php'
$page_title = "Kelola Galeri"; // Set judul halaman sebelum include header
if (!@include_once __DIR__ . '/../../template/header_admin.php') { // PERBAIKAN PATH
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/galeri/kelola_galeri.php. Path yang dicoba: " . __DIR__ . '/../../template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file ('../../template/header_admin.php').</div>";
    exit;
}

// Ambil data galeri dari database
// Diasumsikan tidak ada kolom 'created_at' atau 'uploaded_at' untuk pengurutan utama, jadi pakai 'id DESC'
$query_galeri = null;
if (isset($conn) && $conn) {
    try {
        $sql = "SELECT id, nama_file, keterangan FROM galeri ORDER BY id DESC";
        $query_galeri = mysqli_query($conn, $sql);
        if (!$query_galeri) {
            error_log("MySQLi Query Error (Galeri getAll): " . mysqli_error($conn));
            set_flash_message('danger', 'Gagal memuat data galeri: ' . mysqli_error($conn));
        }
    } catch (Throwable $e) {
        error_log("Error mengambil data galeri di kelola_galeri.php: " . $e->getMessage());
        set_flash_message('danger', 'Terjadi kesalahan saat memuat data galeri.');
    }
} else {
    set_flash_message('danger', 'Koneksi database tidak tersedia untuk memuat galeri.');
    error_log("Koneksi database tidak tersedia di kelola_galeri.php");
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-images"></i> Kelola Galeri</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Galeri Foto</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= $base_url ?>admin/galeri/tambah_foto.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Foto Baru
        </a>
    </div>
</div>

<?php
// Tampilkan flash message di sini jika ada (setelah header dimuat)
if (function_exists('display_flash_message')) {
    echo display_flash_message();
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-th-large me-2"></i>Daftar Foto di Galeri</h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <?php if ($query_galeri && mysqli_num_rows($query_galeri) > 0): ?>
                <?php while ($data = mysqli_fetch_assoc($query_galeri)): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 item-galeri-admin">
                            <div class="item-galeri-img-container">
                                <img src="<?= $base_url ?>public/img/<?= e($data['nama_file']) ?>"
                                    class="card-img-top"
                                    alt="<?= e($data['keterangan']) ?>"
                                    style="height: 200px; object-fit: cover;">
                            </div>
                            <div class="card-body text-center d-flex flex-column">
                                <?php if (!empty($data['keterangan'])): ?>
                                    <p class="card-text small flex-grow-1" style="min-height: 40px;"><?= e($data['keterangan']) ?></p>
                                <?php else: ?>
                                    <p class="card-text small flex-grow-1 text-muted fst-italic" style="min-height: 40px;">Tanpa keterangan</p>
                                <?php endif; ?>
                                <div class="mt-auto">
                                    <a href="<?= $base_url ?>admin/galeri/edit_foto.php?id=<?= e($data['id']) ?>" class="btn btn-outline-warning btn-sm me-1 mb-1" title="Edit Foto">
                                        <i class="fas fa-edit fa-xs"></i> Edit
                                    </a>
                                    <a href="<?= $base_url ?>admin/galeri/hapus_foto.php?id=<?= e($data['id']) ?>" class="btn btn-outline-danger btn-sm mb-1" title="Hapus Foto"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus foto \" <?= e(addslashes($data['keterangan'] ?: $data['nama_file'])) ?>\" ini?')">
                                        <i class="fas fa-trash-alt fa-xs"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <p class="mb-1 lead">Belum ada foto di galeri.</p>
                        <p class="mb-0">Anda bisa mulai dengan <a href="<?= $base_url ?>admin/galeri/tambah_foto.php" class="alert-link">menambahkan foto baru</a>.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Style spesifik untuk halaman kelola galeri admin jika diperlukan */
    .item-galeri-admin .card-img-top {
        border-bottom: 1px solid var(--admin-border-color);
        transition: transform 0.3s ease;
    }

    .item-galeri-admin:hover .card-img-top {
        transform: scale(1.05);
    }

    .item-galeri-img-container {
        overflow: hidden;
        /* Mencegah gambar keluar dari rounded corner card */
        border-top-left-radius: var(--bs-card-inner-border-radius);
        /* Mengikuti Bootstrap */
        border-top-right-radius: var(--bs-card-inner-border-radius);
    }
</style>

<?php
// 3. Sertakan footer admin
// Path yang BENAR dari admin/galeri/kelola_galeri.php ke template/footer_admin.php adalah '../../template/footer_admin.php'
if (!@include_once __DIR__ . '/../../template/footer_admin.php') { // PERBAIKAN PATH
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/galeri/kelola_galeri.php. Path yang dicoba: " . __DIR__ . '/../../template/footer_admin.php');
}
?>