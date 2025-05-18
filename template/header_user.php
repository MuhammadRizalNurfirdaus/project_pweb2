<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\header_user.php
// Skrip pemanggil (misal: user/dashboard.php) HARUS sudah include config.php

// Pastikan fungsi dan konstanta penting sudah ada
if (!function_exists('require_login')) {
    error_log("KRITIS header_user.php: Fungsi require_login() tidak ditemukan.");
    // Darurat, mungkin redirect ke halaman error umum jika BASE_URL ada
    if (defined('BASE_URL') && function_exists('redirect')) redirect(BASE_URL . '?error=auth_missing');
    else exit('Fungsi autentikasi hilang.');
}
require_login(); // Pastikan hanya user yang login bisa akses

if (!defined('BASE_URL')) {
    error_log("KRITIS header_user.php: Konstanta BASE_URL tidak terdefinisi.");
    define('BASE_URL', './'); // Fallback sangat dasar
}
$base_url = BASE_URL;

$page_title = isset($page_title) ? $page_title : "Area Pengguna";
$current_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Fungsi helper lokal untuk navigasi aktif (jika belum ada di helpers.php global)
if (!function_exists('isUserNavLinkActive')) {
    function isUserNavLinkActive($link_path_segment, $current_path)
    {
        // Pastikan $current_path tidak null sebelum strpos
        if ($current_path === null) return false;
        return strpos($current_path, $link_path_segment) !== false;
    }
}

// Ambil foto profil pengguna
$user_photo_url_header = null;
if (function_exists('get_current_user_id')) {
    $current_user_id_for_photo = get_current_user_id();
    if ($current_user_id_for_photo && class_exists('User') && method_exists('User', 'findById')) {
        $user_data_for_photo = User::findById($current_user_id_for_photo);
        if ($user_data_for_photo && !empty($user_data_for_photo['foto_profil'])) {
            if (defined('UPLOADS_PROFIL_PATH') && defined('UPLOADS_URL')) {
                $photo_file_path_on_disk = rtrim(UPLOADS_PROFIL_PATH, '/') . '/' . $user_data_for_photo['foto_profil'];
                if (file_exists($photo_file_path_on_disk)) {
                    $user_photo_url_header = rtrim(UPLOADS_URL, '/') . '/profil/' . rawurlencode($user_data_for_photo['foto_profil']);
                } else {
                    error_log("header_user.php: File foto profil '{$user_data_for_photo['foto_profil']}' tidak ditemukan di server untuk user ID {$current_user_id_for_photo}.");
                }
            }
        }
    }
}
if ($user_photo_url_header === null) { // Default avatar
    $user_photo_url_header = (defined('ASSETS_URL') ? ASSETS_URL : $base_url . 'public/') . 'img/avatar_default.png';
}

// Pastikan fungsi e() dan get_current_user_name() ada
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('get_current_user_name')) {
    function get_current_user_name()
    {
        return 'Pengguna';
    }
}
if (!function_exists('is_admin')) {
    function is_admin()
    {
        return false;
    }
}
if (!function_exists('display_flash_message')) {
    function display_flash_message()
    {
        return '';
    }
}

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
    <link rel="stylesheet" href="<?= e($base_url) ?>public/css/style.css?v=<?= time() ?>">
    <link rel="icon" href="<?= e($base_url) ?>public/img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= e($base_url) ?>public/img/logo_apple_touch.png">
    <style>
        body {
            padding-top: 70px;
            /* Sesuaikan dengan tinggi navbar Anda */
            background-color: #f8f9fa;
        }

        .user-navbar-custom {
            background-color: var(--bs-success-darken, #198754) !important;
            /* Warna hijau gelap bootstrap atau var Anda */
            /* Atau gunakan: background-color: var(--primary-darker) !important; jika sudah didefinisikan */
        }

        .navbar-user-avatar-header {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        #preloader {
            position: fixed;
            left: 0;
            top: 0;
            z-index: 99999;
            width: 100%;
            height: 100%;
            overflow: visible;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #preloader .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid var(--primary-color, #2E8B57);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div id="preloader">
        <div class="spinner"></div>
        <p>Memuat Area Pengguna...</p>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark user-navbar-custom fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= e($base_url) ?>user/dashboard.php">
                <img src="<?= e($base_url) ?>public/img/logo.png" alt="Logo Cilengkrang" style="height: 35px; filter: brightness(0) invert(1);" class="me-2">
                <span>Area Pengguna</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userAreaNavbar" aria-controls="userAreaNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="userAreaNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <?php
                    $is_dashboard_active = isUserNavLinkActive('/user/dashboard.php', $current_uri_path) ||
                        $current_uri_path === rtrim(parse_url($base_url, PHP_URL_PATH), '/') . '/user/' ||
                        $current_uri_path === rtrim(parse_url($base_url, PHP_URL_PATH), '/') . '/user';
                    ?>
                    <li class="nav-item"><a class="nav-link <?= $is_dashboard_active ? 'active' : '' ?>" href="<?= e($base_url) ?>user/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= isUserNavLinkActive('/user/artikel.php', $current_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/artikel.php"><i class="fas fa-newspaper me-1"></i> Artikel</a></li>
                    <li class="nav-item"><a class="nav-link <?= isUserNavLinkActive('/user/pemesanan_tiket.php', $current_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/pemesanan_tiket.php"><i class="fas fa-ticket-alt me-1"></i> Buat Pemesanan</a></li>

                    <!-- Link Riwayat Pemesanan dihapus dari sini, akan diakses via Dashboard -->
                    <!-- <li class="nav-item"><a class="nav-link <?= isUserNavLinkActive('/user/riwayat_pemesanan.php', $current_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/riwayat_pemesanan.php"><i class="fas fa-history me-1"></i> Riwayat Pemesanan</a></li> -->

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center <?= (isUserNavLinkActive('/user/profil.php', $current_uri_path) || isUserNavLinkActive('/user/edit_profil.php', $current_uri_path) || isUserNavLinkActive('/user/ganti_password.php', $current_uri_path)) ? 'active' : '' ?>" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= e($user_photo_url_header) ?>" alt="Foto" class="navbar-user-avatar-header">
                            <?= e(get_current_user_name() ?: 'Akun Saya') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item <?= (isUserNavLinkActive('/user/profil.php', $current_uri_path) || isUserNavLinkActive('/user/edit_profil.php', $current_uri_path)) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/profil.php"><i class="fas fa-id-card me-2"></i>Profil Saya</a></li>
                            <li><a class="dropdown-item <?= isUserNavLinkActive('/user/ganti_password.php', $current_uri_path) ? 'active' : '' ?>" href="<?= e($base_url) ?>user/ganti_password.php"><i class="fas fa-key me-2"></i>Ganti Password</a></li>
                            <?php if (is_admin()): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-user-shield me-2"></i>Ke Panel Admin</a></li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?= e($base_url) ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-page-content-wrapper">
        <main class="flex-shrink-0">
            <div class="container py-4">
                <?= display_flash_message(); // Panggil di sini agar tidak terpengaruh redirect header 
                ?>
                <!-- Konten spesifik halaman pengguna akan dimulai setelah baris ini -->