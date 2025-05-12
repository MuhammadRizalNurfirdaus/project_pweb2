<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\header_admin.php
// PENTING: File pemanggil harus memuat config.php terlebih dahulu

// Validasi session admin
if (function_exists('require_admin')) {
    require_admin();
} else {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['is_loggedin']) || $_SESSION['is_loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
        if (isset($base_url)) {
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', 'Akses ditolak. Anda harus login sebagai admin.');
            }
            header('Location: ' . $base_url . 'auth/login.php');
            exit;
        } else {
            die("Akses ditolak. Konfigurasi tidak valid.");
        }
    }
}

// Set variabel halaman
$page_title = isset($page_title) ? e($page_title) : "Admin Panel";
$current_uri_admin = $_SERVER['REQUEST_URI'];

// Fungsi pengecekan menu aktif
if (!function_exists('isAdminSidebarActive')) {
    function isAdminSidebarActive($link_path, $base_url, $current_uri)
    {
        $base_admin_path = rtrim(parse_url($base_url, PHP_URL_PATH), '/') . '/admin/';
        $full_link_path = rtrim($base_admin_path . ltrim($link_path, '/'), '/');
        $current_path = rtrim(strtok($current_uri, '?'), '/');

        return strpos($current_path, $full_link_path) === 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Cilengkrang Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- PASTIKAN BARIS INI ADA, TIDAK DIKOMENTARI, DAN URL-NYA BENAR -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="icon" href="<?= isset($base_url) ? e($base_url . 'public/img/favicon.ico') : '#' ?>" type="image/x-icon">

    <style>
        /* --- Variabel Warna Dasar Admin (Mode Terang) --- */
        :root {
            --admin-bg-main: #f4f7f6;
            --admin-bg-content: #ffffff;
            --admin-text-primary: #212529;
            --admin-text-secondary: #555555;
            --admin-text-muted: #6c757d;
            --admin-text-sidebar: #bdc3c7;
            --admin-text-sidebar-active: #ffffff;
            --admin-text-card-header: #343a40;
            --admin-text-table-header: #ffffff;
            /* Teks untuk .table-dark thead */
            --admin-text-table-body: #212529;
            /* Teks untuk td dan th biasa */

            --admin-primary-color: #2E8B57;
            --admin-primary-darker: #256e48;
            --admin-primary-lighter: #3CB371;
            --admin-secondary-color: #FFB300;

            --admin-sidebar-bg: #2c3e50;
            --admin-sidebar-hover-bg: #34495e;
            --admin-sidebar-active-bg: var(--admin-primary-color);
            --admin-sidebar-active-border: var(--admin-secondary-color);
            --admin-sidebar-heading-text: #7f8c8d;

            --admin-navbar-top-bg: #ffffff;
            --admin-navbar-top-text: #343a40;
            --admin-navbar-top-link-hover: var(--admin-primary-color);
            --admin-navbar-top-border: #e0e0e0;
            --admin-card-header-bg: #f8f9fa;
            --admin-border-color: #dee2e6;

            --admin-stat-primary: var(--admin-primary-color);
            --admin-stat-success: #198754;
            --admin-stat-info: #0dcaf0;
            --admin-stat-warning: var(--admin-secondary-color);
            --admin-text-gray-300: #adb5bd;
            --admin-text-gray-800: var(--admin-text-primary);

            --admin-link-color: var(--admin-primary-color);
            --admin-link-hover-color: var(--admin-primary-darker);

            /* Variabel untuk tombol tema di admin */
            --theme-toggle-bg: var(--admin-sidebar-hover-bg);
            --theme-toggle-icon-color: var(--admin-text-sidebar);
            --theme-toggle-border: var(--admin-sidebar-heading-text);
            --theme-toggle-hover-bg: var(--admin-sidebar-bg);
        }

        /* --- Style Umum Admin (Menggunakan Variabel di atas) --- */
        body {
            background-color: var(--admin-bg-main) !important;
            color: var(--admin-text-primary) !important;
            transition: background-color 0.3s ease, color 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: 'Roboto', 'Open Sans', 'Segoe UI', sans-serif;
        }

        .admin-wrapper {
            display: flex;
            flex: 1;
            padding-top: 56px;
        }

        .sidebar {
            width: 260px;
            background-color: var(--admin-sidebar-bg);
            color: var(--admin-text-sidebar);
            min-height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1020;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            padding-top: 1rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .sidebar .nav-link {
            color: var(--admin-text-sidebar);
            padding: .9rem 1.25rem;
            display: flex;
            align-items: center;
            font-size: 0.98rem;
            border-left: 4px solid transparent;
            transition: background-color 0.2s ease, color 0.2s ease, border-left-color 0.2s ease;
        }

        .sidebar .nav-link .fas,
        .sidebar .nav-link .far {
            margin-right: 15px;
            width: 22px;
            text-align: center;
            font-size: 1.05em;
        }

        .sidebar .nav-link:hover {
            color: var(--admin-text-sidebar-active);
            background-color: var(--admin-sidebar-hover-bg);
        }

        .sidebar .nav-link.active {
            color: var(--admin-text-sidebar-active);
            background-color: var(--admin-sidebar-active-bg);
            border-left-color: var(--admin-sidebar-active-border);
            font-weight: 500;
        }

        .sidebar .sidebar-heading {
            padding: 1rem 1.25rem .5rem 1.25rem;
            font-size: .75rem;
            text-transform: uppercase;
            color: var(--admin-sidebar-heading-text);
            font-weight: 700;
            letter-spacing: .8px;
            transition: color 0.3s ease;
        }

        .main-admin-content {
            flex: 1;
            padding: 25px 30px;
            margin-left: 260px;
            background-color: var(--admin-bg-content);
            color: var(--admin-text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .main-admin-content h1,
        .main-admin-content h2,
        .main-admin-content h3,
        .main-admin-content h4,
        .main-admin-content h5,
        .main-admin-content h6,
        .main-admin-content .h1,
        .main-admin-content .h2,
        .main-admin-content .h3,
        .main-admin-content .h4,
        .main-admin-content .h5,
        .main-admin-content .h6 {
            color: var(--admin-text-primary);
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            margin-top: 1.2em;
            margin-bottom: 0.8em;
        }

        .main-admin-content p,
        .main-admin-content label,
        .main-admin-content .form-text,
        .main-admin-content div:not(.card):not(.alert):not(.row):not(.col):not(.col-auto):not(.no-gutters):not(.col-mr-2):not([class*="col-md-"]):not([class*="col-lg-"]):not([class*="mb-"]):not([class*="mt-"]):not([class*="p-"]):not([class*="text-center"]):not(.d-grid):not(.d-flex),
        .main-admin-content span:not(.badge):not(.navbar-text):not(.navbar-toggler-icon):not(.text-danger):not(.text-muted) {
            color: var(--admin-text-primary) !important;
        }

        .main-admin-content .text-muted {
            color: var(--admin-text-muted) !important;
        }

        .main-admin-content a:not(.btn):not(.nav-link):not(.dropdown-item):not([class*="card-footer"]) {
            color: var(--admin-link-color);
        }

        .main-admin-content a:not(.btn):not(.nav-link):not(.dropdown-item):not([class*="card-footer"]):hover {
            color: var(--admin-link-hover-color);
        }

        .admin-navbar-top {
            background-color: var(--admin-navbar-top-bg) !important;
            border-bottom: 1px solid var(--admin-navbar-top-border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            z-index: 1030;
            transition: background-color 0.3s ease, border-bottom-color 0.3s ease;
        }

        .admin-navbar-top .navbar-brand {
            color: var(--admin-primary-color) !important;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .admin-navbar-top .navbar-nav .nav-link,
        .admin-navbar-top .navbar-nav .navbar-text {
            color: var(--admin-navbar-top-text) !important;
            font-size: 0.9rem;
        }

        .admin-navbar-top .navbar-nav .nav-link:hover {
            color: var(--admin-navbar-top-link-hover) !important;
        }

        .admin-navbar-top .navbar-brand img {
            max-height: 30px;
            margin-right: 8px;
        }

        .card {
            background-color: var(--admin-bg-content);
            border: 1px solid var(--admin-border-color);
            color: var(--admin-text-primary);
            margin-bottom: 1.5rem;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .card .card-title {
            color: var(--admin-primary-color);
            margin-top: 0;
        }

        .card .card-body {
            color: var(--admin-text-primary);
        }

        .card .card-body p,
        .card .card-body div:not(.row):not(.col):not([class*="col-md-"]):not([class*="col-lg-"]):not(.input-group):not(.alert),
        .card .card-body span:not(.badge) {
            color: var(--admin-text-primary);
        }

        .card .card-body .text-muted {
            color: var(--admin-text-muted) !important;
        }

        .card.border-start-primary {
            border-left-color: var(--admin-stat-primary) !important;
        }

        .card.border-start-success {
            border-left-color: var(--admin-stat-success) !important;
        }

        .card.border-start-info {
            border-left-color: var(--admin-stat-info) !important;
        }

        .card.border-start-warning {
            border-left-color: var(--admin-stat-warning) !important;
        }

        .text-xs {
            font-size: .75rem;
        }

        .font-weight-bold {
            font-weight: 700 !important;
        }

        .card-body .text-primary:not(h1):not(h2):not(h3):not(h4):not(h5):not(h6):not(a) {
            color: var(--admin-stat-primary) !important;
        }

        .card-body .text-success:not(h1):not(h2):not(h3):not(h4):not(h5):not(h6):not(a) {
            color: var(--admin-stat-success) !important;
        }

        .card-body .text-info:not(h1):not(h2):not(h3):not(h4):not(h5):not(h6):not(a) {
            color: var(--admin-stat-info) !important;
        }

        .card-body .text-warning:not(h1):not(h2):not(h3):not(h4):not(h5):not(h6):not(a) {
            color: var(--admin-stat-warning) !important;
        }

        .text-gray-800 {
            color: var(--admin-text-gray-800) !important;
        }

        .text-gray-300 {
            color: var(--admin-text-gray-300) !important;
        }

        .card-header.bg-light {
            background-color: var(--admin-card-header-bg) !important;
            border-bottom: 1px solid var(--admin-border-color);
            transition: background-color 0.3s ease, border-bottom-color 0.3s ease;
        }

        .card-header .text-primary {
            color: var(--admin-primary-color) !important;
        }

        .card-header h1,
        .card-header h2,
        .card-header h3,
        .card-header h4,
        .card-header h5,
        .card-header h6 {
            color: var(--admin-text-card-header) !important;
            margin-bottom: 0;
        }

        .card-footer {
            background-color: var(--admin-card-header-bg);
            border-top: 1px solid var(--admin-border-color);
            transition: background-color 0.3s ease, border-top-color 0.3s ease;
        }

        .card-footer.text-decoration-none span {
            color: var(--admin-link-color) !important;
        }

        .card-footer.text-decoration-none:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }

        body.dark-mode .card-footer.text-decoration-none:hover {
            background-color: rgba(255, 255, 255, 0.04);
        }

        body.dark-mode .card-footer.text-decoration-none span {
            color: var(--admin-link-color) !important;
        }

        .breadcrumb {
            background-color: var(--admin-card-header-bg);
            padding: 0.75rem 1rem;
            border-radius: 0.3rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--admin-border-color);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .breadcrumb-item a {
            color: var(--admin-link-color);
        }

        .breadcrumb-item.active {
            color: var(--admin-text-muted);
        }

        .table {
            color: var(--admin-text-table-body);
            border-color: var(--admin-border-color);
            background-color: var(--admin-bg-content);
            /* Pastikan background table mengikuti bg konten */
        }

        .table th,
        .table td {
            border-color: var(--admin-border-color) !important;
            /* Paksa border agar konsisten */
            color: var(--admin-text-table-body) !important;
            /* Paksa warna teks sel */
            vertical-align: middle;
            background-color: inherit;
            /* Sel mewarisi background dari .table atau tr */
        }

        .table thead th {
            color: var(--admin-text-table-header) !important;
            /* Paksa warna teks header */
            font-weight: 600;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            --bs-table-accent-bg: rgba(0, 0, 0, 0.02);
            background-color: var(--bs-table-accent-bg);
            /* Terapkan variabel striping */
            color: var(--admin-text-table-body) !important;
            /* Teks di striping juga */
        }

        .table-dark {
            --bs-table-bg: #2c3e50;
            --bs-table-border-color: #454d55;
            --bs-table-color: #ffffff;
        }

        .table-dark th,
        .table-dark td,
        .table-dark thead th {
            color: var(--bs-table-color) !important;
            /* Gunakan variabel Bootstrap untuk .table-dark */
            background-color: var(--bs-table-bg) !important;
            border-color: var(--bs-table-border-color) !important;
        }

        .table-hover>tbody>tr:hover>* {
            --bs-table-accent-bg: rgba(0, 0, 0, 0.05);
            background-color: var(--bs-table-accent-bg) !important;
            /* Paksa background hover */
            color: var(--admin-text-table-body) !important;
            /* Teks saat hover */
        }

        .list-group-item {
            background-color: var(--admin-bg-content);
            color: var(--admin-text-primary);
            border-color: var(--admin-border-color);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .list-group-item-action:hover,
        .list-group-item-action:focus {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--admin-primary-color);
        }

        .badge.bg-light {
            background-color: var(--admin-card-header-bg) !important;
            color: var(--admin-text-muted) !important;
            border: 1px solid var(--admin-border-color);
        }

        #theme-toggle-btn {
            position: fixed;
            bottom: 25px;
            left: 25px;
            background-color: var(--theme-toggle-bg);
            color: var(--theme-toggle-icon-color);
            border: 1px solid var(--theme-toggle-border);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            z-index: 1051;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        #theme-toggle-btn:hover {
            background-color: var(--theme-toggle-hover-bg);
            transform: scale(1.1);
        }

        #theme-toggle-btn i {
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .form-label {
            color: var(--admin-text-primary);
        }

        .form-control,
        .form-select {
            background-color: var(--admin-bg-content);
            color: var(--admin-text-primary);
            border-color: var(--admin-border-color);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--admin-primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        .alert {
            border-width: 1px;
            border-style: solid;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }

        .alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffecb5;
            color: #664d03;
        }

        .alert-info {
            background-color: #cff4fc;
            border-color: #b6effb;
            color: #055160;
        }

        .btn-close {
            filter: none;
        }

        /* --- Penyesuaian Mode Gelap untuk Panel Admin --- */
        body.dark-mode {
            --admin-bg-main: #1f2937;
            --admin-bg-content: #111827;
            --admin-text-primary: #e5e7eb;
            --admin-text-secondary: #d1d5db;
            --admin-text-muted: #9ca3af;
            --admin-text-sidebar: #9ca3af;
            --admin-text-sidebar-active: #ffffff;
            --admin-text-card-header: #6ee7b7;
            --admin-text-table-header: var(--admin-primary-lighter);
            --admin-text-table-body: var(--admin-text-primary);

            --admin-primary-color: #34d399;
            --admin-primary-darker: #10b981;
            --admin-primary-lighter: #6ee7b7;
            --admin-secondary-color: #f59e0b;

            --admin-sidebar-bg: #111827;
            --admin-sidebar-hover-bg: #1f2937;
            --admin-sidebar-active-bg: var(--admin-primary-darker);
            --admin-sidebar-active-border: var(--admin-secondary-color);
            --admin-sidebar-heading-text: #6b7280;

            --admin-navbar-top-bg: #1f2937;
            --admin-navbar-top-text: #d1d5db;
            --admin-navbar-top-link-hover: var(--admin-primary-lighter);
            --admin-navbar-top-border: #374151;
            --admin-card-header-bg: #1f2937;
            --admin-border-color: #374151;

            --admin-stat-primary: var(--admin-primary-lighter);
            --admin-stat-success: #22c55e;
            --admin-stat-info: #38bdf8;
            --admin-stat-warning: var(--admin-secondary-color);
            --admin-text-gray-300: #6b7280;
            --admin-text-gray-800: var(--admin-text-primary);

            --admin-link-color: var(--admin-primary-lighter);
            --admin-link-hover-color: #a7f3d0;

            --theme-toggle-bg: var(--admin-sidebar-hover-bg);
            --theme-toggle-icon-color: var(--admin-secondary-color);
            --theme-toggle-border: var(--admin-sidebar-heading-text);
            --theme-toggle-hover-bg: var(--admin-sidebar-bg);
        }

        /* Override style spesifik untuk elemen di mode gelap */
        body.dark-mode .main-admin-content p,
        body.dark-mode .main-admin-content label,
        body.dark-mode .main-admin-content .form-text,
        body.dark-mode .main-admin-content div:not(.card):not(.alert):not(.row):not(.col):not(.col-auto):not(.no-gutters):not(.col-mr-2):not([class*="col-md-"]):not([class*="col-lg-"]):not([class*="mb-"]):not([class*="mt-"]):not([class*="p-"]):not([class*="text-center"]):not(.d-grid):not(.d-flex),
        body.dark-mode .main-admin-content span:not(.badge):not(.navbar-text):not(.navbar-toggler-icon):not(.text-danger):not(.text-muted) {
            color: var(--admin-text-primary) !important;
        }

        body.dark-mode .card .card-body p,
        body.dark-mode .card .card-body div:not(.row):not(.col):not([class*="col-md-"]):not([class*="col-lg-"]):not(.input-group):not(.alert),
        body.dark-mode .card .card-body span:not(.badge) {
            color: var(--admin-text-primary);
        }

        body.dark-mode .table-striped>tbody>tr:nth-of-type(odd)>* {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.03);
            color: var(--admin-text-table-body) !important;
            /* Pastikan teks di striping juga terang */
        }

        body.dark-mode .table-striped>tbody>tr:nth-of-type(odd) td {
            background-color: var(--bs-table-accent-bg) !important;
        }

        body.dark-mode .table-dark {
            --bs-table-bg: #374151;
            --bs-table-border-color: #4b5563;
        }

        body.dark-mode .table-dark th,
        body.dark-mode .table-dark td,
        body.dark-mode .table-dark thead th {
            color: var(--admin-text-primary) !important;
            background-color: var(--bs-table-bg) !important;
            border-color: var(--bs-table-border-color) !important;
        }

        body.dark-mode .table-hover>tbody>tr:hover>* {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.06);
            color: var(--admin-text-primary) !important;
        }

        body.dark-mode .table-hover>tbody>tr:hover td {
            background-color: var(--bs-table-accent-bg) !important;
        }

        body.dark-mode .list-group-item-action:hover,
        body.dark-mode .list-group-item-action:focus {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--admin-primary-lighter);
        }

        body.dark-mode .badge.bg-light {
            background-color: var(--admin-border-color) !important;
            color: var(--admin-text-secondary) !important;
            border-color: #4b5563;
        }

        body.dark-mode .form-control::placeholder {
            color: var(--admin-text-muted);
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            border-color: var(--admin-primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 211, 153, 0.3);
        }

        body.dark-mode .alert-danger {
            background-color: #4a2c30;
            border-color: #583035;
            color: #f5c2c7;
        }

        body.dark-mode .alert-success {
            background-color: #1c3c30;
            border-color: #25503e;
            color: #badbcc;
        }

        body.dark-mode .alert-warning {
            background-color: #4d3c14;
            border-color: #594922;
            color: #ffecb5;
        }

        body.dark-mode .alert-info {
            background-color: #153f4a;
            border-color: #1e5361;
            color: #b6effb;
        }

        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
</head>

<body>
    <!-- Navbar Top -->
    <nav class="navbar navbar-expand-lg admin-navbar-top fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= isset($base_url) ? e($base_url . 'admin/dashboard.php') : '#' ?>">
                <img src="<?= isset($base_url) ? e($base_url . 'public/img/logo.png') : '#' ?>" alt="Logo"> Cilengkrang Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminTopNav" aria-controls="adminTopNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminTopNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <i class="fas fa-user-circle me-1" style="color: var(--admin-primary-color);"></i>
                            Halo, <strong><?= e(function_exists('get_current_user_name') ? get_current_user_name() : 'Admin') ?></strong>!
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= isset($base_url) ? e($base_url) : '#' ?>" target="_blank" title="Lihat Situs Publik">
                            <i class="fas fa-eye"></i> <span class="d-none d-sm-inline-block ms-1">Lihat Situs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?= isset($base_url) ? e($base_url . 'auth/logout.php') : '#' ?>" title="Logout">
                            <i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline-block ms-1">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Sidebar -->
    <div class="admin-wrapper">
        <nav class="sidebar">
            <div class="sidebar-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="sidebar-heading">Utama</li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('dashboard.php', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/dashboard.php') ?>">
                            <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                        </a>
                    </li>

                    <li class="sidebar-heading mt-3">Manajemen Konten</li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('artikel/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/artikel/kelola_artikel.php') ?>">
                            <i class="fas fa-newspaper fa-fw"></i> Artikel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('galeri/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/galeri/kelola_galeri.php') ?>">
                            <i class="fas fa-images fa-fw"></i> Galeri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('wisata/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/wisata/kelola_wisata.php') ?>">
                            <i class="fas fa-map-marked-alt fa-fw"></i> Destinasi Wisata
                        </a>
                    </li>

                    <li class="sidebar-heading mt-3">Manajemen Tiket & Fasilitas</li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('jenis_tiket/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/jenis_tiket/kelola_jenis_tiket.php') ?>">
                            <i class="fas fa-tags fa-fw"></i> Jenis Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('jadwal_ketersediaan/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/jadwal_ketersediaan/kelola_jadwal.php') ?>">
                            <i class="fas fa-calendar-alt fa-fw"></i> Jadwal Ketersediaan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('alat_sewa/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/alat_sewa/kelola_alat.php') ?>">
                            <i class="fas fa-tools fa-fw"></i> Alat Sewa
                        </a>
                    </li>

                    <li class="sidebar-heading mt-3">Manajemen Pemesanan</li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('pemesanan_tiket/', $base_url ?? '', $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= isset($base_url) ? e($base_url . 'admin/pemesanan_tiket/kelola_pemesanan.php') : '#' ?>">
                            <i class="fas fa-ticket-alt fa-fw"></i> Pemesanan Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('pemesanan_sewa/', $base_url ?? '', $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= isset($base_url) ? e($base_url . 'admin/pemesanan_sewa/kelola_pemesanan_sewa.php') : '#' ?>">
                            <i class="fas fa-box-open fa-fw"></i> Pemesanan Sewa Alat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('pembayaran/', $base_url ?? '', $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= isset($base_url) ? e($base_url . 'admin/pembayaran/kelola_pembayaran.php') : '#' ?>">
                            <i class="fas fa-money-check-alt fa-fw"></i> Kelola Pembayaran
                        </a>
                    </li>


                    <li class="sidebar-heading mt-3">Interaksi Pengguna</li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('contact/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/contact/kelola_contact.php') ?>">
                            <i class="fas fa-envelope-open-text fa-fw"></i> Pesan Kontak
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('feedback/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/feedback/kelola_feedback.php') ?>">
                            <i class="fas fa-comment-dots fa-fw"></i> Feedback Pengguna
                        </a>
                    </li>

                    <li class="sidebar-heading mt-3">Administrasi Situs</li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('users/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/users/kelola_users.php') ?>">
                            <i class="fas fa-users-cog fa-fw"></i> Manajemen Pengguna
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isAdminSidebarActive('pengaturan/', $base_url, $current_uri_admin)) ? 'active' : '' ?>"
                            href="<?= e($base_url . 'admin/pengaturan/umum.php') // Arahkan ke halaman pengaturan yang spesifik jika ada 
                                    ?>">
                            <i class="fas fa-cogs fa-fw"></i> Pengaturan Umum
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content Wrapper (Konten akan di-load di sini oleh halaman spesifik) -->
        <main class="main-admin-content">
            <div class="container-fluid pt-3">
                <?php
                if (function_exists('display_flash_message')) {
                    echo display_flash_message();
                }
                ?>
                <!-- Konten spesifik halaman admin akan dimulai setelah baris ini oleh file pemanggil -->