<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\dashboard.php
require_once __DIR__ . '/../config/config.php';
require_login(); // Pastikan hanya user yang login bisa akses

// Anda bisa mengambil data spesifik user di sini jika perlu, misal jumlah pemesanan terakhir
// require_once __DIR__ . '/../models/PemesananTiket.php'; // Mengganti Booking.php
// $recent_pemesanan = PemesananTiket::getByUserId(get_current_user_id(), 3); // Contoh, limit 3

include_once __DIR__ . '/../template/header_user.php';
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="section-title text-start mb-0" style="margin-top:0;">Dashboard Pengguna</h1>
                <p class="section-subtitle text-start ms-0 lead">Selamat datang kembali, <strong><?= e(get_current_user_name()) ?></strong>!</p>
            </div>
        </div>

        <?= display_flash_message(); ?>

        <!-- Kartu Navigasi Utama -->
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card text-center dashboard-card shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-id-card fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Profil Saya</h5>
                        <p class="card-text text-muted small">Lihat dan perbarui informasi pribadi Anda.</p>
                        <a href="<?= $base_url ?>user/profil.php" class="btn btn-outline-primary stretched-link">Buka Profil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center dashboard-card shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-history fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Riwayat Pemesanan</h5>
                        <p class="card-text text-muted small">Lacak status dan detail semua pemesanan tiket Anda.</p>
                        <!-- Mengganti riwayat_booking.php menjadi riwayat_pemesanan.php -->
                        <a href="<?= $base_url ?>user/riwayat_pemesanan.php" class="btn btn-outline-success stretched-link">Lihat Riwayat</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center dashboard-card shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-ticket-alt fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Pesan Tiket Baru</h5>
                        <p class="card-text text-muted small">Rencanakan kunjungan Anda berikutnya ke Cilengkrang.</p>
                        <!-- Mengganti booking.php menjadi pemesanan_tiket.php -->
                        <a href="<?= $base_url ?>user/pemesanan_tiket.php" class="btn btn-outline-info stretched-link">Pesan Sekarang</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center dashboard-card shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-newspaper fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title">Artikel & Berita</h5>
                        <p class="card-text text-muted small">Baca informasi dan tips terbaru seputar wisata.</p>
                        <a href="<?= $base_url ?>user/artikel.php" class="btn btn-outline-secondary stretched-link">Baca Artikel</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center dashboard-card shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-comments fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Beri Feedback</h5>
                        <p class="card-text text-muted small">Bagikan pengalaman dan masukan Anda kepada kami.</p>
                        <a href="<?= $base_url ?>user/feedback.php" class="btn btn-outline-warning stretched-link">Kirim Feedback</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center dashboard-card shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-headset fa-3x text-danger mb-3"></i>
                        <h5 class="card-title">Hubungi Kami</h5>
                        <p class="card-text text-muted small">Ada pertanyaan? Tim kami siap membantu Anda.</p>
                        <a href="<?= $base_url ?>user/contact.php" class="btn btn-outline-danger stretched-link">Kontak Kami</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Video dan Card Destinasi (jika ingin ditampilkan juga di dashboard user) -->
        <div class="mt-5 pt-4 border-top">
            <h3 class="section-title">Jelajahi Pesona Cilengkrang</h3>
            <div class="card mb-4 shadow-lg"> <!-- Video Utama -->
                <div class="video-wrapper"> <!-- Gunakan .video-wrapper untuk aspek rasio -->
                    <video autoplay loop muted playsinline>
                        <source src="<?= $base_url ?>public/img/curug.mp4" type="video/mp4">
                        Browser Anda tidak mendukung tag video.
                    </video>
                </div>
                <div class="card-body text-center">
                    <h4 class="card-title">Keajaiban Curug Cilengkrang</h4>
                    <p class="card-text text-muted">Saksikan keindahan air terjun yang memukau dan menyegarkan.</p>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <!-- Kartu Destinasi (seperti di index.php atau dashboard lama Anda) -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card hover-effect-card h-100">
                        <img src="<?= $base_url ?>public/img/air_panas.jpg" alt="Air Panas" class="card-img-top" style="height: 250px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">Air Panas Alami</h5>
                            <p class="card-text small text-muted">Nikmati kehangatan air panas alami di tengah pegunungan yang sejuk.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3">
                            <a href="#" class="btn btn-sm btn-primary">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card hover-effect-card h-100">
                        <img src="<?= $base_url ?>public/img/gazebo.jpg" alt="Gazebo" class="card-img-top" style="height: 250px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">Gazebo & Area Santai</h5>
                            <p class="card-text small text-muted">Tempat ideal untuk bersantai bersama keluarga dengan pemandangan asri.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3">
                            <a href="#" class="btn btn-sm btn-primary">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card hover-effect-card h-100">
                        <img src="<?= $base_url ?>public/img/kolam_air_panas.jpg" alt="Kolam Air Panas" class="card-img-top" style="height: 250px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">Kolam Air Panas</h5>
                            <p class="card-text small text-muted">Relaksasi total di kolam air panas dengan suasana alam yang menenangkan.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pb-3">
                            <a href="#" class="btn btn-sm btn-primary">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .dashboard-card .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        /* Atur tinggi minimal kartu */
    }

    .dashboard-card .card-title {
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .dashboard-card .btn {
        margin-top: auto;
        /* Tombol rata bawah */
    }

    .hover-effect-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-effect-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--box-shadow-lg);
    }
</style>
<?php
include_once __DIR__ . '/../template/footer.php';
?>