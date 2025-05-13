<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\galeri.php
require_once __DIR__ . '/../config/config.php';
// Jika data galeri dinamis dari DB, include Model/Controller Galeri di sini
// require_once __DIR__ . '/../models/Galeri.php';
// $galeri_model = new Galeri($conn);
// $foto_galeri = $galeri_model->getAllPublished(); // Contoh method

include_once __DIR__ . '/../template/header.php'; // Header PUBLIK

// Contoh data galeri statis (nantinya dari database)
$gallery_items = [
    ['src' => $base_url . 'public/img/air_panas.jpg', 'alt' => 'Pemandian Air Panas Cilengkrang', 'caption' => 'Relaksasi di Air Panas Alami'],
    ['src' => $base_url . 'public/img/gazebo.jpg', 'alt' => 'Gazebo di Tepi Sungai', 'caption' => 'Santai di Gazebo Tepi Sungai'],
    ['src' => $base_url . 'public/img/kolam_air_panas.jpg', 'alt' => 'Kolam Air Panas Utama', 'caption' => 'Kolam Air Panas Utama'],
    ['src' => $base_url . 'public/img/curug_cilengkrang_view.jpg', 'alt' => 'Pemandangan Curug Cilengkrang', 'caption' => 'Keindahan Curug Cilengkrang'], // Ganti dengan gambar curug
    ['src' => $base_url . 'public/img/hutan_pinus.jpg', 'alt' => 'Hutan Pinus Cilengkrang', 'caption' => 'Jelajah Hutan Pinus'], // Sediakan gambar
    ['src' => $base_url . 'public/img/camping_ground.jpg', 'alt' => 'Area Camping Cilengkrang', 'caption' => 'Berkemah di Alam Terbuka'], // Sediakan gambar
];
?>

<div class="main-page-content">
    <section class="hero-small" style="background-image: url('<?= $base_url ?>public/img/pohon.jpg');">
        <div class="container">
            <div class="d-flex justify-content-center align-items-center" style="height: 50vh;">
        <h1 class="display-4 text-center text-white fw-bold">Galeri Foto Cilengkrang</h1>


            </div>
        </div>
    </section>

    <div class="container section-padding">
        <p class="text-center lead mb-5">Berikut adalah koleksi foto-foto yang menangkap momen dan keindahan berbagai sudut di Wisata Alam Cilengkrang.</p>

        <?php if (!empty($gallery_items)): ?>
            <div class="row g-3 g-lg-4">
                <?php foreach ($gallery_items as $item): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="gallery-item shadow-sm">
                            <a href="<?= e($item['src']) ?>" data-bs-toggle="lightbox" data-gallery="wisata-gallery" data-title="<?= e($item['caption']) ?>">
                                <img src="<?= e($item['src']) ?>" class="img-fluid" alt="<?= e($item['alt']) ?>">
                                <?php if (!empty($item['caption'])): ?>
                                    <!-- Opsi caption langsung di gambar, bisa di-style dengan CSS position absolute -->
                                    <!-- <div class="gallery-caption p-2 bg-dark text-white bg-opacity-75 small"><?= e($item['caption']) ?></div> -->
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Belum ada foto untuk ditampilkan di galeri.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bs5-lightbox@1.8.3/dist/index.bundle.min.js"></script>
<style>
    .gallery-item img {
        aspect-ratio: 1 / 1;
        /* Membuat gambar menjadi kotak */
        object-fit: cover;
        border-radius: var(--border-radius-md);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.05);
        box-shadow: var(--box-shadow-lg);
    }

    .gallery-item {
        position: relative;
    }

    .gallery-caption {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px;
        font-size: 0.85rem;
        text-align: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .gallery-item:hover .gallery-caption {
        opacity: 1;
    }

    */
</style>
<?php
include_once __DIR__ . '/../template/footer.php';
?>