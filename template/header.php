<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\header.php

// config.php seharusnya sudah dimuat oleh skrip pemanggil (misalnya index.php)
// dan sudah mendefinisikan BASE_URL, ASSETS_URL, dan variabel global $pengaturan_situs_global
// serta memuat semua helper.

// Pengecekan dependensi dasar yang harus ada dari config.php
if (!defined('BASE_URL') || !function_exists('e') || !function_exists('get_site_settings')) {
    $error_msg_header = "KRITIS header.php: Komponen konfigurasi atau helper inti tidak ditemukan.";
    error_log($error_msg_header);
    // Untuk produksi, mungkin lebih baik menampilkan halaman error atau pesan sederhana
    exit("Kesalahan konfigurasi sistem. Header tidak dapat dimuat. (" . $error_msg_header . ")");
}

// Ambil pengaturan situs menggunakan fungsi helper
$nama_situs_header = get_site_settings('nama_situs', 'Lembah Cilengkrang'); // Fallback jika tidak ada di DB
$tagline_header = get_site_settings('tagline_situs', 'Destinasi Wisata Alam Terbaik');
$deskripsi_meta_header = get_site_settings('deskripsi_situs', $tagline_header); // Gunakan tagline jika deskripsi khusus tidak ada
$logo_filename_header = get_site_settings('logo_situs');
$favicon_filename_header = get_site_settings('favicon_situs');

// Tentukan judul halaman akhir
$page_title_final = (isset($page_title) && !empty(trim($page_title)) ? e(trim($page_title)) . ' - ' : '') . e($nama_situs_header);

// Tentukan URL untuk logo
$logo_url_header_display = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/logo.png'; // Default logo
if (!empty($logo_filename_header) && defined('UPLOADS_SITUS_URL') && defined('UPLOADS_SITUS_PATH')) {
    $logo_server_path_check = rtrim(UPLOADS_SITUS_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($logo_filename_header);
    if (file_exists($logo_server_path_check) && is_file($logo_server_path_check)) {
        $logo_url_header_display = rtrim(UPLOADS_SITUS_URL, '/') . '/' . rawurlencode(basename($logo_filename_header));
    } else {
        error_log("HEADER_DEBUG: File logo '{$logo_filename_header}' dari pengaturan tidak ditemukan di server. Path: {$logo_server_path_check}. Menggunakan default.");
    }
} elseif (!empty($logo_filename_header)) {
    error_log("HEADER_DEBUG: Nama file logo '{$logo_filename_header}' diset, tapi UPLOADS_SITUS_URL/PATH tidak terdefinisi. Menggunakan default.");
}

// Tentukan URL untuk favicon
$favicon_url_header_display = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/favicon.ico'; // Default favicon
if (!empty($favicon_filename_header) && defined('UPLOADS_SITUS_URL') && defined('UPLOADS_SITUS_PATH')) {
    $favicon_server_path_check = rtrim(UPLOADS_SITUS_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($favicon_filename_header);
    if (file_exists($favicon_server_path_check) && is_file($favicon_server_path_check)) {
        $favicon_url_header_display = rtrim(UPLOADS_SITUS_URL, '/') . '/' . rawurlencode(basename($favicon_filename_header));
    } else {
        error_log("HEADER_DEBUG: File favicon '{$favicon_filename_header}' dari pengaturan tidak ditemukan di server. Path: {$favicon_server_path_check}. Menggunakan default.");
    }
} elseif (!empty($favicon_filename_header)) {
    error_log("HEADER_DEBUG: Nama file favicon '{$favicon_filename_header}' diset, tapi UPLOADS_SITUS_URL/PATH tidak terdefinisi. Menggunakan default.");
}


// Untuk foto profil pengguna di navbar (logika Anda sebelumnya sudah baik)
$user_photo_url_display = null;
if (function_exists('is_logged_in') && is_logged_in() && function_exists('get_current_user_id')) {
    if (class_exists('User') && method_exists('User', 'findById')) {
        $current_user_data_for_photo_header = User::findById(get_current_user_id());
        if ($current_user_data_for_photo_header && !empty($current_user_data_for_photo_header['foto_profil'])) {
            if (defined('UPLOADS_PROFIL_PATH') && defined('UPLOADS_PROFIL_URL')) {
                $photo_file_path_header_disk = rtrim(UPLOADS_PROFIL_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($current_user_data_for_photo_header['foto_profil']);
                if (file_exists($photo_file_path_header_disk) && is_file($photo_file_path_header_disk)) {
                    $user_photo_url_display = rtrim(UPLOADS_PROFIL_URL, '/') . '/' . rawurlencode(basename($current_user_data_for_photo_header['foto_profil']));
                }
            }
        }
    }
}
if ($user_photo_url_display === null) { // Default avatar
    $user_photo_url_display = (defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/avatar_default.png';
}

$is_homepage_header = isset($is_homepage) && $is_homepage === true; // Variabel ini harus di-set oleh halaman pemanggil
$current_uri_path_header = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$base_url_path_header = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '', '/');

if (!function_exists('isPublicNavLinkActive')) { // Buat fungsi khusus untuk header publik
    function isPublicNavLinkActive($link_path_segment, $current_path, $base_path)
    {
        if ($current_path === null || $link_path_segment === null) return false;
        $current_relative_path = $current_path;
        if (!empty($base_path) && strpos($current_path, $base_path) === 0) {
            $current_relative_path = substr($current_path, strlen($base_path));
        }
        $normalized_current = '/' . trim($current_relative_path, '/');
        $normalized_link = '/' . trim($link_path_segment, '/');

        if ($normalized_link === '/' || $normalized_link === '') { // Untuk link Beranda
            return $normalized_current === '/' || $normalized_current === '' || strpos($normalized_current, '/index.php') === 0;
        }
        return strpos($normalized_current, $normalized_link) === 0;
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(excerpt(strip_tags($page_description ?? $deskripsi_meta_header), 160)) ?>">
    <meta name="keywords" content="Cilengkrang, Lembah Cilengkrang, Wisata Kuningan, Wisata Jawa Barat, Pajambon, Kramatmulya, air terjun, pemandian air panas, wisata alam, <?= e($nama_situs_header) ?>">
    <title><?= $page_title_final ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Poppins:wght@500;700&family=Lora:wght@600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <link rel="stylesheet" href="<?= e(ASSETS_URL . 'css/style.css') ?>?v=<?= defined('ROOT_PATH') && file_exists(ROOT_PATH . '/public/css/style.css') ? filemtime(ROOT_PATH . '/public/css/style.css') : time() ?>">

    <link rel="icon" href="<?= e($favicon_url_header_display) ?>?t=<?= time() ?>" type="image/vnd.microsoft.icon"> <!-- Type yang lebih umum untuk .ico -->
    <link rel="apple-touch-icon" href="<?= e((defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/logo_apple_touch.png') ?>"> <!-- Path Apple touch icon bisa juga dari pengaturan -->

    <?php if (isset($custom_header_css) && is_array($custom_header_css)): // Untuk CSS spesifik halaman 
    ?>
        <?php foreach ($custom_header_css as $css_file_header): ?>
            <link rel="stylesheet" href="<?= e($css_file_header) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        .navbar-user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        /* Preloader Styles (jika Anda menggunakannya di semua halaman publik) */
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
            transition: opacity 0.5s ease-out, visibility 0s linear 0.5s;
            opacity: 1;
            visibility: visible;
        }

        #preloader.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        #preloader .spinner {
            border: 8px solid #e9ecef;
            border-top: 8px solid <?= e(get_site_settings('THEME_COLOR_PRIMARY', '#28a745')) ?>;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 0.8s linear infinite;
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

<body class="<?= $is_homepage_header ? 'homepage' : 'inner-page' ?>">
    <div id="preloader">
        <div class="spinner"></div>
        <p>Memuat Pesona <?= e($nama_situs_header) ?>...</p>
    </div>

    <header id="header-public">
        <nav class="navbar navbar-expand-lg navbar-public fixed-top <?= $is_homepage_header ? 'navbar-transparent-on-top' : 'navbar-scrolled-solid' // Kelas yang berbeda untuk non-homepage 
                                                                    ?>">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?= e(BASE_URL) ?>">
                    <?php if ($logo_url_header_display && $logo_url_header_display !== ((defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/logo.png')): // Tampilkan jika bukan logo default 
                    ?>
                        <img src="<?= e($logo_url_header_display) ?>?t=<?= time() ?>" alt="Logo <?= e($nama_situs_header) ?>" class="logo me-2" style="max-height: 40px;">
                    <?php else: // Fallback jika logo dari DB tidak ada atau sama dengan default (berarti default tidak diubah) 
                    ?>
                        <img src="<?= e((defined('ASSETS_URL') ? ASSETS_URL : BASE_URL . 'public/') . 'img/logo.png') ?>" alt="Logo <?= e($nama_situs_header) ?>" class="logo me-2" style="max-height: 40px;">
                    <?php endif; ?>
                    <span class="fw-bold fs-5"><?= e($nama_situs_header) ?></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbarNav" aria-controls="publicNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="publicNavbarNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item"><a class="nav-link <?= ($is_homepage_header) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>">Beranda</a></li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= isPublicNavLinkActive('/wisata/', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="#" id="wisataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Jelajahi
                            </a>
                            <ul class="dropdown-menu dropdown-menu-light animate slideIn" aria-labelledby="wisataDropdown">
                                <li><a class="dropdown-item <?= isPublicNavLinkActive('/wisata/semua_destinasi.php', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>wisata/semua_destinasi.php">Semua Destinasi</a></li>
                                <li><a class="dropdown-item <?= isPublicNavLinkActive('/wisata/galeri.php', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>wisata/galeri.php">Galeri Foto & Video</a></li>
                                <!-- Anda bisa menambahkan link lain jika ada file deskripsi.php, event.php, lokasi.php yang terpisah -->
                            </ul>
                        </li>

                        <li class="nav-item"><a class="nav-link <?= isPublicNavLinkActive('/user/artikel.php', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>user/artikel.php">Artikel</a></li>
                        <li class="nav-item"><a class="nav-link <?= isPublicNavLinkActive('/user/pemesanan_tiket.php', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>user/pemesanan_tiket.php">Pesan Tiket</a></li>
                        <li class="nav-item"><a class="nav-link <?= isPublicNavLinkActive('/contact.php', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>contact.php">Kontak</a></li>

                        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center <?= isPublicNavLinkActive('/user/', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="#" id="userMenuDropdownPublic" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?= e($user_photo_url_display) ?>?t=<?= time() ?>" alt="Avatar" class="navbar-user-avatar">
                                    <?= e(get_current_user_name() ?: 'Pengguna') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end animate slideIn" aria-labelledby="userMenuDropdownPublic">
                                    <li><a class="dropdown-item <?= isPublicNavLinkActive('/user/dashboard.php', $current_uri_path_header, $base_url_path_header) ? 'active' : '' ?>" href="<?= e(USER_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Saya</a></li>
                                    <li><a class="dropdown-item <?= (isPublicNavLinkActive('/user/profil.php', $current_uri_path_header, $base_url_path_header) || isPublicNavLinkActive('/user/edit_profil.php', $current_uri_path_header, $base_url_path_header) || isPublicNavLinkActive('/user/ganti_password.php', $current_uri_path_header, $base_url_path_header)) ? 'active' : '' ?>" href="<?= e(USER_URL . 'profil.php') ?>"><i class="fas fa-id-card me-2"></i>Profil Saya</a></li>
                                    <?php if (is_admin()): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-user-shield me-2"></i> Panel Admin</a></li>
                                    <?php endif; ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="<?= e(AUTH_URL . 'logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item ms-lg-2"><a class="btn btn-outline-light btn-sm px-3" href="<?= e(AUTH_URL . 'login.php') ?>">Login</a></li>
                            <li class="nav-item ms-lg-1"><a class="btn btn-warning btn-sm px-3 text-dark" href="<?= e(AUTH_URL . 'register.php') ?>">Daftar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="main-page-content-wrapper"> <!-- Wrapper untuk konten utama agar footer bisa sticky -->
        <main class="flex-shrink-0"> <!-- flex-shrink-0 dan flex-grow-1 di atas untuk sticky footer -->
            <!-- Konten spesifik halaman akan dimulai setelah baris ini -->
            <!-- display_flash_message() sebaiknya dipanggil di dalam kontainer utama halaman spesifik -->