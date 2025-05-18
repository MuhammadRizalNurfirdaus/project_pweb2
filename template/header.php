<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\header.php

$page_title = isset($page_title) ? $page_title : "Lembah Cilengkrang";
$is_homepage = isset($is_homepage) && $is_homepage === true;

if (!defined('BASE_URL')) {
    error_log("KRITIS header.php: Konstanta BASE_URL tidak terdefinisi.");
    // Fallback darurat jika diperlukan, tapi idealnya ini sudah di-set.
    // define('BASE_URL', '/Cilengkrang-Web-Wisata/'); // Contoh fallback jika selalu di subfolder ini
}
$base_url = BASE_URL;

// Variabel untuk foto profil pengguna
$user_photo_url = null;
if (function_exists('is_logged_in') && is_logged_in() && function_exists('get_current_user_id')) {
    if (class_exists('User') && method_exists('User', 'findById')) {
        $current_user_data_for_photo = User::findById(get_current_user_id());
        if ($current_user_data_for_photo && !empty($current_user_data_for_photo['foto_profil'])) {
            if (defined('UPLOADS_PROFIL_PATH') && defined('UPLOADS_URL')) {
                $photo_file_path = rtrim(UPLOADS_PROFIL_PATH, '/') . '/' . $current_user_data_for_photo['foto_profil'];
                if (file_exists($photo_file_path)) {
                    $user_photo_url = rtrim(UPLOADS_URL, '/') . '/profil/' . rawurlencode($current_user_data_for_photo['foto_profil']);
                }
            }
        }
    }
}
// Set avatar default jika tidak ada foto profil atau tidak login
if ($user_photo_url === null && defined('ASSETS_URL')) {
    $user_photo_url = ASSETS_URL . 'img/avatar_default.png'; // Pastikan ASSETS_URL juga terdefinisi
} elseif ($user_photo_url === null) {
    $user_photo_url = $base_url . 'public/img/avatar_default.png'; // Fallback jika ASSETS_URL tidak ada
}

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="<?= e($base_url) ?>public/css/style.css?v=<?= time() ?>">
    <link rel="icon" href="<?= e($base_url) ?>public/img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= e($base_url) ?>public/img/logo_apple_touch.png">
    <style>
        .navbar-user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
    </style>
</head>

<body>
    <div id="preloader">
        <div class="spinner"></div>
        <p>Memuat Pesona Cilengkrang...</p>
    </div>

    <header>
        <nav class="navbar navbar-expand-lg navbar-public fixed-top <?= ($is_homepage) ? 'navbar-transparent-on-top' : 'navbar-scrolled' ?>">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?= e($base_url) ?>">
                    <img src="<?= e($base_url) ?>public/img/logo.png" alt="Logo Wisata Cilengkrang" class="logo me-2">
                    <span>Lembah Cilengkrang</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbarNav" aria-controls="publicNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="publicNavbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <?php
                        $request_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                        $base_url_path = rtrim(parse_url($base_url, PHP_URL_PATH), '/');
                        $is_current_page_home = ($is_homepage ||
                            strpos($request_uri_path, 'index.php') !== false ||
                            rtrim($request_uri_path, '/') === $base_url_path ||
                            $request_uri_path === $base_url_path . '/' ||
                            $request_uri_path === $base_url_path);

                        // Fungsi helper untuk logika active nav link
                        if (!function_exists('isNavLinkActive')) {
                            function isNavLinkActive($link_path_segment, $current_path)
                            {
                                return strpos($current_path, $link_path_segment) !== false;
                            }
                        }
                        ?>
                        <li class="nav-item"><a class="nav-link <?= $is_current_page_home ? 'active' : '' ?>" href="<?= e($base_url) ?>">Beranda</a></li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= isNavLinkActive('/wisata/', $request_uri_path) ? 'active' : '' ?>" href="#" id="wisataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Jelajahi
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="wisataDropdown">
                                <li><a class="dropdown-item <?= isNavLinkActive('/wisata/deskripsi.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>wisata/deskripsi.php">Tentang Cilengkrang</a></li>
                                <li><a class="dropdown-item <?= isNavLinkActive('/wisata/semua_destinasi.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>wisata/semua_destinasi.php">Semua Destinasi</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item <?= isNavLinkActive('/wisata/galeri.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>wisata/galeri.php">Galeri Foto</a></li>
                                <li><a class="dropdown-item <?= isNavLinkActive('/wisata/video.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>wisata/video.php">Video Wisata</a></li>
                                <li><a class="dropdown-item <?= isNavLinkActive('/wisata/lokasi.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>wisata/lokasi.php">Peta & Lokasi</a></li>
                                <li><a class="dropdown-item <?= isNavLinkActive('/wisata/event.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>wisata/event.php">Event & Kegiatan</a></li>
                            </ul>
                        </li>

                        <li class="nav-item"><a class="nav-link <?= isNavLinkActive('/user/artikel.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/artikel.php">Artikel</a></li>
                        <li class="nav-item"><a class="nav-link <?= isNavLinkActive('/user/pemesanan_tiket.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/pemesanan_tiket.php">Pesan Tiket</a></li>
                        <li class="nav-item"><a class="nav-link <?= (basename($request_uri_path) === 'contact.php' && !isNavLinkActive('/admin/contact', $request_uri_path)) ? 'active' : '' ?>" href="<?= e($base_url) ?>contact.php">Kontak</a></li>

                        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center <?= isNavLinkActive('/user/', $request_uri_path) ? 'active' : '' ?>" href="#" id="userMenuDropdownPublic" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?= e($user_photo_url) ?>" alt="Avatar" class="navbar-user-avatar">
                                    <?= e(get_current_user_name() ?: 'Pengguna') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuDropdownPublic">
                                    <li><a class="dropdown-item <?= isNavLinkActive('/user/dashboard.php', $request_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Saya</a></li>
                                    <li><a class="dropdown-item <?= (isNavLinkActive('/user/profil.php', $request_uri_path) || isNavLinkActive('/user/edit_profil.php', $request_uri_path) || isNavLinkActive('/user/ganti_password.php', $request_uri_path)) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/profil.php"><i class="fas fa-id-card me-2"></i>Profil Saya</a></li>

                                    <?php if (function_exists('is_admin') && is_admin()): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-user-shield me-2"></i> Panel Admin</a></li>
                                    <?php endif; ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="<?= e($base_url) ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item ms-lg-2"><a class="btn btn-outline-light btn-sm px-3" href="<?= e($base_url) ?>auth/login.php">Login</a></li>
                            <li class="nav-item ms-lg-1"><a class="btn btn-warning btn-sm px-3 text-dark" href="<?= e($base_url) ?>auth/register.php">Daftar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="main-page-content">
        <?php
        // Pemanggilan display_flash_message() sebaiknya ada di dalam kontainer utama halaman spesifik,
        // bukan di header global, untuk menghindari masalah dengan redirect.
        // if (function_exists('display_flash_message')) echo display_flash_message(); 
        ?>