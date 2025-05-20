<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\semua_destinasi.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di semua_destinasi.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan WisataController sudah dimuat (config.php seharusnya sudah melakukannya)
if (!class_exists('WisataController') || !method_exists('WisataController', 'getAllForAdmin')) { // Asumsi getAllForAdmin bisa dipakai publik
    error_log("KRITIS semua_destinasi.php: WisataController atau metode getAllForAdmin tidak ditemukan.");
    // Tampilkan pesan error sederhana atau redirect ke beranda
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Data destinasi tidak dapat dimuat saat ini.');
    if (function_exists('redirect')) redirect(BASE_URL);
    exit;
}

$page_title = "Semua Destinasi Wisata - " . NAMA_SITUS;
$page_description = "Jelajahi semua destinasi wisata menarik yang ditawarkan oleh " . NAMA_SITUS . ". Temukan petualangan Anda berikutnya!";
// Anda bisa menambahkan $page_keywords jika perlu untuk SEO

// Ambil semua data destinasi wisata
$daftar_destinasi = [];
$error_fetch_destinasi = null;
try {
    // Menggunakan 'nama ASC' sebagai default, atau Anda bisa ganti dengan 'created_at DESC' untuk yang terbaru
    $daftar_destinasi = WisataController::getAllForAdmin('nama ASC');
    if ($daftar_destinasi === false) {
        $daftar_destinasi = [];
        $error_fetch_destinasi = "Tidak dapat mengambil daftar destinasi saat ini.";
        if (class_exists('Wisata') && method_exists('Wisata', 'getLastError')) {
            $modelError = Wisata::getLastError();
            if ($modelError) $error_fetch_destinasi .= " Detail: " . e($modelError);
        }
        error_log("Error di semua_destinasi.php saat getAllForAdmin(): " . $error_fetch_destinasi);
    }
} catch (Exception $e) {
    $daftar_destinasi = [];
    $error_fetch_destinasi = "Terjadi kesalahan saat memuat destinasi.";
    error_log("Exception di semua_destinasi.php: " . $e->getMessage());
}

// Sertakan header publik
require_once ROOT_PATH . '/template/header.php';
?>

<main class="container mt-5 mb-5">
    <header class="text-center mb-5">
        <h1 class="display-5 fw-bold"><?= e($page_title) ?></h1>
        <p class="lead text-muted"><?= e($page_description) ?></p>
    </header>

    <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

    <?php if ($error_fetch_destinasi): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_fetch_destinasi) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($daftar_destinasi)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($daftar_destinasi as $destinasi): ?>
                <?php
                $nama_destinasi = e($destinasi['nama'] ?? 'Destinasi Tidak Diketahui');
                $deskripsi_singkat = e(excerpt($destinasi['deskripsi'] ?? '', 120)); // Ambil 120 karakter deskripsi
                $gambar_url = null;
                if (!empty($destinasi['gambar'])) {
                    $path_gambar_server = (defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH : ROOT_PATH . '/public/uploads/wisata/') . basename($destinasi['gambar']);
                    if (file_exists($path_gambar_server) && is_file($path_gambar_server)) {
                        $gambar_url = (defined('UPLOADS_WISATA_URL') ? UPLOADS_WISATA_URL : BASE_URL . 'public/uploads/wisata/') . rawurlencode(basename($destinasi['gambar']));
                    }
                }
                // Fallback image jika gambar tidak ada atau tidak ditemukan
                $placeholder_image_url = defined('ASSETS_URL') ? ASSETS_URL . 'img/placeholder_wisata.png' : BASE_URL . 'public/img/placeholder_wisata.png';
                $display_image_url = $gambar_url ? $gambar_url . '?t=' . time() : $placeholder_image_url;
                $detail_url = BASE_URL . 'wisata/detail_destinasi.php?id=' . ($destinasi['id'] ?? 0);
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm hover-shadow-lg transition-fast">
                        <a href="<?= e($detail_url) ?>">
                            <img src="<?= e($display_image_url) ?>" class="card-img-top" alt="Gambar <?= $nama_destinasi ?>" style="height: 200px; object-fit: cover;">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><a href="<?= e($detail_url) ?>" class="text-decoration-none text-dark stretched-link-pseudo"><?= $nama_destinasi ?></a></h5>
                            <?php if (!empty($destinasi['lokasi'])): ?>
                                <p class="card-text text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i><?= e($destinasi['lokasi']) ?></p>
                            <?php endif; ?>
                            <p class="card-text flex-grow-1"><?= $deskripsi_singkat ?></p>
                            <a href="<?= e($detail_url) ?>" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Lihat Detail <i class="fas fa-arrow-right ms-1"></i></a>
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
// Sertakan footer publik
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
        /* Workaround untuk stretched-link di card-title jika ada elemen lain */
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1;
        content: "";
    }
</style>