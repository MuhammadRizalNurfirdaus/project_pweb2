<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\detail_pemesanan_sewa.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php");
    exit("Server Error.");
}
// 2. Otentikasi Admin
require_admin();
// 3. Sertakan Controller
if (!require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat PemesananSewaAlatController.php");
    set_flash_message('danger', 'Kesalahan sistem.');
    redirect('admin/dashboard.php');
}

// 4. Ambil ID dan Data
$pemesanan_sewa_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$detail_sewa = null;
$pesan_error = null;

if (!$pemesanan_sewa_id) {
    set_flash_message('danger', 'ID Pemesanan Sewa tidak valid.');
    redirect('admin/pemesanan_sewa/kelola_pemesanan_sewa.php');
}

if (class_exists('PemesananSewaAlatController') && method_exists('PemesananSewaAlatController', 'getDetailSewaByIdForAdmin')) {
    try {
        $detail_sewa = PemesananSewaAlatController::getDetailSewaByIdForAdmin($pemesanan_sewa_id);
        if (!$detail_sewa) {
            $pesan_error = "Detail pemesanan sewa ID " . e($pemesanan_sewa_id) . " tidak ditemukan.";
        }
    } catch (Throwable $e) {
        error_log("Error di detail_pemesanan_sewa.php: " . $e->getMessage());
        $pesan_error = "Gagal memuat detail pemesanan sewa.";
    }
} else {
    $pesan_error = "Komponen sistem tidak ditemukan.";
    error_log($pesan_error);
}

if ($pesan_error && !isset($_SESSION['flash_message'])) {
    set_flash_message('danger', $pesan_error);
}

// 6. Proses Form Update Catatan dan Denda
if (is_post() && isset($_POST['update_catatan_denda_submit']) && $detail_sewa) {
    $catatan_baru = input('catatan_item_sewa');
    $denda_baru = filter_var(input('denda'), FILTER_VALIDATE_FLOAT);

    if ($denda_baru === false || $denda_baru < 0) {
        set_flash_message('danger', 'Format denda tidak valid atau negatif.');
    } else {
        $data_update = [
            'id' => $pemesanan_sewa_id,
            'catatan_item_sewa' => $catatan_baru,
            'denda' => $denda_baru
        ];
        if (method_exists('PemesananSewaAlatController', 'updateCatatanDanDenda')) {
            if (PemesananSewaAlatController::updateCatatanDanDenda($data_update)) {
                set_flash_message('success', 'Catatan dan denda berhasil diperbarui.');
            } else {
                if (!isset($_SESSION['flash_message'])) {
                    set_flash_message('danger', 'Gagal memperbarui catatan dan denda.');
                }
            }
        } else {
            set_flash_message('danger', 'Fungsi update tidak tersedia.');
        }
    }
    redirect('admin/pemesanan_sewa/detail_pemesanan_sewa.php?id=' . $pemesanan_sewa_id);
}

// 7. Set judul halaman dan SERTAKAN HEADER ADMIN
$pageTitle = $detail_sewa ? "Detail Sewa ID: " . e($detail_sewa['id']) : "Detail Pemesanan Sewa";
if (!include_once __DIR__ . '/../../template/header_admin.php') { /* Error handling header */
    exit;
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php"><i class="fas fa-boxes-stacked"></i> Kelola Pemesanan Sewa</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-info-circle"></i> Detail Sewa</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail Pemesanan Sewa Alat</h1>
    <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Pemesanan Sewa
    </a>
</div>

<?php display_flash_message(); ?>

<?php if ($detail_sewa): ?>
    <div class="row">
        <!-- Kolom Informasi Detail Sewa -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Pemesanan Sewa ID: <?= e($detail_sewa['id']) ?></h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">ID Pemesanan Sewa</dt>
                        <dd class="col-sm-8"><?= e($detail_sewa['id']) ?></dd>

                        <dt class="col-sm-4">Pemesan (Tiket)</dt>
                        <dd class="col-sm-8">
                            <?= e($detail_sewa['nama_pemesan'] ?? 'N/A') ?>
                            <?php if (isset($detail_sewa['id_user_pemesan_tiket'])): ?>
                                <small class="text-muted d-block">(User ID: <?= e($detail_sewa['id_user_pemesan_tiket']) ?>)</small>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4">Kode Pemesanan Tiket</dt>
                        <dd class="col-sm-8">
                            <?php if (isset($detail_sewa['kode_pemesanan_tiket']) && isset($detail_sewa['pemesanan_tiket_id'])): ?>
                                <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/detail_pemesanan.php?id=<?= e($detail_sewa['pemesanan_tiket_id']) ?>"><?= e($detail_sewa['kode_pemesanan_tiket']) ?></a>
                            <?php else: echo '-';
                            endif; ?>
                        </dd>

                        <hr class="my-2">

                        <dt class="col-sm-4">Alat Disewa</dt>
                        <dd class="col-sm-8"><?= e($detail_sewa['nama_alat'] ?? 'N/A') ?> <small class="text-muted">(ID Alat: <?= e($detail_sewa['sewa_alat_id'] ?? '-') ?>)</small></dd>

                        <dt class="col-sm-4">Jumlah</dt>
                        <dd class="col-sm-8"><?= e($detail_sewa['jumlah'] ?? '-') ?></dd>

                        <dt class="col-sm-4">Harga Satuan (saat pesan)</dt>
                        <dd class="col-sm-8"><?= function_exists('formatRupiah') ? formatRupiah($detail_sewa['harga_satuan_saat_pesan'] ?? 0) : e($detail_sewa['harga_satuan_saat_pesan'] ?? 0) ?></dd>

                        <dt class="col-sm-4">Durasi Satuan</dt>
                        <dd class="col-sm-8"><?= e($detail_sewa['durasi_satuan_saat_pesan'] ?? '-') ?> <?= e($detail_sewa['satuan_durasi_saat_pesan'] ?? '') ?></dd>

                        <dt class="col-sm-4">Periode Sewa</dt>
                        <dd class="col-sm-8">
                            Mulai: <?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($detail_sewa['tanggal_mulai_sewa'] ?? '', true) : e($detail_sewa['tanggal_mulai_sewa'] ?? '-')) ?><br>
                            Akhir: <?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($detail_sewa['tanggal_akhir_sewa_rencana'] ?? '', true) : e($detail_sewa['tanggal_akhir_sewa_rencana'] ?? '-')) ?>
                        </dd>

                        <dt class="col-sm-4">Total Harga Item Sewa</dt>
                        <dd class="col-sm-8 fw-bold"><?= function_exists('formatRupiah') ? formatRupiah($detail_sewa['total_harga_item'] ?? 0) : e($detail_sewa['total_harga_item'] ?? 0) ?></dd>

                        <dt class="col-sm-4">Status Item Sewa</dt>
                        <dd class="col-sm-8">
                            <span class="badge rounded-pill bg-<?= function_exists('getSewaStatusBadgeClass') ? getSewaStatusBadgeClass($detail_sewa['status_item_sewa'] ?? '') : 'secondary' ?>">
                                <?= e(ucfirst($detail_sewa['status_item_sewa'] ?? 'Tidak Diketahui')); ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Denda</dt>
                        <dd class="col-sm-8"><?= function_exists('formatRupiah') ? formatRupiah($detail_sewa['denda'] ?? 0) : e($detail_sewa['denda'] ?? 0) ?></dd>

                        <dt class="col-sm-4">Tanggal Pesan Sewa</dt>
                        <dd class="col-sm-8"><?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($detail_sewa['created_at'] ?? '', true) : e($detail_sewa['created_at'] ?? '-')) ?></dd>

                        <dt class="col-sm-4">Terakhir Update</dt>
                        <dd class="col-sm-8"><?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($detail_sewa['updated_at'] ?? '', true) : e($detail_sewa['updated_at'] ?? '-')) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Kolom Aksi (Update Catatan/Denda dan Cetak) -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update & Aksi Lainnya</h6>
                </div>
                <div class="card-body">
                    <form action="<?= e(ADMIN_URL) ?>/pemesanan_sewa/detail_pemesanan_sewa.php?id=<?= e($pemesanan_sewa_id) ?>" method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="catatan_item_sewa" class="form-label">Catatan Item Sewa:</label>
                            <textarea class="form-control form-control-sm" id="catatan_item_sewa" name="catatan_item_sewa" rows="3"><?= e($detail_sewa['catatan_item_sewa'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="denda" class="form-label">Denda (Rp):</label>
                            <input type="number" class="form-control form-control-sm" id="denda" name="denda" value="<?= e((float)($detail_sewa['denda'] ?? 0.00)) ?>" step="any" min="0">
                        </div>
                        <button type="submit" name="update_catatan_denda_submit" class="btn btn-success btn-sm"><i class="fas fa-save me-2"></i>Simpan Catatan/Denda</button>
                    </form>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/cetak_bukti_sewa.php?id=<?= e($pemesanan_sewa_id) ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="fas fa-print me-2"></i>Cetak Bukti Sewa
                        </a>
                        <!-- Anda bisa menambahkan tombol aksi lain di sini -->
                        <!-- Contoh: Tombol hapus pemesanan sewa -->
                        <form action="<?= e(ADMIN_URL) ?>/pemesanan_sewa/hapus_pemesanan_sewa.php" method="POST" onsubmit="return confirm('PERHATIAN: Menghapus pemesanan sewa ini (ID: <?= e($pemesanan_sewa_id) ?>) akan menghapus data item sewa ini secara permanen. Stok alat akan dikembalikan jika relevan. Lanjutkan?');" class="mt-2">
                            <input type="hidden" name="id_pemesanan_sewa" value="<?= e($pemesanan_sewa_id) ?>">
                            <button type="submit" name="hapus_sewa_submit" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash-alt me-2"></i>Hapus Pemesanan Sewa Ini
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Menutup .row -->

<?php else: ?>
    <div class="alert alert-warning">
        <?php if ($pesan_error): echo e($pesan_error);
        else: echo "Detail pemesanan sewa tidak ditemukan atau ID tidak valid.";
        endif; ?>
        <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php" class="alert-link ms-2">Kembali ke daftar</a>.
    </div>
<?php endif; ?>

<?php
// Definisi fungsi getSewaStatusBadgeClass() sudah dipindahkan ke helpers.php
if (!include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari detail_pemesanan_sewa.php.");
    echo "<p style='color:red;font-family:Arial,sans-serif;padding:15px;margin:20px;border:2px solid red;background-color:#ffebee;'>Error Kritis: Footer tidak termuat.</p>";
}
?>