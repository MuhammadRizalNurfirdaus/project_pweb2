<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\artikel_detail.php

// 1. Load Konfigurasi dan Inisialisasi
// Pastikan config.php sudah:
// - session_start()
// - Mendefinisikan konstanta (BASE_URL, VIEWS_PATH, UPLOADS_URL, AUTH_URL, CURRENT_URL, dll.)
// - Memuat semua Model (Artikel, Feedback, User jika ada) dan Controller
// - Menginisialisasi koneksi DB ke Model (misal: Artikel::init($conn, UPLOADS_ARTIKEL_PATH), Feedback::setDbConnection($conn))
// - Memuat semua file helper (e(), redirect(), set_flash_message(), display_flash_message(), is_post, verify_csrf_token, generate_csrf_token_input, formatTanggalIndonesia, dll.)
if (!require_once __DIR__ . '/../config/config.php') {
    error_log("KRITIS artikel_detail.php: Gagal memuat file konfigurasi utama (config.php).");
    exit("Terjadi kesalahan pada sistem. Silakan coba lagi nanti.");
}

// 2. Ambil ID Artikel dari URL dan Validasi
$artikel_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$artikel_id || $artikel_id <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Artikel yang Anda cari tidak ditemukan atau ID tidak valid.');
    if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL . 'user/artikel.php');
    exit;
}

// 3. Proses Submit Feedback Jika Ada Permintaan POST
if (function_exists('is_post') && is_post() && isset($_POST['submit_feedback'])) {
    if (function_exists('verify_csrf_token') && function_exists('generate_csrf_token_input') && verify_csrf_token()) {
        $data_feedback_post = [
            'artikel_id' => $artikel_id,
            'komentar'   => $_POST['komentar'] ?? '',
            'rating'     => $_POST['rating'] ?? '',
        ];

        if (class_exists('FeedbackController') && method_exists('FeedbackController', 'submitFeedbackForArtikel')) {
            FeedbackController::submitFeedbackForArtikel($data_feedback_post);
            // Redirect untuk PRG pattern. Flash message sudah di-set oleh controller.
            if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL . 'user/artikel_detail.php?id=' . $artikel_id . '#feedback-section');
            exit;
        } else {
            error_log("artikel_detail.php: FeedbackController atau metode submitFeedbackForArtikel tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Tidak dapat memproses feedback (FC-SBM-NF).');
            if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL . 'user/artikel_detail.php?id=' . $artikel_id);
            exit;
        }
    } else {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF). Silakan coba lagi.');
        if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL . 'user/artikel_detail.php?id=' . $artikel_id);
        exit;
    }
}


// 4. Ambil Data Artikel dan Feedback dari Controller
$data_halaman = null;
if (class_exists('ArtikelController') && method_exists('ArtikelController', 'getArtikelDetailForUserPage')) {
    $data_halaman = ArtikelController::getArtikelDetailForUserPage($artikel_id);
} else {
    error_log("artikel_detail.php: ArtikelController atau metode getArtikelDetailForUserPage tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Tidak dapat mengambil data artikel (AC-GDP-NF).');
    if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL . 'user/artikel.php');
    exit;
}

if (!$data_halaman || empty($data_halaman['artikel'])) {
    if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Detail artikel tidak ditemukan. Mungkin telah dihapus atau ID salah.');
    }
    if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL . 'user/artikel.php');
    exit;
}

$artikel = $data_halaman['artikel'];
$feedbacks = $data_halaman['feedbacks'] ?? []; // Pastikan feedbacks adalah array

// 5. Set Judul Halaman dan Sertakan Header
// Fungsi e() untuk escaping output. Jika tidak ada, gunakan htmlspecialchars sebagai fallback.
$esc = function_exists('e') ? 'e' : function ($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
};
$pageTitle = $esc($artikel['judul'] ?? "Detail Artikel");

// Sertakan header
if (defined('VIEWS_PATH')) {
    if (!include_once VIEWS_PATH . '/header.php') {
        error_log("KRITIS artikel_detail.php: Gagal memuat file header dari: " . VIEWS_PATH . '/header.php');
        exit("Terjadi kesalahan pada tampilan. Silakan coba lagi nanti.");
    }
} else {
    error_log("KRITIS artikel_detail.php: Konstanta VIEWS_PATH tidak terdefinisi.");
    exit("Terjadi kesalahan konfigurasi sistem (header).");
}
?>

<div class="container mt-4 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $esc(BASE_URL) ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= $esc(BASE_URL . 'user/artikel.php') ?>">Artikel</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $esc(mb_strimwidth($artikel['judul'] ?? '', 0, 50, "...")) ?></li>
        </ol>
    </nav>

    <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Detail Artikel -->
            <article class="blog-post mb-4">
                <h1 class="blog-post-title display-5 mb-1"><?= $esc($artikel['judul'] ?? '') ?></h1>
                <p class="blog-post-meta text-muted">
                    Dipublikasikan pada: <?= (function_exists('formatTanggalIndonesia')) ? $esc(formatTanggalIndonesia($artikel['created_at'] ?? '', false, true)) : $esc($artikel['created_at'] ?? '') ?>
                    <?php if (!empty($artikel['nama_penulis'])): // Asumsi 'nama_penulis' ada jika ditampilkan 
                    ?>
                        oleh <a href="#"><?= $esc($artikel['nama_penulis']) ?></a>
                    <?php endif; ?>
                </p>

                <?php if (!empty($artikel['gambar']) && defined('UPLOADS_URL')): ?>
                    <?php
                    $gambar_url = rtrim(UPLOADS_URL, '/') . '/artikel/' . rawurlencode($artikel['gambar']);
                    ?>
                    <img src="<?= $esc($gambar_url) ?>" class="img-fluid rounded mb-4 shadow-sm" alt="Gambar untuk <?= $esc($artikel['judul'] ?? '') ?>" style="max-height: 450px; width: 100%; object-fit: cover;">
                <?php endif; ?>

                <div class="blog-post-content lead">
                    <?php
                    // PENTING: Jika $artikel['isi'] adalah HTML dari WYSIWYG, JANGAN GUNAKAN $esc() atau htmlspecialchars().
                    // Pastikan HTML tersebut SUDAH DISANITASI (misal dengan HTML Purifier) SEBELUM DISIMPAN atau SEBELUM DITAMPILKAN.
                    // Contoh untuk HTML yang sudah aman:
                    // echo $artikel['isi'] ?? ''; 

                    // Jika $artikel['isi'] adalah plain text dan ingin line break:
                    echo nl2br($esc($artikel['isi'] ?? ''));
                    ?>
                </div>
            </article>
            <hr class="my-4">

            <!-- Bagian Feedback -->
            <section id="feedback-section" class="feedback-section mb-4">
                <h3 class="mb-3">Feedback Pengunjung (<?= count($feedbacks) ?>)</h3>
                <?php if (!empty($feedbacks)): ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title mb-1">
                                    <strong><?= $esc($fb['user_nama'] ?? 'Pengunjung') ?></strong>
                                    <small class="text-muted ms-2">- <?= (function_exists('formatTanggalIndonesia')) ? $esc(formatTanggalIndonesia($fb['created_at'] ?? '', true, true)) : $esc($fb['created_at'] ?? '') ?></small>
                                </h6>
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= ($i <= (int)($fb['rating'] ?? 0)) ? 'text-warning' : 'text-secondary' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1 text-muted">(<?= $esc((int)($fb['rating'] ?? 0)) ?>/5)</span>
                                </div>
                                <p class="card-text mb-0"><?= nl2br($esc($fb['komentar'] ?? '')) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Belum ada feedback untuk artikel ini. Jadilah yang pertama!</p>
                <?php endif; ?>
            </section>
            <hr class="my-4">

            <!-- Form Feedback -->
            <section class="feedback-form-section" id="feedback-form-container">
                <h4>Berikan Feedback Anda</h4>
                <?php if (isset($_SESSION['user_id'])): // Cek jika user login 
                ?>
                    <form action="<?= $esc(BASE_URL . 'user/artikel_detail.php?id=' . $artikel_id) ?>#feedback-form-container" method="POST" id="feedback-form">
                        <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating Anda <span class="text-danger">*</span></label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="" disabled selected>-- Pilih Bintang --</option>
                                <option value="5">★★★★★ (Sangat Bagus)</option>
                                <option value="4">★★★★☆ (Bagus)</option>
                                <option value="3">★★★☆☆ (Cukup)</option>
                                <option value="2">★★☆☆☆ (Kurang)</option>
                                <option value="1">★☆☆☆☆ (Buruk)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="komentar" class="form-label">Komentar Anda <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="komentar" name="komentar" rows="4" required placeholder="Tulis komentar Anda di sini..."></textarea>
                        </div>
                        <button type="submit" name="submit_feedback" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Kirim Feedback
                        </button>
                    </form>
                <?php else: ?>
                    <?php if (defined('AUTH_URL') && defined('CURRENT_URL') && defined('BASE_URL')): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>
                            Silakan <a href="<?= $esc(AUTH_URL . '/login.php?redirect_to=' . rawurlencode($currentUrl)) ?>" class="alert-link">login</a> untuk memberikan feedback.
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Login diperlukan untuk memberikan feedback.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

        <div class="col-lg-4">
            <div class="position-sticky" style="top: 2rem;">
                <div class="p-4 mb-3 bg-light rounded shadow-sm">
                    <h4 class="fst-italic">Tentang Situs</h4>
                    <p class="mb-0">Jelajahi keindahan alam dan budaya di Cilengkrang. Temukan informasi terbaru mengenai destinasi wisata, atraksi, dan tips perjalanan.</p>
                </div>

                <?php
                if (class_exists('ArtikelController') && method_exists('ArtikelController', 'getArtikelLain') && isset($artikel['id'])) {
                    $artikel_lain = ArtikelController::getArtikelLain(3, [$artikel['id']]);
                    if (!empty($artikel_lain)):
                ?>
                        <div class="p-4">
                            <h4 class="fst-italic">Artikel Lainnya</h4>
                            <ol class="list-unstyled mb-0">
                                <?php foreach ($artikel_lain as $other_artikel): ?>
                                    <li>
                                        <a href="<?= $esc(BASE_URL . 'user/artikel_detail.php?id=' . ($other_artikel['id'] ?? '')) ?>" class="d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center py-3 link-body-emphasis text-decoration-none border-top">
                                            <?php if (!empty($other_artikel['gambar']) && defined('UPLOADS_URL')):
                                                $img_lain_url = rtrim(UPLOADS_URL, '/') . '/artikel/' . rawurlencode($other_artikel['gambar']);
                                            ?>
                                                <img src="<?= $esc($img_lain_url) ?>" alt="Gambar mini <?= $esc($other_artikel['judul'] ?? '') ?>" width="96" height="96" class="rounded" style="object-fit:cover;">
                                            <?php else: ?>
                                                <svg class="bd-placeholder-img flex-shrink-0 me-3 rounded" width="96" height="96" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Gambar" preserveAspectRatio="xMidYMid slice" focusable="false">
                                                    <title>Placeholder</title>
                                                    <rect width="100%" height="100%" fill="#6c757d"></rect><text x="50%" y="50%" fill="#dee2e6" dy=".3em" text-anchor="middle">Gambar</text>
                                                </svg>
                                            <?php endif; ?>
                                            <div class="col-lg-8">
                                                <h6 class="mb-0"><?= $esc($other_artikel['judul'] ?? '') ?></h6>
                                                <small class="text-body-secondary"><?= (function_exists('formatTanggalIndonesia')) ? $esc(formatTanggalIndonesia($other_artikel['created_at'] ?? '', false, false)) : $esc($other_artikel['created_at'] ?? '') ?></small>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                <?php
                    endif;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// Sertakan footer
if (defined('VIEWS_PATH')) {
    if (!include_once VIEWS_PATH . '/footer.php') {
        error_log("KRITIS artikel_detail.php: Gagal memuat file footer dari: " . VIEWS_PATH . '/footer.php');
        exit("Terjadi kesalahan pada tampilan footer. Silakan coba lagi nanti.");
    }
} else {
    error_log("KRITIS artikel_detail.php: Konstanta VIEWS_PATH tidak terdefinisi (footer).");
    exit("Terjadi kesalahan konfigurasi sistem (footer).");
}
?>