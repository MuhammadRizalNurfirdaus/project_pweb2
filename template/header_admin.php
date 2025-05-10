<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\template\header_admin.php
// PENTING: Skrip pemanggil (misal: admin/dashboard.php) HARUS sudah:
// 1. require_once __DIR__ . '/../../config/config.php'; (atau path relatif yang benar)
// 2. Baru setelah itu, skrip pemanggil bisa include header ini.
// Fungsi require_admin() dari auth_helpers.php (di-include oleh config.php) akan dipanggil di SINI.

require_admin(); // Proteksi halaman admin

$page_title = isset($page_title) ? e($page_title) : "Admin Panel"; // Judul default
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - Cilengkrang Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Style utama (bisa jadi tidak terlalu banyak pengaruh jika admin punya style sendiri) -->
    <link rel="stylesheet" href="<?= $base_url ?>public/css/style.css">
    <!-- Style khusus admin (buat file ini jika perlu banyak kustomisasi) -->
    <!-- <link rel="stylesheet" href="<?= $base_url ?>public/css/admin_style.css"> -->

    <style>
        /* Style untuk layout admin (bisa dipindah ke admin_style.css) */
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            padding-top: 56px;
            background-color: var(--light-bg);
        }

        .admin-wrapper {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 260px;
            background-color: var(--dark-bg, #343a40);
            color: #adb5bd;
            padding-top: 1rem;
            min-height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--box-shadow-sm);
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: .8rem 1.25rem;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
            transition: background-color 0.2s ease, color 0.2s ease, border-left-color 0.2s ease;
        }

        .sidebar .nav-link .fas,
        .sidebar .nav-link .far {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1em;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #454d55;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--primary-color, #2E8B57);
            border-left-color: var(--secondary-color, #FFB300);
            font-weight: 500;
        }

        .sidebar .sidebar-heading {
            padding: .75rem 1.25rem;
            font-size: .8rem;
            text-transform: uppercase;
            color: #868e96;
            font-weight: 600;
            letter-spacing: .5px;
        }

        .main-admin-content {
            flex: 1;
            padding: 25px;
            margin-left: 260px;
        }

        .admin-navbar-top {
            background-color: var(--primary-color, #2E8B57) !important;
            z-index: 1020;
            box-shadow: var(--box-shadow-sm);
        }

        .card.border-start-primary {
            border-left-width: .25rem !important;
            border-left-color: var(--bs-primary) !important;
        }

        .card.border-start-success {
            border-left-width: .25rem !important;
            border-left-color: var(--bs-success) !important;
        }

        .card.border-start-info {
            border-left-width: .25rem !important;
            border-left-color: var(--bs-info) !important;
        }

        .card.border-start-warning {
            border-left-width: .25rem !important;
            border-left-color: var(--bs-warning) !important;
        }

        .text-xs {
            font-size: .75rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar-top fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $base_url ?>admin/dashboard.php">
                <img src="<?= $base_url ?>public/img/logo.png" alt="Logo" style="height: 30px; filter: brightness(0) invert(1); margin-right: 8px;"> Admin Cilengkrang
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminTopNav" aria-controls="adminTopNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminTopNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item"><span class="navbar-text text-white me-3"><i class="fas fa-user-shield me-1"></i> Halo, <strong><?= e(get_current_user_name()) ?></strong>!</span></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>" target="_blank" title="Lihat Situs Publik"><i class="fas fa-eye"></i> <span class="d-lg-none ms-1">Lihat Situs</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>auth/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i> <span class="d-lg-none ms-1">Logout</span></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="admin-wrapper">
        <nav class="sidebar">
            <div class="sidebar-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/dashboard') !== false || $_SERVER['REQUEST_URI'] == $base_url . 'admin/') ? 'active' : '' ?>" href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

                    <li class="sidebar-heading mt-3">Manajemen Konten</li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/artikel/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/artikel/kelola_artikel.php"><i class="fas fa-newspaper"></i> Artikel</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/galeri/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/galeri/kelola_galeri.php"><i class="fas fa-images"></i> Galeri</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/wisata/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/wisata/kelola_wisata.php"><i class="fas fa-map-marked-alt"></i> Wisata</a></li>

                    <li class="sidebar-heading mt-3">Interaksi Pengguna</li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/booking/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/booking/kelola_booking.php"><i class="fas fa-calendar-check"></i> Booking</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/contact/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/contact/kelola_contact.php"><i class="fas fa-envelope"></i> Pesan Kontak</a></li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/feedback/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/feedback/kelola_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>

                    <li class="sidebar-heading mt-3">Administrasi</li>
                    <li class="nav-item"><a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'admin/users/') !== false) ? 'active' : '' ?>" href="<?= $base_url ?>admin/users/kelola_users.php"><i class="fas fa-users-cog"></i> Pengguna</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-cogs"></i> Pengaturan</a></li>
                </ul>
            </div>
        </nav>

        <main class="main-admin-content">
            <div class="container-fluid pt-3">
                <?= display_flash_message(); ?>
                <!-- Konten spesifik halaman admin akan dimulai setelah baris ini -->