<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\riwayat_pemesanan.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/flash_message.php';
require_once __DIR__ . '/../controllers/PemesananTiketController.php';
require_once __DIR__ . '/../controllers/PemesananSewaAlatController.php';
// Koneksi $conn diasumsikan tersedia

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id <= 0) {
    set_flash_message('danger', 'Sesi tidak valid atau Anda belum login.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Panggil metode statis untuk mendapatkan data
$riwayatPemesananTiket = PemesananTiketController::getPemesananByUser($user_id);
$riwayatPemesananSewa = PemesananSewaAlatController::getPemesananSewaByUser($user_id);


$pageTitle = "Riwayat Pemesanan";

$headerFile = 'header_user.php';
include_once __DIR__ . '/../template/' . $headerFile;
?>

<main id="main">
    <!-- ======= Breadcrumbs ======= -->
    <section class="breadcrumbs">
        <div class="container">
            <ol>
                <li><a href="<?php echo e(BASE_URL); ?>">Home</a></li>
                <li><a href="<?php echo e(BASE_URL); ?>/user/dashboard.php">Dashboard</a></li>
                <li>Riwayat Pemesanan</li>
            </ol>
            <h2>Riwayat Pemesanan Anda</h2>
        </div>
    </section><!-- End Breadcrumbs -->

    <section id="riwayat" class="riwayat section-bg">
        <div class="container" data-aos="fade-up">

            <?php display_flash_message(); ?>

            <!-- Riwayat Pemesanan Tiket -->
            <div class="row mb-5">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2>Riwayat Pemesanan Tiket</h2>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle"> <!-- Tambah align-middle -->
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kode Pesan</th>
                                            <th>Tgl Kunjungan</th>
                                            <th class="text-end">Total Harga</th>
                                            <th class="text-center">Status</th>
                                            <th>Tgl Pesan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($riwayatPemesananTiket)): ?>
                                            <?php foreach ($riwayatPemesananTiket as $pesanan): ?>
                                                <tr>
                                                    <td><strong><?php echo e($pesanan['kode_pemesanan']); ?></strong></td>
                                                    <td><?php echo formatTanggalIndonesia($pesanan['tanggal_kunjungan']); ?></td>
                                                    <td class="text-end"><?php echo formatRupiah($pesanan['total_harga_akhir']); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?php echo getStatusTiketBadgeClass($pesanan['status']); ?>">
                                                            <?php echo e(ucfirst(str_replace('_', ' ', $pesanan['status']))); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatTanggalIndonesia($pesanan['created_at'], true); ?></td>
                                                    <td>
                                                        <?php $detail_tiket_url = BASE_URL . '/user/detail_pemesanan_tiket_user.php?kode=' . e($pesanan['kode_pemesanan']); ?>
                                                        <a href="<?php echo e($detail_tiket_url); ?>" class="btn btn-info btn-sm" title="Lihat Detail Tiket">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3">Anda belum memiliki riwayat pemesanan tiket.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- End Riwayat Tiket -->

            <!-- Riwayat Pemesanan Sewa Alat -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2>Riwayat Sewa Alat</h2>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle"> <!-- Tambah align-middle -->
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID Sewa</th>
                                            <th>Nama Alat</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Tgl Mulai</th>
                                            <th>Tgl Akhir</th>
                                            <th class="text-center">Status</th>
                                            <th>Tgl Pesan</th>
                                            <!-- <th>Aksi</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($riwayatPemesananSewa)): ?>
                                            <?php foreach ($riwayatPemesananSewa as $sewa): ?>
                                                <tr>
                                                    <td><?php echo e($sewa['id']); ?></td>
                                                    <td><?php echo e($sewa['nama_alat'] ?? 'N/A'); ?></td>
                                                    <td class="text-center"><?php echo e($sewa['jumlah']); ?></td>
                                                    <td><?php echo formatTanggalIndonesia($sewa['tanggal_mulai_sewa'], true); ?></td>
                                                    <td><?php echo formatTanggalIndonesia($sewa['tanggal_akhir_sewa_rencana'], true); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?php echo getSewaStatusBadgeClass($sewa['status_item_sewa']); ?>">
                                                            <?php echo e($sewa['status_item_sewa']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatTanggalIndonesia($sewa['created_at'], true); ?></td>
                                                    <!-- Aksi detail sewa bisa ditambahkan jika diperlukan -->
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-3">Anda belum memiliki riwayat sewa alat.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- End Riwayat Sewa -->

        </div>
    </section><!-- End Riwayat Section -->

</main><!-- End #main -->

<?php
// Pastikan fungsi helper ada di includes/helpers.php
if (!function_exists('getStatusTiketBadgeClass')) {
    function getStatusTiketBadgeClass($status)
    { /*...*/
    }
}
if (!function_exists('getSewaStatusBadgeClass')) {
    function getSewaStatusBadgeClass($status)
    { /*...*/
    }
}

include_once __DIR__ . '/../template/footer.php';
?>