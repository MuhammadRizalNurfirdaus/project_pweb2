<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\semua_destinasi.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di semua_destinasi.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan WisataController sudah dimuat
if (!class_exists('WisataController') || !method_exists('WisataController', 'getAllForAdmin')) {
    error_log("KRITIS semua_destinasi.php: WisataController atau metode getAllForAdmin tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Data destinasi tidak dapat dimuat saat ini.');
    if (function_exists('redirect')) redirect(BASE_URL);
    exit;
}

$page_title = "Semua Destinasi Wisata - " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Lembah Cilengkrang");
$page_description = "Jelajahi semua destinasi wisata menarik yang ditawarkan oleh " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Lembah Cilengkrang") . ". Temukan petualangan Anda berikutnya!";

// Ambil semua data destinasi wisata
$daftar_destinasi = [];
$error_fetch_destinasi = null;
try {
    // Menggunakan 'nama ASC' sebagai default. getAllForAdmin sudah bisa menerima limit null.
    $daftar_destinasi_raw = WisataController::getAllForAdmin('nama ASC', null);
    if ($daftar_destinasi_raw && is_array($daftar_destinasi_raw)) {
        $daftar_destinasi = $daftar_destinasi_raw;
    } elseif ($daftar_destinasi_raw === false) {
        $daftar_destinasi = [];
        $modelError = (class_exists('Wisata') && method_exists('Wisata', 'getLastError')) ? Wisata::getLastError() : null;
        $error_fetch_destinasi = "Tidak dapat mengambil daftar destinasi saat ini.";
        if ($modelError) $error_fetch_destinasi .= " Detail: " . e($modelError);
        error_log("Error di semua_destinasi.php saat WisataController::getAllForAdmin(): " . $error_fetch_destinasi);
    }
} catch (Exception $e) {
    $daftar_destinasi = [];
    $error_fetch_destinasi = "Terjadi kesalahan saat memuat destinasi.";
    error_log("Exception di semua_destinasi.php: " . $e->getMessage());
}

// Sertakan header publik
require_once ROOT_PATH . '/template/header.php';

// Definisikan path dasar untuk gambar DENGAN BENAR (konsisten dengan kelola_alat.php)
$base_uploads_wisata_path_server = defined('UPLOADS_WISATA_PATH')
    ? rtrim(UPLOADS_WISATA_PATH, '/\\')
    : rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'wisata';

$base_uploads_wisata_url_web = defined('UPLOADS_WISATA_URL')
    ? rtrim(UPLOADS_WISATA_URL, '/')
    : (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/public/uploads/wisata' : './public/uploads/wisata');

// Untuk debugging path dasar:
// error_log("SEMUA_DESTINASI DEBUG - Server Path Base: " . $base_uploads_wisata_path_server);
// error_log("SEMUA_DESTINASI DEBUG - Web URL Base: " . $base_uploads_wisata_url_web);
?>

<main class="container mt-5 mb-5">
    <header class="text-center mb-5">
        <h1 class="display-5 fw-bold"><?= e($page_title) ?></h1>
        <p class="lead text-muted"><?= e($page_description) ?></p>
    </header>

    <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

    <?php if ($error_fetch_destinasi && empty($daftar_destinasi)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_fetch_destinasi) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($daftar_destinasi)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($daftar_destinasi as $destinasi): ?>
                <?php
                $nama_destinasi_item = e($destinasi['nama'] ?? 'Destinasi Tidak Diketahui');
                $deskripsi_singkat_item = e(excerpt($destinasi['deskripsi'] ?? '', 120));
                $detail_url_item = BASE_URL . 'wisata/detail_destinasi.php?id=' . ($destinasi['id'] ?? 0);

                $gambar_file_db = $destinasi['gambar'] ?? null;
                $display_image_url_final = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/placeholder_wisata.png'; // Default placeholder
                $path_gambar_server_aktual_item = null;

                if (!empty($gambar_file_db)) {
                    $nama_file_bersih_item = basename($gambar_file_db);
                    $path_gambar_server_aktual_item = $base_uploads_wisata_path_server . DIRECTORY_SEPARATOR . $nama_file_bersih_item;

                    if (file_exists($path_gambar_server_aktual_item) && is_file($path_gambar_server_aktual_item)) {
                        $display_image_url_final = $base_uploads_wisata_url_web . '/' . rawurlencode($nama_file_bersih_item);
                    } else {
                        // Aktifkan log ini jika gambar dari DB tidak ditemukan di server
                        // error_log("SEMUA_DESTINASI_ITEM_DEBUG: Gambar '{$nama_file_bersih_item}' (ID: {$destinasi['id']}) tidak ditemukan di server. Path dicek: {$path_gambar_server_aktual_item}");
                    }
                }
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm hover-shadow-lg transition-fast">
                        <a href="<?= e($detail_url_item) ?>">
                            <img src="<?= e($display_image_url_final) ?><?= strpos($display_image_url_final, '?') === false ? '?t=' . time() : '&t=' . time() // Cache buster 
                                                                        ?>" class="card-img-top" alt="Gambar <?= $nama_destinasi_item ?>" style="height: 200px; object-fit: cover;">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><a href="<?= e($detail_url_item) ?>" class="text-decoration-none text-dark stretched-link-pseudo"><?= $nama_destinasi_item ?></a></h5>
                            <?php if (!empty($destinasi['lokasi'])): ?>
                                <p class="card-text text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i><?= e($destinasi['lokasi']) ?></p>
                            <?php endif; ?>
                            <p class="card-text flex-grow-1"><?= $deskripsi_singkat_item ?></p>
                            <a href="<?= e($detail_url_item) ?>" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Lihat Detail <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-muted small">
                            <i class="fas fa-clock me-1"></i> Diperbarui: <?= e(formatTanggalIndonesia($destinasi['updated_at'] ?? $destinasi['created_at'] ?? null, false)) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$error_fetch_destinasi): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>Saat ini belum ada destinasi wisata yang tersedia. Silakan kembali lagi nanti.
        </div>
    <?php endif; ?>

</main>

<?php
require_once ROOT_PATH . '/template/footer.php';
?>
<style>
    .card.hover-shadow-lg:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
    }

    .transition-fast {
        transition: all 0.2s ease-in-out;
    }

    .stretched-link-pseudo::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1;
        content: "";
    }
</style>