<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\kelola_artikel.php

// 1. Sertakan config.php dan Model Artikel
// Path dari admin/artikel/ ke config/ adalah ../../config/
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/kelola_artikel.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}
// Path dari admin/artikel/ ke models/ adalah ../../models/
if (!@require_once __DIR__ . '/../../models/Artikel.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat models/Artikel.php dari admin/artikel/kelola_artikel.php");
    exit("Terjadi kesalahan kritis pada server (model tidak termuat). Mohon coba lagi nanti.");
}

// 2. Sertakan header admin
// Path yang BENAR dari admin/artikel/kelola_artikel.php ke template/header_admin.php adalah '../../template/header_admin.php'
$page_title = "Kelola Artikel"; // Set judul halaman sebelum include header
if (!@include_once __DIR__ . '/../../template/header_admin.php') { // PERBAIKAN PATH DI SINI
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/artikel/kelola_artikel.php. Path yang dicoba: " . __DIR__ . '/../../template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file ('../../template/header_admin.php').</div>";
    exit;
}

// 3. Inisialisasi Model dan ambil data artikel
$artikel_model = new Artikel($conn); // $conn dari config.php
$daftar_artikel = []; // Inisialisasi
if (isset($conn) && $conn) {
    try {
        $daftar_artikel = $artikel_model->getAll(); // Mengambil semua artikel
    } catch (Throwable $e) {
        error_log("Error mengambil daftar artikel di kelola_artikel.php: " . $e->getMessage());
        set_flash_message('danger', 'Gagal memuat daftar artikel. Error: ' . $e->getMessage());
        // Biarkan $daftar_artikel kosong, pesan error akan ditampilkan di bawah
    }
} else {
    set_flash_message('danger', 'Koneksi database tidak tersedia untuk memuat artikel.');
    error_log("Koneksi database tidak tersedia di kelola_artikel.php");
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-newspaper"></i> Kelola Artikel</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Daftar Artikel</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Artikel Baru
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
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Artikel Tersedia</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;">#</th>
                        <th scope="col">Judul</th>
                        <th scope="col" style="width: 30%;">Isi (Ringkasan)</th>
                        <th scope="col" style="width: 15%;">Gambar</th>
                        <th scope="col" style="width: 15%;">Tanggal Publikasi</th>
                        <th scope="col" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_artikel) && is_array($daftar_artikel)): ?>
                        <?php $nomor = 1; ?>
                        <?php foreach ($daftar_artikel as $artikel): ?>
                            <tr>
                                <th scope="row"><?= $nomor++ ?></th>
                                <td><?= e($artikel['judul']) ?></td>
                                <td><?= e(substr(strip_tags($artikel['isi']), 0, 120)) ?>...</td>
                                <td>
                                    <?php if (!empty($artikel['gambar'])): ?>
                                        <img src="<?= $base_url ?>public/uploads/artikel/<?= e($artikel['gambar']) ?>"
                                            alt="Gambar <?= e($artikel['judul']) ?>" class="img-thumbnail" style="width: 100px; height: auto; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted">Tanpa Gambar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d M Y, H:i', strtotime($artikel['created_at']))) ?> WIB</td>
                                <td>
                                    <a href="<?= $base_url ?>admin/artikel/edit_artikel.php?id=<?= e($artikel['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Artikel">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?= $base_url ?>admin/artikel/hapus_artikel.php?id=<?= e($artikel['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Artikel"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus artikel \" <?= e(addslashes($artikel['judul'])) ?>\" ini secara permanen?')">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <p class="mb-2 lead">Belum ada artikel yang ditambahkan.</p>
                                <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Buat Artikel Pertama Anda
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Akhir Konten Kelola Artikel -->

<?php
// 3. Sertakan footer admin
// Path yang BENAR dari admin/artikel/kelola_artikel.php ke template/footer_admin.php adalah '../../template/footer_admin.php'
if (!@include_once __DIR__ . '/../../template/footer_admin.php') { // PERBAIKAN PATH DI SINI
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/artikel/kelola_artikel.php. Path yang dicoba: " . __DIR__ . '/../../template/footer_admin.php');
}
?>