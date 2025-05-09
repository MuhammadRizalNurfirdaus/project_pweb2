<?php
session_start();
include_once '../config/database.php';
include '../template/header_user.php';
?>

<div class="container mt-4">
    <!-- Video -->
    <div class="card mb-4 shadow">
        <video autoplay loop muted class="w-100 rounded-top" style="max-height: 80vh; object-fit: cover;">
            <source src="../public/img/curug.mp4" type="video/mp4">
            Browser Anda tidak mendukung video.
        </video>
        <div class="card-body">
            <h4 class="card-title">Curug Cilengkrang</h4>
            <p class="card-text text-muted">Destinasi air terjun terbaik untuk keluarga.</p>
        </div>
    </div>

    <!-- Menu Navigasi -->
    <div class="row g-4 mt-4">
        <!-- Kartu 1 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="position-relative overflow-hidden rounded hover-card">
                <img src="../public/img/air_panas.jpg" alt="Air Panas" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
                <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
                    <h5 class="fw-semibold fs-5">Air Panas</h5>
                    <p class="mt-2 small">Nikmati kehangatan air panas alami di pegunungan.</p>
                </div>
            </div>
        </div>

        <!-- Kartu 2 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="position-relative overflow-hidden rounded hover-card">
                <img src="../public/img/gazebo.jpg" alt="Gazebo" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
                <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
                    <h5 class="fw-semibold fs-5">Gazebo</h5>
                    <p class="mt-2 small">Tempat bersantai keluarga dengan pemandangan asri.</p>
                </div>
            </div>
        </div>

        <!-- Kartu 3 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="position-relative overflow-hidden rounded hover-card">
                <img src="../public/img/kolam_air_panas.jpg" alt="Kolam Air Panas" class="img-fluid" style="height: 300px; object-fit: cover; width: 100%;">
                <div class="card-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-3">
                    <h5 class="fw-semibold fs-5">Kolam Air Panas</h5>
                    <p class="mt-2 small">Relaksasi di kolam hangat dengan suasana alam.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-card {
            cursor: pointer;
        }

        .card-overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(0, 128, 0, 0.8);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .hover-card:hover .card-overlay {
            opacity: 1;
        }
    </style>


    <?php include_once '../template/footer.php'; ?>