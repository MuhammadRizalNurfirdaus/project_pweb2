<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\detail_destinasi.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di detail_destinasi.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan WisataController sudah dimuat
if (
    !class_exists('WisataController') || !method_exists('WisataController', 'getById') ||
    !class_exists('Wisata') || !method_exists('Wisata', 'getLastError')
) { // Tambah pengecekan Model Wisata untuk getLastError
    error_log("KRITIS detail_destinasi.php: WisataController/Wisata atau metode yang dibutuhkan tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Data destinasi tidak dapat dimuat saat ini.');
    if (function_exists('redirect')) redirect(BASE_URL);
    exit;
}

// 3. Ambil ID destinasi dari URL
$id_destinasi = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if ($id_destinasi <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Destinasi yang Anda cari tidak valid atau tidak ditemukan.');
    if (function_exists('redirect')) redirect(BASE_URL . 'wisata/semua_destinasi.php');
    exit;
}

// 4. Ambil data destinasi berdasarkan ID
$destinasi = null;
$error_fetch_detail = null;
try {
    $destinasi = WisataController::getById($id_destinasi);
    if ($destinasi === null) {
        $model_error = Wisata::getLastError();
        if ($model_error) {
            $error_fetch_detail = "Gagal mengambil detail destinasi. Detail: " . e($model_error);
            error_log("Error di detail_destinasi.php saat WisataController::getById({$id_destinasi}): " . $model_error);
        } else {
            $error_fetch_detail = "Destinasi wisata dengan ID tersebut tidak ditemukan.";
        }
    }
} catch (Exception $e) {
    $error_fetch_detail = "Terjadi kesalahan saat memuat detail destinasi.";
    error_log("Exception di detail_destinasi.php: " . $e->getMessage());
}

if (!$destinasi) { // Jika $destinasi masih null setelah try-catch
    $flash_message_error = $error_fetch_detail ?: "Destinasi wisata tidak ditemukan atau tidak tersedia.";
    if (function_exists('set_flash_message')) set_flash_message('danger', $flash_message_error);
    if (function_exists('redirect')) redirect(BASE_URL . 'wisata/semua_destinasi.php');
    exit;
}

$page_title = e($destinasi['nama']) . " - " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Detail Destinasi");
$page_description = e(excerpt($destinasi['deskripsi'] ?? '', 155));

// Sertakan header publik
require_once ROOT_PATH . '/template/header.php';

// --- PENANGANAN PATH DAN URL GAMBAR ---
$base_uploads_wisata_path_server_detail = defined('UPLOADS_WISATA_PATH')
    ? rtrim(UPLOADS_WISATA_PATH, '/\\')
    : rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'wisata';

$base_uploads_wisata_url_web_detail = defined('UPLOADS_WISATA_URL')
    ? rtrim(UPLOADS_WISATA_URL, '/')
    : (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/public/uploads/wisata' : './public/uploads/wisata');

$gambar_file_db_detail = $destinasi['gambar'] ?? null;
// Definisikan placeholder di sini agar selalu ada nilainya
$placeholder_image_url_detail = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/placeholder_wisata_large.png';
$display_image_url_detail = $placeholder_image_url_detail; // Default ke placeholder
$path_gambar_server_aktual_detail = null;

if (!empty($gambar_file_db_detail)) {
    $nama_file_bersih_detail = basename($gambar_file_db_detail);
    $path_gambar_server_aktual_detail = $base_uploads_wisata_path_server_detail . DIRECTORY_SEPARATOR . $nama_file_bersih_detail;

    if (file_exists($path_gambar_server_aktual_detail) && is_file($path_gambar_server_aktual_detail)) {
        $display_image_url_detail = $base_uploads_wisata_url_web_detail . '/' . rawurlencode($nama_file_bersih_detail);
    } else {
        error_log("DETAIL_DESTINASI_DEBUG: Gambar '{$nama_file_bersih_detail}' (ID: {$id_destinasi}) tidak ditemukan di server. Path dicek: {$path_gambar_server_aktual_detail}");
        // Biarkan $display_image_url_detail tetap sebagai placeholder jika file tidak ada
    }
}
// --- AKHIR PENANGANAN PATH DAN URL GAMBAR ---

?>

<main class="container mt-4 mb-5 pt-3">
    <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <?php /* Menghapus link "Beranda" dan "Semua Destinasi" dari breadcrumb di halaman detail */ ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e($destinasi['nama']) ?></li>
        </ol>
    </nav>

    <article class="destinasi-detail">
        <header class="mb-4 text-center">
            <h1 class="display-4 fw-bold" style="color: <?= defined('THEME_COLOR_PRIMARY') ? THEME_COLOR_PRIMARY : '#20c997'; ?>;"><?= e($destinasi['nama']) ?></h1>
            <?php if (!empty($destinasi['lokasi'])): ?>
                <p class="lead text-muted"><i class="fas fa-map-marker-alt me-2"></i><?= e($destinasi['lokasi']) ?></p>
            <?php endif; ?>
        </header>

        <div class="row g-lg-5 g-md-4 g-3">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <figure class="figure">
                    <img src="<?= e($display_image_url_detail) ?><?= strpos($display_image_url_detail, '?') === false && $display_image_url_detail !== $placeholder_image_url_detail ? '?t=' . time() : '' // Cache buster hanya jika bukan placeholder 
                                                                    ?>"
                        class="figure-img img-fluid rounded shadow-lg"
                        alt="Gambar <?= e($destinasi['nama']) ?>"
                        style="max-height: 550px; width:100%; object-fit: cover;">
                    <?php
                    // PERBAIKAN: Variabel $gambar_url tidak terdefinisi di scope ini, gunakan $display_image_url_detail
                    if ($display_image_url_detail !== $placeholder_image_url_detail):
                    ?>
                        <figcaption class="figure-caption text-center mt-2">Pemandangan utama di <?= e($destinasi['nama']) ?></figcaption>
                    <?php endif; ?>
                </figure>
            </div>
            <div class="col-lg-5">
                <div class="sticky-lg-top" style="top: 80px;">
                    <h3 class="mb-3 fw-semibold">Deskripsi Destinasi</h3>
                    <div class="deskripsi-content lead" style="font-size: 1.05rem; text-align: justify;">
                        <?= nl2br(e($destinasi['deskripsi'])) ?>
                    </div>
                    <hr class="my-4">
                    <p class="text-muted small">
                        <i class="fas fa-history me-1"></i> Informasi terakhir diperbarui pada: <?= e(formatTanggalIndonesia($destinasi['updated_at'] ?? $destinasi['created_at'] ?? null, true, false)) ?>
                    </p>
                    <a href="<?= e(BASE_URL . 'user/pemesanan_tiket.php?destinasi_id=' . $id_destinasi) ?>" class="btn btn-success btn-lg mt-3 mb-2 w-100 shadow-sm">
                        <i class="fas fa-ticket-alt me-2"></i>Pesan Tiket ke <?= e($destinasi['nama']) ?>
                    </a>
                    <a href="<?= e(BASE_URL . 'wisata/semua_destinasi.php') ?>" class="btn btn-outline-secondary mt-1 w-100">
                        <i class="fas fa-arrow-left me-2"></i>Lihat Destinasi Lain
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($destinasi['lokasi']) && (strpos($destinasi['lokasi'], 'http') === 0 || strpos($destinasi['lokasi'], '<iframe') !== false)): ?>
            <hr class="my-5">
            <section class="lokasi-map mt-4">
                <h3 class="mb-4 text-center fw-semibold">Peta Lokasi</h3>
                <?php if (strpos($destinasi['lokasi'], '<iframe') !== false): ?>
                    <div class="ratio ratio-16x9 shadow rounded">
                        <?= $destinasi['lokasi'] // PERHATIAN: Pastikan ini aman dari XSS saat input! 
                        ?>
                    </div>
                <?php elseif (strpos($destinasi['lokasi'], 'http') === 0): ?>
                    <div class="text-center">
                        <p>Untuk melihat lokasi lebih detail, kunjungi tautan berikut:</p>
                        <a href="<?= e($destinasi['lokasi']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info">
                            <i class="fas fa-map-marked-alt me-2"></i>Buka di Google Maps <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </article>
</main>

<?php
require_once ROOT_PATH . '/template/footer.php';
?>
<style>
    .deskripsi-content p:last-child {
        margin-bottom: 0;
    }

    @media (min-width: 992px) {
        .sticky-lg-top {
            top: 90px;
            /* Sedikit lebih banyak ruang dari navbar */
        }
    }
</style>