<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\index.php

// 1. Selalu sertakan config.php pertama kali
if (!@require_once __DIR__ . '/config/config.php') {
  http_response_code(500);
  error_log("FATAL: Gagal memuat config.php dari index.php");
  // Tampilkan pesan error yang lebih ramah pengguna di produksi
  exit("Terjadi kesalahan pada server. Mohon coba lagi nanti atau hubungi administrator.");
}

// 2. Sertakan Model Artikel untuk mengambil data artikel terbaru
$artikel_terbaru = []; // Inisialisasi
if (!@require_once __DIR__ . '/models/Artikel.php') {
  error_log("Kritis: Gagal memuat model Artikel.php dari index.php. Fitur artikel terbaru mungkin tidak berfungsi.");
  // Halaman bisa tetap berjalan, $artikel_terbaru akan kosong
} else {
  if (isset($conn) && $conn) {
    $artikel_model = new Artikel($conn);
    try {
      $artikel_terbaru = $artikel_model->getLatest(3); // Ambil 3 artikel terbaru
    } catch (Throwable $e) { // Menangkap semua jenis error/exception dari model
      error_log("Error saat mengambil artikel terbaru di index.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
      // $artikel_terbaru akan tetap array kosong
    }
  } else {
    error_log("Kritis: Koneksi database (\$conn) tidak tersedia di index.php setelah memuat config. Fitur yang memerlukan database mungkin gagal.");
  }
}

$page_title = "Beranda";
$is_homepage = true; // Digunakan di header.php untuk logika navbar transparan

// 3. Sertakan header publik
// Pastikan header.php tidak memanggil require_login() karena ini adalah halaman publik
if (!@include_once __DIR__ . '/template/header.php') {
  http_response_code(500);
  error_log("FATAL: Gagal memuat template/header.php dari index.php");
  exit("Terjadi kesalahan pada tampilan server. Mohon coba lagi nanti.");
}
?>

<div class="main-page-content">

  <section class="hero-video-background text-white text-center d-flex align-items-center">
    <!-- Video latar belakang dengan poster untuk browser yang tidak mendukung video autoplay -->
    <video playsinline autoplay muted loop poster="<?= $base_url ?>public/img/background_poster.jpg" id="bgvid" aria-label="Video latar pemandangan Lembah Cilengkrang">
      <source src="<?= $base_url ?>public/img/background.mp4" type="video/mp4">
      Browser Anda tidak mendukung tag video.
    </video>
    <div class="hero-overlay"></div>
    <div class="container hero-content animate-on-scroll">
      <!-- Logo di tengah hero sudah dihapus sesuai permintaan -->

      <h1 class="display-3 fw-bolder text-shadow-strong">Jelajahi Pesona Alam Cilengkrang</h1>
      <p class="lead my-4 col-lg-10 mx-auto text-shadow-soft">
        Lembah Cilengkrang terletak di Pajambon, Kramatmulya, Kuningan, Jawa Barat, sekitar 30km dari pusat kota Kuningan.
        Destinasi ini menawarkan keindahan air terjun menawan, relaksasi di pemandian air panas alami, dan kesegaran udara pegunungan.
      </p>
      <a href="<?= $base_url ?>wisata/deskripsi.php" class="btn btn-primary btn-lg me-sm-2 mb-3 mb-sm-0 hero-btn">
        <i class="fas fa-info-circle me-2"></i>Pelajari Lebih Lanjut
      </a>
      <!-- Mengganti user/booking.php menjadi user/pemesanan_tiket.php -->
      <a href="<?= $base_url ?>user/pemesanan_tiket.php" class="btn btn-light btn-lg hero-btn">
        <i class="fas fa-ticket-alt me-2"></i>Pesan Tiket Sekarang
      </a>
    </div>
  </section>

  <section class="section-padding">
    <div class="container">
      <h2 class="section-title">Mengapa Memilih Cilengkrang?</h2>
      <p class="section-subtitle">Destinasi sempurna untuk petualangan, relaksasi, dan momen tak terlupakan bersama orang terkasih.</p>
      <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card feature-card text-center p-lg-4 p-3 h-100 animate-on-scroll">
            <div class="icon text-primary display-1 mb-3"><i class="fas fa-water"></i></div>
            <div class="card-body p-0">
              <h5 class="card-title h4">Air Terjun Memukau</h5>
              <p class="card-text">Saksikan keagungan Curug Cilengkrang dengan airnya yang jernih dan panorama alam yang menyegarkan mata serta jiwa.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="card feature-card text-center p-lg-4 p-3 h-100 animate-on-scroll" data-animation-delay="100ms">
            <div class="icon text-success display-1 mb-3"><i class="fas fa-hot-tub"></i></div>
            <div class="card-body p-0">
              <h5 class="card-title h4">Pemandian Air Panas</h5>
              <p class="card-text">Relaksasikan tubuh dan pikiran Anda di sumber air panas alami yang kaya akan mineral dan berkhasiat untuk kesehatan.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="card feature-card text-center p-lg-4 p-3 h-100 animate-on-scroll" data-animation-delay="200ms">
            <div class="icon text-info display-1 mb-3"><i class="fas fa-tree"></i></div>
            <div class="card-body p-0">
              <h5 class="card-title h4">Keindahan Hutan Pinus</h5>
              <p class="card-text">Nikmati trekking santai atau piknik di tengah keteduhan hutan pinus yang asri, hirup udara segar khas pegunungan.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section-padding bg-light-custom">
    <div class="container">
      <h2 class="section-title">Destinasi Populer Kami</h2>
      <div class="row g-4">
        <?php
        // Data destinasi ini idealnya diambil dari database (tabel 'wisata')
        // Contoh data statis untuk sekarang:
        $destinasi_populer = [
          ['id_db' => 1, 'slug' => 'pemandian-air-panas', 'gambar' => 'air_panas.jpg', 'judul' => 'Pemandian Air Panas', 'deskripsi' => 'Rasakan relaksasi alami dengan berendam di air hangat pegunungan yang menyegarkan.'],
          ['id_db' => 2, 'slug' => 'gazebo-area-santai', 'gambar' => 'gazebo.jpg', 'judul' => 'Gazebo & Area Santai', 'deskripsi' => 'Tempat ideal untuk bersantai bersama keluarga dengan pemandangan asri dan udara segar.'],
          ['id_db' => 3, 'slug' => 'kolam-air-panas-keluarga', 'gambar' => 'kolam_air_panas.jpg', 'judul' => 'Kolam Air Panas Keluarga', 'deskripsi' => 'Nikmati momen relaksasi bersama keluarga di kolam air panas dengan suasana alam yang menenangkan.'],
        ];
        $delay_animasi = 0;
        foreach ($destinasi_populer as $dest) :
        ?>
          <div class="col-lg-4 col-md-6 animate-on-scroll" data-animation-delay="<?= $delay_animasi ?>ms">
            <div class="card destination-card shadow-sm h-100">
              <a href="<?= $base_url ?>wisata/detail_destinasi.php?id=<?= e($dest['id_db']) ?>&slug=<?= e($dest['slug']) ?>" class="stretched-link-card-custom">
                <img src="<?= $base_url ?>public/img/<?= e($dest['gambar']) ?>" loading="lazy" alt="<?= e($dest['judul']) ?>" class="card-img-top">
              </a>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= e($dest['judul']) ?></h5>
                <p class="card-text text-muted small flex-grow-1"><?= e($dest['deskripsi']) ?></p>
                <a href="<?= $base_url ?>wisata/detail_destinasi.php?id=<?= e($dest['id_db']) ?>&slug=<?= e($dest['slug']) ?>" class="btn btn-sm btn-primary mt-auto align-self-start">Lihat Detail <i class="fas fa-chevron-right fa-xs ms-1"></i></a>
              </div>
            </div>
          </div>
        <?php $delay_animasi += 100;
        endforeach; ?>
      </div>
      <div class="text-center mt-5">
        <a href="<?= $base_url ?>wisata/semua_destinasi.php" class="btn btn-secondary btn-lg">
          <i class="fas fa-th-large me-2"></i>Lihat Semua Destinasi
        </a>
      </div>
    </div>
  </section>

  <?php if (!empty($artikel_terbaru)): ?>
    <section class="section-padding">
      <div class="container">
        <h2 class="section-title">Baca Artikel & Tips Terbaru</h2>
        <div class="row g-4">
          <?php
          $delay_animasi_artikel = 0;
          foreach ($artikel_terbaru as $artikel): ?>
            <div class="col-md-6 col-lg-4 animate-on-scroll" data-animation-delay="<?= $delay_animasi_artikel ?>ms">
              <div class="card article-card-home h-100 shadow-sm">
                <?php
                $gambar_url_artikel = $base_url . 'public/img/default_artikel_thumbnail.jpg'; // Pastikan gambar ini ada
                if (!empty($artikel['gambar'])) {
                  $gambar_url_artikel = $base_url . 'public/uploads/artikel/' . e($artikel['gambar']);
                }
                ?>
                <a href="<?= $base_url ?>user/artikel_detail.php?id=<?= e($artikel['id']) ?>" class="text-decoration-none">
                  <img src="<?= $gambar_url_artikel ?>" loading="lazy" class="card-img-top" alt="<?= e($artikel['judul']) ?>">
                </a>
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title">
                    <a href="<?= $base_url ?>user/artikel_detail.php?id=<?= e($artikel['id']) ?>" class="text-decoration-none stretched-link-card-custom">
                      <?= e($artikel['judul']) ?>
                    </a>
                  </h5>
                  <p class="card-text text-muted small mb-2">
                    <i class="fas fa-calendar-alt me-1"></i> <?= e(date('d F Y', strtotime($artikel['created_at']))) ?>
                  </p>
                  <p class="card-text flex-grow-1"><?= e(substr(strip_tags($artikel['isi']), 0, 100)) ?>...</p>
                  <a href="<?= $base_url ?>user/artikel_detail.php?id=<?= e($artikel['id']) ?>" class="btn btn-sm btn-outline-secondary mt-auto align-self-start">
                    Baca Selengkapnya <i class="fas fa-long-arrow-alt-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php $delay_animasi_artikel += 100;
          endforeach; ?>
        </div>
        <div class="text-center mt-5">
          <a href="<?= $base_url ?>user/artikel.php" class="btn btn-outline-primary btn-lg">Lihat Semua Artikel</a>
        </div>
      </div>
    </section>
  <?php else: ?>
    <section class="section-padding">
      <div class="container">
        <h2 class="section-title">Artikel & Tips Terbaru</h2>
        <p class="text-center text-muted">Belum ada artikel terbaru untuk saat ini. Silakan kunjungi kembali nanti!</p>
      </div>
    </section>
  <?php endif; ?>

  <section class="section-padding bg-light-custom testimonial-section">
    <div class="container">
      <h2 class="section-title">Apa Kata Mereka?</h2>
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">
            <div class="carousel-indicators">
              <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Testimoni 1"></button>
              <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="1" aria-label="Testimoni 2"></button>
              <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="2" aria-label="Testimoni 3"></button>
            </div>
            <div class="carousel-inner rounded shadow-lg">
              <?php
              // Data testimoni ini idealnya dari database
              $testimonies = [
                ['avatar' => 'avatar1.jpg', 'nama' => 'Rina Amelia - Bandung', 'testimoni' => 'Pengalaman luar biasa! Air panasnya benar-benar menyegarkan dan pemandangannya indah sekali. Sangat cocok untuk liburan keluarga.'],
                ['avatar' => 'avatar2.jpg', 'nama' => 'Budi Santoso - Jakarta', 'testimoni' => 'Stafnya ramah dan fasilitasnya cukup bersih. Anak-anak senang bermain di area curug yang sejuk. Pasti akan kembali lagi suatu saat!'],
                ['avatar' => 'avatar3.jpg', 'nama' => 'Siti Nurhaliza - Cimahi', 'testimoni' => 'Tempat yang tepat untuk healing dari hiruk pikuk kota. Suasana hutannya menenangkan, dan air panasnya bikin rileks. Recommended!'],
              ];
              foreach ($testimonies as $index => $testi) :
              ?>
                <div class="carousel-item <?= $index == 0 ? 'active' : '' ?>">
                  <div class="testimonial-card-item p-4 p-md-5">
                    <!-- Pastikan gambar avatar ada dan DIOPTIMASI -->
                    <img src="<?= $base_url ?>public/img/<?= e($testi['avatar']) ?>" loading="lazy" class="testimonial-avatar rounded-circle mb-3" alt="Foto <?= e($testi['nama']) ?>">
                    <blockquote><?= e($testi['testimoni']) ?></blockquote>
                    <cite class="testimonial-author"><?= e($testi['nama']) ?></cite>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section-padding text-center cta-section">
    <div class="container">
      <div class="animate-on-scroll">
        <h2 class="section-title text-white">Siap untuk Petualangan Berikutnya?</h2>
        <p class="lead mb-4 mx-auto text-white-90" style="max-width: 700px;">Cilengkrang menanti kedatangan Anda dengan sejuta pesona alam, keramahan, dan pengalaman tak terlupakan yang akan memperkaya jiwa.</p>
        <!-- Mengganti user/booking.php menjadi user/pemesanan_tiket.php -->
        <a href="<?= $base_url ?>user/pemesanan_tiket.php" class="btn btn-light btn-lg me-sm-2 mb-3 mb-sm-0 hero-btn">
          <i class="fas fa-calendar-check me-2"></i> Rencanakan Kunjungan
        </a>
        <a href="<?= $base_url ?>kontak.php" class="btn btn-outline-light btn-lg hero-btn">
          <i class="fas fa-envelope me-2"></i> Hubungi Kami
        </a>
      </div>
    </div>
  </section>

</div> <!-- Penutup .main-page-content -->

<?php
if (!@include_once __DIR__ . '/template/footer.php') {
  error_log("FATAL: Gagal memuat template/footer.php dari index.php");
  // Jika header berhasil, mungkin cukup log error saja, atau tampilkan pesan minimal
  // echo "<p style='text-align:center;color:red;'>Error memuat footer.</p>";
}
?>