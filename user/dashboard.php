<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\dashboard.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di user/dashboard.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya pengguna yang sudah login yang bisa akses
// require_login() akan redirect ke halaman login jika belum login
if (!function_exists('require_login')) {
    error_log("FATAL ERROR di user/dashboard.php: Fungsi require_login() tidak ditemukan.");
    // Fallback jika helper tidak ada (seharusnya tidak terjadi)
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else header('Location: ' . (defined('BASE_URL') ? BASE_URL . 'auth/login.php' : '../auth/login.php'));
        exit;
    }
} else {
    require_login();
}

// 3. Ambil data spesifik user jika perlu (Contoh: Pemesanan terakhir)
// Pastikan Model PemesananTiket.php dan kelasnya sudah dimuat oleh config.php
$recent_pemesanan = [];
$error_pemesanan = null;
if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'getByUserId')) {
    try {
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            // Ambil 3 pemesanan terbaru yang tidak di-soft-delete oleh user
            $recent_pemesanan = PemesananTiket::getByUserId($current_user_id, 3);
        }
    } catch (Exception $e) {
        error_log("Error mengambil pemesanan terakhir di user/dashboard.php: " . $e->getMessage());
        $error_pemesanan = "Gagal memuat riwayat pemesanan terbaru.";
    }
}

$page_title = "Dashboard Pengguna - " . NAMA_SITUS;
// Sertakan header pengguna (yang mungkin berbeda dari header publik/admin)
include_once ROOT_PATH . '/template/header_user.php';
?>

<div class="main-page-content-user">
    <div class="container py-5">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="display-5 fw-bold text-dark">Dashboard Saya</h1>
                <p class="lead text-muted">Selamat datang kembali, <strong><?= e(get_current_user_name()) ?></strong>! Kelola akun dan aktivitas Anda di sini.</p>
            </div>
        </div>

        <?= display_flash_message(); ?>

        <?php if ($error_pemesanan): ?>
            <div class="alert alert-warning small"><i class="fas fa-exclamation-triangle me-1"></i> <?= e($error_pemesanan) ?></div>
        <?php endif; ?>


        <!-- Kartu Navigasi Utama -->
        <h3 class="mb-4 mt-4 pt-2 fs-4 fw-semibold text-secondary">Akses Cepat</h3>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(USER_URL . 'profil.php') ?>" class="text-decoration-none">
                    <div class="card dashboard-card-nav shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-primary text-white mb-3">
                                <i class="fas fa-user-edit fa-2x"></i>
                            </div>
                            <h5 class="card-title">Profil Saya</h5>
                            <p class="card-text text-muted small">Lihat & perbarui informasi pribadi Anda.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(USER_URL . 'riwayat_pemesanan.php') ?>" class="text-decoration-none">
                    <div class="card dashboard-card-nav shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-success text-white mb-3">
                                <i class="fas fa-history fa-2x"></i>
                            </div>
                            <h5 class="card-title">Riwayat Pemesanan</h5>
                            <p class="card-text text-muted small">Lacak status & detail semua pemesanan tiket Anda.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(USER_URL . 'pemesanan_tiket.php') ?>" class="text-decoration-none">
                    <div class="card dashboard-card-nav shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-info text-white mb-3">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                            <h5 class="card-title">Pesan Tiket Baru</h5>
                            <p class="card-text text-muted small">Rencanakan kunjungan Anda berikutnya.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(USER_URL . 'artikel.php') ?>" class="text-decoration-none">
                    <div class="card dashboard-card-nav shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-secondary text-white mb-3">
                                <i class="fas fa-newspaper fa-2x"></i>
                            </div>
                            <h5 class="card-title">Artikel & Berita</h5>
                            <p class="card-text text-muted small">Baca info & tips terbaru seputar wisata.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(USER_URL . 'feedback.php') ?>" class="text-decoration-none">
                    <div class="card dashboard-card-nav shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-warning text-dark mb-3">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                            <h5 class="card-title">Beri Feedback</h5>
                            <p class="card-text text-muted small">Bagikan pengalaman & masukan Anda.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(BASE_URL . 'contact.php') // Mengarah ke contact.php publik 
                            ?>" class="text-decoration-none">
                    <div class="card dashboard-card-nav shadow-sm h-100 text-center">
                        <div class="card-body">
                            <div class="icon-circle bg-danger text-white mb-3">
                                <i class="fas fa-headset fa-2x"></i>
                            </div>
                            <h5 class="card-title">Hubungi Dukungan</h5>
                            <p class="card-text text-muted small">Ada pertanyaan? Tim kami siap membantu.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>


        <?php if (!empty($recent_pemesanan)): ?>
            <div class="mt-5 pt-4 border-top">
                <h3 class="mb-4 fs-4 fw-semibold text-secondary">Pemesanan Terakhir Anda</h3>
                <div class="list-group shadow-sm">
                    <?php foreach ($recent_pemesanan as $pemesanan): ?>
                        <a href="<?= e(USER_URL . 'detail_pemesanan.php?id=' . $pemesanan['id']) ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold">Pesanan: <?= e($pemesanan['kode_pemesanan']) ?></h6>
                                <small class="text-muted"><?= e(formatTanggalIndonesia($pemesanan['created_at'], false)) ?></small>
                            </div>
                            <p class="mb-1 text-muted">Tanggal Kunjungan: <?= e(formatTanggalIndonesia($pemesanan['tanggal_kunjungan'], false)) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Total: <?= e(formatRupiah($pemesanan['total_harga_akhir'])) ?></small>
                                <?= getStatusBadgeClassHTML($pemesanan['status'], 'Status Tidak Diketahui') ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="<?= e(USER_URL . 'riwayat_pemesanan.php') ?>" class="btn btn-sm btn-outline-secondary">Lihat Semua Riwayat Pemesanan</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bagian Video dan Card Destinasi (disesuaikan) -->
        <div class="mt-5 pt-4 border-top">
            <h3 class="mb-4 fs-4 fw-semibold text-secondary">Inspirasi Petualangan</h3>
            <div class="card mb-4 shadow-lg overflow-hidden"> <!-- Video Utama -->
                <div class="ratio ratio-16x9">
                    <video autoplay loop muted playsinline poster="<?= e(ASSETS_URL . 'img/background_poster.jpg') ?>">
                        <source src="<?= e(ASSETS_URL . 'img/curug.mp4') ?>" type="video/mp4">
                        Browser Anda tidak mendukung tag video.
                    </video>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <?php
                // Contoh data statis, idealnya dari Model Wisata
                $inspirasi_destinasi = [
                    ['id' => 1, 'nama' => 'Air Panas Alami', 'gambar' => 'air_panas.jpg', 'deskripsi_singkat' => 'Nikmati kehangatan air panas alami di tengah pegunungan.'],
                    ['id' => 2, 'nama' => 'Gazebo & Area Santai', 'gambar' => 'gazebo.jpg', 'deskripsi_singkat' => 'Tempat ideal untuk bersantai bersama keluarga.'],
                    ['id' => 3, 'nama' => 'Air Terjun Cilengkrang', 'gambar' => 'terjun2.jpg', 'deskripsi_singkat' => 'Keindahan alam yang menyejukkan dan memukau.'],
                ];
                foreach ($inspirasi_destinasi as $dest):
                    $img_url = ASSETS_URL . 'img/' . e($dest['gambar']);
                    // Cek jika file ada, jika tidak, pakai placeholder
                    if (!file_exists(ROOT_PATH . '/public/img/' . $dest['gambar'])) {
                        $img_url = ASSETS_URL . 'img/placeholder_wisata.png';
                    }
                ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card dashboard-destinasi-card h-100 shadow-hover">
                            <img src="<?= e($img_url) ?>" alt="<?= e($dest['nama']) ?>" class="card-img-top">
                            <div class="card-body">
                                <h5 class="card-title"><?= e($dest['nama']) ?></h5>
                                <p class="card-text small text-muted"><?= e($dest['deskripsi_singkat']) ?></p>
                            </div>
                            <div class="card-footer bg-white border-0 text-center pb-3">
                                <a href="<?= e(BASE_URL . 'wisata/detail_destinasi.php?id=' . $dest['id']) ?>" class="btn btn-sm btn-success">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<style>
    .main-page-content-user {
        background-color: #f8f9fa;
        /* Latar belakang sedikit berbeda dari body */
        width: 100%;
    }

    .dashboard-card-nav {
        transition: all 0.3s ease-in-out;
        border: 1px solid #e0e0e0;
    }

    .dashboard-card-nav:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        border-color: var(--bs-primary);
        /* Menggunakan warna primary Bootstrap */
    }

    .dashboard-card-nav .card-body {
        padding: 1.75rem 1.5rem;
    }

    .icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-card-nav .card-title {
        font-weight: 600;
        color: #333;
    }

    .shadow-hover:hover {
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .175) !important;
        transform: translateY(-3px);
        transition: all .2s ease-out;
    }

    .dashboard-destinasi-card .card-img-top {
        height: 200px;
        object-fit: cover;
    }

    .ratio-16x9 video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
<?php
include_once ROOT_PATH . '/template/footer_user.php'; // Pastikan ini footer untuk user
?>