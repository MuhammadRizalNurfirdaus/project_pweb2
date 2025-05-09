<?php
session_start();
include_once '../config/database.php';
include '../template/header_user.php';
?>

<div class="container mt-4">
    <!-- Video Section -->
    <div class="card mb-4 shadow">
        <video controls class="w-100 rounded-top">
            <source src="public/img/curug.mp4" type="video/mp4">
            Browser anda tidak mendukung video.
        </video>
        <div class="card-body">
            <h4 class="card-title">Curug Cilengkrang</h4>
            <p class="card-text text-muted">Destinasi air terjun terbaik untuk keluarga.</p>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h3 class="card-title">Selamat Datang di Dashboard Pengunjung!</h3>
            <p class="card-text">Silakan pilih menu yang tersedia:</p>
            <div class="list-group">
                <a href="artikel.php" class="list-group-item list-group-item-action">📄 Artikel Wisata</a>
                <a href="booking.php" class="list-group-item list-group-item-action">🎟️ Pemesanan Tiket</a>
                <a href="feedback.php" class="list-group-item list-group-item-action">💬 Beri Feedback</a>
                <a href="contact.php" class="list-group-item list-group-item-action">📞 Hubungi Kami</a>
            </div>
        </div>
    </div>

    <!-- Highlighted Wisata -->
    <div class="row">
        <div class="col-md-6 col-lg-4 mx-auto">
            <div class="card shadow-sm">
                <img src="public/img/air_panas.jpg" class="card-img-top" alt="Air Panas">
                <div class="card-body">
                    <h5 class="card-title">Air Panas</h5>
                    <p class="card-text">Nikmati kehangatan air panas alami di pegunungan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../template/footer.php'; ?>