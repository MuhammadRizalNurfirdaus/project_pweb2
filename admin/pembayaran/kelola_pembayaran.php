<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\kelola_pembayaran.php

// LANGKAH 1: Sertakan config.php
if (!file_exists(__DIR__ . '/../../config/config.php')) {
    $critical_error = "KRITIS kelola_pembayaran.php: File konfigurasi utama (config.php) tidak ditemukan.";
    error_log($critical_error . " Path yang dicoba: " . realpath(__DIR__ . '/../../config/config.php'));
    exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Detail: " . htmlspecialchars($critical_error) . "</div>");
}
require_once __DIR__ . '/../../config/config.php';

// LANGKAH 2: Otentikasi Admin
try {
    if (!function_exists('require_admin')) {
        throw new Exception("Fungsi require_admin() tidak ditemukan.");
    }
    require_admin();
} catch (Exception $e) {
    error_log("FATAL ERROR di kelola_pembayaran.php: Exception saat require_admin(): " . $e->getMessage());
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak. Sesi tidak valid atau Anda belum login sebagai admin.');
    if (function_exists('redirect') && defined('AUTH_URL')) redirect(AUTH_URL . 'login.php');
    else exit('Akses ditolak.');
}

// LANGKAH 3: Set judul halaman
$pageTitle = "Kelola Pembayaran";

// LANGKAH 4: Sertakan header admin
$header_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : dirname(__DIR__, 2) . '/template/header_admin.php';
if (!@include_once $header_admin_path) {
    http_response_code(500);
    $error_msg_hdr = "FATAL ERROR HALAMAN: Tidak dapat memuat file header admin. Path dicek: " . realpath($header_admin_path) . ".";
    error_log($error_msg_hdr);
    exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Error Kritis Tampilan</strong><br>Detail: " . htmlspecialchars($error_msg_hdr) . "</div>");
}

// LANGKAH 5: Pengambilan dan Pemfilteran Data
$semuaPembayaran = [];
$data_pembayaran_awal = [];
$filter_status_pembayaran_url = isset($_GET['status_pembayaran']) ? trim(strtolower($_GET['status_pembayaran'])) : null;
$pesan_error_data_pembayaran = null;

if (!class_exists('PembayaranController')) {
    $pesan_error_data_pembayaran = "Komponen sistem pembayaran (PembayaranController) tidak ditemukan.";
    error_log($pesan_error_data_pembayaran . " - Pastikan PembayaranController.php sudah dimuat oleh config.php atau di-require di halaman ini.");
} elseif (!method_exists('PembayaranController', 'getAllPembayaranForAdmin')) {
    $pesan_error_data_pembayaran = "Metode yang dibutuhkan (getAllPembayaranForAdmin) pada PembayaranController tidak ditemukan.";
    error_log($pesan_error_data_pembayaran);
} else {
    try {
        $data_pembayaran_awal = PembayaranController::getAllPembayaranForAdmin('p.created_at DESC');
        if (!is_array($data_pembayaran_awal)) {
            error_log("PERINGATAN kelola_pembayaran.php: PembayaranController::getAllPembayaranForAdmin() tidak mengembalikan array. Hasil: " . gettype($data_pembayaran_awal));
            $data_pembayaran_awal = [];
            $pesan_error_data_pembayaran = "Format data pembayaran tidak sesuai.";
        }

        if ($filter_status_pembayaran_url && !empty($data_pembayaran_awal)) {
            $semuaPembayaran = array_filter($data_pembayaran_awal, function ($p) use ($filter_status_pembayaran_url) {
                return isset($p['status_pembayaran']) && strtolower(trim((string)$p['status_pembayaran'])) === $filter_status_pembayaran_url;
            });
        } else {
            $semuaPembayaran = $data_pembayaran_awal;
        }
    } catch (Throwable $e) {
        error_log("Error ambil data pembayaran di kelola_pembayaran.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        $pesan_error_data_pembayaran = "Gagal memuat data pembayaran karena kesalahan server.";
    }
}

if ($pesan_error_data_pembayaran && function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
    set_flash_message('danger', $pesan_error_data_pembayaran);
}

$unique_statuses = [];
if (!empty($data_pembayaran_awal)) {
    $statuses_from_data = array_column($data_pembayaran_awal, 'status_pembayaran');
    $unique_statuses = array_values(array_unique(array_filter($statuses_from_data, function ($value) {
        return !is_null($value) && $value !== '';
    })));
    sort($unique_statuses);
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(defined('ADMIN_URL') ? ADMIN_URL : '#') ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-credit-card"></i> Kelola Pembayaran</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Pembayaran
        <?php if ($filter_status_pembayaran_url): ?>
            <small class="text-muted fs-6">(Filter: <?= e(ucfirst(str_replace('_', ' ', $filter_status_pembayaran_url))) ?>)</small>
        <?php endif; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (!empty($unique_statuses)): ?>
            <div class="dropdown me-2">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownFilterStatus" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-filter"></i> Filter Status
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownFilterStatus">
                    <li><a class="dropdown-item <?= !$filter_status_pembayaran_url ? 'active' : '' ?>" href="<?= e(defined('ADMIN_URL') ? ADMIN_URL : '#') ?>pembayaran/kelola_pembayaran.php">Semua Status</a></li>
                    <?php foreach ($unique_statuses as $status_opt): ?>
                        <?php if (!empty(trim((string)$status_opt))): ?>
                            <li>
                                <a class="dropdown-item <?= ($filter_status_pembayaran_url === strtolower(trim((string)$status_opt))) ? 'active' : '' ?>"
                                    href="<?= e(defined('ADMIN_URL') ? ADMIN_URL : '#') ?>pembayaran/kelola_pembayaran.php?status_pembayaran=<?= e(strtolower(trim((string)$status_opt))) ?>">
                                    <?= e(ucfirst(str_replace(['_', '-'], ' ', trim((string)$status_opt)))) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (function_exists('display_flash_message')) display_flash_message(); ?>

<div class="card shadow mb-4">
    <div class="card-header bg-light py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Transaksi Pembayaran</h6>
        <small class="text-muted">Total: <?= is_array($semuaPembayaran) ? count($semuaPembayaran) : 0 ?> data</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle" id="dataTablePembayaran" width="100%" cellspacing="0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">No.</th>
                        <th class="text-center">ID Bayar</th>
                        <th>Kode Pesanan & Pemesan</th>
                        <th>Metode</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-center">Status</th>
                        <th>Waktu Bayar</th>
                        <th class="text-center">Bukti</th>
                        <th>Tgl. Dibuat Sistem</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pesan_error_data_pembayaran) && !empty($semuaPembayaran) && is_array($semuaPembayaran)): ?>
                        <?php $nomor_urut = 1; ?>
                        <?php foreach ($semuaPembayaran as $pembayaran): ?>
                            <?php
                            $status_pembayaran_raw = $pembayaran['status_pembayaran'] ?? 'unknown';
                            $status_badge_html = function_exists('getStatusBadgeClassHTML')
                                ? getStatusBadgeClassHTML($status_pembayaran_raw)
                                : '<span class="badge bg-secondary">' . e(ucfirst(str_replace(['_', '-'], ' ', $status_pembayaran_raw))) . '</span>';
                            ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut++ ?></td>
                                <td class="text-center"><strong><?= e($pembayaran['id'] ?? '') ?></strong></td>
                                <td>
                                    <?php
                                    $kode_display = $pembayaran['kode_pemesanan_tiket'] ?? $pembayaran['kode_pemesanan'] ?? null;
                                    if ($kode_display && isset($pembayaran['pemesanan_tiket_id']) && defined('ADMIN_URL')):
                                    ?>
                                        <a href="<?= e(ADMIN_URL . 'pemesanan_tiket/detail_pemesanan.php?id=' . $pembayaran['pemesanan_tiket_id']) ?>" title="Lihat Detail Pemesanan Tiket Terkait">
                                            <strong><?= e($kode_display) ?></strong>
                                        </a>
                                    <?php elseif (isset($pembayaran['pemesanan_tiket_id'])): ?>
                                        (ID Pemesanan: <?= e($pembayaran['pemesanan_tiket_id']) ?>)
                                    <?php else: echo '-';
                                    endif; ?>
                                    <br><small class="text-muted">Pemesan: <?= e($pembayaran['user_nama_pemesan'] ?? 'Tamu/Tidak Diketahui') ?></small>
                                </td>
                                <td><?= e($pembayaran['metode_pembayaran'] ?? '-') ?></td>
                                <td class="text-end fw-bold">
                                    <?= function_exists('formatRupiah') ? formatRupiah($pembayaran['jumlah_dibayar'] ?? 0) : 'Rp ' . number_format((float)($pembayaran['jumlah_dibayar'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <?= $status_badge_html ?>
                                </td>
                                <td>
                                    <?= (!empty($pembayaran['waktu_pembayaran']) && function_exists('formatTanggalIndonesia')) ? e(formatTanggalIndonesia($pembayaran['waktu_pembayaran'], true)) : (!empty($pembayaran['waktu_pembayaran']) ? e(date('d M Y, H:i', strtotime($pembayaran['waktu_pembayaran']))) : '-') ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                                        <?php
                                        $bukti_file_on_disk = defined('UPLOADS_BUKTI_PEMBAYARAN_PATH') ? rtrim(UPLOADS_BUKTI_PEMBAYARAN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pembayaran['bukti_pembayaran'] : '';
                                        $bukti_url_web = defined('UPLOADS_BUKTI_PEMBAYARAN_URL') ? rtrim(UPLOADS_BUKTI_PEMBAYARAN_URL, '/') . '/' . rawurlencode($pembayaran['bukti_pembayaran']) : '#';
                                        ?>
                                        <?php if (!empty($bukti_file_on_disk) && file_exists($bukti_file_on_disk)): ?>
                                            <a href="<?= e($bukti_url_web) ?>" target="_blank" class="btn btn-sm btn-outline-info py-1 px-2" title="Lihat Bukti Pembayaran">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted" title="File bukti tidak ditemukan di server: <?= e($pembayaran['bukti_pembayaran']) ?>"><i class="fas fa-times-circle text-danger"></i></span>
                                        <?php endif; ?>
                                    <?php else: echo '-';
                                    endif; ?>
                                </td>
                                <td>
                                    <?= (isset($pembayaran['created_at']) && function_exists('formatTanggalIndonesia')) ? e(formatTanggalIndonesia($pembayaran['created_at'], true)) : (isset($pembayaran['created_at']) ? e(date('d M Y, H:i', strtotime($pembayaran['created_at']))) : '-') ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group" aria-label="Aksi Pembayaran">
                                        <a href="<?= e(defined('ADMIN_URL') ? ADMIN_URL : '#') ?>pembayaran/detail_pembayaran.php?id=<?= e($pembayaran['id'] ?? '') ?>" class="btn btn-primary btn-sm py-1 px-2" title="Lihat Detail & Kelola Pembayaran">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm py-1 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#hapusPembayaranModal"
                                            data-pembayaran-id="<?= e($pembayaran['id'] ?? '') ?>"
                                            data-kode-pesanan="<?= e($pembayaran['kode_pemesanan_tiket'] ?? $pembayaran['kode_pemesanan'] ?? 'N/A') ?>"
                                            title="Hapus Pembayaran">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <?php if ($pesan_error_data_pembayaran): ?>
                                    <p class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= e($pesan_error_data_pembayaran) ?></p>
                                <?php elseif ($filter_status_pembayaran_url && empty($semuaPembayaran)): ?>
                                    <p class="mb-0">Tidak ada data pembayaran dengan status "<?= e(ucfirst(str_replace('_', ' ', $filter_status_pembayaran_url))) ?>".</p>
                                    <a href="<?= e(defined('ADMIN_URL') ? ADMIN_URL : '#') ?>pembayaran/kelola_pembayaran.php" class="btn btn-sm btn-link">Tampilkan Semua Status</a>
                                <?php else: ?>
                                    <p class="mb-0">Belum ada data transaksi pembayaran.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Pembayaran -->
<div class="modal fade" id="hapusPembayaranModal" tabindex="-1" aria-labelledby="hapusPembayaranModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="hapusPembayaranModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Penghapusan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pembayaran untuk kode pesanan <strong id="kodePesananDiModal"></strong> (ID Pembayaran: <strong id="pembayaranIdDiModal"></strong>)?</p>
                <p class="text-danger">Tindakan ini tidak dapat diurungkan.</p>
                <small class="text-muted">Catatan: Menghapus entri pembayaran ini mungkin memerlukan penyesuaian manual pada status Pemesanan Tiket terkait jika pembayaran sudah dikonfirmasi sebelumnya.</small>
            </div>
            <div class="modal-footer">
                <form id="formHapusPembayaran" action="<?= e(defined('ADMIN_URL') ? ADMIN_URL : '#') ?>pembayaran/hapus_pembayaran.php" method="POST" style="display: inline;">
                    <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
                    <input type="hidden" name="pembayaran_id_to_delete" id="pembayaranIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Batal</button>
                    <button type="submit" name="confirm_delete_pembayaran" class="btn btn-danger"><i class="fas fa-trash-alt me-1"></i>Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var hapusPembayaranModal = document.getElementById('hapusPembayaranModal');
        if (hapusPembayaranModal) {
            hapusPembayaranModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                if (button) { // Pastikan button ada
                    var pembayaranId = button.getAttribute('data-pembayaran-id');
                    var kodePesanan = button.getAttribute('data-kode-pesanan');

                    var modalPembayaranId = hapusPembayaranModal.querySelector('#pembayaranIdDiModal');
                    var modalKodePesanan = hapusPembayaranModal.querySelector('#kodePesananDiModal');
                    var inputPembayaranIdToDelete = hapusPembayaranModal.querySelector('#pembayaranIdToDelete');

                    if (modalPembayaranId) modalPembayaranId.textContent = pembayaranId || 'N/A';
                    if (modalKodePesanan) modalKodePesanan.textContent = kodePesanan || 'N/A';
                    if (inputPembayaranIdToDelete) inputPembayaranIdToDelete.value = pembayaranId || '';
                }
            });
        }
    });
</script>

<?php
$footer_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer_admin.php' : dirname(__DIR__, 2) . '/template/footer_admin.php';
if (!@include_once $footer_admin_path) {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari kelola_pembayaran.php. Path: " . realpath($footer_admin_path));
}
?>