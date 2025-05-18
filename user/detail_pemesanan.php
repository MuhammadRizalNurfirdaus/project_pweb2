<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\detail_pemesanan.php

// Aktifkan ini hanya untuk debugging jika Anda tidak melihat error apa pun di log server
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php'; // Harusnya sudah memuat semua yang dibutuhkan

// Pengecekan fungsi krusial dari auth_helpers.php
if (!function_exists('require_login') || !function_exists('get_current_user_id') || !function_exists('is_admin')) {
    error_log("FATAL detail_pemesanan.php: Fungsi autentikasi penting hilang. Pastikan auth_helpers.php dimuat dengan benar oleh config.php.");
    exit("Kesalahan konfigurasi sistem: Fungsi autentikasi penting hilang. (ERR_AUTH_MISSING_DP)");
}

// Pengecekan controller utama
if (!class_exists('PemesananTiketController')) {
    error_log("KRITIS detail_pemesanan.php: PemesananTiketController tidak ditemukan. Pastikan dimuat di config.php.");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem: Komponen detail pemesanan tidak dapat dimuat (ERR_PTC_NF_DP).');
        redirect('user/dashboard.php');
    } else {
        exit("Kesalahan sistem: Komponen detail pemesanan tidak dapat dimuat (ERR_PTC_NF_NOREDIR_DP).");
    }
}

require_login(); // Memastikan pengguna sudah login
$current_user_id = get_current_user_id();

// Ambil kode pemesanan dari URL
$kode_pemesanan_url = input('kode', null, 'GET'); // Menggunakan helper input, default null

if (empty($kode_pemesanan_url)) {
    set_flash_message('warning', 'Kode pemesanan tidak disertakan atau tidak valid.');
    // Jika admin, mungkin redirect ke halaman kelola pemesanan, jika user ke dashboard
    redirect(is_admin() ? ADMIN_URL . 'pemesanan_tiket/kelola_pemesanan.php' : USER_URL . 'riwayat_pemesanan.php');
    exit;
}

// Ambil detail pemesanan lengkap menggunakan Controller
$pemesanan = PemesananTiketController::getPemesananLengkapByKode($kode_pemesanan_url);

// Pengecekan yang lebih robust untuk $pemesanan dan $pemesanan['header']
if (
    !$pemesanan || // Jika $pemesanan adalah null, false, atau array kosong
    !is_array($pemesanan) ||
    !isset($pemesanan['header']) ||
    !is_array($pemesanan['header']) || // Pastikan 'header' adalah array
    empty($pemesanan['header']['id'])    // Pastikan 'id' di dalam header ada dan tidak kosong
) {
    error_log("Detail pemesanan tidak ditemukan atau data header tidak valid untuk kode: " . e($kode_pemesanan_url) . ". Hasil dari PemesananTiketController::getPemesananLengkapByKode(): " . print_r($pemesanan, true));
    set_flash_message('danger', 'Detail pemesanan dengan kode ' . e($kode_pemesanan_url) . ' tidak ditemukan atau data tidak lengkap.');
    redirect(is_admin() ? ADMIN_URL . 'pemesanan_tiket/kelola_pemesanan.php' : USER_URL . 'riwayat_pemesanan.php');
    exit;
}

// Jika lolos pengecekan di atas, $pemesanan['header'] pasti array dan memiliki 'id'
$header = $pemesanan['header'];

// Verifikasi kepemilikan (kecuali jika admin)
if (!is_admin() && (int)($header['user_id'] ?? 0) !== (int)$current_user_id) {
    set_flash_message('danger', 'Anda tidak memiliki akses untuk melihat detail pemesanan ini.');
    redirect('user/dashboard.php'); // User hanya boleh lihat miliknya
    exit;
}

// Ekstrak data lain dengan aman menggunakan null coalescing operator
$detail_tiket = $pemesanan['detail_tiket'] ?? [];
$detail_sewa = $pemesanan['detail_sewa'] ?? [];
$pembayaran = $pemesanan['pembayaran'] ?? null; // Bisa null jika belum ada pembayaran

$page_title = "Detail Pemesanan: " . e($header['kode_pemesanan'] ?? 'Tidak Diketahui');

// Gunakan VIEWS_PATH yang didefinisikan di config.php
$header_template_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . (is_admin() ? '/header_admin.php' : '/header_user.php');
if (file_exists($header_template_path)) {
    include_once $header_template_path;
} else {
    error_log("FATAL detail_pemesanan.php: File header (" . basename($header_template_path) . ") tidak ditemukan di '{$header_template_path}'.");
    exit("Kesalahan tampilan: Komponen header tidak ditemukan.");
}
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <h1 class="mb-0"><i class="fas fa-receipt me-2"></i>Detail Pemesanan</h1>
                    <p class="text-muted">Kode Pemesanan: <strong><?= e($header['kode_pemesanan'] ?? 'N/A') ?></strong></p>
                </div>
                <div class="col-sm-4 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= e(is_admin() ? ADMIN_URL . 'pemesanan_tiket/kelola_pemesanan.php' : USER_URL . 'riwayat_pemesanan.php') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
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
                        <p><strong>Tanggal Pemesanan:</strong> <?= e(formatTanggalIndonesia($header['created_at'] ?? null, true)) ?></p>
                        <p><strong>Tanggal Kunjungan:</strong> <?= e(formatTanggalIndonesia($header['tanggal_kunjungan'] ?? null)) ?></p>
                        <p><strong>Pemesan:</strong> <?= e($header['user_nama_lengkap'] ?? ($header['nama_pemesan_tamu'] ?? 'N/A')) ?></p>
                        <p><strong>Email:</strong> <?= e($header['user_email'] ?? ($header['email_pemesan_tamu'] ?? 'N/A')) ?></p>
                        <p><strong>No. HP:</strong> <?= e($header['user_no_hp'] ?? ($header['nohp_pemesan_tamu'] ?? 'N/A')) ?></p>
                        <?php if (isset($header['catatan_umum_pemesanan']) && $header['catatan_umum_pemesanan'] !== '' && $header['catatan_umum_pemesanan'] !== null): ?>
                            <p><strong>Catatan Pemesanan:</strong> <?= nl2br(e($header['catatan_umum_pemesanan'])) ?></p>
                        <?php endif; ?>

                        <hr>
                        <h6>Item Tiket:</h6>
                        <?php if (!empty($detail_tiket) && is_array($detail_tiket)): ?>
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($detail_tiket as $item_t): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                        <span class="me-2">
                                            <?= e($item_t['nama_layanan_display'] ?? 'Tiket') ?> (<?= e($item_t['tipe_hari'] ?? '-') ?>)
                                            <small class="d-block text-muted">Jumlah: <?= e($item_t['jumlah'] ?? 0) ?> x @ <?= e(formatRupiah($item_t['harga_satuan_saat_pesan'] ?? 0)) ?></small>
                                        </span>
                                        <strong><?= e(formatRupiah($item_t['subtotal_item'] ?? 0)) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada item tiket.</p>
                        <?php endif; ?>

                        <?php if (!empty($detail_sewa) && is_array($detail_sewa)): ?>
                            <hr>
                            <h6>Item Sewa Alat:</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($detail_sewa as $item_s): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                        <span class="me-2">
                                            <?= e($item_s['nama_alat'] ?? 'Alat Sewa') ?>
                                            <small class="d-block text-muted">Jumlah: <?= e($item_s['jumlah'] ?? 0) ?> x @ <?= e(formatRupiah($item_s['harga_satuan_saat_pesan'] ?? 0)) ?> per <?= e(($item_s['durasi_satuan_saat_pesan'] ?? 1) . ' ' . ($item_s['satuan_durasi_saat_pesan'] ?? 'Unit')) ?></small>
                                            <small class="d-block text-muted">Periode: <?= e(formatTanggalIndonesia($item_s['tanggal_mulai_sewa'] ?? null, true)) ?> s/d <?= e(formatTanggalIndonesia($item_s['tanggal_akhir_sewa_rencana'] ?? null, true)) ?></small>
                                            <?php if (isset($item_s['catatan_item_sewa']) && $item_s['catatan_item_sewa'] !== '' && $item_s['catatan_item_sewa'] !== null): ?>
                                                <small class="d-block text-info">Catatan: <?= e($item_s['catatan_item_sewa']) ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <strong><?= e(formatRupiah($item_s['total_harga_item'] ?? 0)) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <hr class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Total Pembayaran:</h5>
                            <h4 class="text-success"><strong><?= e(formatRupiah($header['total_harga_akhir'] ?? 0)) ?></strong></h4>
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
                            <?= getStatusBadgeClassHTML($header['status'] ?? 'tidak diketahui', 'Status Tidak Diketahui') ?>
                        </p>

                        <?php if ($pembayaran && is_array($pembayaran)):
                        ?>
                            <p><strong>Status Pembayaran:</strong>
                                <?= getStatusBadgeClassHTML($pembayaran['status_pembayaran'] ?? 'tidak diketahui', 'Status Pembayaran Tidak Diketahui') ?>
                            </p>
                            <p><strong>Metode Pembayaran:</strong> <?= e($pembayaran['metode_pembayaran'] ?? 'Belum ditentukan') ?></p>
                            <?php if (!empty($pembayaran['waktu_pembayaran'])): ?>
                                <p><strong>Waktu Pembayaran:</strong> <?= e(formatTanggalIndonesia($pembayaran['waktu_pembayaran'], true)) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                                <?php
                                $bukti_file_name = basename($pembayaran['bukti_pembayaran']);
                                $bukti_path_fisik = defined('UPLOADS_BUKTI_PEMBAYARAN_PATH') ? UPLOADS_BUKTI_PEMBAYARAN_PATH . DIRECTORY_SEPARATOR . $bukti_file_name : '';
                                $bukti_url = defined('UPLOADS_BUKTI_PEMBAYARAN_URL') ? UPLOADS_BUKTI_PEMBAYARAN_URL . $bukti_file_name : '';
                                ?>
                                <p><strong>Bukti Pembayaran:</strong>
                                    <?php if (!empty($bukti_url) && file_exists($bukti_path_fisik)): ?>
                                        <a href="<?= e($bukti_url) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> Lihat Bukti
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">File bukti tidak dapat diakses.</span>
                                        <?php error_log("Bukti pembayaran tidak ditemukan atau URL/Path salah. Path Fisik: " . $bukti_path_fisik . ", URL: " . $bukti_url . " untuk pemesanan " . e($header['kode_pemesanan'] ?? 'N/A')); ?>
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
                                <?= getStatusBadgeClassHTML('pending', 'Menunggu Pembayaran') ?>
                            </p>
                            <p class="text-muted">Belum ada informasi pembayaran untuk pesanan ini.</p>
                        <?php endif; ?>

                        <hr>

                        <?php
                        $status_header_lower = strtolower($header['status'] ?? 'pending');
                        $status_pembayaran_lower = ($pembayaran && isset($pembayaran['status_pembayaran'])) ? strtolower($pembayaran['status_pembayaran']) : 'pending';

                        $successful_payment_statuses = (class_exists('Pembayaran') && defined('Pembayaran::SUCCESSFUL_PAYMENT_STATUSES'))
                            ? Pembayaran::SUCCESSFUL_PAYMENT_STATUSES
                            : ['paid', 'success', 'confirmed'];

                        $allow_konfirmasi_user = false;
                        if (
                            in_array($status_header_lower, ['pending', 'waiting_payment']) &&
                            in_array($status_pembayaran_lower, ['pending', 'waiting_payment']) &&
                            !is_admin()
                        ) {
                            $allow_konfirmasi_user = true;
                        }

                        $sudah_lunas_atau_dikonfirmasi_oleh_admin = in_array($status_header_lower, ['paid', 'confirmed', 'completed']) ||
                            (isset($pembayaran['status_pembayaran']) && in_array(strtolower($pembayaran['status_pembayaran']), $successful_payment_statuses));

                        $menunggu_konfirmasi_admin = $status_header_lower === 'awaiting_confirmation' ||
                            (isset($pembayaran['status_pembayaran']) && strtolower($pembayaran['status_pembayaran']) === 'awaiting_confirmation');

                        $gagal_atau_batal = in_array($status_header_lower, ['cancelled', 'expired', 'failed']) ||
                            (isset($pembayaran['status_pembayaran']) && in_array(strtolower($pembayaran['status_pembayaran']), ['failed', 'cancelled', 'expired']));
                        ?>

                        <?php if ($allow_konfirmasi_user): ?>
                            <h6 class="mt-3">Instruksi Pembayaran (Transfer Manual)</h6>
                            <p>Silakan lakukan pembayaran sejumlah <strong><?= e(formatRupiah($header['total_harga_akhir'] ?? 0)) ?></strong> ke salah satu rekening berikut:</p>
                            <ul class="list-unstyled">
                                <li><strong>Bank BRI:</strong> 1122334455 (a.n. Lembah Cilengkrang Wisata)</li>
                                <li><strong>Bank BNI46:</strong> 6677889900 (a.n. Pengelola Cilengkrang)</li>
                                <!-- Anda bisa menambahkan Bank XYZ dan ABC jika masih relevan -->
                                <!-- <li><strong>Bank XYZ:</strong> 123-456-7890 (a.n. Lembah Cilengkrang)</li> -->
                                <!-- <li><strong>Bank ABC:</strong> 098-765-4321 (a.n. Yayasan Wisata Cilengkrang)</li> -->
                            </ul>
                            <p>Setelah melakukan pembayaran, mohon unggah bukti transfer Anda di bawah ini.</p>

                            <form action="<?= e(USER_URL . 'proses_konfirmasi_pembayaran.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation mt-3" novalidate>
                                <?= generate_csrf_token_input(); ?>
                                <input type="hidden" name="kode_pemesanan" value="<?= e($header['kode_pemesanan'] ?? '') ?>">
                                <input type="hidden" name="pembayaran_id" value="<?= e($pembayaran['id'] ?? '') ?>">
                                <input type="hidden" name="pemesanan_tiket_id" value="<?= e($header['id'] ?? '') ?>">

                                <div class="mb-3">
                                    <label for="metode_pembayaran" class="form-label">Bank Tujuan Transfer Anda <span class="text-danger">*</span></label>
                                    <select name="metode_pembayaran" id="metode_pembayaran" class="form-select" required>
                                        <option value="">-- Pilih Bank Tujuan --</option>
                                        <option value="Transfer Bank BRI">Transfer Bank BRI</option>
                                        <option value="Transfer Bank BNI">Transfer Bank BNI46</option>
                                        <!-- <option value="Transfer Bank XYZ">Transfer Bank XYZ</option> -->
                                        <!-- <option value="Transfer Bank ABC">Transfer Bank ABC</option> -->
                                        <option value="Lainnya">Bank Lainnya (Sebutkan di Catatan)</option>
                                    </select>
                                    <div class="invalid-feedback">Silakan pilih bank tujuan transfer Anda.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="bukti_pembayaran" class="form-label">Unggah Bukti Transfer <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/jpeg,image/png,application/pdf" required>
                                    <small class="form-text text-muted">Format: JPG, PNG, PDF. Maks: 2MB.</small>
                                    <div class="invalid-feedback">Silakan unggah bukti pembayaran.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="catatan_user" class="form-label">Catatan Tambahan <span class="text-muted">(Opsional)</span></label>
                                    <textarea name="catatan_user" id="catatan_user" class="form-control" rows="2" placeholder="Misal: transfer dari rekening a.n. Budi ke Bank BRI"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-paper-plane me-1"></i> Kirim Konfirmasi Pembayaran
                                </button>
                            </form>
                        <?php elseif ($menunggu_konfirmasi_admin): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-hourglass-half fa-2x mb-2"></i><br>
                                Terima kasih! Konfirmasi pembayaran Anda telah kami terima dan sedang menunggu verifikasi oleh Administrator.
                                Anda akan dihubungi atau status pesanan akan diperbarui setelah diverifikasi. Silakan cek <a href="<?= e(USER_URL . 'riwayat_pemesanan.php') ?>" class="alert-link">Riwayat Pemesanan</a> Anda secara berkala.
                            </div>
                        <?php elseif ($sudah_lunas_atau_dikonfirmasi_oleh_admin): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                Pembayaran untuk pesanan ini telah berhasil dan dikonfirmasi.
                            </div>
                            <?php if (in_array($status_header_lower, ['paid', 'confirmed', 'completed']) && !empty($header['kode_pemesanan']) && !is_admin()): ?>
                                <div class="text-center mt-3">
                                    <a href="<?= e(USER_URL . 'cetak_tiket.php?kode=' . urlencode($header['kode_pemesanan'])) ?>" class="btn btn-info" target="_blank">
                                        <i class="fas fa-ticket-alt me-1"></i> Lihat/Cetak Tiket
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($gagal_atau_batal): ?>
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-times-circle fa-2x mb-2"></i><br>
                                Pemesanan ini <?= e(ucfirst(str_replace(['_'], ' ', $status_header_lower))) ?>.
                                <?php if ($pembayaran && isset($pembayaran['status_pembayaran']) && $pembayaran['status_pembayaran'] !== $status_header_lower): ?>
                                    Status Pembayaran: <?= e(ucfirst(str_replace(['_'], ' ', $status_pembayaran_lower))) ?>.
                                <?php endif; ?>
                                <?php if (in_array($status_header_lower, ['expired', 'failed', 'cancelled'])): ?>
                                    Silakan buat pemesanan baru jika ingin melanjutkan.
                                <?php endif; ?>
                            </div>
                        <?php else: // Kondisi default atau admin melihat halaman ini
                        ?>
                            <p class="text-info">
                                <?php if (is_admin()): ?>
                                    Admin dapat mengelola pembayaran ini melalui <a href="<?= e(ADMIN_URL . 'pembayaran/kelola_pembayaran.php?kode_pemesanan=' . urlencode($header['kode_pemesanan'] ?? '')) ?>">Kelola Pembayaran</a>.
                                <?php else: ?>
                                    Tidak ada aksi pembayaran yang diperlukan untuk status pesanan saat ini.
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$footer_template_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . (is_admin() ? '/footer_admin.php' : '/footer_user.php');
if (file_exists($footer_template_path)) {
    include_once $footer_template_path;
} else {
    $fallback_footer_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . '/footer.php';
    if (file_exists($fallback_footer_path)) {
        error_log("PERINGATAN detail_pemesanan.php: File footer (" . basename($footer_template_path) . ") tidak ditemukan, menggunakan footer.php sebagai fallback.");
        include_once $fallback_footer_path;
    } else {
        error_log("FATAL detail_pemesanan.php: File footer (" . basename($footer_template_path) . ") dan footer.php tidak ditemukan.");
    }
}
?>
<script>
    (function() {
        'use strict'
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
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