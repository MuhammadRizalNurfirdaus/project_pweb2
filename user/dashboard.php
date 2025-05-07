<?php
session_start();
include_once '../config/database.php';
include '../template/header_user.php';

?>
<div class="card mb-3">
    <video controls class="w-100 rounded">
        <source src="public/img/curug.mp4" type="video/mp4">
        Browser anda tidak mendukung video.
    </video>
    <div class="card-body">
        <h5 class="card-title">Curug Cilengkrang</h5>
        <p class="card-text">Destinasi air terjun terbaik untuk keluarga.</p>
    </div>
</div>

<div class="container mt-5">
    <h2>Selamat datang di Dashboard Pengunjung!</h2>
    <p>Silakan pilih menu yang tersedia:</p>
    <ul>
        <li><a href="artikel.php">Artikel Wisata</a></li>
        <li><a href="booking.php">Pemesanan Tiket</a></li>
        <li><a href="feedback.php">Beri Feedback</a></li>
        <li><a href="contact.php">Hubungi Kami</a></li>
    </ul>
</div>
<div class="col-md-4">
    <div class="card mb-4">
        <img src="public/img/air_panas.jpg" class="card-img-top" alt="Air Panas">
        <div class="card-body">
            <h5 class="card-title">Air Panas</h5>
            <p class="card-text">Nikmati kehangatan air panas alami di pegunungan.</p>
        </div>
    </div>
</div>

<?php include_once '../template/footer.php'; ?>