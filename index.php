<?php
session_start();
?>

<?php include 'template/header_user.php'; ?>

<div class="container mt-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1>Selamat Datang di Cilengkrang Web Wisata</h1>
            <p class="lead">Nikmati pesona alam Cilengkrang, tempat wisata yang menawarkan pemandangan indah, udara segar, dan fasilitas lengkap untuk liburan Anda.</p>
            <a href="booking.php" class="btn btn-success btn-lg">Pesan Tiket Sekarang</a>
        </div>
        <div class="col-md-6">
            <video autoplay loop muted class="w-100 rounded shadow">
                <source src="public/img/background.mp4" type="video/mp4">
                Browser Anda tidak mendukung video.
            </video>
        </div>
    </div>

    <hr class="my-5">

    <div class="row text-center">
        <div class="col-md-4">
            <img src="public/img/air_panas.jpg" class="img-fluid rounded mb-3" alt="Air Panas">
            <h4>Air Panas</h4>
            <p>Rasakan kehangatan air alami dari pegunungan.</p>
        </div>
        <div class="col-md-4">
            <img src="public/img/gazebo.jpg" class="img-fluid rounded mb-3" alt="Gazebo">
            <h4>Gazebo Alam</h4>
            <p>Tempat bersantai dengan panorama hijau menenangkan.</p>
        </div>
        <div class="col-md-4">
            <img src="public/img/sungai.jpg" class="img-fluid rounded mb-3" alt="Sungai">
            <h4>Sungai Jernih</h4>
            <p>Segarkan diri dengan bermain di sungai yang bersih dan sejuk.</p>
        </div>
    </div>
</div>

<?php include 'template/footer.php'; ?>