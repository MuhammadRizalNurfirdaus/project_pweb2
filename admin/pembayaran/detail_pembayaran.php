<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\detail_pembayaran.php

if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/pembayaran/detail_pembayaran.php");
    exit("Kesalahan konfigurasi server.");
}

require_admin();

$pageTitle = "Detail Pembayaran";

if (!include_once VIEWS_PATH . '/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat header_admin.php dari detail_pembayaran.php.");
    exit("Error Kritis: Header admin tidak dapat dimuat.");
}

$pembayaran_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$pembayaran_id || $pembayaran_id <= 0) {
    set_flash_message('danger', 'ID Pembayaran tidak valid atau tidak disertakan.');
    redirect(ADMIN_URL . '/pembayaran/kelola_pembayaran.php'); // Asumsi ada halaman kelola pembayaran
    exit;
}

$data_lengkap = null;
if (class_exists('PembayaranController') && method_exists('PembayaranController', 'getDetailPembayaranLengkap')) {
    $data_lengkap = PembayaranController::getDetailPembayaranLengkap($pembayaran_id);
} else {
    set_flash_message('danger', 'Kesalahan sistem: Komponen untuk mengambil detail pembayaran tidak tersedia.');
    redirect(ADMIN_URL . '/pembayaran/kelola_pembayaran.php');
    exit;
}

if (!$data_lengkap || empty($data_lengkap['pembayaran'])) {
    set_flash_message('danger', 'Data pembayaran untuk ID #' . e($pembayaran_id) . ' tidak ditemukan.');
    redirect(ADMIN_URL . '/pembayaran/kelola_pembayaran.php');
    exit;
}

$info_pembayaran = $data_lengkap['pembayaran'];
$pemesanan_detail = $data_lengkap['pemesanan_detail']; // Ini berisi 'header', 'detail_tiket', 'detail_sewa'
$header_pemesanan = $pemesanan_detail['header'] ?? null;
$detail_tiket_items = $pemesanan_detail['detail_tiket'] ?? [];
$detail_sewa_items = $pemesanan_detail['detail_sewa'] ?? [];

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/pembayaran/kelola_pembayaran.php"><i class="fas fa-credit-card"></i> Kelola Pembayaran</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-eye"></i> Detail Pembayaran #<?= e($info_pembayaran['id']) ?></li>
    </ol>
</nav>

<?php display_flash_message(); ?>

<div class="row">
    <!-- Kolom Kiri: Detail Pembayaran & Update Status Pembayaran -->
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0"><i class="fas fa-money-check-alt me-2"></i>Detail Pembayaran #<?= e($info_pembayaran['id']) ?></h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>ID Pemesanan Terkait:</strong>
                    <?php if ($header_pemesanan): ?>
                        <a href="<?= e(ADMIN_URL . '/pemesanan_tiket/detail_pemesanan.php?id=' . $header_pemesanan['id']) ?>"><?= e($header_pemesanan['kode_pemesanan']) ?> (ID: <?= e($header_pemesanan['id']) ?>)</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </p>
                <p class="mb-1"><strong>Metode Pembayaran:</strong> <?= e($info_pembayaran['metode_pembayaran'] ?? 'N/A') ?></p>
                <p class="mb-1"><strong>Jumlah Dibayar:</strong> <?= formatRupiah($info_pembayaran['jumlah_dibayar'] ?? 0) ?></p>
                <p class="mb-1"><strong>Status Pembayaran:</strong> <?= getStatusBadgeClassHTML($info_pembayaran['status_pembayaran'] ?? 'unknown') ?></p>
                <p class="mb-1"><strong>Waktu Pembayaran:</strong> <?= !empty($info_pembayaran['waktu_pembayaran']) ? e(formatTanggalIndonesia($info_pembayaran['waktu_pembayaran'], true)) : 'Belum ada' ?></p>

                <?php if (!empty($info_pembayaran['bukti_pembayaran'])): ?>
                    <p class="mb-1"><strong>Bukti Pembayaran:</strong>
                        <a href="<?= e(ASSETS_URL . '/uploads/bukti_pembayaran/' . $info_pembayaran['bukti_pembayaran']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1">
                            <i class="fas fa-receipt me-1"></i>Lihat Bukti
                        </a>
                    </p>
                <?php endif; ?>
                <p class="mb-1"><strong>ID Transaksi Gateway:</strong> <?= e($info_pembayaran['id_transaksi_gateway'] ?? 'N/A') ?></p>
                <p class="mb-0"><strong>Nomor Virtual Account:</strong> <?= e($info_pembayaran['nomor_virtual_account'] ?? 'N/A') ?></p>
                <hr>
                <h6>Update Status Pembayaran:</h6>
                <form action="<?= e(ADMIN_URL) ?>/pembayaran/proses_update_status_pembayaran.php" method="POST"> <!-- Arahkan ke controller/handler pembayaran -->
                    <?= generate_csrf_token_input() ?>
                    <input type="hidden" name="pembayaran_id" value="<?= e($info_pembayaran['id']) ?>">
                    <input type="hidden" name="pemesanan_id" value="<?= e($header_pemesanan['id'] ?? '') ?>"> <!-- Untuk redirect dan update pemesanan jika perlu -->
                    <input type="hidden" name="redirect_url" value="<?= e(ADMIN_URL . '/pembayaran/detail_pembayaran.php?id=' . $info_pembayaran['id']) ?>">

                    <div class="mb-2">
                        <label for="status_pembayaran_baru" class="form-label">Status Pembayaran Baru:</label>
                        <select class="form-select form-select-sm" id="status_pembayaran_baru" name="new_payment_status" required>
                            <?php $opsi_status_bayar = Pembayaran::ALLOWED_STATUSES ?? ['pending', 'awaiting_confirmation', 'success', 'paid', 'confirmed', 'failed', 'expired', 'refunded', 'cancelled']; ?>
                            <?php foreach ($opsi_status_bayar as $opsi_sb): ?>
                                <option value="<?= e($opsi_sb) ?>" <?= (($info_pembayaran['status_pembayaran'] ?? '') == $opsi_sb) ? 'selected' : '' ?>>
                                    <?= e(ucfirst(str_replace('_', ' ', $opsi_sb))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="jumlah_dibayar_update" class="form-label">Jumlah Dibayar (jika status success/paid):</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="jumlah_dibayar_update" name="jumlah_dibayar_update" value="<?= e(floatval($info_pembayaran['jumlah_dibayar'] ?? 0)) ?>">
                    </div>
                    <button type="submit" name="submit_update_payment_status" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-sync-alt me-2"></i>Update Status Pembayaran
                    </button>
                </form>
            </div>
        </div>
        <div class="d-grid gap-2">
            <a href="<?= e(ADMIN_URL) ?>/pembayaran/kelola_pembayaran.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Pembayaran</a>
            <a href="<?= e(ADMIN_URL) ?>/pembayaran/cetak_pembayaran.php?id=<?= e($info_pembayaran['id']) ?>" target="_blank" class="btn btn-info"><i class="fas fa-print me-2"></i>Cetak Bukti Pembayaran</a>
        </div>
    </div>

    <!-- Kolom Kanan: Detail Pemesanan Terkait -->
    <div class="col-lg-7">
        <?php if ($header_pemesanan): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Detail Pemesanan Terkait #<?= e($header_pemesanan['kode_pemesanan']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6>Informasi Pemesan:</h6>
                            <p class="mb-1"><strong>Nama:</strong> <?= e($header_pemesanan['user_nama_lengkap'] ?? $header_pemesanan['nama_pemesan_tamu'] ?? 'N/A') ?></p>
                            <p class="mb-1"><i class="fas fa-envelope fa-fw me-1 text-muted"></i> <?= e($header_pemesanan['user_email'] ?? $header_pemesanan['email_pemesan_tamu'] ?? 'N/A') ?></p>
                            <p class="mb-0"><i class="fas fa-phone fa-fw me-1 text-muted"></i> <?= e($header_pemesanan['user_no_hp'] ?? $header_pemesanan['nohp_pemesan_tamu'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Detail Kunjungan & Pesanan:</h6>
                            <p class="mb-1"><strong>Tgl. Kunjungan:</strong> <?= e(formatTanggalIndonesia($header_pemesanan['tanggal_kunjungan'] ?? null, false, true)) ?></p>
                            <p class="mb-1"><strong>Tgl. Pesan:</strong> <?= e(formatTanggalIndonesia($header_pemesanan['created_at'] ?? null, true)) ?></p>
                            <p class="mb-1"><strong>Status Pesanan:</strong> <?= getStatusBadgeClassHTML($header_pemesanan['status'] ?? 'unknown') ?></p>
                            <p class="mb-0"><strong>Total Tagihan:</strong> <strong class="text-success fs-5"><?= formatRupiah($header_pemesanan['total_harga_akhir'] ?? 0) ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($detail_tiket_items)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Item Tiket</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Jenis Tiket</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detail_tiket_items as $item_t): ?>
                                        <tr>
                                            <td><?= e($item_t['nama_layanan_display'] ?? 'N/A') ?> <small class="text-muted">(<?= e($item_t['tipe_hari'] ?? '-') ?>)</small></td>
                                            <td class="text-center"><?= e($item_t['jumlah'] ?? 0) ?></td>
                                            <td class="text-end"><?= formatRupiah($item_t['subtotal_item'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($detail_sewa_items)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Item Sewa Alat</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Alat</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detail_sewa_items as $item_s): ?>
                                        <tr>
                                            <td><?= e($item_s['nama_alat'] ?? 'N/A') ?></td>
                                            <td class="text-center"><?= e($item_s['jumlah'] ?? 0) ?></td>
                                            <td class="text-end"><?= formatRupiah($item_s['total_harga_item'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Detail pemesanan tiket terkait tidak ditemukan atau tidak dapat dimuat.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
if (!include_once VIEWS_PATH . '/footer_admin.php') {
    error_log("PERINGATAN: Gagal memuat footer_admin.php dari detail_pembayaran.php.");
}
?>