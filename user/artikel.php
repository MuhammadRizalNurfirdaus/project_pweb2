<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\artikel.php
require_once __DIR__ . '/../config/config.php'; // Akses config.php dari user/
require_once __DIR__ . '/../models/Artikel.php';   // Akses Artikel.php dari user/

require_login(); // Pastikan pengguna sudah login

// Inisialisasi model Artikel
$artikel_model = new Artikel($conn); // $conn dari config.php
$daftar_artikel = $artikel_model->getAll(); // Ambil semua artikel

// Sertakan header_user.php setelah semua logika PHP awal selesai
include_once __DIR__ . '/../template/header_user.php';
?>

<div class="main-page-content"> <!-- Pembungkus konten utama -->
    <div class="container section-padding">
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h1 class="section-title text-start mb-0" style="margin-top:0;">Artikel & Berita Wisata</h1>
                <p class="section-subtitle text-start ms-0">Temukan cerita, tips, dan informasi menarik seputar destinasi Cilengkrang.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <!-- Mungkin ada tombol filter atau search di sini nanti -->
            </div>
        </div>

        <?php if (!empty($daftar_artikel)): ?>
            <div class="row g-4">
                <?php foreach ($daftar_artikel as $artikel): ?>
                    <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                        <div class="card article-card h-100">
                            <?php if (!empty($artikel['gambar'])): ?>
                                <img src="<?= $base_url ?>public/uploads/artikel/<?= e($artikel['gambar']) ?>" class="card-img-top" alt="<?= e($artikel['judul']) ?>">
                            <?php else: ?>
                                <img src="<?= $base_url ?>public/img/default_artikel.jpg" class="card-img-top" alt="Gambar default artikel"> <!-- Sediakan gambar default -->
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><a href="<?= $base_url ?>user/artikel_detail.php?id=<?= e($artikel['id']) ?>" class="stretched-link text-decoration-none"><?= e($artikel['judul']) ?></a></h5>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-calendar-alt me-1"></i> <?= e(date('d F Y', strtotime($artikel['created_at']))) ?>
                                </p>
                                <p class="card-text flex-grow-1"><?= e(substr(strip_tags($artikel['isi']), 0, 120)) ?>...</p>
                                <a href="<?= $base_url ?>user/artikel_detail.php?id=<?= e($artikel['id']) ?>" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Baca Selengkapnya <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <p class="mb-0">Belum ada artikel yang dipublikasikan saat ini. Silakan cek kembali nanti!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include_once __DIR__ . '/../template/footer.php';
?>