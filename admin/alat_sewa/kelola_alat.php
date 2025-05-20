<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\kelola_alat.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_alat.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di kelola_alat.php: Fungsi require_admin() tidak ditemukan.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia. (Error Code: ADM_AUTH_NF_KELOLA)");
}
require_admin();

// 3. Pastikan SewaAlatController dan metode getAll ada
if (!class_exists('SewaAlatController') || !method_exists('SewaAlatController', 'getAll')) {
    error_log("FATAL ERROR di kelola_alat.php: SewaAlatController atau metode getAll tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data alat sewa tidak dapat dimuat (SAC_GA_NF).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// 4. Set judul halaman
$page_title = "Kelola Alat Sewa";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Ambil semua data alat sewa
$daftar_alat_sewa = [];
$error_alat_sewa = null;

try {
    $daftar_alat_sewa = SewaAlatController::getAll();

    if ($daftar_alat_sewa === false) {
        $daftar_alat_sewa = [];
        $db_error_detail = (class_exists('SewaAlat') && method_exists('SewaAlat', 'getLastError')) ? SewaAlat::getLastError() : 'Tidak ada detail error dari model.';
        $error_alat_sewa = "Gagal mengambil data alat sewa." . ($db_error_detail ? " Detail: " . e($db_error_detail) : "");
        error_log("Error di kelola_alat.php saat SewaAlatController::getAll(): " . $error_alat_sewa);
    }
} catch (Exception $e) {
    $error_alat_sewa = "Terjadi exception saat mengambil data alat sewa: " . e($e->getMessage());
    error_log("Error di kelola_alat.php (Exception): " . $e->getMessage());
    $daftar_alat_sewa = [];
}

// --- PENANGANAN PATH DAN URL GAMBAR ---
// Ambil path dasar dari konstanta yang didefinisikan di config.php
// Ini adalah path absolut di server tempat file gambar disimpan.
$server_upload_path_base = defined('UPLOADS_ALAT_SEWA_PATH')
    ? rtrim(UPLOADS_ALAT_SEWA_PATH, '/\\')
    : rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'alat_sewa';

// Ini adalah URL dasar yang akan digunakan di tag <img> src.
$web_upload_url_base = defined('UPLOADS_ALAT_SEWA_URL')
    ? rtrim(UPLOADS_ALAT_SEWA_URL, '/')
    : (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/public/uploads/alat_sewa' : './public/uploads/alat_sewa');

// !! DEBUGGING EKSPLISIT !!
// Tampilkan nilai konstanta dan path dasar yang digunakan. HAPUS atau KOMENTARI SETELAH DEBUGGING.
/*
echo "<!-- DEBUG INFO (HAPUS SETELAH SELESAI): <br>";
echo "ROOT_PATH: " . (defined('ROOT_PATH') ? ROOT_PATH : 'TIDAK TERDEFINISI') . "<br>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'TIDAK TERDEFINISI') . "<br>";
echo "UPLOADS_ALAT_SEWA_PATH: " . (defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH : 'TIDAK TERDEFINISI') . "<br>";
echo "UPLOADS_ALAT_SEWA_URL: " . (defined('UPLOADS_ALAT_SEWA_URL') ? UPLOADS_ALAT_SEWA_URL : 'TIDAK TERDEFINISI') . "<br>";
echo "Digunakan server_upload_path_base: " . htmlspecialchars($server_upload_path_base) . "<br>";
echo "Digunakan web_upload_url_base: " . htmlspecialchars($web_upload_url_base) . "<br>";
echo "-->";
*/
// Log ke file error PHP untuk debugging yang lebih persisten
error_log("DEBUG KELOLA ALAT - Base Path Server Digunakan: " . $server_upload_path_base);
error_log("DEBUG KELOLA ALAT - Base URL Web Digunakan: " . $web_upload_url_base);
// --- AKHIR DEBUGGING EKSPLISIT ---

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tools"></i> Kelola Alat Sewa</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Alat Sewa</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e(ADMIN_URL . 'alat_sewa/tambah_alat.php') ?>" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Alat Sewa Baru
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<?php if ($error_alat_sewa): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_alat_sewa) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-light py-3 d-flex flex-row align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Data Alat Sewa Tersedia</h5>
        <span class="badge bg-info rounded-pill"><?= is_array($daftar_alat_sewa) ? count($daftar_alat_sewa) : 0 ?> Item</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle" id="dataTableAlatSewa">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 3%;" class="text-center">No.</th>
                        <th style="width: 5%;" class="text-center">ID</th>
                        <th style="width: 20%;">Nama Item</th>
                        <th style="width: 15%;">Kategori</th>
                        <th class="text-end" style="width: 10%;">Harga Sewa</th>
                        <th style="width: 12%;">Periode Harga</th>
                        <th class="text-center" style="width: 7%;">Stok</th>
                        <th class="text-center" style="width: 10%;">Gambar</th>
                        <th style="width: 10%;">Kondisi</th>
                        <th style="width: 18%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_alat_sewa) && is_array($daftar_alat_sewa)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftar_alat_sewa as $alat): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <td class="text-center"><?= e($alat['id'] ?? 'N/A') ?></td>
                                <td><?= e($alat['nama_item'] ?? 'Tanpa Nama') ?></td>
                                <td><?= e($alat['kategori_alat'] ?? 'N/A') ?></td>
                                <td class="text-end"><?= e(formatRupiah($alat['harga_sewa'] ?? 0)) ?></td>
                                <td><?= e($alat['durasi_harga_sewa'] ?? 'N/A') ?> <?= e($alat['satuan_durasi_harga'] ?? '') ?></td>
                                <td class="text-center"><?= e($alat['stok_tersedia'] ?? '0') ?></td>
                                <td class="text-center">
                                    <?php
                                    $gambar_file_dari_db = $alat['gambar_alat'] ?? null;
                                    $url_gambar_final = null;
                                    $path_gambar_server_aktual = null;
                                    $file_exists_status_debug = "File di DB kosong"; // Default status debug

                                    if (!empty($gambar_file_dari_db)) {
                                        $nama_file_bersih = basename($gambar_file_dari_db);
                                        $path_gambar_server_aktual = $server_upload_path_base . DIRECTORY_SEPARATOR . $nama_file_bersih;

                                        if (file_exists($path_gambar_server_aktual) && is_file($path_gambar_server_aktual)) {
                                            $url_gambar_final = $web_upload_url_base . '/' . rawurlencode($nama_file_bersih);
                                            $file_exists_status_debug = "ADA & VALID";
                                        } else {
                                            $file_exists_status_debug = "TIDAK ADA / PATH SERVER SALAH";
                                            // Aktifkan log ini untuk debugging mendalam per item
                                            error_log("KELOLA_ALAT_ITEM_DEBUG: AlatID=" . ($alat['id'] ?? 'N/A') .
                                                " | FileDB='" . ($gambar_file_dari_db ?? 'NULL') .
                                                "' | PathServerDicek='" . ($path_gambar_server_aktual ?? 'NULL') .
                                                "' | file_exists()=FALSE" .
                                                " | URLAkanJadi='" . ($web_upload_url_base . '/' . rawurlencode($nama_file_bersih)) . "'");
                                        }
                                    }
                                    ?>
                                    <?php if ($url_gambar_final): ?>
                                        <img src="<?= e($url_gambar_final) ?>?t=<?= time() ?>"
                                            alt="Gambar <?= e($alat['nama_item'] ?? '') ?>" class="img-thumbnail"
                                            style="width: 70px; height: auto; max-height:50px; object-fit: cover; cursor:pointer;"
                                            onclick="showImageModal('<?= e($url_gambar_final) ?>', '<?= e(addslashes($alat['nama_item'] ?? 'Gambar Alat')) ?>')">
                                    <?php elseif (!empty($gambar_file_dari_db)): ?>
                                        <small class="text-danger" title="File '<?= e($gambar_file_dari_db) ?>' tidak ditemukan di server. Path dicek: <?= e($path_gambar_server_aktual ?? 'Tidak valid') ?>. Status: <?= e($file_exists_status_debug) ?>"><i class="fas fa-image"></i> Gagal</small>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= getStatusBadgeClassHTML($alat['kondisi_alat'] ?? 'N/A', 'N/A') ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= e(ADMIN_URL . 'alat_sewa/edit_alat.php?id=' . ($alat['id'] ?? '')) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Alat Sewa">
                                        <i class="fas fa-edit fa-xs"></i>
                                    </a>
                                    <a href="<?= e(ADMIN_URL . 'alat_sewa/hapus_alat.php?id=' . ($alat['id'] ?? '')) ?>&csrf_token=<?= e(generate_csrf_token()) ?>"
                                        class="btn btn-danger btn-sm mb-1" title="Hapus Alat Sewa"
                                        onclick="return confirm('PERHATIAN: Menghapus alat ini akan gagal jika masih ada pemesanan aktif yang menggunakannya. Yakin ingin mencoba menghapus alat \" <?= e(addslashes($alat['nama_item'] ?? 'ini')) ?>\"?');">
                                        <i class="fas fa-trash-alt fa-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif (!$error_alat_sewa): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="mb-2 lead">Belum ada data alat sewa yang ditambahkan.</p>
                                <a href="<?= e(ADMIN_URL . 'alat_sewa/tambah_alat.php') ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Alat Sewa Pertama
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($error_alat_sewa && empty($daftar_alat_sewa)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Gagal memuat data alat sewa.
                                <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT && !empty($error_alat_sewa)): ?>
                                    <br><small>Detail: <?= e($error_alat_sewa) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan gambar -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalLabel">Pratinjau Gambar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="imagePreviewSrc" class="img-fluid" alt="Pratinjau Gambar" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof showImageModal === 'undefined') {
        function showImageModal(src, title) {
            const imagePreviewSrcEl = document.getElementById('imagePreviewSrc');
            const imagePreviewModalLabelEl = document.getElementById('imagePreviewModalLabel');
            const imagePreviewModalEl = document.getElementById('imagePreviewModal');

            if (imagePreviewSrcEl) {
                imagePreviewSrcEl.src = src;
            }
            if (imagePreviewModalLabelEl) {
                imagePreviewModalLabelEl.textContent = title || 'Pratinjau Gambar';
            }

            if (imagePreviewModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                let modalInstance = bootstrap.Modal.getInstance(imagePreviewModalEl);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(imagePreviewModalEl);
                }
                modalInstance.show();
            } else {
                console.error("Bootstrap Modal tidak ditemukan atau tidak terinisialisasi.");
            }
        }
    }
</script>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>