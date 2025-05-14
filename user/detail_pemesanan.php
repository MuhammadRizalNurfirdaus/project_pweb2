<?php
require_once __DIR__ . '/../config/config.php';

// Pastikan PemesananTiketController ada, karena itu yang akan kita gunakan
if (!class_exists('PemesananTiketController')) {
    error_log("KRITIS detail_pemesanan.php: PemesananTiketController tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen detail pemesanan tidak dapat dimuat (PTCON_NF).');
    redirect('user/dashboard.php');
    exit;
}
// Model Pembayaran juga dibutuhkan jika PemesananTiketController::getPemesananLengkapByKode menggunakannya
if (!class_exists('Pembayaran')) {
    error_log("KRITIS detail_pemesanan.php: Model Pembayaran tidak ditemukan.");
    // Handle error, mungkin redirect atau pesan
}


require_login();
$current_user_id = get_current_user_id();

$kode_pemesanan_url = input('kode', 'GET');

if (empty($kode_pemesanan_url)) {
    set_flash_message('warning', 'Kode pemesanan tidak disertakan.');
    redirect('user/dashboard.php');
    exit;
}

// ---- PERUBAHAN PANGGILAN CONTROLLER ----
// Panggil metode dari PemesananTiketController yang mengambil berdasarkan KODE PEMESANAN
$pemesanan = PemesananTiketController::getPemesananLengkapByKode($kode_pemesanan_url);

if (!$pemesanan || !isset($pemesanan['header']) || empty($pemesanan['header'])) {
    set_flash_message('danger', 'Detail pemesanan dengan kode ' . e($kode_pemesanan_url) . ' tidak ditemukan atau data tidak lengkap.');
    redirect('user/dashboard.php');
    exit;
}

// Pastikan pemesanan ini milik user yang sedang login (kecuali admin)
if (!is_admin() && (int)$pemesanan['header']['user_id'] !== (int)$current_user_id) {
    set_flash_message('danger', 'Anda tidak memiliki akses untuk melihat detail pemesanan ini.');
    redirect('user/dashboard.php');
    exit;
}

$header = $pemesanan['header'];
$detail_tiket = $pemesanan['detail_tiket'] ?? [];
$detail_sewa = $pemesanan['detail_sewa'] ?? [];
$pembayaran = $pemesanan['pembayaran'] ?? null;  // Ini adalah data pembayaran dari Pembayaran::findByKodePemesanan

$page_title = "Detail Pemesanan: " . e($header['kode_pemesanan']);

if (!defined('VIEWS_PATH')) {
    define('VIEWS_PATH', __DIR__ . '/../template');
}
include_once VIEWS_PATH . '/header_user.php';
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <h1 class="mb-0"><i class="fas fa-receipt me-2"></i>Detail Pemesanan</h1>
                    <p class="text-muted">Kode Pemesanan: <strong><?= e($header['kode_pemesanan']) ?></strong></p>
                </div>
                <div class="col-sm-4 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= e(USER_URL . 'dashboard.php') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-tachometer-alt me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?= display_flash_message(); ?>

        <div class="row">
            <!-- Kolom Informasi Pemesanan -->
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Ringkasan Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Tanggal Pemesanan:</strong> <?= e(formatTanggalIndonesia($header['created_at'], true)) ?></p>
                        <p><strong>Tanggal Kunjungan:</strong> <?= e(formatTanggalIndonesia($header['tanggal_kunjungan'])) ?></p>
                        <p><strong>Pemesan:</strong> <?= e($header['user_nama_lengkap'] ?: ($header['nama_pemesan_tamu'] ?? 'N/A')) ?></p>
                        <p><strong>Email:</strong> <?= e($header['user_email'] ?: ($header['email_pemesan_tamu'] ?? 'N/A')) ?></p>
                        <p><strong>No. HP:</strong> <?= e($header['user_no_hp'] ?: ($header['nohp_pemesan_tamu'] ?? 'N/A')) ?></p>
                        <?php if (!empty($header['catatan_umum_pemesanan'])): ?>
                            <p><strong>Catatan Pemesanan:</strong> <?= nl2br(e($header['catatan_umum_pemesanan'])) ?></p>
                        <?php endif; ?>

                        <hr>
                        <h6>Item Tiket:</h6>
                        <?php if (!empty($detail_tiket)): ?>
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($detail_tiket as $item_t): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                        <span class="me-2">
                                            <?= e($item_t['nama_layanan_display'] ?? 'Tiket') ?> (<?= e($item_t['tipe_hari'] ?? '-') ?>)
                                            <small class="d-block text-muted">Jumlah: <?= e($item_t['jumlah']) ?> x @ <?= e(formatRupiah($item_t['harga_satuan_saat_pesan'])) ?></small>
                                        </span>
                                        <strong><?= e(formatRupiah($item_t['subtotal_item'])) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada item tiket.</p>
                        <?php endif; ?>

                        <?php if (!empty($detail_sewa)): ?>
                            <hr>
                            <h6>Item Sewa Alat:</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($detail_sewa as $item_s): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                        <span class="me-2">
                                            <?= e($item_s['nama_alat'] ?? 'Alat Sewa') ?>
                                            <small class="d-block text-muted">Jumlah: <?= e($item_s['jumlah']) ?> x @ <?= e(formatRupiah($item_s['harga_satuan_saat_pesan'])) ?> per <?= e($item_s['durasi_satuan_saat_pesan'] . ' ' . $item_s['satuan_durasi_saat_pesan']) ?></small>
                                            <small class="d-block text-muted">Periode: <?= e(formatTanggalIndonesia($item_s['tanggal_mulai_sewa'], true)) ?> s/d <?= e(formatTanggalIndonesia($item_s['tanggal_akhir_sewa_rencana'], true)) ?></small>
                                            <?php if (!empty($item_s['catatan_item_sewa'])): ?>
                                                <small class="d-block text-info">Catatan: <?= e($item_s['catatan_item_sewa']) ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <strong><?= e(formatRupiah($item_s['total_harga_item'])) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <hr class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Total Pembayaran:</h5>
                            <h4 class="text-success"><strong><?= e(formatRupiah($header['total_harga_akhir'])) ?></strong></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Informasi Pembayaran dan Aksi -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Status & Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status Pesanan:</strong>
                            <span class="badge bg-<?= e(getStatusBadgeClass($header['status'])) ?> text-white p-2">
                                <?= e(ucfirst(str_replace('_', ' ', $header['status']))) ?>
                            </span>
                        </p>

                        <?php if ($pembayaran): ?>
                            <p><strong>Status Pembayaran:</strong>
                                <span class="badge bg-<?= e(getStatusBadgeClass($pembayaran['status_pembayaran'])) ?> text-white p-2">
                                    <?= e(ucfirst(str_replace('_', ' ', $pembayaran['status_pembayaran']))) ?>
                                </span>
                            </p>
                            <p><strong>Metode Pembayaran:</strong> <?= e($pembayaran['metode_pembayaran'] ?? 'Belum ditentukan') ?></p>
                            <?php if ($pembayaran['waktu_pembayaran']): ?>
                                <p><strong>Waktu Pembayaran:</strong> <?= e(formatTanggalIndonesia($pembayaran['waktu_pembayaran'], true)) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                                <?php
                                $bukti_path_fisik = defined('UPLOADS_BUKTI_PEMBAYARAN_PATH') ? UPLOADS_BUKTI_PEMBAYARAN_PATH . DIRECTORY_SEPARATOR . basename($pembayaran['bukti_pembayaran']) : '';
                                $bukti_url = defined('UPLOADS_BUKTI_PEMBAYARAN_URL') ? UPLOADS_BUKTI_PEMBAYARAN_URL . basename($pembayaran['bukti_pembayaran']) : '';
                                ?>
                                <p><strong>Bukti Pembayaran:</strong>
                                    <?php if (file_exists($bukti_path_fisik) && !empty($bukti_url)): ?>
                                        <a href="<?= e($bukti_url) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> Lihat Bukti
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">File bukti tidak dapat diakses. (Nama file: <?= e($pembayaran['bukti_pembayaran']) ?>)</span>
                                        <?php error_log("Bukti pembayaran tidak ditemukan di path fisik: " . $bukti_path_fisik . " untuk pemesanan " . $header['kode_pemesanan']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($pembayaran['nomor_virtual_account'])): ?>
                                <p><strong>Nomor Virtual Account:</strong> <kbd><?= e($pembayaran['nomor_virtual_account']) ?></kbd></p>
                            <?php endif; ?>
                            <?php if (!empty($pembayaran['id_transaksi_gateway'])): ?>
                                <p><strong>ID Transaksi Gateway:</strong> <?= e($pembayaran['id_transaksi_gateway']) ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><strong>Status Pembayaran:</strong>
                                <span class="badge bg-warning text-dark p-2">
                                    Menunggu Pembayaran
                                </span>
                            </p>
                            <p class="text-muted">Belum ada informasi pembayaran untuk pesanan ini.</p>
                        <?php endif; ?>

                        <hr>

                        <?php
                        // Konstanta Status dari Model Pemesanan dan Pembayaran
                        // Pastikan konstanta ini didefinisikan di model masing-masing
                        $status_pemesanan_pending = defined('Pemesanan::STATUS_PENDING') ? Pemesanan::STATUS_PENDING : 'pending';
                        $status_pemesanan_waiting_payment = defined('Pemesanan::STATUS_WAITING_PAYMENT') ? Pemesanan::STATUS_WAITING_PAYMENT : 'waiting_payment';
                        $status_pemesanan_confirmed = defined('Pemesanan::STATUS_CONFIRMED') ? Pemesanan::STATUS_CONFIRMED : 'confirmed';
                        $status_pemesanan_paid = defined('Pemesanan::STATUS_PAID') ? Pemesanan::STATUS_PAID : 'paid';
                        $status_pemesanan_awaiting_confirmation = defined('Pemesanan::STATUS_AWAITING_CONFIRMATION') ? Pemesanan::STATUS_AWAITING_CONFIRMATION : 'awaiting_confirmation';
                        $status_pemesanan_cancelled = defined('Pemesanan::STATUS_CANCELLED') ? Pemesanan::STATUS_CANCELLED : 'cancelled';
                        $status_pemesanan_expired = defined('Pemesanan::STATUS_EXPIRED') ? Pemesanan::STATUS_EXPIRED : 'expired';
                        $status_pemesanan_failed = defined('Pemesanan::STATUS_FAILED') ? Pemesanan::STATUS_FAILED : 'failed';

                        $status_pembayaran_pending = defined('Pembayaran::STATUS_PENDING') ? Pembayaran::STATUS_PENDING : 'pending';
                        $status_pembayaran_waiting_payment = defined('Pembayaran::STATUS_WAITING_PAYMENT') ? Pembayaran::STATUS_WAITING_PAYMENT : 'waiting_payment';
                        $status_pembayaran_successful = defined('Pembayaran::SUCCESSFUL_PAYMENT_STATUSES') ? Pembayaran::SUCCESSFUL_PAYMENT_STATUSES : ['paid', 'confirmed', 'success'];
                        $status_pembayaran_awaiting_confirmation = defined('Pembayaran::STATUS_AWAITING_CONFIRMATION') ? Pembayaran::STATUS_AWAITING_CONFIRMATION : 'awaiting_confirmation';
                        $status_pembayaran_failed_group = defined('Pembayaran::STATUS_FAILED') ? [Pembayaran::STATUS_FAILED, Pembayaran::STATUS_CANCELLED, Pembayaran::STATUS_EXPIRED] : ['failed', 'cancelled', 'expired'];

                        $allow_konfirmasi = false;
                        if ($header['status'] === $status_pemesanan_pending || $header['status'] === $status_pemesanan_waiting_payment) {
                            if (!$pembayaran || ($pembayaran && in_array($pembayaran['status_pembayaran'], [$status_pembayaran_pending, $status_pembayaran_waiting_payment]))) {
                                $allow_konfirmasi = true;
                            }
                        }

                        $sudah_lunas_atau_dikonfirmasi = in_array($header['status'], [$status_pemesanan_confirmed, $status_pemesanan_paid]) ||
                            ($pembayaran && count(array_intersect($status_pembayaran_successful, [$pembayaran['status_pembayaran']])) > 0);

                        $sedang_dicek = ($header['status'] === $status_pemesanan_awaiting_confirmation) ||
                            ($pembayaran && $pembayaran['status_pembayaran'] === $status_pembayaran_awaiting_confirmation);

                        $gagal_atau_batal = in_array($header['status'], [$status_pemesanan_cancelled, $status_pemesanan_expired, $status_pemesanan_failed]) ||
                            ($pembayaran && count(array_intersect($status_pembayaran_failed_group, [$pembayaran['status_pembayaran']])) > 0);
                        ?>

                        <?php if ($allow_konfirmasi): ?>
                            <h6 class="mt-3">Instruksi Pembayaran (Transfer Manual)</h6>
                            <p>Silakan lakukan pembayaran sejumlah <strong><?= e(formatRupiah($header['total_harga_akhir'])) ?></strong> ke rekening berikut:</p>
                            <ul class="list-unstyled">
                                <li><strong>Bank XYZ:</strong> 123-456-7890 (a.n. Lembah Cilengkrang)</li>
                                <li><strong>Bank ABC:</strong> 098-765-4321 (a.n. Yayasan Wisata Cilengkrang)</li>
                            </ul>
                            <p>Setelah melakukan pembayaran, mohon unggah bukti transfer Anda di bawah ini.</p>

                            <form action="<?= e(USER_URL . 'proses_konfirmasi_pembayaran.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation mt-3" novalidate>
                                <?= generate_csrf_token_input(); ?>
                                <input type="hidden" name="kode_pemesanan" value="<?= e($header['kode_pemesanan']) ?>">
                                <input type="hidden" name="pembayaran_id" value="<?= e($pembayaran['id'] ?? '') ?>">

                                <div class="mb-3">
                                    <label for="metode_pembayaran" class="form-label">Metode Pembayaran Anda <span class="text-danger">*</span></label>
                                    <select name="metode_pembayaran" id="metode_pembayaran" class="form-select" required>
                                        <option value="">-- Pilih Bank Tujuan Transfer --</option>
                                        <option value="Transfer Bank XYZ">Transfer Bank XYZ</option>
                                        <option value="Transfer Bank ABC">Transfer Bank ABC</option>
                                        <option value="Lainnya">Lainnya (Sebutkan di Catatan)</option>
                                    </select>
                                    <div class="invalid-feedback">Silakan pilih metode pembayaran.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="bukti_pembayaran" class="form-label">Unggah Bukti Transfer <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/jpeg, image/png, application/pdf" required>
                                    <small class="form-text text-muted">Format: JPG, PNG, PDF. Maks: 2MB.</small>
                                    <div class="invalid-feedback">Silakan unggah bukti pembayaran.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="catatan_user" class="form-label">Catatan Tambahan <span class="text-muted">(Opsional)</span></label>
                                    <textarea name="catatan_user" id="catatan_user" class="form-control" rows="2" placeholder="Misal: transfer dari rekening a.n. Budi"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check-circle me-1"></i> Konfirmasi Pembayaran
                                </button>
                            </form>
                        <?php elseif ($sudah_lunas_atau_dikonfirmasi): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                Pembayaran untuk pesanan ini telah berhasil dan dikonfirmasi.
                            </div>
                            <?php if (in_array(strtolower($header['status']), [$status_pemesanan_confirmed, $status_pemesanan_paid])): ?>
                                <div class="text-center mt-3">
                                    <a href="<?= e(USER_URL . 'cetak_tiket.php?kode=' . $header['kode_pemesanan']) ?>" class="btn btn-info">
                                        <i class="fas fa-ticket-alt me-1"></i> Lihat/Cetak Tiket
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($sedang_dicek): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-hourglass-half fa-2x mb-2"></i><br>
                                Konfirmasi pembayaran Anda sedang kami proses. Mohon tunggu.
                            </div>
                        <?php elseif ($gagal_atau_batal): ?>
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-times-circle fa-2x mb-2"></i><br>
                                Pembayaran untuk pesanan ini <?= e(ucfirst(str_replace('_', ' ', $pembayaran ? $pembayaran['status_pembayaran'] : $header['status']))) ?>.
                                <?php if (in_array($header['status'], [$status_pemesanan_expired, $status_pemesanan_failed, $status_pemesanan_cancelled])): ?>
                                    Silakan buat pemesanan baru jika ingin melanjutkan.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-info">Tidak ada aksi pembayaran yang diperlukan untuk status pesanan saat ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
include_once VIEWS_PATH . '/footer.php';
?>
<script>
    // Script validasi Bootstrap dasar
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>