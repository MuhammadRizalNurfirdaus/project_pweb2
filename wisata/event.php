<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\wisata\event.php
require_once __DIR__ . '/../config/config.php';
// Jika halaman ini hanya bisa diakses user login, tambahkan: require_login();
// Jika data event dinamis dari DB, include Model/Controller Event di sini

include_once __DIR__ . '/../template/header.php';

// Contoh data event (nantinya bisa diambil dari database)
$events = [
    [
        'nama' => 'Festival Alam Cilengkrang Tahunan',
        'tanggal' => 'Setiap Bulan Agustus (Tanggal akan diumumkan)',
        'deskripsi' => 'Rayakan keindahan alam Cilengkrang dengan berbagai pertunjukan seni, pameran produk lokal, workshop lingkungan, dan aktivitas outdoor seru untuk seluruh keluarga.',
        'gambar' => $base_url . 'public/img/event_festival.jpg' // Sediakan gambar event
    ],
    [
        'nama' => 'Kemah Ceria & Edukasi Lingkungan',
        'tanggal' => 'Jadwal Sesuai Permintaan Sekolah/Grup',
        'deskripsi' => 'Program berkemah yang mendidik dan menyenangkan bagi siswa sekolah untuk belajar lebih dekat dengan alam, konservasi, dan teamwork.',
        'gambar' => $base_url . 'public/img/event_kemah.jpg' // Sediakan gambar event
    ],
    [
        'nama' => 'Jelajah Alam Cilengkrang Bersama Komunitas',
        'tanggal' => 'Setiap Sabtu Minggu ke-2 (Pukul 07:00 WIB)',
        'deskripsi' => 'Bergabunglah dengan komunitas pencinta alam untuk menjelajahi jalur trekking tersembunyi, menemukan flora fauna unik, dan menikmati udara segar pegunungan.',
        'gambar' => $base_url . 'public/img/event_jelajah.jpg' // Sediakan gambar event
    ],
];
?>

<div class="main-page-content">
    <section class="hero-small" style="background-image: url('<?= $base_url ?>public/img/background.jpg');"> <!-- Ganti dengan gambar yang relevan -->
        <div class="container">
            <h1 class="display-4">Event & Kegiatan di Cilengkrang</h1>
            <p class="lead">Jangan Lewatkan Keseruan dan Momen Spesial Bersama Kami.</p>
        </div>
    </section>

    <div class="container section-padding">
        <p class="text-center lead mb-5">Cilengkrang tidak hanya menawarkan keindahan alam, tetapi juga berbagai event dan kegiatan menarik yang bisa Anda ikuti. Berikut adalah beberapa di antaranya:</p>

        <?php if (!empty($events)): ?>
            <div class="row g-4">
                <?php foreach ($events as $event): ?>
                    <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                        <div class="card event-card h-100 shadow-sm">
                            <?php if (isset($event['gambar']) && !empty($event['gambar'])): ?>
                                <img src="<?= e($event['gambar']) ?>" class="card-img-top" alt="<?= e($event['nama']) ?>" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-primary"><?= e($event['nama']) ?></h5>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-calendar-alt me-1"></i> <?= e($event['tanggal']) ?>
                                </p>
                                <p class="card-text flex-grow-1"><?= e($event['deskripsi']) ?></p>
                                <a href="#" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Info Selengkapnya</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Saat ini belum ada event terjadwal. Pantau terus halaman ini untuk update terbaru!</div>
        <?php endif; ?>

        <div class="text-center mt-5 pt-4 border-top">
            <h4>Punya Ide Event atau Kegiatan?</h4>
            <p>Kami terbuka untuk kolaborasi dan ide-ide kreatif. <a href="<?= $base_url ?>kontak.php">Hubungi kami</a> untuk diskusi lebih lanjut!</p>
        </div>
    </div>
</div>
<style>
    .event-card .card-title {
        font-weight: 600;
    }
</style>
<?php
include_once __DIR__ . '/../template/footer.php';
?>