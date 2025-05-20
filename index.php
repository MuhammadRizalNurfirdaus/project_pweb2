<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\index.php

// 1. Selalu sertakan config.php pertama kali
// config.php akan memuat semua helper, memulai session, koneksi DB, model, dan controller.
if (!@require_once __DIR__ . '/config/config.php') {
  http_response_code(503);
  error_log("FATAL ERROR di index.php: Gagal memuat config.php. Path yang dicoba: " . realpath(__DIR__ . '/config/config.php'));
  $error_message_display = "Terjadi kesalahan pada server. Mohon coba lagi nanti atau hubungi administrator. (Kode: IDX_CFG_LOAD_FAIL)";
  if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) { // Hanya tampilkan detail path jika development
    $error_message_display = "Kesalahan Kritis: Gagal memuat file konfigurasi utama (config.php). Aplikasi tidak dapat berjalan. Path yang dicoba: " . realpath(__DIR__ . '/config/config.php');
  }
  exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>" . htmlspecialchars($error_message_display, ENT_QUOTES, 'UTF-8') . "</div>");
}

// 2. Ambil data artikel terbaru
$artikel_terbaru = [];
$error_artikel = null;

if (class_exists('Artikel') && method_exists('Artikel', 'getLatest')) {
  // $conn seharusnya sudah tersedia dan valid dari config.php
  if (isset($conn) && $conn instanceof mysqli && @$conn->ping()) { // @ untuk menekan warning jika ping gagal tapi koneksi ada
    try {
      $artikel_terbaru = Artikel::getLatest(3);
      if ($artikel_terbaru === false || $artikel_terbaru === null) {
        $artikel_terbaru = [];
        $artikel_model_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : null;
        if ($artikel_model_error) {
          $error_artikel = "Gagal mengambil artikel terbaru: " . e($artikel_model_error);
          error_log("INDEX_PAGE_ERROR - Artikel::getLatest(): " . $artikel_model_error);
        }
      }
    } catch (Throwable $e) {
      error_log("INDEX_PAGE_EXCEPTION - Artikel::getLatest(): " . $e->getMessage() . "\n" . $e->getTraceAsString());
      $error_artikel = "Terjadi kesalahan sistem saat memuat artikel terbaru.";
      $artikel_terbaru = [];
    }
  } else {
    $error_artikel = "Koneksi database tidak tersedia untuk mengambil artikel.";
    error_log("INDEX_PAGE_CRITICAL - Koneksi database (\$conn) tidak tersedia/valid untuk artikel.");
  }
} else {
  $error_artikel = "Fitur artikel tidak dapat dimuat saat ini.";
  error_log("INDEX_PAGE_CRITICAL - Kelas Artikel atau metode getLatest tidak ditemukan.");
}

// Ambil data destinasi populer
$destinasi_populer = [];
$error_destinasi_populer = null;

if (class_exists('WisataController') && method_exists('WisataController', 'getAllForAdmin')) {
  if (isset($conn) && $conn instanceof mysqli && @$conn->ping()) {
    try {
      // PERBAIKAN: Pastikan pemanggilan sesuai dengan definisi di WisataController
      // Jika WisataController::getAllForAdmin($orderBy, $limit)
      $destinasi_populer_raw = WisataController::getAllForAdmin('created_at DESC', 3); // Ambil 3 terbaru

      if ($destinasi_populer_raw && is_array($destinasi_populer_raw)) {
        $destinasi_populer = $destinasi_populer_raw;
      } elseif ($destinasi_populer_raw === false) { // Jika controller mengembalikan false
        $destinasi_populer = [];
        $controller_or_model_error = (class_exists('Wisata') && method_exists('Wisata', 'getLastError')) ? Wisata::getLastError() : 'Error tidak spesifik dari WisataController.';
        $error_destinasi_populer = "Gagal mengambil data destinasi populer." . ($controller_or_model_error ? " Detail: " . e($controller_or_model_error) : "");
        error_log("INDEX_PAGE_ERROR - WisataController::getAllForAdmin() untuk destinasi populer: " . $error_destinasi_populer);
      }
    } catch (Throwable $e) {
      $destinasi_populer = [];
      $error_destinasi_populer = "Terjadi kesalahan sistem saat memuat destinasi populer.";
      error_log("INDEX_PAGE_EXCEPTION - Mengambil destinasi populer: " . $e->getMessage());
    }
  } else {
    $error_destinasi_populer = "Koneksi database tidak tersedia untuk mengambil destinasi.";
    error_log("INDEX_PAGE_CRITICAL - Koneksi database (\$conn) tidak tersedia/valid untuk destinasi.");
  }
} else {
  $error_destinasi_populer = "Fitur destinasi tidak dapat dimuat saat ini.";
  error_log("INDEX_PAGE_CRITICAL - Kelas WisataController atau metode getAllForAdmin tidak ditemukan.");
}


$page_title = "Beranda - " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Lembah Cilengkrang");
$is_homepage = true;

// 3. Sertakan header publik
if (!@include_once ROOT_PATH . '/template/header.php') {
  http_response_code(500);
  error_log("FATAL ERROR di index.php: Gagal memuat template/header.php.");
  exit("Terjadi kesalahan pada tampilan server. Mohon coba lagi nanti.");
}
?>

<div class="main-page-content">

  <section class="hero-video-background text-white text-center d-flex align-items-center"
    style="background-image: url('<?= e(ASSETS_URL . 'img/air.jpg') ?>'); background-size: cover; background-position: center;">
    <video playsinline autoplay muted loop poster="<?= e(ASSETS_URL . 'img/background_poster.jpg') ?>" id="bgvid" aria-label="Video latar pemandangan Lembah Cilengkrang">
      <source src="<?= e(ASSETS_URL . 'img/background.mp4') ?>" type="video/mp4">
      Browser Anda tidak mendukung tag video.
    </video>
    <div class="hero-overlay"></div>
    <div class="container hero-content animate-on-scroll">
      <h1 class="display-3 fw-bolder text-shadow-strong">Jelajahi Pesona Alam Cilengkrang</h1>
      <p class="lead my-4 col-lg-10 mx-auto text-shadow-soft">
        <?= e(defined('PENGANTAR_SINGKAT_SITUS') ? PENGANTAR_SINGKAT_SITUS : "Lembah Cilengkrang terletak di Pajambon, Kramatmulya, Kuningan, Jawa Barat, sekitar 30km dari pusat kota Kuningan. Destinasi ini menawarkan keindahan air terjun menawan, relaksasi di pemandian air panas alami, dan kesegaran udara pegunungan.") ?>
      </p>
      <a href="<?= e(BASE_URL . 'wisata/semua_destinasi.php') ?>" class="btn btn-primary btn-lg me-sm-2 mb-3 mb-sm-0 hero-btn">
        <i class="fas fa-info-circle me-2"></i>Pelajari Lebih Lanjut
      </a>
      <a href="<?= e(BASE_URL . 'user/pemesanan_tiket.php') ?>" class="btn btn-light btn-lg hero-btn">
        <i class="fas fa-ticket-alt me-2"></i>Pesan Tiket Sekarang
      </a>
    </div>
  </section>

  <section class="section-padding">
    <div class="container">
      <div class="text-center mb-5">
        <span class="text-uppercase text-primary fw-bold small">Keunggulan Kami</span>
        <h2 class="section-title mt-2">Mengapa Memilih Cilengkrang?</h2>
        <p class="section-subtitle lead text-muted col-md-8 mx-auto">Destinasi sempurna untuk petualangan, relaksasi, dan momen tak terlupakan bersama orang terkasih.</p>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card feature-card text-center p-lg-4 p-3 h-100 animate-on-scroll shadow-hover">
            <div class="icon text-success display-1 mb-3"><i class="fas fa-water"></i></div>
            <div class="card-body p-0">
              <h5 class="card-title h4">Air Terjun Memukau</h5>
              <p class="card-text">Saksikan keagungan Curug Cilengkrang dengan airnya yang jernih dan panorama alam yang menyegarkan mata serta jiwa.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="card feature-card text-center p-lg-4 p-3 h-100 animate-on-scroll shadow-hover" data-animation-delay="100ms">
            <div class="icon text-info display-1 mb-3"><i class="fas fa-hot-tub"></i></div>
            <div class="card-body p-0">
              <h5 class="card-title h4">Pemandian Air Panas</h5>
              <p class="card-text">Relaksasikan tubuh dan pikiran Anda di sumber air panas alami yang kaya akan mineral dan berkhasiat untuk kesehatan.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="card feature-card text-center p-lg-4 p-3 h-100 animate-on-scroll shadow-hover" data-animation-delay="200ms">
            <div class="icon text-warning display-1 mb-3"><i class="fas fa-tree"></i></div>
            <div class="card-body p-0">
              <h5 class="card-title h4">Keindahan Hutan Pinus</h5>
              <p class="card-text">Nikmati trekking santai atau piknik di tengah keteduhan hutan pinus yang asri, hirup udara segar khas pegunungan.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($destinasi_populer) && is_array($destinasi_populer)): ?>
    <section class="section-padding bg-light-custom">
      <div class="container">
        <div class="text-center mb-5">
          <span class="text-uppercase text-primary fw-bold small">Jelajahi Lebih</span>
          <h2 class="section-title mt-2">Destinasi Populer Kami</h2>
        </div>
        <div class="row g-4 text-center">
          <?php
          $delay_animasi = 0;
          foreach ($destinasi_populer as $dest) :
            $nama_dest = e($dest['nama'] ?? 'Destinasi');
            $desk_singkat_dest = e(excerpt($dest['deskripsi'] ?? '', 100));
            $detail_url_dest = BASE_URL . 'wisata/detail_destinasi.php?id=' . ($dest['id'] ?? 0);

            $gambar_dest_url_final = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/placeholder_wisata.png'; // Fallback
            if (!empty($dest['gambar'])) {
              $path_cek_gambar_dest = (defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH : ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'wisata' . DIRECTORY_SEPARATOR) . basename($dest['gambar']);
              if (file_exists($path_cek_gambar_dest) && is_file($path_cek_gambar_dest)) {
                $gambar_dest_url_final = (defined('UPLOADS_WISATA_URL') ? UPLOADS_WISATA_URL : BASE_URL . 'public/uploads/wisata/') . rawurlencode(basename($dest['gambar']));
              }
            }
          ?>
            <div class="col-lg-4 col-md-6 animate-on-scroll" data-animation-delay="<?= $delay_animasi ?>ms">
              <div class="card destination-card shadow-sm h-100 hover-shadow-lg transition-fast">
                <a href="<?= e($detail_url_dest) ?>" class="stretched-link-card-custom">
                  <img src="<?= e($gambar_dest_url_final) ?><?= strpos($gambar_dest_url_final, '?') === false ? '?t=' . time() : '&t=' . time() ?>" loading="lazy" alt="<?= $nama_dest ?>" class="card-img-top" style="height: 220px; object-fit: cover;">
                </a>
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title"><?= $nama_dest ?></h5>
                  <p class="card-text text-muted small flex-grow-1"><?= $desk_singkat_dest ?></p>
                  <div class="mt-auto">
                    <a href="<?= e($detail_url_dest) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya <i class="fas fa-angle-right ms-1"></i></a>
                  </div>
                </div>
              </div>
            </div>
          <?php $delay_animasi += 100;
          endforeach; ?>
        </div>
        <?php
        // Cek apakah total destinasi lebih banyak dari yang ditampilkan
        $total_destinasi_keseluruhan = (class_exists('Wisata') && method_exists('Wisata', 'countAll')) ? Wisata::countAll() : count($destinasi_populer);
        if ($total_destinasi_keseluruhan > count($destinasi_populer)):
        ?>
          <div class="text-center mt-5">
            <a href="<?= e(BASE_URL . 'wisata/semua_destinasi.php') ?>" class="btn btn-primary btn-lg">Lihat Semua Destinasi <i class="fas fa-arrow-right ms-2"></i></a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php elseif ($error_destinasi_populer): ?>
    <section class="section-padding bg-light-custom">
      <div class="container text-center">
        <p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> <?= e($error_destinasi_populer) ?></p>
      </div>
    </section>
  <?php endif; ?>


  <?php if (!empty($artikel_terbaru) && is_array($artikel_terbaru)): ?>
    <section class="section-padding">
      <div class="container">
        <div class="text-center mb-5">
          <span class="text-uppercase text-primary fw-bold small">Info & Berita</span>
          <h2 class="section-title mt-2">Artikel & Tips Terbaru</h2>
        </div>
        <div class="row g-4">
          <?php
          $delay_animasi_artikel = 0;
          foreach ($artikel_terbaru as $artikel):
            $judul_artikel = e($artikel['judul'] ?? 'Judul Artikel');
            $link_artikel = BASE_URL . 'user/artikel_detail.php?id=' . ($artikel['id'] ?? 0);
            $gambar_artikel_final = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/placeholder_artikel.png';
            if (!empty($artikel['gambar'])) {
              $path_cek_artikel = (defined('UPLOADS_ARTIKEL_PATH') ? UPLOADS_ARTIKEL_PATH : ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'artikel' . DIRECTORY_SEPARATOR) . basename($artikel['gambar']);
              if (file_exists($path_cek_artikel) && is_file($path_cek_artikel)) {
                $gambar_artikel_final = (defined('UPLOADS_ARTIKEL_URL') ? UPLOADS_ARTIKEL_URL : BASE_URL . 'public/uploads/artikel/') . rawurlencode(basename($artikel['gambar']));
              }
            }
          ?>
            <div class="col-md-6 col-lg-4 animate-on-scroll" data-animation-delay="<?= $delay_animasi_artikel ?>ms">
              <div class="card article-card-home h-100 shadow-sm hover-shadow-lg transition-fast">
                <a href="<?= e($link_artikel) ?>" class="text-decoration-none">
                  <img src="<?= e($gambar_artikel_final) ?><?= strpos($gambar_artikel_final, '?') === false ? '?t=' . time() : '&t=' . time() ?>" loading="lazy" class="card-img-top" alt="<?= $judul_artikel ?>" style="height: 200px; object-fit: cover;">
                </a>
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title">
                    <a href="<?= e($link_artikel) ?>" class="text-decoration-none stretched-link-card-custom">
                      <?= $judul_artikel ?>
                    </a>
                  </h5>
                  <p class="card-text text-muted small mb-2">
                    <i class="fas fa-calendar-alt me-1"></i> <?= e(formatTanggalIndonesia($artikel['created_at'] ?? null, false)) ?>
                  </p>
                  <p class="card-text flex-grow-1"><?= e(excerpt(strip_tags($artikel['isi'] ?? ''), 100)) ?></p>
                  <div class="mt-auto">
                    <a href="<?= e($link_artikel) ?>" class="btn btn-sm btn-outline-secondary align-self-start">
                      Baca Selengkapnya <i class="fas fa-long-arrow-alt-right ms-1"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php $delay_animasi_artikel += 100;
          endforeach; ?>
        </div>
        <?php
        $total_artikel_keseluruhan = (class_exists('Artikel') && method_exists('Artikel', 'countAll')) ? Artikel::countAll() : count($artikel_terbaru);
        if ($total_artikel_keseluruhan > count($artikel_terbaru)):
        ?>
          <div class="text-center mt-5">
            <a href="<?= e(BASE_URL . 'user/artikel.php') ?>" class="btn btn-outline-primary btn-lg">Lihat Semua Artikel</a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php elseif ($error_artikel): ?>
    <section class="section-padding">
      <div class="container text-center">
        <p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> <?= e($error_artikel) ?></p>
      </div>
    </section>
  <?php endif; ?>

  <section class="section-padding bg-light-custom testimonial-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="text-uppercase text-primary fw-bold small">Ulasan Pengunjung</span>
        <h2 class="section-title mt-2">Apa Kata Mereka?</h2>
      </div>
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
              // Data testimoni ini idealnya dari database (Model Feedback)
              $testimonies = []; // Akan diisi dari DB jika ada
              if (class_exists('Feedback') && method_exists('Feedback', 'getAll')) {
                // Ambil beberapa testimoni acak atau terbaru dengan rating bagus
                // $allFeedbacks = Feedback::getAll(); // Ini mengambil semua, mungkin terlalu banyak
                // Anda mungkin perlu metode baru di Feedback Model, misal: Feedback::getFeatured(3)
                // Untuk contoh, kita tetap pakai data statis jika pengambilan dari DB gagal/belum ada
                $allFeedbacks = []; // Kosongkan dulu
                if (empty($allFeedbacks)) { // Fallback ke data statis jika DB kosong atau error
                  $testimonies = [
                    ['user_nama' => 'Rina Amelia - Bandung', 'komentar' => 'Pengalaman luar biasa! Air panasnya benar-benar menyegarkan dan pemandangannya indah sekali. Sangat cocok untuk liburan keluarga.', 'foto_profil' => 'avatar1.jpg'], // Asumsi ada user_nama dan foto_profil di feedback
                    ['user_nama' => 'Budi Santoso - Jakarta', 'komentar' => 'Stafnya ramah dan fasilitasnya cukup bersih. Anak-anak senang bermain di area curug yang sejuk. Pasti akan kembali lagi suatu saat!', 'foto_profil' => 'avatar2.jpg'],
                    ['user_nama' => 'Siti Nurhaliza - Cimahi', 'komentar' => 'Tempat yang tepat untuk healing dari hiruk pikuk kota. Suasana hutannya menenangkan, dan air panasnya bikin rileks. Recommended!', 'foto_profil' => 'avatar3.jpg'],
                  ];
                } else {
                  // Olah $allFeedbacks menjadi format $testimonies
                  // Misalnya, ambil 3 teratas atau acak
                  // $testimonies = array_slice($allFeedbacks, 0, 3);
                }
              } else {
                $testimonies = [ /* Data statis fallback */];
              }


              foreach ($testimonies as $index => $testi) :
                $avatar_fallback_url = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/avatar_placeholder.png';
                $avatar_display_url = $avatar_fallback_url;
                if (!empty($testi['foto_profil'])) { // Jika feedback dari user terdaftar dan ada foto profil
                  $path_cek_avatar = (defined('UPLOADS_PROFIL_PATH') ? UPLOADS_PROFIL_PATH : ROOT_PATH . '/public/uploads/profil/') . basename($testi['foto_profil']);
                  if (file_exists($path_cek_avatar) && is_file($path_cek_avatar)) {
                    $avatar_display_url = (defined('UPLOADS_PROFIL_URL') ? UPLOADS_PROFIL_URL : BASE_URL . 'public/uploads/profil/') . rawurlencode(basename($testi['foto_profil']));
                  } elseif (file_exists(ROOT_PATH . '/public/img/' . $testi['foto_profil'])) { // Fallback ke img statis jika nama file cocok
                    $avatar_display_url = (defined('ASSETS_URL') ? ASSETS_URL . 'img/' : BASE_URL . 'public/img/') . e($testi['foto_profil']);
                  }
                } elseif (isset($testi['avatar']) && file_exists(ROOT_PATH . '/public/img/' . $testi['avatar'])) { // Untuk data statis lama
                  $avatar_display_url = (defined('ASSETS_URL') ? ASSETS_URL . 'img/' : BASE_URL . 'public/img/') . e($testi['avatar']);
                }
              ?>
                <div class="carousel-item <?= $index == 0 ? 'active' : '' ?>">
                  <div class="testimonial-card-item p-4 p-md-5">
                    <img src="<?= e($avatar_display_url) ?>?t=<?= time() ?>" loading="lazy" class="testimonial-avatar rounded-circle mb-3 shadow-sm" alt="Foto <?= e($testi['nama'] ?? ($testi['user_nama'] ?? 'Pengunjung')) ?>" style="width: 80px; height: 80px; object-fit: cover;">
                    <blockquote class="fs-5 fst-italic text-dark mb-3">"<?= e($testi['testimoni'] ?? ($testi['komentar'] ?? 'Tidak ada komentar.')) ?>"</blockquote>
                    <cite class="testimonial-author fw-bold d-block text-primary"><?= e($testi['nama'] ?? ($testi['user_nama'] ?? 'Seorang Pengunjung')) ?></cite>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Sebelumnya</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Berikutnya</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section-padding text-center cta-section" style="position: relative; background-image: url('<?= e(ASSETS_URL . 'img/air3.jpg') ?>'); background-size: cover; background-position: center center;">
    <div class="cta-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.55);"></div>
    <div class="container py-5" style="position:relative; z-index:2;">
      <div class="animate-on-scroll text-white">
        <h2 class="section-title display-5">Siap untuk Petualangan Berikutnya?</h2>
        <p class="lead mb-4 mx-auto" style="max-width: 700px;">
          <?= e(NAMA_SITUS) ?> menanti kedatangan Anda dengan sejuta pesona alam, keramahan, dan pengalaman tak terlupakan yang akan memperkaya jiwa.
        </p>
        <a href="<?= e(BASE_URL . 'user/pemesanan_tiket.php') ?>" class="btn btn-light btn-lg me-sm-2 mb-3 mb-sm-0 hero-btn shadow">
          <i class="fas fa-calendar-check me-2"></i> Rencanakan Kunjungan Anda
        </a>
        <a href="<?= e(BASE_URL . 'contact.php') ?>" class="btn btn-outline-light btn-lg hero-btn shadow">
          <i class="fas fa-envelope me-2"></i> Hubungi Kami
        </a>
      </div>
    </div>
  </section>

</div> <!-- Penutup .main-page-content -->

<?php
if (!@include_once ROOT_PATH . '/template/footer.php') {
  error_log("FATAL: Gagal memuat template/footer.php dari index.php");
}
?>