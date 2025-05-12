<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\detail_pemesanan.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/pemesanan_tiket/detail_pemesanan.php");
    exit("Kesalahan konfigurasi server. Tidak dapat memuat file penting.");
}

// 2. Panggil fungsi otentikasi admin
require_admin();

// 3. Sertakan Controller PemesananTiket
if (!require_once __DIR__ . '/../../controllers/PemesananTiketController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat controllers/PemesananTiketController.php");
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket tidak dapat dimuat.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php'); // Redirect ke kelola jika controller gagal
}

// 4. Set judul halaman
$pageTitle = "Detail Pemesanan Tiket";

// 5. Validasi ID dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    set_flash_message('danger', 'ID Pemesanan Tiket tidak valid atau tidak ditemukan.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}
$pemesanan_id = (int)$_GET['id'];

// 6. Ambil semua data lengkap untuk pemesanan ini menggunakan metode statis dari Controller
// Metode getDetailPemesananLengkap() di Controller akan memanggil metode statis dari Model-model terkait
$data_pemesanan_lengkap = null;
if (class_exists('PemesananTiketController') && method_exists('PemesananTiketController', 'getDetailPemesananLengkap')) {
    try {
        $data_pemesanan_lengkap = PemesananTiketController::getDetailPemesananLengkap($pemesanan_id);
    } catch (Throwable $e) {
        error_log("Error mengambil detail pemesanan lengkap (ID: {$pemesanan_id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
        set_flash_message('danger', 'Gagal memuat detail pemesanan. Error: ' . $e->getMessage());
        // Jangan redirect di sini dulu, biarkan header dimuat agar flash message bisa tampil
    }
} else {
    error_log("Controller PemesananTiketController atau method getDetailPemesananLengkap tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen detail pemesanan tidak tersedia.');
}


// 7. Sertakan header admin (Setelah semua data diambil atau error ditangani)
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php.");
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px;'>Error Kritis: Gagal memuat komponen header halaman admin.</p>";
    exit; // Exit jika header gagal, karena halaman tidak akan tampil benar
}


// 8. Cek apakah data pemesanan berhasil diambil setelah header dimuat
if (!$data_pemesanan_lengkap || empty($data_pemesanan_lengkap['header'])) {
    // Flash message sudah di-set sebelumnya jika ada error pengambilan data
    // Redirect sekarang aman karena header sudah dimuat
    if (!isset($_SESSION['flash_message'])) { // Jika belum ada flash message spesifik
        set_flash_message('danger', 'Data pemesanan tiket tidak ditemukan atau tidak dapat dimuat.');
    }
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}

$header_pemesanan = $data_pemesanan_lengkap['header'];
$detail_tiket_items = $data_pemesanan_lengkap['detail_tiket'] ?? []; // Default ke array kosong
$detail_sewa_items = $data_pemesanan_lengkap['detail_sewa'] ?? [];   // Default ke array kosong
$info_pembayaran = $data_pemesanan_lengkap['pembayaran'] ?? null;   // Bisa null
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/kelola_pemesanan.php"><i class="fas fa-ticket-alt"></i> Kelola Pemesanan Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-eye"></i> Detail Pemesanan: <?= e($header_pemesanan['kode_pemesanan'] ?? 'N/A') ?></li>
    </ol>
</nav>

<?php display_flash_message(); ?>

<div class="row">
    <!-- Kolom Kiri: Detail Pemesanan Utama & Item Tiket/Sewa -->
    <div class="col-lg-8">
        <!-- Card Detail Pemesan & Pemesanan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Detail Pemesanan #<?= e($header_pemesanan['kode_pemesanan'] ?? 'N/A') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h6>Informasi Pemesan:</h6>
                        <p class="mb-1">
                            <strong>Nama:</strong>
                            <?php if (!empty($header_pemesanan['user_id']) && !empty($header_pemesanan['user_nama'])): ?>
                                <?= e($header_pemesanan['user_nama']) ?> (ID User: <?= e($header_pemesanan['user_id']) ?>)
                            <?php elseif (!empty($header_pemesanan['nama_pemesan_tamu'])): ?>
                                <?= e($header_pemesanan['nama_pemesan_tamu']) ?> <span class="badge bg-secondary">Tamu</span>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($header_pemesanan['user_id']) && !empty($header_pemesanan['user_email'])): ?>
                            <p class="mb-1"><i class="fas fa-envelope fa-fw me-1 text-muted"></i><?= e($header_pemesanan['user_email']) ?></p>
                        <?php elseif (!empty($header_pemesanan['email_pemesan_tamu'])): ?>
                            <p class="mb-1"><i class="fas fa-envelope fa-fw me-1 text-muted"></i><?= e($header_pemesanan['email_pemesan_tamu']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($header_pemesanan['nohp_pemesan_tamu']) && empty($header_pemesanan['user_id'])): // Hanya tampilkan nohp tamu jika bukan user terdaftar 
                        ?>
                            <p class="mb-0"><i class="fas fa-phone fa-fw me-1 text-muted"></i><?= e($header_pemesanan['nohp_pemesan_tamu']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Detail Kunjungan & Pesanan:</h6>
                        <p class="mb-1"><strong>Tanggal Kunjungan:</strong> <?= e(formatTanggalIndonesia($header_pemesanan['tanggal_kunjungan'] ?? '', false)) ?></p>
                        <p class="mb-1"><strong>Tanggal Pesan:</strong> <?= e(formatTanggalIndonesia($header_pemesanan['created_at'] ?? '', true)) ?></p>
                        <p class="mb-1"><strong>Status Pemesanan:</strong>
                            <?php
                            // PERBAIKAN DI SINI: Gunakan key 'status'
                            $status_pemesanan_raw = $header_pemesanan['status'] ?? 'unknown';
                            $status_pemesanan_text = ucfirst(str_replace('_', ' ', $status_pemesanan_raw));
                            $status_class = 'bg-secondary'; // Default
                            switch (strtolower($status_pemesanan_raw)) {
                                case 'pending':
                                    $status_class = 'bg-warning text-dark';
                                    break;
                                case 'waiting_payment':
                                    $status_class = 'bg-info text-dark';
                                    break;
                                case 'paid':
                                    $status_class = 'bg-primary';
                                    break;
                                case 'confirmed':
                                    $status_class = 'bg-success';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-dark';
                                    break;
                                case 'cancelled':
                                    $status_class = 'bg-danger';
                                    break;
                                case 'expired':
                                    $status_class = 'bg-light text-dark border';
                                    break;
                            }
                            ?>
                            <span class="badge <?= $status_class ?>"><?= e($status_pemesanan_text) ?></span>
                        </p>
                        <p class="mb-0"><strong>Total Harga:</strong> <strong class="text-success fs-5"><?= formatRupiah($header_pemesanan['total_harga_akhir'] ?? 0) ?></strong></p>
                    </div>
                </div>
                <?php if (!empty($header_pemesanan['catatan_umum_pemesanan'])): ?>
                    <hr class="my-3">
                    <h6>Catatan dari Pemesan:</h6>
                    <p class="text-muted fst-italic mb-0"><em><?= nl2br(e($header_pemesanan['catatan_umum_pemesanan'])) ?></em></p>
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
                    <table class="table table-sm table-striped table-hover mb-0 align-middle">
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
                                        <?= e($item_t['nama_layanan_display'] ?? $item_t['jenis_tiket_nama'] ?? 'N/A') ?>
                                        <?= isset($item_t['tipe_hari']) ? ' (' . e($item_t['tipe_hari']) . ')' : '' ?>
                                        <?php if (!empty($item_t['nama_wisata_terkait'])): ?>
                                            <br><small class="text-muted">Destinasi: <?= e($item_t['nama_wisata_terkait']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= e($item_t['jumlah'] ?? 0) ?></td>
                                    <td class="text-end"><?= formatRupiah($item_t['harga_satuan_saat_pesan'] ?? 0) ?></td>
                                    <td class="text-end fw-bold"><?= formatRupiah($item_t['subtotal_item'] ?? 0) ?></td>
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
                    <table class="table table-sm table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Alat</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Harga/Durasi</th>
                                <th>Periode Sewa</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Status Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_sewa_items as $item_s): ?>
                                <tr>
                                    <td><?= e($item_s['nama_alat'] ?? $item_s['nama_item'] ?? 'N/A') ?></td>
                                    <td class="text-center"><?= e($item_s['jumlah'] ?? 0) ?></td>
                                    <td class="text-end"><?= formatRupiah($item_s['harga_satuan_saat_pesan'] ?? 0) ?> / <?= e($item_s['durasi_satuan_saat_pesan'] ?? 1) ?> <?= e($item_s['satuan_durasi_saat_pesan'] ?? 'Pcs') ?></td>
                                    <td>
                                        <small>Mulai: <?= e(formatTanggalIndonesia($item_s['tanggal_mulai_sewa'] ?? '', true)) ?></small><br>
                                        <small>Sampai: <?= e(formatTanggalIndonesia($item_s['tanggal_akhir_sewa_rencana'] ?? '', true)) ?></small>
                                    </td>
                                    <td class="text-end fw-bold"><?= formatRupiah($item_s['total_harga_item'] ?? 0) ?></td>
                                    <td class="text-center"><span class="badge bg-info"><?= e(ucfirst($item_s['status_item_sewa'] ?? 'N/A')) ?></span></td>
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
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Informasi Pembayaran</h5>
            </div>
            <div class="card-body">
                <?php if ($info_pembayaran): ?>
                    <p class="mb-1"><strong>Metode:</strong> <?= e($info_pembayaran['metode_pembayaran'] ?? 'Belum Dipilih') ?></p>
                    <p class="mb-1"><strong>Jumlah Harus Dibayar:</strong> <?= formatRupiah($header_pemesanan['total_harga_akhir'] ?? 0) ?></p>
                    <p class="mb-1"><strong>Jumlah Sudah Dibayar:</strong> <?= formatRupiah($info_pembayaran['jumlah_dibayar'] ?? 0) ?></p>
                    <p class="mb-1"><strong>Status Pembayaran:</strong>
                        <?php
                        $status_bayar_raw = $info_pembayaran['status_pembayaran'] ?? 'unknown';
                        $status_bayar_text = ucfirst(str_replace('_', ' ', $status_bayar_raw));
                        $status_bayar_class = 'bg-secondary';
                        switch (strtolower($status_bayar_raw)) {
                            case 'pending':
                                $status_bayar_class = 'bg-warning text-dark';
                                break;
                            case 'awaiting_confirmation':
                                $status_bayar_class = 'bg-info text-dark';
                                break;
                            case 'success':
                            case 'paid':
                            case 'confirmed':
                                $status_bayar_class = 'bg-success';
                                break;
                            case 'failed':
                            case 'expired':
                            case 'refunded':
                                $status_bayar_class = 'bg-danger';
                                break;
                        }
                        ?>
                        <span class="badge <?= $status_bayar_class ?>"><?= e($status_bayar_text) ?></span>
                    </p>
                    <p class="mb-1"><strong>Waktu Pembayaran:</strong> <?= !empty($info_pembayaran['waktu_pembayaran']) ? e(formatTanggalIndonesia($info_pembayaran['waktu_pembayaran'], true)) : 'Belum ada' ?></p>
                    <?php if (!empty($info_pembayaran['bukti_pembayaran'])): ?>
                        <p class="mb-1"><strong>Bukti Bayar:</strong> <a href="<?= e(BASE_URL . 'public/uploads/bukti_pembayaran/' . $info_pembayaran['bukti_pembayaran']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-1"><i class="fas fa-receipt"></i> Lihat Bukti</a></p>
                    <?php endif; ?>
                    <?php if (!empty($info_pembayaran['id_transaksi_gateway'])): ?>
                        <p class="mb-0"><strong>ID Transaksi Gateway:</strong> <?= e($info_pembayaran['id_transaksi_gateway']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Belum ada informasi pembayaran untuk pemesanan ini.</p>
                <?php endif; ?>
                <hr class="my-3">
                <h6>Update Status Pembayaran:</h6>
                <form action="<?= e(ADMIN_URL) ?>/pemesanan_tiket/proses_pemesanan.php" method="POST">
                    <input type="hidden" name="pemesanan_id" value="<?= e($pemesanan_id) ?>">
                    <input type="hidden" name="pembayaran_id" value="<?= e($info_pembayaran['id'] ?? '') ?>">
                    <input type="hidden" name="action" value="update_status_pembayaran">
                    <div class="mb-2">
                        <label for="status_pembayaran_baru" class="form-label sr-only">Status Pembayaran Baru:</label>
                        <select class="form-select form-select-sm" id="status_pembayaran_baru" name="status_pembayaran_baru" required>
                            <?php $opsi_status_bayar = ['pending', 'awaiting_confirmation', 'success', 'failed', 'expired', 'refunded']; ?>
                            <?php foreach ($opsi_status_bayar as $opsi_sb): ?>
                                <option value="<?= e($opsi_sb) ?>" <?= (($info_pembayaran['status_pembayaran'] ?? '') == $opsi_sb) ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $opsi_sb))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info btn-sm w-100"><i class="fas fa-sync-alt me-2"></i>Update Status Bayar</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Update Status Pemesanan Utama</h6>
            </div>
            <div class="card-body">
                <form action="<?= e(ADMIN_URL) ?>/pemesanan_tiket/proses_pemesanan.php" method="POST">
                    <input type="hidden" name="pemesanan_id" value="<?= e($pemesanan_id) ?>">
                    <input type="hidden" name="action" value="update_status_pemesanan">
                    <div class="mb-2">
                        <label for="status_pemesanan_baru_header" class="form-label sr-only">Status Pemesanan Baru:</label>
                        <select class="form-select form-select-sm" id="status_pemesanan_baru_header" name="status_pemesanan_baru" required>
                            <?php $opsi_status_pesan = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired']; ?>
                            <?php foreach ($opsi_status_pesan as $opsi_sp): ?>
                                <option value="<?= e($opsi_sp) ?>" <?= (($header_pemesanan['status'] ?? '') == $opsi_sp) ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $opsi_sp))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-2"></i>Update Status Pesanan</button>
                </form>
            </div>
        </div>
        <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/kelola_pemesanan.php" class="btn btn-secondary w-100"><i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar</a>
    </div>
</div>

<?php
include_once __DIR__ . '/../../template/footer_admin.php';
?>