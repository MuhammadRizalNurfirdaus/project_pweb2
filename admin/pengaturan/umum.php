<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pengaturan\umum.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di pengaturan/umum.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Otentikasi Admin
require_admin();

// 3. Pastikan Controller dan Model ada
if (!class_exists('PengaturanController') || !method_exists('PengaturanController', 'getPengaturanSitus')) {
    error_log("FATAL ERROR di pengaturan/umum.php: PengaturanController atau metode getPengaturanSitus tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen pengaturan tidak dapat dimuat.');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// 4. Ambil data pengaturan saat ini
$pengaturan = PengaturanController::getPengaturanSitus();
if ($pengaturan === null && !PengaturanSitus::getLastError()) {
    // Jika tidak ada error tapi pengaturan null, mungkin tabel kosong (belum di-seed)
    // Ini seharusnya tidak terjadi jika Anda sudah INSERT baris default
    set_flash_message('warning', 'Data pengaturan situs belum ada. Silakan isi dan simpan.');
    $pengaturan = []; // Inisialisasi array kosong agar tidak error di bawah
} elseif ($pengaturan === null && PengaturanSitus::getLastError()) {
    set_flash_message('danger', 'Gagal memuat data pengaturan situs: ' . e(PengaturanSitus::getLastError()));
    $pengaturan = [];
}


// 5. Set judul halaman
$pageTitle = "Pengaturan Umum Situs";

// 6. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// Ambil data form dari session untuk repopulasi jika ada error sebelumnya
$session_key = 'flash_form_data_pengaturan_umum';
$input_nama_situs = $_SESSION[$session_key]['nama_situs'] ?? $pengaturan['nama_situs'] ?? '';
$input_tagline_situs = $_SESSION[$session_key]['tagline_situs'] ?? $pengaturan['tagline_situs'] ?? '';
$input_deskripsi_situs = $_SESSION[$session_key]['deskripsi_situs'] ?? $pengaturan['deskripsi_situs'] ?? '';
$input_email_kontak = $_SESSION[$session_key]['email_kontak_situs'] ?? $pengaturan['email_kontak_situs'] ?? '';
$input_telepon_kontak = $_SESSION[$session_key]['telepon_kontak_situs'] ?? $pengaturan['telepon_kontak_situs'] ?? '';
$input_alamat_situs = $_SESSION[$session_key]['alamat_situs'] ?? $pengaturan['alamat_situs'] ?? '';
$input_link_fb = $_SESSION[$session_key]['link_facebook'] ?? $pengaturan['link_facebook'] ?? '';
$input_link_ig = $_SESSION[$session_key]['link_instagram'] ?? $pengaturan['link_instagram'] ?? '';
$input_link_tw = $_SESSION[$session_key]['link_twitter'] ?? $pengaturan['link_twitter'] ?? '';
$input_link_yt = $_SESSION[$session_key]['link_youtube'] ?? $pengaturan['link_youtube'] ?? '';
$input_ga_id = $_SESSION[$session_key]['google_analytics_id'] ?? $pengaturan['google_analytics_id'] ?? '';
$input_items_per_page = $_SESSION[$session_key]['items_per_page'] ?? $pengaturan['items_per_page'] ?? 10;
$input_mode_pemeliharaan = isset($_SESSION[$session_key]['mode_pemeliharaan']) ? 1 : ($pengaturan['mode_pemeliharaan'] ?? 0);


$current_logo = $pengaturan['logo_situs'] ?? null;
$current_favicon = $pengaturan['favicon_situs'] ?? null;

// Path URL untuk menampilkan gambar (jika ada)
// Asumsi logo dan favicon disimpan di UPLOADS_URL (dari config.php)
$base_upload_url_display = defined('UPLOADS_URL') ? rtrim(UPLOADS_URL, '/') . '/' : (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/public/uploads/' : './public/uploads/');
$base_upload_path_server_check = defined('UPLOADS_PATH') ? rtrim(UPLOADS_PATH, '/\\') . DIRECTORY_SEPARATOR : rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;


if (isset($_SESSION[$session_key])) {
    unset($_SESSION[$session_key]);
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-cogs"></i> Pengaturan Umum</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-cogs me-2"></i>Formulir Pengaturan Umum Situs</h6>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>

        <form action="<?= e(ADMIN_URL . 'pengaturan/proses_pengaturan.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input('csrf_token_pengaturan'); ?>
            <input type="hidden" name="action" value="update_umum">

            <h5 class="mb-3 mt-2">Informasi Dasar Situs</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_situs" class="form-label">Nama Situs <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_situs" name="nama_situs" value="<?= e($input_nama_situs) ?>" required>
                    <div class="invalid-feedback">Nama situs wajib diisi.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tagline_situs" class="form-label">Tagline/Slogan Situs</label>
                    <input type="text" class="form-control" id="tagline_situs" name="tagline_situs" value="<?= e($input_tagline_situs) ?>" placeholder="Contoh: Keindahan Alam Tersembunyi">
                </div>
            </div>
            <div class="mb-3">
                <label for="deskripsi_situs" class="form-label">Deskripsi Singkat Situs</label>
                <textarea class="form-control" id="deskripsi_situs" name="deskripsi_situs" rows="3"><?= e($input_deskripsi_situs) ?></textarea>
                <small class="form-text text-muted">Akan muncul di meta description untuk SEO (maks 160 karakter disarankan).</small>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">Informasi Kontak</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email_kontak_situs" class="form-label">Email Kontak Utama</label>
                    <input type="email" class="form-control" id="email_kontak_situs" name="email_kontak_situs" value="<?= e($input_email_kontak) ?>" placeholder="info@namasitus.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telepon_kontak_situs" class="form-label">Telepon Kontak Utama</label>
                    <input type="tel" class="form-control" id="telepon_kontak_situs" name="telepon_kontak_situs" value="<?= e($input_telepon_kontak) ?>" placeholder="Contoh: 081234567890">
                </div>
            </div>
            <div class="mb-3">
                <label for="alamat_situs" class="form-label">Alamat Fisik (Jika Ada)</label>
                <textarea class="form-control" id="alamat_situs" name="alamat_situs" rows="2"><?= e($input_alamat_situs) ?></textarea>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">Logo & Favicon</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="logo_situs_file" class="form-label">Logo Situs</label>
                    <?php if (!empty($current_logo) && file_exists($base_upload_path_server_check . $current_logo)): ?>
                        <div class="mb-2">
                            <img src="<?= e($base_upload_url_display . rawurlencode($current_logo)) ?>?t=<?= time() ?>" alt="Logo Saat Ini" style="max-height: 60px; background: #f0f0f0; padding: 5px; border-radius: 4px;">
                            <label class="form-check-label ms-2"><input type="checkbox" class="form-check-input" name="hapus_logo_situs" value="1"> Hapus logo saat ini</label>
                        </div>
                    <?php elseif (!empty($current_logo)): ?>
                        <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> File logo saat ini (<?= e($current_logo) ?>) tidak ditemukan di server.</p>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="logo_situs_file" name="logo_situs_file" accept="image/png, image/jpeg, image/gif, image/svg+xml, image/webp">
                    <small class="form-text text-muted">Format: PNG, JPG, GIF, SVG, WEBP. Maks 2MB. Akan menggantikan logo saat ini jika diunggah.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="favicon_situs_file" class="form-label">Favicon Situs</label>
                    <?php if (!empty($current_favicon) && file_exists($base_upload_path_server_check . $current_favicon)): ?>
                        <div class="mb-2">
                            <img src="<?= e($base_upload_url_display . rawurlencode($current_favicon)) ?>?t=<?= time() ?>" alt="Favicon Saat Ini" style="max-height: 32px; max-width:32px; background: #f0f0f0; padding: 2px; border-radius: 4px;">
                            <label class="form-check-label ms-2"><input type="checkbox" class="form-check-input" name="hapus_favicon_situs" value="1"> Hapus favicon saat ini</label>
                        </div>
                    <?php elseif (!empty($current_favicon)): ?>
                        <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> File favicon saat ini (<?= e($current_favicon) ?>) tidak ditemukan di server.</p>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="favicon_situs_file" name="favicon_situs_file" accept="image/x-icon, image/png, image/svg+xml">
                    <small class="form-text text-muted">Format: ICO, PNG, SVG. Maks 512KB. Akan menggantikan favicon saat ini jika diunggah.</small>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">Media Sosial (Opsional)</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="link_facebook" class="form-label"><i class="fab fa-facebook-square me-1"></i> URL Facebook</label>
                    <input type="url" class="form-control" id="link_facebook" name="link_facebook" value="<?= e($input_link_fb) ?>" placeholder="https://facebook.com/akunwisata">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="link_instagram" class="form-label"><i class="fab fa-instagram me-1"></i> URL Instagram</label>
                    <input type="url" class="form-control" id="link_instagram" name="link_instagram" value="<?= e($input_link_ig) ?>" placeholder="https://instagram.com/akunwisata">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="link_twitter" class="form-label"><i class="fab fa-twitter-square me-1"></i> URL Twitter/X</label>
                    <input type="url" class="form-control" id="link_twitter" name="link_twitter" value="<?= e($input_link_tw) ?>" placeholder="https://x.com/akunwisata">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="link_youtube" class="form-label"><i class="fab fa-youtube me-1"></i> URL YouTube</label>
                    <input type="url" class="form-control" id="link_youtube" name="link_youtube" value="<?= e($input_link_yt) ?>" placeholder="https://youtube.com/c/akunwisata">
                </div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">Pengaturan Lainnya</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="google_analytics_id" class="form-label">ID Google Analytics</label>
                    <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" value="<?= e($input_ga_id) ?>" placeholder="UA-XXXXX-Y atau G-XXXXXXX">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="items_per_page" class="form-label">Item per Halaman (Paginasi)</label>
                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" value="<?= e($input_items_per_page) ?>" min="1" max="100">
                </div>
                <div class="col-md-2 mb-3 align-self-center">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="mode_pemeliharaan" name="mode_pemeliharaan" value="1" <?= ($input_mode_pemeliharaan == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mode_pemeliharaan">Mode Pemeliharaan?</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="submit_pengaturan_umum" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>
<script>
    // Validasi Bootstrap
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
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
    })();
</script>