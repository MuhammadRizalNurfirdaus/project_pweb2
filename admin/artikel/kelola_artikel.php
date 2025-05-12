<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\kelola_artikel.php

// 1. Sertakan config.php (sudah memuat koneksi $conn dan helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/kelola_artikel.php");
    exit("Kesalahan konfigurasi server. Tidak dapat memuat file penting.");
}

// 2. Panggil fungsi otentikasi admin
require_admin(); // Pastikan fungsi ini ada dan berfungsi dari auth_helpers.php

// 3. Sertakan Model Artikel (yang sekarang seharusnya statis)
if (!require_once __DIR__ . '/../../models/Artikel.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat models/Artikel.php dari admin/artikel/kelola_artikel.php");
    set_flash_message('danger', 'Kesalahan sistem: Model Artikel tidak dapat dimuat.');
    redirect('admin/dashboard.php');
}

// 4. Set judul halaman dan sertakan header admin
$pageTitle = "Kelola Daftar Artikel";
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/artikel/kelola_artikel.php.");
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px;'>Error Kritis: Gagal memuat komponen header halaman admin.</p>";
    // exit;
}

// 5. Ambil data artikel menggunakan metode statis dari Model
$daftar_artikel = []; // Inisialisasi sebagai array kosong
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error && class_exists('Artikel') && method_exists('Artikel', 'getAll')) {
    try {
        // PERUBAHAN DI SINI: Panggil metode statis
        $daftar_artikel = Artikel::getAll();
    } catch (Throwable $e) {
        error_log("Error mengambil daftar artikel di kelola_artikel.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        set_flash_message('danger', 'Gagal memuat daftar artikel. Silakan coba lagi nanti.');
    }
} elseif (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    set_flash_message('danger', 'Koneksi database tidak tersedia untuk memuat artikel.');
    error_log("Koneksi database tidak tersedia di kelola_artikel.php saat mencoba memuat artikel.");
} elseif (!class_exists('Artikel') || !method_exists('Artikel', 'getAll')) {
    set_flash_message('danger', 'Kesalahan sistem: Komponen Artikel atau metode tidak ditemukan.');
    error_log("Class Artikel atau method getAll tidak ditemukan di kelola_artikel.php.");
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-newspaper"></i> Kelola Artikel</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Daftar Artikel</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e(ADMIN_URL) ?>/artikel/tambah_artikel.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Artikel Baru
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Artikel Tersedia</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 3%;" class="text-center">No.</th>
                        <th scope="col" style="width: 5%;" class="text-center">ID</th>
                        <th scope="col" style="width: 22%;">Judul</th>
                        <th scope="col" style="width: 30%;">Isi (Ringkasan)</th>
                        <th scope="col" style="width: 15%;" class="text-center">Gambar</th>
                        <th scope="col" style="width: 10%;">Tanggal Publikasi</th>
                        <th scope="col" style="width: 15%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_artikel) && is_array($daftar_artikel)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftar_artikel as $artikel): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <td class="text-center"><strong><?= e($artikel['id']) ?></strong></td>
                                <td><?= e($artikel['judul']) ?></td>
                                <td>
                                    <?php
                                    $ringkasan = strip_tags($artikel['isi']);
                                    if (mb_strlen($ringkasan) > 80) {
                                        $ringkasan = mb_substr($ringkasan, 0, 80) . "...";
                                    }
                                    echo e($ringkasan);
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($artikel['gambar'])): ?>
                                        <?php $gambar_url = BASE_URL . 'public/uploads/artikel/' . rawurlencode(e($artikel['gambar'])); ?>
                                        <img src="<?= $gambar_url ?>" alt="Gambar <?= e($artikel['judul']) ?>" class="img-thumbnail" style="max-width: 80px; max-height: 60px; object-fit: cover;" loading="lazy">
                                    <?php else: ?>
                                        <span class="text-muted">Tanpa Gambar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($artikel['created_at']) && $artikel['created_at'] !== '0000-00-00 00:00:00') {
                                        try {
                                            $date_obj = new DateTime($artikel['created_at']);
                                            echo e($date_obj->format('d M Y'));
                                        } catch (Exception $ex) {
                                            error_log("Error parsing date for article ID " . $artikel['id'] . ": " . $artikel['created_at'] . " - " . $ex->getMessage());
                                            echo e($artikel['created_at']);
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= e(ADMIN_URL) ?>/artikel/edit_artikel.php?id=<?= e($artikel['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Artikel">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="<?= e(ADMIN_URL) ?>/artikel/hapus_artikel.php" method="POST" style="display:inline;" onsubmit="return confirm('Anda yakin ingin menghapus artikel \" <?= e(addslashes($artikel['judul'])) ?>\" ini beserta semua feedback terkait secara permanen?');">
                                        <input type="hidden" name="id_artikel" value="<?= e($artikel['id']) ?>">
                                        <button type="submit" name="hapus_artikel_submit" class="btn btn-danger btn-sm mb-1" title="Hapus Artikel">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="mb-2 lead">Belum ada artikel yang ditambahkan.</p>
                                <a href="<?= e(ADMIN_URL) ?>/artikel/tambah_artikel.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Buat Artikel Pertama
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
if (!include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/artikel/kelola_artikel.php.");
}
?>