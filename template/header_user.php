<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\header_user.php
// Skrip pemanggil (misal: user/dashboard.php) HARUS sudah include config.php

require_login(); // Pastikan hanya user yang login bisa akses

$page_title = isset($page_title) ? e($page_title) : "Area Pengguna";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - Cilengkrang Wisata</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Open+Sans:wght@400;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>public/css/style.css">
    <!-- <link rel="stylesheet" href="<?= $base_url ?>public/css/user_style.css"> <!-- CSS Khusus User Area -->
    <style>
        body {
            padding-top: 70px;
            /* Sesuaikan jika tinggi navbar user berbeda */
        }

        .user-navbar-custom {
            background-color: var(--primary-darker) !important;
            /* Warna beda untuk user area */
        }
    </style>
    <div id="preloader"> <!-- Preloader juga bisa untuk area user -->
        <div class="spinner"></div>
        <p>Memuat...</p>
    </div>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark user-navbar-custom fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= $base_url ?>user/dashboard.php">
                <img src="<?= $base_url ?>public/img/logo.png" alt="Logo" style="height: 35px; filter: brightness(0) invert(1);" class="me-2">
                <span>Area Pengguna</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userAreaNavbar" aria-controls="userAreaNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="userAreaNavbar">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'user/dashboard') !== false || $_SERVER['REQUEST_URI'] == $base_url . 'user/') ? 'active' : '' ?>" href="<?= $base_url ?>user/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'user/artikel.php') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>user/artikel.php"><i class="fas fa-newspaper me-1"></i> Artikel</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'user/booking.php') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>user/booking.php"><i class="fas fa-ticket-alt me-1"></i> Buat Booking</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'user/riwayat_booking.php') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>user/riwayat_booking.php"><i class="fas fa-history me-1"></i> Riwayat Saya</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-cog me-1"></i> <?= e(get_current_user_name()) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>user/profil.php"><i class="fas fa-id-card me-2"></i>Edit Profil</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>user/ganti_password.php"><i class="fas fa-key me-2"></i>Ganti Password</a></li>
                            <?php if (is_admin()): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-user-shield me-2"></i>Ke Panel Admin</a></li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_url ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-page-content"> <!-- Wrapper untuk konten utama -->
        <div class="container py-4"> <!-- Padding atas dan bawah untuk konten -->
            <?= display_flash_message(); ?>
            <!-- Konten spesifik halaman pengguna akan dimulai setelah baris ini -->