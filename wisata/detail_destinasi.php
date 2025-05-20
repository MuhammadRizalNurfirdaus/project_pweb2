<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\detail_destinasi.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di detail_destinasi.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan WisataController sudah dimuat
if (!class_exists('WisataController') || !method_exists('WisataController', 'getById')) {
    error_log("KRITIS detail_destinasi.php: WisataController atau metode getById tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Data destinasi tidak dapat dimuat saat ini.');
    if (function_exists('redirect')) redirect(BASE_URL);
    exit;
}

// 3. Ambil ID destinasi dari URL
$id_destinasi = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if ($id_destinasi <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Destinasi yang Anda cari tidak valid.');
    if (function_exists('redirect')) redirect(BASE_URL . 'wisata/semua_destinasi.php'); // Arahkan ke daftar destinasi
    exit;
}

// 4. Ambil data destinasi berdasarkan ID
$destinasi = null;
$error_fetch_detail = null;
try {
    $destinasi = WisataController::getById($id_destinasi);
    if ($destinasi === null && !Wisata::getLastError()) { // Tidak ditemukan, bukan error DB
        $error_fetch_detail = "Destinasi wisata tidak ditemukan.";
    } elseif ($destinasi === null && Wisata::getLastError()) { // Error DB
        $error_fetch_detail = "Gagal mengambil detail destinasi. " . e(Wisata::getLastError());
        error_log("Error di detail_destinasi.php saat WisataController::getById({$id_destinasi}): " . Wisata::getLastError());
    }
} catch (Exception $e) {
    $error_fetch_detail = "Terjadi kesalahan saat memuat detail destinasi.";
    error_log("Exception di detail_destinasi.php: " . $e->getMessage());
}

// Jika destinasi tidak ditemukan setelah semua pengecekan
if (!$destinasi && !$error_fetch_detail) {
    $error_fetch_detail = "Destinasi wisata tidak ditemukan atau tidak tersedia.";
}
if ($error_fetch_detail && !$destinasi) {
    if (function_exists('set_flash_message')) set_flash_message('danger', $error_fetch_detail);
    if (function_exists('redirect')) redirect(BASE_URL . 'wisata/semua_destinasi.php');
    exit;
}


$page_title = ($destinasi && isset($destinasi['nama'])) ? e($destinasi['nama']) : "Detail Destinasi";
$page_title .= " - " . NAMA_SITUS;
$page_description = ($destinasi && isset($destinasi['deskripsi'])) ? e(excerpt($destinasi['deskripsi'], 155)) : "Informasi detail mengenai destinasi wisata.";

// Sertakan header publik
require_once ROOT_PATH . '/template/header.php';

// Path gambar
$gambar_url = null;
$placeholder_image_url = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/placeholder_wisata_large.png'; // Placeholder lebih besar
if ($destinasi && !empty($destinasi['gambar'])) {
    $path_gambar_server = (defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH : ROOT_PATH . '/public/uploads/wisata/') . basename($destinasi['gambar']);
    if (file_exists($path_gambar_server) && is_file($path_gambar_server)) {
        $gambar_url = (defined('UPLOADS_WISATA_URL') ? UPLOADS_WISATA_URL : BASE_URL . 'public/uploads/wisata/') . rawurlencode(basename($destinasi['gambar']));
    }
}
$display_image_url = $gambar_url ? $gambar_url . '?t=' . time() : $placeholder_image_url;

?>

<main class="container mt-5 mb-5">
    <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

    <?php if ($destinasi): ?>
        <article class="destinasi-detail">
            <header class="mb-4">
                <h1 class="display-4 fw-bold"><?= e($destinasi['nama']) ?></h1>
                <?php if (!empty($destinasi['lokasi'])): ?>
                    <p class="lead text-muted"><i class="fas fa-map-marker-alt me-2"></i><?= e($destinasi['lokasi']) ?></p>
                <?php endif; ?>
            </header>

            <div class="row g-4">
                <div class="col-lg-7">
                    <figure class="figure">
                        <img src="<?= e($display_image_url) ?>" class="figure-img img-fluid rounded shadow-lg" alt="Gambar <?= e($destinasi['nama']) ?>" style="max-height: 500px; width:100%; object-fit: cover;">
                        <figcaption class="figure-caption text-center mt-2">Pemandangan di <?= e($destinasi['nama']) ?></figcaption>
                    </figure>
                </div>
                <div class="col-lg-5">
                    <div class="sticky-top" style="top: 20px;"> <?php // Membuat deskripsi "sticky" saat scroll 
                                                                ?>
                        <h3 class="mb-3">Deskripsi Destinasi</h3>
                        <div class="deskripsi-content fs-5">
                            <?php
                            // Jika deskripsi Anda mengandung HTML dari editor WYSIWYG, jangan di-escape di sini
                            // Tapi pastikan HTMLnya sudah disanitasi saat disimpan ke DB atau saat diambil dari DB.
                            // Untuk contoh ini, saya asumsikan deskripsi adalah teks biasa.
                            echo nl2br(e($destinasi['deskripsi']));
                            ?>
                        </div>
                        <hr class="my-4">
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Informasi ini terakhir diperbarui pada: <?= e(formatTanggalIndonesia($destinasi['updated_at'] ?? $destinasi['created_at'] ?? null, true, true)) ?>
                        </p>
                        <a href="<?= e(BASE_URL . 'wisata/semua_destinasi.php') ?>" class="btn btn-outline-secondary mt-3">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Destinasi
                        </a>
                        <?php /* Anda bisa menambahkan tombol lain di sini, misal tombol pesan tiket jika terintegrasi */ ?>
                    </div>
                </div>
            </div>

            <?php // Bagian opsional: peta lokasi jika ada data lokasi valid untuk embed 
            ?>
            <?php if (!empty($destinasi['lokasi']) && (strpos($destinasi['lokasi'], 'http') === 0 || strpos($destinasi['lokasi'], '<iframe') !== false)): ?>
                <hr class="my-5">
                <section class="lokasi-map mt-4">
                    <h3 class="mb-3">Peta Lokasi</h3>
                    <?php if (strpos($destinasi['lokasi'], '<iframe') !== false): ?>
                        <div class="ratio ratio-16x9">
                            <?= $destinasi['lokasi'] // Output langsung jika berupa iframe, pastikan sudah aman 
                            ?>
                        </div>
                    <?php elseif (strpos($destinasi['lokasi'], 'http') === 0): // Jika berupa link Google Maps 
                    ?>
                        <p>Lihat lokasi di <a href="<?= e($destinasi['lokasi']) ?>" target="_blank" rel="noopener noreferrer">Google Maps <i class="fas fa-external-link-alt fa-xs"></i></a></p>
                        <?php // Atau coba embed jika formatnya diketahui, misal:
                        // $embed_url = str_replace("/maps/place/", "/maps/embed/v1/place?key=API_KEY_ANDA&q=", $destinasi['lokasi']);
                        // echo '<div class="ratio ratio-16x9"><iframe src="'.e($embed_url).'" allowfullscreen></iframe></div>';
                        ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>


        </article>
    <?php else: ?>
        <?php // Ini seharusnya sudah ditangani oleh redirect di atas jika $destinasi null 
        ?>
        <div class="alert alert-warning text-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Detail untuk destinasi ini tidak dapat ditemukan.
        </div>
    <?php endif; ?>
</main>

<?php
// Sertakan footer publik
require_once ROOT_PATH . '/template/footer.php';
?>
<style>
    .deskripsi-content p:last-child {
        margin-bottom: 0;
    }
</style>