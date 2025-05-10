<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\video.php
require_once __DIR__ . '/../config/config.php';
// Jika halaman ini hanya bisa diakses user login, tambahkan: require_login();

include_once __DIR__ . '/../template/header.php'; // Header publik

// Contoh data video (nantinya bisa dari database)
$videos = [
    [
        'src' => $base_url . 'public/img/background.mp4', // Video utama Anda
        'title' => 'Pesona Alam Cilengkrang - Cinematic View',
        'description' => 'Nikmati keindahan lanskap Cilengkrang dari udara dan darat dalam sajian video sinematik.'
    ],
    [
        'src' => $base_url . 'public/img/curug.mp4', // Video curug
        'title' => 'Gemuruh Air Terjun Curug Cilengkrang',
        'description' => 'Rasakan kesegaran dan keindahan Curug Cilengkrang dari dekat.'
    ],
    // Tambahkan video lain jika ada
    // [
    //     'src' => $base_url . 'public/img/aktivitas_outbound.mp4',
    //     'title' => 'Keseruan Aktivitas Outbound di Cilengkrang',
    //     'description' => 'Lihat berbagai kegiatan outbound yang bisa Anda nikmati bersama tim atau keluarga.'
    // ],
];
?>

<div class="main-page-content">
    <section class="hero-small" style="background-image: url('<?= $base_url ?>public/img/video_hero_bg.jpg');"> <!-- Sediakan gambar background untuk hero video -->
        <div class="container">
            <h1 class="display-4">Video Galeri Cilengkrang</h1>
            <p class="lead">Lihat Lebih Dekat Keindahan Wisata Kami Melalui Video.</p>
        </div>
    </section>

    <div class="container section-padding">
        <p class="text-center lead mb-5">Saksikan berbagai video menarik yang menampilkan pesona alam, kegiatan seru, dan fasilitas di Wisata Alam Cilengkrang.</p>

        <?php if (!empty($videos)): ?>
            <div class="row g-4">
                <?php foreach ($videos as $video): ?>
                    <div class="col-md-6">
                        <div class="card video-card shadow-sm h-100">
                            <div class="video-wrapper-custom"> <!-- Wrapper khusus untuk aspek rasio video -->
                                <video controls poster="<?= $base_url ?>public/img/video_poster_placeholder.jpg"> <!-- Sediakan gambar poster -->
                                    <source src="<?= e($video['src']) ?>" type="video/mp4">
                                    Browser Anda tidak mendukung tag video.
                                </video>
                            </div>
                            <div class="card-body">
                                <?php if (isset($video['title']) && !empty($video['title'])): ?>
                                    <h5 class="card-title text-primary"><?= e($video['title']) ?></h5>
                                <?php endif; ?>
                                <?php if (isset($video['description']) && !empty($video['description'])): ?>
                                    <p class="card-text small text-muted"><?= e($video['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Belum ada video untuk ditampilkan.</div>
        <?php endif; ?>
    </div>
</div>
<style>
    .video-wrapper-custom {
        position: relative;
        width: 100%;
        padding-top: 56.25%;
        /* 16:9 Aspect Ratio */
        background-color: #000;
        /* Warna latar jika video belum load */
        border-top-left-radius: var(--border-radius-md);
        /* Sesuaikan dengan radius card */
        border-top-right-radius: var(--border-radius-md);
        overflow: hidden;
    }

    .video-wrapper-custom video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* Agar video memenuhi wrapper tanpa distorsi */
    }

    .video-card .card-body {
        padding: 1rem 1.25rem;
    }
</style>

<?php
include_once __DIR__ . '/../template/footer.php';
?>