<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\kelola_pembayaran.php

require_once __DIR__ . '/../../config/config.php'; // Harus paling atas untuk session & config
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/flash_message.php';
require_once __DIR__ . '/../../controllers/PembayaranController.php';
// Koneksi global $conn sudah tersedia dari config.php

require_admin();

$semuaPembayaran = PembayaranController::getAllPembayaranForAdmin();

$pageTitle = "Kelola Pembayaran";

include_once __DIR__ . '/../../template/header_admin.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-credit-card"></i> Kelola Pembayaran</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Pembayaran</h1>
</div>

<?php display_flash_message(); ?>

<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Pembayaran</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;">ID Bayar</th>
                        <th scope="col" style="width: 15%;">Kode Pemesanan</th>
                        <th scope="col" style="width: 15%;">Metode</th>
                        <th scope="col" style="width: 15%;" class="text-end">Jumlah Dibayar</th>
                        <th scope="col" style="width: 10%;" class="text-center">Status</th>
                        <th scope="col" style="width: 15%;">Waktu Bayar</th>
                        <th scope="col" style="width: 10%;" class="text-center">Bukti</th>
                        <th scope="col" style="width: 15%;">Dibuat</th>
                        <!-- <th scope="col" style="width: 10%;">Aksi</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($semuaPembayaran)): ?>
                        <?php foreach ($semuaPembayaran as $pembayaran): ?>
                            <tr>
                                <td><?php echo e($pembayaran['id']); ?></td>
                                <td>
                                    <?php if (!empty($pembayaran['kode_pemesanan'])): ?>
                                        <a href="<?php echo e(ADMIN_URL); ?>/pemesanan_tiket/detail_pemesanan.php?id=<?php echo e($pembayaran['pemesanan_tiket_id']); ?>" title="Lihat Detail Pemesanan Tiket">
                                            <?php echo e($pembayaran['kode_pemesanan']); ?>
                                        </a>
                                    <?php else: ?>
                                        (ID: <?php echo e($pembayaran['pemesanan_tiket_id']); ?>)
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($pembayaran['metode_pembayaran'] ?? '-'); ?></td>
                                <td class="text-end"><?php echo formatRupiah($pembayaran['jumlah_dibayar']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo getStatusBadgeClass($pembayaran['status_pembayaran']); ?>">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $pembayaran['status_pembayaran']))); ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($pembayaran['waktu_pembayaran']) ? formatTanggalIndonesia($pembayaran['waktu_pembayaran'], true) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                                        <?php $bukti_url = BASE_URL . '/public/uploads/bukti_pembayaran/' . $pembayaran['bukti_pembayaran']; ?>
                                        <a href="<?php echo e($bukti_url); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat Bukti: <?php echo e($pembayaran['bukti_pembayaran']); ?>">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatTanggalIndonesia($pembayaran['created_at'], true); ?></td>
                                <!-- Aksi Konfirmasi mungkin lebih tepat di detail pemesanan -->
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <p class="mb-0 lead">Belum ada data pembayaran.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php
// Pastikan fungsi ini ada di helpers.php atau definisikan di sini jika hanya untuk halaman ini
if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status)
    {
        switch (strtolower($status)) {
            case 'success':
            case 'paid':
            case 'confirmed':
                return 'success';
            case 'pending':
            case 'waiting_payment':
            case 'awaiting_confirmation':
                return 'warning text-dark';
            case 'failed':
            case 'expired':
            case 'cancelled':
            case 'refunded':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}

include_once __DIR__ . '/../../template/footer_admin.php';
?>