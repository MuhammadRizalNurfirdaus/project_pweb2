<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\header.php
// (Tidak ada perubahan signifikan di sini, pastikan $base_url, helper, dll. sudah tersedia dari config.php)

$page_title = isset($page_title) ? e($page_title) : "Lembah Cilengkrang";
$is_homepage = isset($is_homepage) ? $is_homepage : false;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Jelajahi pesona alam Lembah Cilengkrang, Pajambon, Kramatmulya, Kuningan, Jawa Barat. Temukan air terjun, pemandian air panas, dan keindahan alam lainnya.">
    <meta name="keywords" content="Cilengkrang, Lembah Cilengkrang, Wisata Kuningan, Wisata Jawa Barat, Pajambon, Kramatmulya, air terjun, pemandian air panas, wisata alam">
    <title><?= e($page_title) ?> - Wisata Cilengkrang</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Open+Sans:wght@400;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>public/css/style.css">
    <!-- Favicon -->
    <link rel="icon" href="<?= $base_url ?>public/img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= $base_url ?>public/img/logo_apple_touch.png">


</head>

<body>
    <div id="preloader">
        <div class="spinner"></div>
        <p>Memuat Pesona Cilengkrang...</p>
    </div>
    <header>
        <nav class="navbar navbar-expand-lg navbar-public fixed-top <?= ($is_homepage) ? 'navbar-transparent-on-top' : '' ?>">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?= $base_url ?>">
                    <img src="<?= $base_url ?>public/img/logo.png" alt="Logo Wisata Cilengkrang" class="logo me-2">
                    <span>Lembah Cilengkrang</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbarNav" aria-controls="publicNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="publicNavbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item"><a class="nav-link <?= ($is_homepage) ? 'active' : ((strpos($_SERVER['REQUEST_URI'], 'index.php') !== false || rtrim($_SERVER['REQUEST_URI'], '/') == rtrim(parse_url($base_url, PHP_URL_PATH), '/') || $_SERVER['REQUEST_URI'] == parse_url($base_url, PHP_URL_PATH)) ? 'active' : '') ?>" href="<?= $base_url ?>">Beranda</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= (strpos($_SERVER['REQUEST_URI'], '/wisata/') !== false) ? 'active' : '' ?>" href="#" id="wisataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Jelajahi
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="wisataDropdown">
                                <li><a class="dropdown-item" href="<?= $base_url ?>wisata/deskripsi.php">Tentang Cilengkrang</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>wisata/semua_destinasi.php">Semua Destinasi</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>wisata/galeri.php">Galeri Foto</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>wisata/video.php">Video Wisata</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>wisata/lokasi.php">Peta & Lokasi</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>wisata/event.php">Event & Kegiatan</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'user/artikel.php') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>user/artikel.php">Artikel</a></li>
                        <!-- Mengganti 'user/booking.php' menjadi 'user/pemesanan_tiket.php' dan teks tautan -->
                        <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'user/pemesanan_tiket.php') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>user/pemesanan_tiket.php">Pesan Tiket</a></li>
                        <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], '/kontak.php') !== false && strpos($_SERVER['REQUEST_URI'], 'admin/contact') === false) ? 'active' : '' ?>" href="<?= $base_url ?>kontak.php">Kontak</a></li>

                        <?php if (is_logged_in()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userMenuDropdownPublic" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?= e(get_current_user_name()) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuDropdownPublic">
                                    <li><a class="dropdown-item" href="<?= $base_url ?>user/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Saya</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url ?>user/profil.php"><i class="fas fa-id-card me-2"></i>Profil Saya</a></li>
                                    <?php if (is_admin()): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-user-shield me-2"></i> Panel Admin</a></li>
                                    <?php endif; ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="<?= $base_url ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item ms-lg-2"><a class="btn btn-outline-light btn-sm px-3" href="<?= $base_url ?>auth/login.php">Login</a></li>
                            <li class="nav-item ms-lg-1"><a class="btn btn-warning btn-sm px-3 text-dark" href="<?= $base_url ?>auth/register.php">Daftar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="main-page-content">
        <?php echo display_flash_message(); ?>