<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\detail_pemesanan.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers, konstanta, dll)
// config.php sudah menjalankan session_start() di awal.
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503); // Service Unavailable
    $logMessage = "FATAL: Gagal memuat config.php dari admin/pemesanan_tiket/detail_pemesanan.php";
    error_log($logMessage);
    $displayMessage = "Kesalahan konfigurasi server. Aplikasi tidak dapat memuat file penting.";
    if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
        $displayMessage .= " Detail: Gagal memuat config.php.";
    }
    exit($displayMessage); // Keluar secepatnya
}

// 2. Panggil fungsi otentikasi admin
// Asumsikan require_admin() akan redirect dan exit jika tidak terotentikasi.
require_admin();

// 3. Sertakan Controller PemesananTiket
// CONTROLLERS_PATH sudah didefinisikan di config.php
// PemesananTiketController.php juga sudah di-require_once di config.php bagian 7.b
// jadi pengecekan file dan require ulang di sini tidak krusial, tapi bisa untuk keamanan ganda.
if (!class_exists('PemesananTiketController')) {
    http_response_code(500);
    error_log("FATAL: Class PemesananTiketController tidak ditemukan setelah config.php dimuat. Path: " . (defined('CONTROLLERS_PATH') ? CONTROLLERS_PATH . '/PemesananTiketController.php' : 'Tidak diketahui'));
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket inti tidak dapat ditemukan.');
    redirect(ADMIN_URL . '/dashboard.php'); // Redirect ke halaman yang lebih aman
    exit;
}


// 4. Set judul halaman untuk template header
$pageTitle = "Detail Pemesanan Tiket";

// 5. Validasi ID Pemesanan dari parameter URL
$pemesanan_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$pemesanan_id || $pemesanan_id <= 0) {
    set_flash_message('danger', 'ID Pemesanan Tiket tidak valid atau tidak disertakan.');
    redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
    exit;
}

// 6. Ambil semua data lengkap untuk pemesanan ini
$data_pemesanan_lengkap = null;
$error_saat_ambil_data = null;

// Class PemesananTiketController sudah pasti ada karena dicek di atas (dan dimuat di config.php)
if (method_exists('PemesananTiketController', 'getDetailPemesananLengkap')) {
    try {
        $data_pemesanan_lengkap = PemesananTiketController::getDetailPemesananLengkap($pemesanan_id);
    } catch (Throwable $e) { // Menangkap semua jenis error/exception
        $error_saat_ambil_data = 'Terjadi kesalahan saat memuat detail pemesanan.';
        if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
            $error_saat_ambil_data .= ' Detail: ' . $e->getMessage();
        }
        error_log("Error mengambil detail pemesanan lengkap (ID: {$pemesanan_id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
} else {
    $error_saat_ambil_data = 'Kesalahan sistem: Fungsi untuk mengambil detail pemesanan tidak tersedia.';
    error_log("FATAL: Metode getDetailPemesananLengkap tidak ditemukan di PemesananTiketController. (ID Pemesanan: {$pemesanan_id})");
}


// 7. Sertakan header admin
// header_admin.php diharapkan memanggil display_flash_message() di dalamnya.
// VIEWS_PATH (alias dari TEMPLATE_PATH di config Anda) sudah didefinisikan di config.php
$header_admin_path = VIEWS_PATH . '/header_admin.php';
if (!file_exists($header_admin_path)) {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari detail_pemesanan.php. Path: " . $header_admin_path);
    // Tidak bisa menggunakan set_flash_message atau redirect jika header gagal, tampilkan pesan error dasar
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px; background-color: #fff0f0; border: 1px solid red;'>Error Kritis: Gagal memuat komponen header halaman admin. Halaman tidak dapat ditampilkan.</p>";
    exit;
}
require_once $header_admin_path; // Ini akan mengirim output, termasuk flash message yang mungkin sudah ada

// 8. Tangani error pengambilan data atau jika data tidak ditemukan SETELAH header dimuat
// Jika ada error saat ambil data, pesan akan diset dan redirect.
// Flash message yang diset di sini akan muncul di halaman tujuan redirect (kelola_pemesanan.php)
if ($error_saat_ambil_data) {
    set_flash_message('danger', $error_saat_ambil_data);
    // Menggunakan JavaScript untuk redirect jika header sudah terkirim
    echo "<script>window.location.href='" . e(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php') . "';</script>";
    exit;
}

// Jika tidak ada error, tapi data utama (header pemesanan) kosong
if (!$data_pemesanan_lengkap || empty($data_pemesanan_lengkap['header'])) {
    set_flash_message('danger', 'Data pemesanan tiket untuk ID #' . e($pemesanan_id) . ' tidak ditemukan atau tidak dapat dimuat dengan benar.');
    echo "<script>window.location.href='" . e(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php') . "';</script>";
    exit;
}

// Ekstrak data untuk kemudahan penggunaan di template
$header_pemesanan   = $data_pemesanan_lengkap['header'];
$detail_tiket_items = $data_pemesanan_lengkap['detail_tiket'] ?? [];
$detail_sewa_items  = $data_pemesanan_lengkap['detail_sewa'] ?? [];
$info_pembayaran    = $data_pemesanan_lengkap['pembayaran'] ?? null;

$id_pembayaran_terkait = $info_pembayaran['id'] ?? null;

// Asumsikan helpers.php (dimuat oleh config.php) menyediakan fungsi-fungsi berikut:
// - e($string) : untuk escaping output HTML
// - formatTanggalIndonesia($date, $withTime = false, $withDayName = false)
// - formatRupiah($number)
// - getStatusBadgeClassHTML($status) : untuk status pemesanan & pembayaran
// - getSewaStatusBadgeClassHTML($status) : untuk status item sewa
// - generate_csrf_token_input() : menghasilkan input hidden CSRF token
// - display_flash_message() : dipanggil di header_admin.php

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/kelola_pemesanan.php"><i class="fas fa-ticket-alt"></i> Kelola Pemesanan Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-eye"></i> Detail Pemesanan: <?= e($header_pemesanan['kode_pemesanan'] ?? 'N/A') ?></li>
    </ol>
</nav>

<?php
// display_flash_message(); // Sudah dipanggil di dalam template/header_admin.php berdasarkan asumsi
?>

<div class="row">
    <!-- Kolom Kiri: Detail Pemesanan Utama & Item Tiket/Sewa -->
    <div class="col-lg-8">
        <!-- Card Detail Pemesan & Pemesanan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Detail Pemesanan #<?= e($header_pemesanan['kode_pemesanan'] ?? 'N/A') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h6>Informasi Pemesan:</h6>
                        <p class="mb-1">
                            <strong>Nama:</strong>
                            <?php if (!empty($header_pemesanan['user_id']) && isset($header_pemesanan['user_nama_lengkap'])): ?>
                                <?= e($header_pemesanan['user_nama_lengkap']) ?> (ID User: <?= e($header_pemesanan['user_id']) ?>)
                            <?php elseif (!empty($header_pemesanan['nama_pemesan_tamu'])): ?>
                                <?= e($header_pemesanan['nama_pemesan_tamu']) ?> <span class="badge bg-info text-dark">Tamu</span>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($header_pemesanan['user_id']) && isset($header_pemesanan['user_email'])): ?>
                            <p class="mb-1"><i class="fas fa-envelope fa-fw me-1 text-muted"></i><?= e($header_pemesanan['user_email']) ?></p>
                        <?php elseif (!empty($header_pemesanan['email_pemesan_tamu'])): ?>
                            <p class="mb-1"><i class="fas fa-envelope fa-fw me-1 text-muted"></i><?= e($header_pemesanan['email_pemesan_tamu']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($header_pemesanan['user_id']) && isset($header_pemesanan['user_no_hp'])): ?>
                            <p class="mb-0"><i class="fas fa-phone fa-fw me-1 text-muted"></i><?= e($header_pemesanan['user_no_hp']) ?></p>
                        <?php elseif (!empty($header_pemesanan['nohp_pemesan_tamu'])): ?>
                            <p class="mb-0"><i class="fas fa-phone fa-fw me-1 text-muted"></i><?= e($header_pemesanan['nohp_pemesan_tamu']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Detail Kunjungan & Pesanan:</h6>
                        <p class="mb-1"><strong>Tanggal Kunjungan:</strong> <?= e(formatTanggalIndonesia($header_pemesanan['tanggal_kunjungan'] ?? null, false, true)) ?></p>
                        <p class="mb-1"><strong>Tanggal Pesan:</strong> <?= e(formatTanggalIndonesia($header_pemesanan['created_at'] ?? null, true, true)) ?></p>
                        <p class="mb-1"><strong>Status Pemesanan:</strong>
                            <?php
                            $status_pemesanan_raw = $header_pemesanan['status'] ?? 'unknown'; // Kolom 'status' dari tabel pemesanan_tiket
                            echo getStatusBadgeClassHTML($status_pemesanan_raw);
                            ?>
                        </p>
                        <p class="mb-0"><strong>Total Harga:</strong> <strong class="text-success fs-5"><?= formatRupiah($header_pemesanan['total_harga_akhir'] ?? 0) ?></strong></p>
                    </div>
                </div>
                <?php if (!empty($header_pemesanan['catatan_umum_pemesanan'])): // Sesuaikan nama kolom dari controller jika berbeda, misal 'catatan_pemesanan' atau 'catatan' 
                ?>
                    <hr class="my-3">
                    <h6>Catatan dari Pemesan:</h6>
                    <p class="text-muted fst-italic mb-0"><em><?= nl2br(e($header_pemesanan['catatan_umum_pemesanan'])) ?></em></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Detail Item Tiket -->
        <?php if (!empty($detail_tiket_items) && is_array($detail_tiket_items)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Item Tiket Dipesan</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
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
                                            <?= e($item_t['nama_layanan_display'] ?? $item_t['jenis_tiket_nama'] ?? 'N/A') // Pilih nama yang sesuai dari controller 
                                            ?>
                                            <?= isset($item_t['tipe_hari']) ? ' <small class="text-muted">(' . e($item_t['tipe_hari']) . ')</small>' : '' ?>
                                            <?php if (!empty($item_t['nama_wisata_terkait'])): ?>
                                                <br><small class="text-muted">Destinasi: <?= e($item_t['nama_wisata_terkait']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= e($item_t['jumlah'] ?? 0) ?></td>
                                        <td class="text-end"><?= formatRupiah($item_t['harga_satuan_saat_pesan'] ?? 0) ?></td>
                                        <td class="text-end fw-bold"><?= formatRupiah($item_t['subtotal_item'] ?? ($item_t['jumlah'] * $item_t['harga_satuan_saat_pesan'])) // Fallback subtotal jika tidak ada dari controller 
                                                                        ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Card Detail Item Sewa Alat -->
        <?php if (!empty($detail_sewa_items) && is_array($detail_sewa_items)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Item Alat Disewa</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
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
                                        <td class="text-end"><?= formatRupiah($item_s['harga_satuan_saat_pesan'] ?? 0) ?> / <?= e($item_s['durasi_satuan_saat_pesan'] ?? 1) ?> <?= e($item_s['satuan_durasi_saat_pesan'] ?? 'Unit') ?></td>
                                        <td>
                                            <small>Mulai: <?= e(formatTanggalIndonesia($item_s['tanggal_mulai_sewa'] ?? null, true, true)) ?></small><br>
                                            <small>Sampai: <?= e(formatTanggalIndonesia($item_s['tanggal_akhir_sewa_rencana'] ?? null, true, true)) ?></small>
                                        </td>
                                        <td class="text-end fw-bold"><?= formatRupiah($item_s['total_harga_item'] ?? 0) ?></td>
                                        <td class="text-center">
                                            <?php
                                            $status_sewa_item = $item_s['status_item_sewa'] ?? 'unknown';
                                            echo getSewaStatusBadgeClassHTML($status_sewa_item);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kolom Kanan: Pembayaran & Update Status -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white py-3">
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
                        echo getStatusBadgeClassHTML($status_bayar_raw);
                        ?>
                    </p>
                    <p class="mb-1"><strong>Waktu Pembayaran:</strong> <?= !empty($info_pembayaran['waktu_pembayaran']) ? e(formatTanggalIndonesia($info_pembayaran['waktu_pembayaran'], true, true)) : 'Belum ada' ?></p>
                    <?php if (!empty($info_pembayaran['bukti_pembayaran'])): ?>
                        <p class="mb-1"><strong>Bukti Bayar:</strong>
                            <a href="<?= e(ASSETS_URL . '/uploads/bukti_pembayaran/' . $info_pembayaran['bukti_pembayaran']) // Gunakan ASSETS_URL 
                                        ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1">
                                <i class="fas fa-receipt me-1"></i>Lihat Bukti
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($info_pembayaran['id_transaksi_gateway'])): ?>
                        <p class="mb-0"><strong>ID Transaksi Gateway:</strong> <?= e($info_pembayaran['id_transaksi_gateway']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Belum ada informasi pembayaran untuk pemesanan ini.</p>
                <?php endif; ?>

                <?php if ($id_pembayaran_terkait): // Form update status pembayaran jika ada pembayaran 
                ?>
                    <hr class="my-3">
                    <h6>Update Status Pembayaran:</h6>
                    <form action="<?= e(ADMIN_URL) ?>/pembayaran/proses_update_status_pembayaran.php" method="POST">
                        <?= generate_csrf_token_input() ?>
                        <input type="hidden" name="pemesanan_id" value="<?= e($pemesanan_id) ?>">
                        <input type="hidden" name="pembayaran_id" value="<?= e($id_pembayaran_terkait) ?>">
                        <input type="hidden" name="redirect_url" value="<?= e(ADMIN_URL . '/pemesanan_tiket/detail_pemesanan.php?id=' . $pemesanan_id) ?>">

                        <div class="mb-2">
                            <label for="status_pembayaran_baru" class="form-label visually-hidden">Status Pembayaran Baru:</label>
                            <select class="form-select form-select-sm" id="status_pembayaran_baru" name="new_payment_status" required>
                                <?php $opsi_status_bayar = ['pending', 'awaiting_confirmation', 'success', 'paid', 'confirmed', 'failed', 'expired', 'refunded', 'cancelled']; ?>
                                <?php foreach ($opsi_status_bayar as $opsi_sb): ?>
                                    <option value="<?= e($opsi_sb) ?>" <?= (($info_pembayaran['status_pembayaran'] ?? '') == $opsi_sb) ? 'selected' : '' ?>>
                                        <?= e(ucfirst(str_replace('_', ' ', $opsi_sb))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="submit_update_payment_status" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-sync-alt me-2"></i>Update Status Pembayaran
                        </button>
                    </form>
                <?php elseif (!$info_pembayaran && !in_array(strtolower($header_pemesanan['status'] ?? ''), ['cancelled', 'expired', 'failed', 'refunded', 'completed'])): // Form buat entri pembayaran jika belum ada & status pesanan memungkinkan 
                ?>
                    <hr class="my-3">
                    <p class="text-muted small">Belum ada entri pembayaran. Buat entri manual jika pembayaran diterima di luar sistem.</p>
                    <form action="<?= e(ADMIN_URL) ?>/pembayaran/proses_update_status_pembayaran.php" method="POST">
                        <?= generate_csrf_token_input() ?>
                        <input type="hidden" name="pemesanan_id_for_create_payment" value="<?= e($pemesanan_id) ?>">
                        <input type="hidden" name="action" value="create_manual_payment_entry">
                        <input type="hidden" name="redirect_url" value="<?= e(ADMIN_URL . '/pemesanan_tiket/detail_pemesanan.php?id=' . $pemesanan_id) ?>">
                        <button type="submit" name="submit_create_manual_payment" class="btn btn-outline-success btn-sm w-100" onclick="return confirm('Ini akan membuat entri pembayaran baru dengan status MENUNGGU PEMBAYARAN (pending) untuk pemesanan ini. Lanjutkan?')">
                            <i class="fas fa-plus-circle me-2"></i>Buat Entri Pembayaran Manual
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Update Status Pemesanan Utama</h6>
            </div>
            <div class="card-body">
                <form action="<?= e(ADMIN_URL) ?>/pemesanan_tiket/proses_pemesanan.php" method="POST">
                    <?= generate_csrf_token_input() ?>
                    <input type="hidden" name="pemesanan_id" value="<?= e($pemesanan_id) ?>">
                    <input type="hidden" name="action" value="update_status_pemesanan">
                    <input type="hidden" name="redirect_url" value="<?= e(ADMIN_URL . '/pemesanan_tiket/detail_pemesanan.php?id=' . $pemesanan_id) ?>">
                    <div class="mb-2">
                        <label for="status_pemesanan_baru_header" class="form-label visually-hidden">Status Pemesanan Baru:</label>
                        <select class="form-select form-select-sm" id="status_pemesanan_baru_header" name="new_order_status" required>
                            <?php $opsi_status_pesan = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded']; ?>
                            <?php foreach ($opsi_status_pesan as $opsi_sp): ?>
                                <option value="<?= e($opsi_sp) ?>" <?= (($header_pemesanan['status'] ?? '') == $opsi_sp) ? 'selected' : '' ?>>
                                    <?= e(ucfirst(str_replace('_', ' ', $opsi_sp))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="submit_update_order_status" class="btn btn-success btn-sm w-100">
                        <i class="fas fa-save me-2"></i>Update Status Pesanan
                    </button>
                </form>
            </div>
        </div>
        <div class="d-grid gap-2">
            <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/kelola_pemesanan.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar</a>
            <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/cetak_pesanan_tiket.php?id=<?= e($pemesanan_id) ?>" target="_blank" class="btn btn-info"><i class="fas fa-print me-2"></i>Cetak Detail Pemesanan</a>
        </div>
    </div>
</div>

<?php
// VIEWS_PATH (alias dari TEMPLATE_PATH di config Anda) sudah didefinisikan di config.php
$footer_admin_path = VIEWS_PATH . '/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("PERINGATAN: Gagal memuat template/footer_admin.php dari detail_pemesanan.php. Path: " . $footer_admin_path);
} else {
    require_once $footer_admin_path;
}
?>