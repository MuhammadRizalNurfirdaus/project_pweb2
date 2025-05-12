<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\detail_pemesanan.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PemesananTiketController.php';
// Jika ada controller pembayaran terpisah:
// require_once __DIR__ . '/../../controllers/PembayaranController.php';

// require_admin(); // Pastikan admin sudah login

$page_title = "Detail Pemesanan Tiket";

// Validasi ID dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    set_flash_message('danger', 'ID Pemesanan Tiket tidak valid.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}
$pemesanan_id = (int)$_GET['id'];

// Ambil semua data lengkap untuk pemesanan ini
$data_pemesanan_lengkap = PemesananTiketController::getDetailPemesananLengkap($pemesanan_id);

if (!$data_pemesanan_lengkap || empty($data_pemesanan_lengkap['header'])) {
    set_flash_message('danger', 'Data pemesanan tiket tidak ditemukan.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}

$header_pemesanan = $data_pemesanan_lengkap['header'];
$detail_tiket_items = $data_pemesanan_lengkap['detail_tiket'];
$detail_sewa_items = $data_pemesanan_lengkap['detail_sewa'];
$info_pembayaran = $data_pemesanan_lengkap['pembayaran']; // Bisa null jika belum ada entri pembayaran

include_once __DIR__ . '/../../template/header_admin.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/pemesanan_tiket/kelola_pemesanan.php"><i class="fas fa-ticket-alt"></i> Kelola Pemesanan Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-eye"></i> Detail Pemesanan: <?= e($header_pemesanan['kode_pemesanan']) ?></li>
    </ol>
</nav>

<?php display_flash_message(); ?>

<div class="row">
    <!-- Kolom Kiri: Detail Pemesanan Utama & Item Tiket/Sewa -->
    <div class="col-lg-8">
        <!-- Card Detail Pemesan & Pemesanan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Detail Pemesanan #<?= e($header_pemesanan['kode_pemesanan']) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Pemesan:</h6>
                        <p>
                            <strong>Nama:</strong>
                            <?php if (!empty($header_pemesanan['user_id']) && !empty($header_pemesanan['user_nama'])): ?>
                                <?= e($header_pemesanan['user_nama']) ?> (ID User: <?= e($header_pemesanan['user_id']) ?>)
                                <br><i class="fas fa-envelope fa-fw me-1 text-muted"></i><?= e($header_pemesanan['user_email'] ?? 'Email tidak ada') ?>
                            <?php elseif (!empty($header_pemesanan['nama_pemesan_tamu'])): ?>
                                <?= e($header_pemesanan['nama_pemesan_tamu']) ?> (Tamu)
                                <br><i class="fas fa-envelope fa-fw me-1 text-muted"></i><?= e($header_pemesanan['email_pemesan_tamu'] ?? 'Email tidak ada') ?>
                                <?php if (!empty($header_pemesanan['nohp_pemesan_tamu'])): ?>
                                    <br><i class="fas fa-phone fa-fw me-1 text-muted"></i><?= e($header_pemesanan['nohp_pemesan_tamu']) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Data pemesan tidak lengkap.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Detail Kunjungan & Pesanan:</h6>
                        <p><strong>Tanggal Kunjungan:</strong> <?= e(date('d F Y', strtotime($header_pemesanan['tanggal_kunjungan']))) ?></p>
                        <p><strong>Tanggal Pesan:</strong> <?= e(date('d F Y, H:i:s', strtotime($header_pemesanan['created_at']))) ?></p>
                        <p><strong>Status Pemesanan:</strong>
                            <span class="badge bg-<?= e(strtolower($header_pemesanan['status_pemesanan'])) == 'pending' ? 'warning text-dark' : (strtolower($header_pemesanan['status_pemesanan']) == 'confirmed' || strtolower($header_pemesanan['status_pemesanan']) == 'paid' ? 'success' : 'secondary') ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $header_pemesanan['status_pemesanan']))) ?>
                            </span>
                        </p>
                        <p><strong>Total Harga Keseluruhan:</strong> <strong class="text-success fs-5">Rp <?= e(number_format($header_pemesanan['total_harga_akhir'], 0, ',', '.')) ?></strong></p>
                    </div>
                </div>
                <?php if (!empty($header_pemesanan['catatan_umum_pemesanan'])): ?>
                    <hr>
                    <h6>Catatan dari Pemesan:</h6>
                    <p class="text-muted fst-italic"><em><?= nl2br(e($header_pemesanan['catatan_umum_pemesanan'])) ?></em></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Detail Item Tiket -->
        <?php if (!empty($detail_tiket_items) && is_array($detail_tiket_items)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Item Tiket Dipesan</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jenis Tiket</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_tiket_items as $item_t): ?>
                                <tr>
                                    <td>
                                        <?= e($item_t['nama_layanan_display'] ?? 'N/A') ?> (<?= e($item_t['tipe_hari'] ?? 'N/A') ?>)
                                        <?php if (!empty($item_t['nama_wisata_terkait'])): ?>
                                            <br><small class="text-muted">Destinasi: <?= e($item_t['nama_wisata_terkait']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= e($item_t['jumlah']) ?></td>
                                    <td class="text-end">Rp <?= e(number_format($item_t['harga_satuan_saat_pesan'], 0, ',', '.')) ?></td>
                                    <td class="text-end">Rp <?= e(number_format($item_t['subtotal_item'], 0, ',', '.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Card Detail Item Sewa Alat -->
        <?php if (!empty($detail_sewa_items) && is_array($detail_sewa_items)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Item Alat Disewa</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Alat</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Harga Satuan (saat pesan)</th>
                                <th>Periode Sewa</th>
                                <th class="text-end">Subtotal Sewa</th>
                                <th class="text-center">Status Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_sewa_items as $item_s): ?>
                                <tr>
                                    <td><?= e($item_s['nama_item'] ?? 'N/A') ?></td>
                                    <td class="text-center"><?= e($item_s['jumlah']) ?></td>
                                    <td class="text-end">Rp <?= e(number_format($item_s['harga_satuan_saat_pesan'], 0, ',', '.')) ?></td>
                                    <td>
                                        Mulai: <?= e(date('d M Y, H:i', strtotime($item_s['tanggal_mulai_sewa']))) ?><br>
                                        Sampai: <?= e(date('d M Y, H:i', strtotime($item_s['tanggal_akhir_sewa_rencana']))) ?>
                                    </td>
                                    <td class="text-end">Rp <?= e(number_format($item_s['total_harga_item'], 0, ',', '.')) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= e(ucfirst($item_s['status_item_sewa'])) ?></span>
                                        <!-- Tombol update status item sewa bisa di sini atau di kelola_pemesanan_sewa.php -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kolom Kanan: Pembayaran & Update Status -->
    <div class="col-lg-4">
        <!-- Card Pembayaran -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Informasi Pembayaran</h5>
            </div>
            <div class="card-body">
                <?php if ($info_pembayaran): ?>
                    <p><strong>Metode:</strong> <?= e($info_pembayaran['metode_pembayaran'] ?? 'Belum Dipilih') ?></p>
                    <p><strong>Jumlah Harus Dibayar:</strong> Rp <?= e(number_format($header_pemesanan['total_harga_akhir'], 0, ',', '.')) ?></p>
                    <p><strong>Jumlah Dibayar:</strong> Rp <?= e(number_format($info_pembayaran['jumlah_dibayar'], 0, ',', '.')) ?></p>
                    <p><strong>Status Pembayaran:</strong>
                        <span class="badge bg-<?= e(strtolower($info_pembayaran['status_pembayaran']) == 'pending' ? 'warning text-dark' : (strtolower($info_pembayaran['status_pembayaran']) == 'success' ? 'success' : 'danger')) ?>">
                            <?= e(ucfirst($info_pembayaran['status_pembayaran'])) ?>
                        </span>
                    </p>
                    <p><strong>Waktu Pembayaran:</strong> <?= !empty($info_pembayaran['waktu_pembayaran']) ? e(date('d M Y, H:i', strtotime($info_pembayaran['waktu_pembayaran']))) : 'Belum ada' ?></p>
                    <?php if (!empty($info_pembayaran['bukti_pembayaran'])): ?>
                        <p><strong>Bukti Bayar:</strong> <a href="<?= e($base_url . 'public/uploads/bukti_pembayaran/' . $info_pembayaran['bukti_pembayaran']) ?>" target="_blank">Lihat Bukti</a></p>
                    <?php endif; ?>
                    <?php if (!empty($info_pembayaran['id_transaksi_gateway'])): ?>
                        <p><strong>ID Transaksi Gateway:</strong> <?= e($info_pembayaran['id_transaksi_gateway']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Belum ada informasi pembayaran untuk pemesanan ini.</p>
                <?php endif; ?>
                <hr>
                <h6>Update Status Pembayaran:</h6>
                <form action="<?= e($base_url) ?>admin/pemesanan_tiket/proses_pemesanan.php" method="POST"> <!-- Atau ke proses_update_status_pembayaran.php -->
                    <input type="hidden" name="pemesanan_id" value="<?= e($pemesanan_id) ?>">
                    <input type="hidden" name="pembayaran_id" value="<?= e($info_pembayaran['id'] ?? '') ?>">
                    <input type="hidden" name="action" value="update_status_pembayaran">
                    <div class="mb-3">
                        <label for="status_pembayaran_baru" class="form-label">Status Pembayaran Baru:</label>
                        <select class="form-select" id="status_pembayaran_baru" name="status_pembayaran_baru" required>
                            <option value="pending" <?= (($info_pembayaran['status_pembayaran'] ?? '') == 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="awaiting_confirmation" <?= (($info_pembayaran['status_pembayaran'] ?? '') == 'awaiting_confirmation') ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                            <option value="success" <?= (($info_pembayaran['status_pembayaran'] ?? '') == 'success') ? 'selected' : '' ?>>Success (Lunas)</option>
                            <option value="failed" <?= (($info_pembayaran['status_pembayaran'] ?? '') == 'failed') ? 'selected' : '' ?>>Failed (Gagal)</option>
                            <option value="expired" <?= (($info_pembayaran['status_pembayaran'] ?? '') == 'expired') ? 'selected' : '' ?>>Expired (Kadaluarsa)</option>
                            <option value="refunded" <?= (($info_pembayaran['status_pembayaran'] ?? '') == 'refunded') ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info btn-sm w-100"><i class="fas fa-sync-alt me-2"></i>Update Status Bayar</button>
                </form>
            </div>
        </div>

        <!-- Card Update Status Pemesanan Utama -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Update Status Pemesanan Utama</h6>
            </div>
            <div class="card-body">
                <form action="<?= e($base_url) ?>admin/pemesanan_tiket/proses_pemesanan.php" method="POST"> <!-- Atau ke proses_update_status_pemesanan_tiket.php -->
                    <input type="hidden" name="pemesanan_id" value="<?= e($pemesanan_id) ?>">
                    <input type="hidden" name="action" value="update_status_pemesanan">
                    <div class="mb-3">
                        <label for="status_pemesanan_baru" class="form-label">Status Pemesanan Baru:</label>
                        <select class="form-select" id="status_pemesanan_baru" name="status_pemesanan_baru" required>
                            <option value="pending" <?= ($header_pemesanan['status_pemesanan'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="waiting_payment" <?= ($header_pemesanan['status_pemesanan'] == 'waiting_payment') ? 'selected' : '' ?>>Waiting Payment</option>
                            <option value="paid" <?= ($header_pemesanan['status_pemesanan'] == 'paid') ? 'selected' : '' ?>>Paid (Sudah Bayar)</option>
                            <option value="confirmed" <?= ($header_pemesanan['status_pemesanan'] == 'confirmed') ? 'selected' : '' ?>>Confirmed (Terkonfirmasi)</option>
                            <option value="completed" <?= ($header_pemesanan['status_pemesanan'] == 'completed') ? 'selected' : '' ?>>Completed (Selesai Kunjungan)</option>
                            <option value="cancelled" <?= ($header_pemesanan['status_pemesanan'] == 'cancelled') ? 'selected' : '' ?>>Cancelled (Dibatalkan)</option>
                            <option value="expired" <?= ($header_pemesanan['status_pemesanan'] == 'expired') ? 'selected' : '' ?>>Expired (Kadaluarsa)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-2"></i>Update Status Pesanan</button>
                </form>
            </div>
        </div>
        <a href="<?= e($base_url) ?>admin/pemesanan_tiket/kelola_pemesanan.php" class="btn btn-secondary w-100"><i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Pemesanan</a>
    </div>
</div>


<?php
include_once __DIR__ . '/../../template/footer_admin.php';
?>