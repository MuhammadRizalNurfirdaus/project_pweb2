<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\lokasi.php
require_once __DIR__ . '/../config/config.php';

// Set variabel halaman untuk header
$page_title = "Lokasi Wisata Lembah Cilengkrang";
// $is_homepage = false; // Tidak perlu diset jika defaultnya false di header.php

include_once __DIR__ . '/../template/header.php'; // Header publik

$latitude = -6.93631;
$longitude = 108.428879;
$zoom_level = 15; // Anda bisa menyesuaikan level zoom (misalnya 14-17)
$marker_query = "Lembah+Cilengkrang,+Pajambon,+Kuningan"; // Untuk penanda jika mungkin
$google_maps_embed_url = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15843.322070039048!2d{$longitude}!3d{$latitude}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e692ff3f6f1597b%3A0x40a810886063062c!2sLembah%20Cilengkrang!5e0!3m2!1sid!2sid!4vDATE_TIMESTAMP";
$google_maps_embed_url = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.838261736097!2d108.42389007481896!3d-6.936302993087586!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e692ff000000001%3A0x7b236c524f4f585e!2sLembah%20Cilengkrang!5e0!3m2!1sid!2sid!4v1715400000000!5m2!1sid!2sid"; // Ganti 4v dengan timestamp yang valid jika perlu

// Link untuk tombol "Dapatkan Petunjuk Arah"
$google_maps_direction_url = "https://www.google.com/maps/dir/?api=1&destination=Lembah+Cilengkrang,+Pajambon,+Kramatmulya,+Kuningan,+Jawa+Barat";

?>

<div class="main-page-content">
    <section class="hero-small" style="background-image: url('<?= $base_url ?>public/img/mobil.jpg');"> <!-- Sediakan gambar public/img/peta_ilustrasi.jpg -->
        <div class="container">
            <h1 class="display-4 text-white fw-bold">Lokasi Kami</h1>
            <p class="lead">Temukan Rute Terbaik Menuju Petualangan Anda di Lembah Cilengkrang.</p>
        </div>
    </section>

    <div class="container section-padding">
        <div class="row">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <h2 class="section-title text-start" style="margin-top:0;">Peta Interaktif Lembah Cilengkrang</h2>
                <div class="map-responsive shadow-lg rounded">
                    <iframe src="<?= e($google_maps_embed_url) ?>" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Peta Lokasi Lembah Cilengkrang"></iframe>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card location-info-card shadow-sm h-100"> <!-- Tambah h-100 untuk tinggi yang sama jika kontennya pendek -->
                    <div class="card-body d-flex flex-column">
                        <h3 class="card-title text-primary"><i class="fas fa-map-marked-alt me-2"></i>Alamat Lengkap</h3>
                        <address class="fs-5 mb-0">
                            <strong>Lembah Cilengkrang</strong><br>
                            Desa Pajambon, Kecamatan Kramatmulya,<br>
                            Kabupaten Kuningan, Jawa Barat, Indonesia.
                        </address>
                        <p class="text-muted small mt-1">(Sekitar 30km dari pusat kota Kuningan)</p>
                        <hr>
                        <h4 class="h5 mt-3">Aksesibilitas:</h4>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-car me-2 text-muted"></i> Dapat diakses dengan kendaraan pribadi (mobil/motor).</li>
                            <li><i class="fas fa-bus me-2 text-muted"></i> Angkutan umum mungkin terbatas, pertimbangkan ojek atau sewa kendaraan dari titik tertentu.</li>
                            <li><i class="fas fa-road me-2 text-muted"></i> Kondisi jalan menuju lokasi bervariasi, sebagian baik dan sebagian mungkin memerlukan perhatian lebih.</li>
                        </ul>
                        <div class="mt-auto"> <!-- Dorong tombol ke bawah -->
                            <hr>
                            <a href="<?= e($google_maps_direction_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-directions me-2"></i> Dapatkan Petunjuk Arah
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-4 border-top">
            <h3 class="text-center section-title">Tips Perjalanan ke Cilengkrang</h3>
            <div class="row g-4"> <!-- Ganti g-3 menjadi g-4 untuk jarak lebih -->
                <div class="col-md-4">
                    <div class="card alert-light-custom border text-center h-100 p-3">
                        <div class="icon-feature-tip mb-3">
                            <i class="fas fa-cloud-sun fa-3x text-success"></i>
                        </div>
                        <h5 class="h6">Cek Cuaca</h5>
                        <p class="small mb-0">Periksa prakiraan cuaca sebelum berangkat, terutama jika berencana aktivitas outdoor seperti berkemah atau trekking.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card alert-light-custom border text-center h-100 p-3">
                        <div class="icon-feature-tip mb-3">
                            <i class="fas fa-gas-pump fa-3x text-success"></i>
                        </div>
                        <h5 class="h6">Bahan Bakar</h5>
                        <p class="small mb-0">Pastikan bahan bakar kendaraan Anda cukup. Pom bensin mungkin lebih jarang ditemui di area dekat wisata alam.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card alert-light-custom border text-center h-100 p-3"> 
                        <div class=" icon-feature-tip mb-3">
                            <i class="fas fa-camera-retro fa-3x text-success"></i>
                        </div>
                        <h5 class="h6">Abadikan Momen</h5>
                        <p class="small mb-0">Bawa kamera atau ponsel dengan baterai penuh untuk mengabadikan keindahan alam dan momen spesial Anda!</p>
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
        padding: clamp(4rem, 10vh, 6rem) 1.25rem;
        /* Padding disesuaikan (clamp(atas/bawah, preferensi viewport, maksimal) kiri/kanan) */
        color: var(--light-text);
        text-align: center;
        border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
        margin-bottom: 2.5rem;
        /* Jarak ke konten di bawah */
        position: relative;
        /* Untuk overlay jika diperlukan */
    }

    .hero-small::before {
        /* Overlay untuk kontras teks */
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.35);
        border-radius: इन्हेरिट;
        /* Mengikuti border-radius parent */
    }

    .hero-small .container {
        position: relative;
        /* Agar konten di atas overlay */
        z-index: 1;
    }

    .hero-small h1 {
        color: var(--light-text);
        text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.6);
        margin-top: 0;
        font-size: clamp(2.2rem, 5vw, 3rem);
        /* Ukuran font h1 di hero kecil */
    }

    .hero-small p.lead {
        font-size: clamp(1rem, 2.5vw, 1.25rem);
        color: rgba(255, 255, 255, 0.9);
    }

    .map-responsive {
        overflow: hidden;
        padding-bottom: 56.25%;
        /* 16:9 Aspect Ratio */
        position: relative;
        height: 0;
        border-radius: var(--border-radius-md);
        /* Tambahkan border-radius ke wrapper peta */
    }

    .map-responsive iframe {
        left: 0;
        top: 0;
        height: 100%;
        width: 100%;
        position: absolute;
        /* border-radius sudah di wrapper */
    }

    .location-info-card {
        border-left: 5px solid var(--primary-color);
    }

    .location-info-card address {
        line-height: 1.6;
    }

    .alert-light-custom {
        /* Mengganti .alert.alert-light untuk styling yang lebih fleksibel */
        background-color: var(--very-light-bg);
        /* Atau sedikit off-white var(--light-bg) */
        border: 1px solid var(--border-color) !important;
        /* Bootstrap mungkin override, jadi !important jika perlu */
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    .icon-feature-tip i {
        transition: color 0.3s ease;
    }

    /* Mode Gelap untuk elemen spesifik halaman lokasi */
    body.dark-mode .hero-small::before {
        background-color: rgba(0, 0, 0, 0.55);
        /* Overlay lebih gelap di mode gelap */
    }

    body.dark-mode .location-info-card {
        border-left-color: var(--primary-lighter);
        /* Warna border aksen di mode gelap */
    }

    body.dark-mode .alert-light-custom {
        background-color: var(--very-light-bg);
        /* Sesuai dengan background card di mode gelap */
        border-color: var(--border-color) !important;
    }

    body.dark-mode .alert-light-custom .text-secondary {
        /* Ubah warna ikon di tips perjalanan */
        color: var(--secondary-color) !important;
        /* Jadi kuning agar lebih terlihat */
    }
</style>
<?php
include_once __DIR__ . '/../template/footer.php';
?>