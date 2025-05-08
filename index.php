<?php
session_start();
?>

<?php include 'template/header_user.php'; ?>

<div class="container mt-5">
  <div class="row align-items-center" style="height: 100vh;">
    <div class="col-md-6">
      <h1>Selamat Datang di Cilengkrang Web Wisata</h1>
      <p class="lead">Nikmati pesona alam Cilengkrang, tempat wisata yang menawarkan pemandangan indah, udara segar, dan fasilitas lengkap untuk liburan Anda.</p>
      <a href="/user/artikel.php" class="btn btn-success btn-lg">Pelajari Lebih Lanjut</a>
    </div>
    <div class="col-md-6">
      <div class="ratio ratio-16x9 rounded shadow" style="overflow: hidden; height: 100%; width: 100%;">
        <video autoplay loop muted class="w-100 h-100 object-fit-cover" style="max-height: 100vh;">
          <source src="public/img/background.mp4" type="video/mp4">
          Browser Anda tidak mendukung video.
        </video>
      </div>
    </div>
  </div>


  <hr class="my-5">

  <section class="px-4 py-5">
    <h1 class="display-5 fw-bold text-success mb-3">Cilengkrang</h1>
    <p class="fs-5 fw-semibold text-secondary">Mari Berwisata Di cilengkrang</p>

    <div class="row g-4 mt-4">
      <!-- Card 1 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="position-relative overflow-hidden rounded hover-card">
          <img src="https://cdn.wisata.app/diary/11401120-5039-80d2-8127-f94a90ab16f6.jpg" alt="" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
          <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
            <h5 class="fw-semibold fs-5">Pemandian Air Panas</h5>
            <p class="mt-2 small">Rasakan relaksasi alami di Pemandian Air Panas Cilengkrang, dengan air hangat pegunungan yang menyegarkan tubuh, meredakan stres, dan menyehatkan kulit.</p>
          </div>
        </div>
      </div>
      <!-- Card 2 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="position-relative overflow-hidden rounded hover-card">
          <img src="https://nativeindonesia.com/wp-content/uploads/lembah-cilengkrang/salah-satu-kolam-air-panas.jpg" alt="" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
          <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
            <h5 class="fw-semibold fs-5">Paket Nasi Timbel</h5>
            <p class="mt-2 small">Merupakan sebuah kampung adat yang masih lestari. Masyarakatnya masih memegang adat tradisi nenek moyang mereka</p>
          </div>
        </div>
      </div>
      <!-- Card 3 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="position-relative overflow-hidden rounded hover-card">
          <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQUbMYpE1uyrG9uJdkebJ_H22MuUxKY7dJPiw&s" alt="" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
          <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
            <h5 class="fw-semibold fs-5">Paket Nasi Timbel</h5>
            <p class="mt-2 small">Merupakan sebuah kampung adat yang masih lestari. Masyarakatnya masih memegang adat tradisi nenek moyang mereka</p>
          </div>
        </div>
      </div>
      <!-- Card 4 -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="position-relative overflow-hidden rounded hover-card">
          <img src="https://cdn.wisata.app/diary/11401120-5039-80d2-8127-f94a90ab16f6.jpg" alt="" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
          <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
            <h5 class="fw-semibold fs-5">Paket Nasi Timbel</h5>
            <p class="mt-2 small">Merupakan sebuah kampung adat yang masih lestari. Masyarakatnya masih memegang adat tradisi nenek moyang mereka</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <style>
    .hover-card {
      cursor: pointer;
    }

    .card-overlay {
      position: absolute;
      inset: 0;
      background-color: rgba(0, 128, 0, 0.8);
      /* Hijau transparan */
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
    }

    .hover-card:hover .card-overlay {
      opacity: 1;
    }
  </style>

</div>

<?php include 'template/footer.php'; ?>