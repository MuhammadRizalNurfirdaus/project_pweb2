<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\kelola_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin(); // WAJIB diaktifkan

// 3. Sertakan JenisTiketController
// Diasumsikan config.php sudah memuat Controller atau Anda menggunakan autoloader.
$controllerPath = CONTROLLERS_PATH . '/JenisTiketController.php';
if (!class_exists('JenisTiketController')) {
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        error_log("FATAL ERROR di kelola_jenis_tiket.php: File JenisTiketController.php tidak ditemukan di " . $controllerPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}

// 4. Set judul halaman
$pageTitle = "Kelola Jenis Tiket";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php'; // header_admin.php akan memanggil display_flash_message()

// 6. Ambil semua data jenis tiket melalui Controller
$daftar_jenis_tiket = [];
$error_jenis_tiket = null;

if (!method_exists('JenisTiketController', 'getAllForAdmin')) {
    error_log("FATAL ERROR di kelola_jenis_tiket.php: Metode JenisTiketController::getAllForAdmin() tidak ditemukan.");
    $error_jenis_tiket = 'Kesalahan sistem: Fungsi untuk mengambil data jenis tiket tidak tersedia.';
} else {
    try {
        $daftar_jenis_tiket = JenisTiketController::getAllForAdmin(); // Menggunakan metode spesifik admin jika ada
        if ($daftar_jenis_tiket === false) {
            $daftar_jenis_tiket = []; // Pastikan array untuk view
            $error_jenis_tiket = "Gagal mengambil data jenis tiket. " . (method_exists('JenisTiket', 'getLastError') ? JenisTiket::getLastError() : 'Periksa log server.');
            error_log("Error di kelola_jenis_tiket.php saat mengambil jenis tiket: " . $error_jenis_tiket);
        }
    } catch (Exception $e) {
        $error_jenis_tiket = "Terjadi exception saat mengambil data jenis tiket: " . $e->getMessage();
        error_log("Error di kelola_jenis_tiket.php (Exception): " . $e->getMessage());
        $daftar_jenis_tiket = [];
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tags"></i> Kelola Jenis Tiket</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Daftar Jenis Tiket</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php') ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Tambah Jenis Tiket Baru
        </a>
    </div>
</div>

<?php // display_flash_message() sudah dipanggil di header_admin.php 
?>

<?php if ($error_jenis_tiket): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_jenis_tiket) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Jenis Tiket Tersedia</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped" id="dataTableJenisTiket" width="100%" cellspacing="0">
                <thead class="table-dark"> <!-- Atau gunakan class table-light sesuai tema admin Anda -->
                    <tr>
                        <th style="width: 3%;">No.</th>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 20%;">Nama Layanan</th>
                        <th style="width: 15%;">Tipe Hari</th>
                        <th scope="col" class="text-end" style="width: 12%;">Harga</th>
                        <th scope="col">Destinasi Terkait</th>
                        <th scope="col" class="text-center" style="width: 10%;">Status</th>
                        <th scope="col" style="width: 10%;">Dibuat</th>
                        <th scope="col" style="width: 15%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_jenis_tiket) && is_array($daftar_jenis_tiket)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftar_jenis_tiket as $jenis): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <th scope="row"><?= e($jenis['id']) ?></th>
                                <td><?= e($jenis['nama_layanan_display']) ?></td>
                                <td><?= e($jenis['tipe_hari']) ?></td>
                                <td class="text-end"><?= e(formatRupiah($jenis['harga'])) // Menggunakan formatRupiah 
                                                        ?></td>
                                <td><?= e($jenis['nama_wisata_terkait'] ?? 'Umum/Tidak Spesifik') ?></td>
                                <td class="text-center">
                                    <?php if (isset($jenis['aktif']) && $jenis['aktif'] == 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(formatTanggalIndonesia($jenis['created_at'] ?? null, false, true)) // Format tanggal 
                                    ?></td>
                                <td class="text-center">
                                    <a href="<?= e(ADMIN_URL . '/jenis_tiket/edit_jenis_tiket.php?id=' . $jenis['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Jenis Tiket">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?= e(ADMIN_URL . '/jenis_tiket/hapus_jenis_tiket.php?id=' . $jenis['id']) ?>&csrf_token=<?= e(generate_csrf_token()) // Tambah CSRF token 
                                                                                                                                        ?>"
                                        class="btn btn-danger btn-sm mb-1" title="Hapus Jenis Tiket"
                                        onclick="return confirm('PERHATIAN: Menghapus jenis tiket ini bisa gagal jika masih digunakan dalam pemesanan atau jadwal. Yakin ingin mencoba menghapus jenis tiket \" <?= e(addslashes($jenis['nama_layanan_display'] . ' - ' . $jenis['tipe_hari'])) ?>\"?')">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <p class="mb-2 lead">Belum ada jenis tiket yang ditambahkan.</p>
                                <a href="<?= e(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php') ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Tambah Jenis Tiket Pertama
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