<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\riwayat_pemesanan.php

require_once __DIR__ . '/../config/config.php';

// Pastikan komponen yang dibutuhkan sudah dimuat oleh config.php
if (!function_exists('require_login') || !function_exists('get_current_user_id')) {
    error_log("FATAL riwayat_pemesanan.php: Fungsi autentikasi penting hilang.");
    exit("Kesalahan konfigurasi sistem: Fungsi autentikasi penting hilang. (ERR_AUTH_MISSING_RW)");
}
// Pengecekan Model PemesananTiket
if (!class_exists('PemesananTiket') || !method_exists('PemesananTiket', 'getByUserId')) {
    error_log("KRITIS riwayat_pemesanan.php: Model PemesananTiket atau metode getByUserId tidak ditemukan.");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem: Tidak dapat memuat riwayat pemesanan (MPT_NF_RW).');
        redirect('user/dashboard.php');
    } else {
        exit("Kesalahan sistem: Tidak dapat memuat riwayat pemesanan (MPT_NF_NOREDIR_RW).");
    }
}


require_login();
$current_user_id = get_current_user_id();

$page_title = "Riwayat Pemesanan Saya";

// Ambil semua pemesanan untuk user yang sedang login
// Metode PemesananTiket::getByUserId($user_id, $limit = null) sekarang hanya mengambil yang belum di-soft-delete
$riwayat_pemesanan = PemesananTiket::getByUserId($current_user_id);

$header_template_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . '/header_user.php';
if (file_exists($header_template_path)) {
    include_once $header_template_path;
} else {
    error_log("FATAL riwayat_pemesanan.php: File header_user.php tidak ditemukan di '{$header_template_path}'.");
    exit("Kesalahan tampilan: Komponen header tidak ditemukan.");
}
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <h1 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Pemesanan</h1>
                    <p class="text-muted">Berikut adalah daftar semua pemesanan tiket yang pernah Anda buat.</p>
                </div>
                <div class="col-sm-4 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= e(USER_URL . 'pemesanan_tiket.php') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Buat Pemesanan Baru
                    </a>
                </div>
            </div>
        </div>

        <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Daftar Pemesanan</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($riwayat_pemesanan)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kode Pemesanan</th>
                                    <th>Tanggal Kunjungan</th>
                                    <th>Total Pembayaran</th>
                                    <th>Status Pesanan</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat_pemesanan as $index => $pesanan): ?>
                                    <tr>
                                        <td><?= $index + 1 ?>.</td>
                                        <td>
                                            <a href="<?= e(USER_URL . 'detail_pemesanan.php?kode=' . urlencode($pesanan['kode_pemesanan'] ?? '')) ?>">
                                                <strong><?= e($pesanan['kode_pemesanan'] ?? 'N/A') ?></strong>
                                            </a>
                                        </td>
                                        <td><?= e(formatTanggalIndonesia($pesanan['tanggal_kunjungan'] ?? null)) ?></td>
                                        <td><?= e(formatRupiah($pesanan['total_harga_akhir'] ?? 0)) ?></td>
                                        <td>
                                            <?= getStatusBadgeClassHTML($pesanan['status'] ?? 'tidak diketahui', 'Tidak Diketahui') ?>
                                        </td>
                                        <td><?= e(formatTanggalIndonesia($pesanan['created_at'] ?? null, true)) ?></td>
                                        <td>
                                            <a href="<?= e(USER_URL . 'detail_pemesanan.php?kode=' . urlencode($pesanan['kode_pemesanan'] ?? '')) ?>" class="btn btn-info btn-sm" title="Lihat Detail">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                            <?php // Tombol Hapus dari Riwayat (Soft Delete) 
                                            ?>
                                            <button type="button" class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#hapusRiwayatModal"
                                                data-pemesanan-id="<?= e($pesanan['id'] ?? '') ?>"
                                                data-kode-pesanan="<?= e($pesanan['kode_pemesanan'] ?? 'N/A') ?>"
                                                title="Hapus dari Riwayat">
                                                <i class="fas fa-eraser"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                        Anda belum memiliki riwayat pemesanan.
                        <a href="<?= e(USER_URL . 'pemesanan_tiket.php') ?>" class="d-block mt-2">Buat pemesanan pertama Anda sekarang!</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Riwayat -->
<div class="modal fade" id="hapusRiwayatModal" tabindex="-1" aria-labelledby="hapusRiwayatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hapusRiwayatModalLabel"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Konfirmasi Hapus dari Riwayat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pesanan dengan kode <strong id="kodePesananDiModalHapusRiwayat"></strong> dari riwayat Anda?</p>
                <p class="text-muted"><small>Catatan: Ini hanya akan menyembunyikan pesanan dari daftar riwayat Anda. Data pesanan akan tetap ada di sistem admin.</small></p>
            </div>
            <div class="modal-footer">
                <form id="formHapusRiwayat" action="<?= e(USER_URL . 'proses_hapus_riwayat.php') ?>" method="POST" style="display: inline;">
                    <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
                    <input type="hidden" name="pemesanan_id_to_soft_delete" id="pemesananIdToSoftDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Batal</button>
                    <button type="submit" name="confirm_soft_delete" class="btn btn-warning"><i class="fas fa-eraser me-1"></i>Ya, Hapus dari Riwayat</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var hapusRiwayatModal = document.getElementById('hapusRiwayatModal');
        if (hapusRiwayatModal) {
            hapusRiwayatModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                if (button) {
                    var pemesananId = button.getAttribute('data-pemesanan-id');
                    var kodePesanan = button.getAttribute('data-kode-pesanan');

                    var modalKodePesanan = hapusRiwayatModal.querySelector('#kodePesananDiModalHapusRiwayat');
                    var inputPemesananId = hapusRiwayatModal.querySelector('#pemesananIdToSoftDelete');

                    if (modalKodePesanan) modalKodePesanan.textContent = kodePesanan || 'N/A';
                    if (inputPemesananId) inputPemesananId.value = pemesananId || '';
                }
            });
        }
    });
</script>

<?php
$base_template_path = defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template';
$footer_user_path = $base_template_path . '/footer_user.php';
$footer_default_path = $base_template_path . '/footer.php';

if (file_exists($footer_user_path)) {
    include_once $footer_user_path;
} elseif (file_exists($footer_default_path)) {
    error_log("PERINGATAN riwayat_pemesanan.php: File footer_user.php tidak ditemukan di '{$footer_user_path}', menggunakan footer.php dari '{$footer_default_path}' sebagai fallback.");
    include_once $footer_default_path;
} else {
    error_log("FATAL riwayat_pemesanan.php: File footer_user.php dan footer.php tidak ditemukan. Path yang dicek: '{$footer_user_path}' dan '{$footer_default_path}'.");
}
?>