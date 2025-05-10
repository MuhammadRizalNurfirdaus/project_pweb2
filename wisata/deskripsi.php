<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\deskripsi.php
require_once __DIR__ . '/../config/config.php'; // Akses config.php dari wisata/
// Jika halaman ini hanya bisa diakses user login, tambahkan: require_login();

include_once __DIR__ . '/../template/header.php'; // Header publik
?>

<div class="main-page-content"> <!-- Pembungkus konten utama -->
    <section class="hero-small" style="background-image: url('<?= $base_url ?>public/img/gazebo.jpg');">
        <div class="container">
            <h1 class="display-4">Deskripsi Wisata Cilengkrang</h1>
            <p class="lead">Menyelami Keindahan dan Pesona Alam yang Ditawarkan.</p>
        </div>
    </section>

    <div class="container section-padding">
        <div class="row">
            <div class="col-lg-8">
                <h2 class="section-title text-start" style="margin-top:0;">Selamat Datang di Surga Tersembunyi</h2>
                <p class="lead mb-4">
                    Cilengkrang adalah sebuah destinasi wisata alam memukau yang terletak di [Sebutkan Lokasi Lebih Detail, misal: kaki Gunung Manglayang], menawarkan perpaduan sempurna antara keindahan alam yang masih asri, udara pegunungan yang sejuk menyegarkan, dan beragam atraksi menarik yang siap memanjakan setiap pengunjung.
                </p>
                <p>
                    Dari gemericik air Curug Cilengkrang yang menenangkan, kehangatan alami pemandian air panas yang merelaksasi, hingga keteduhan gazebo-gazebo yang tersebar di tengah hutan pinus yang rindang, Cilengkrang menjanjikan pengalaman liburan yang tak terlupakan. Tempat ini adalah pilihan ideal bagi Anda yang ingin melepaskan diri sejenak dari hiruk pikuk perkotaan dan menyatu kembali dengan alam.
                </p>

                <h3 class="mt-5">Apa yang Bisa Anda Temukan?</h3>
                <ul class="list-unstyled feature-list">
                    <li><i class="fas fa-tint text-primary me-2"></i> Pemandian Air Panas Alami dengan khasiat terapeutik.</li>
                    <li><i class="fas fa-water text-primary me-2"></i> Curug (Air Terjun) Cilengkrang yang eksotis dan menyegarkan.</li>
                    <li><i class="fas fa-campground text-primary me-2"></i> Area Camping Ground yang luas dan nyaman.</li>
                    <li><i class="fas fa-tree text-primary me-2"></i> Hutan Pinus yang rindang untuk trekking dan bersantai.</li>
                    <li><i class="fas fa-camera text-primary me-2"></i> Spot-spot foto Instagramable dengan latar alam yang indah.</li>
                    <li><i class="fas fa-utensils text-primary me-2"></i> Warung dan area kuliner dengan hidangan lokal.</li>
                </ul>

                <p class="mt-4">
                    Kami mengundang Anda untuk menjelajahi setiap sudut keindahan Cilengkrang, merasakan kedamaian yang ditawarkannya, dan membawa pulang kenangan manis. Cilengkrang bukan hanya sekadar tempat wisata, tetapi juga sebuah pengalaman yang akan memperkaya jiwa Anda.
                </p>
            </div>
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;"> <!-- Membuat konten sidebar sticky -->
                    <div class="card shadow-sm mb-4">
                        <img src="<?= $base_url ?>public/img/air_panas_polos.jpg" class="card-img-top" alt="Atraksi Unggulan">
                        <div class="card-body">
                            <h5 class="card-title">Atraksi Unggulan</h5>
                            <p class="card-text small text-muted">Jangan lewatkan pemandian air panas dan keindahan Curug Cilengkrang.</p>
                            <a href="<?= $base_url ?>wisata/galeri.php" class="btn btn-sm btn-outline-primary">Lihat Galeri</a>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Informasi Cepat</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><i class="fas fa-clock me-2"></i> Jam Buka: 08:00 - 17:00 WIB</li>
                                <li class="list-group-item"><i class="fas fa-ticket-alt me-2"></i> Tiket Masuk: Mulai Rp 20.000</li>
                                <li class="list-group-item"><i class="fas fa-map-marker-alt me-2"></i> <a href="<?= $base_url ?>wisata/lokasi.php">Lihat Peta Lokasi</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .hero-small {
        background-size: cover;
        background-position: center;
        padding: 60px 20px;
        color: var(--light-text);
        text-align: center;
        border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
        margin-bottom: 2rem;
    }

    .hero-small h1 {
        color: var(--light-text);
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        margin-top: 0;
    }

    .feature-list li {
        padding: 8px 0;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
    }

    .feature-list li i {
        width: 25px;
        /* Agar ikon rata */
    }
</style>
<?php
include_once __DIR__ . '/../template/footer.php';
?>