<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\dashboard.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/dashboard.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan SEMUA Model yang diperlukan
require_once __DIR__ . '/../models/Artikel.php';
require_once __DIR__ . '/../models/PemesananTiket.php';
require_once __DIR__ . '/../models/SewaAlat.php';
require_once __DIR__ . '/../models/PemesananSewaAlat.php';
require_once __DIR__ . '/../models/Feedback.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Pembayaran.php';

// 4. Sertakan header admin
if (!include_once __DIR__ . '/../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php.");
    echo "<p>Error header.</p>";
}

// --- Pengambilan Data Dinamis untuk Kartu Statistik ---
$total_artikel_display = '-';
$total_pemesanan_tiket_pending_display = '-';
$total_pemesanan_sewa_pending_display = '-';
$total_item_alat_sewa_display = '-';
$total_feedback_display = '-';
$total_pengguna_terdaftar_display = '-';
$total_pendapatan_display = 'Rp 0';

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {

    // --- Total Artikel ---
    try {
        if (class_exists('Artikel') && method_exists('Artikel', 'countAll')) {
            $total_artikel_display = Artikel::countAll(); // Panggil statis
        } else {
            error_log("Dashboard: Artikel::countAll() tidak ada.");
            $total_artikel_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error Artikel: " . $e->getMessage());
        $total_artikel_display = 'Error';
    }

    // --- Pemesanan Tiket Pending ---
    try {
        if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'countByStatus')) {
            $total_pemesanan_tiket_pending_display = PemesananTiket::countByStatus('pending'); // Panggil statis
        } else {
            error_log("Dashboard: PemesananTiket::countByStatus() tidak ada.");
            $total_pemesanan_tiket_pending_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error Pemesanan Tiket: " . $e->getMessage());
        $total_pemesanan_tiket_pending_display = 'Error';
    }

    // --- Pemesanan Sewa Alat Pending ---
    try {
        if (class_exists('PemesananSewaAlat') && method_exists('PemesananSewaAlat', 'countByStatus')) {
            $total_pemesanan_sewa_pending_display = PemesananSewaAlat::countByStatus('Dipesan'); // Panggil statis
        } else {
            error_log("Dashboard: PemesananSewaAlat::countByStatus() tidak ada.");
            $total_pemesanan_sewa_pending_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error Pemesanan Sewa: " . $e->getMessage());
        $total_pemesanan_sewa_pending_display = 'Error';
    }

    // --- Total Item Alat Sewa ---
    try {
        if (class_exists('SewaAlat') && method_exists('SewaAlat', 'countAll')) {
            $total_item_alat_sewa_display = SewaAlat::countAll(); // Panggil statis
        } else {
            error_log("Dashboard: SewaAlat::countAll() tidak ada.");
            $total_item_alat_sewa_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error Alat Sewa: " . $e->getMessage());
        $total_item_alat_sewa_display = 'Error';
    }

    // --- Total Feedback ---
    try {
        if (class_exists('Feedback') && method_exists('Feedback', 'countAll')) {
            $total_feedback_display = Feedback::countAll(); // Panggil statis
        } else {
            error_log("Dashboard: Feedback::countAll() tidak ada.");
            $total_feedback_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error Feedback: " . $e->getMessage());
        $total_feedback_display = 'Error';
    }

    // --- Pengguna Terdaftar ---
    try {
        if (class_exists('User') && method_exists('User', 'countAll')) {
            $total_pengguna_terdaftar_display = User::countAll(); // Panggil statis
        } else {
            error_log("Dashboard: User::countAll() tidak ada.");
            $total_pengguna_terdaftar_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error User: " . $e->getMessage());
        $total_pengguna_terdaftar_display = 'Error';
    }

    // --- Total Pendapatan ---
    try {
        if (class_exists('Pembayaran') && method_exists('Pembayaran', 'getTotalRevenue')) {
            $pendapatan = Pembayaran::getTotalRevenue(['success', 'paid', 'confirmed']); // Panggil statis
            $total_pendapatan_display = formatRupiah($pendapatan);
        } else {
            error_log("Dashboard: Pembayaran::getTotalRevenue() tidak ada.");
            $total_pendapatan_display = 'N/A';
        }
    } catch (Throwable $e) {
        error_log("Dashboard Error Pendapatan: " . $e->getMessage());
        $total_pendapatan_display = 'Error';
    }
} else {
    // ... (error handling DB connection) ...
    error_log("Dashboard Critical Error: Koneksi database tidak valid.");
    $total_artikel_display = 'DB Error';
    $total_pemesanan_tiket_pending_display = 'DB Error';
    $total_pemesanan_sewa_pending_display = 'DB Error';
    $total_item_alat_sewa_display = 'DB Error';
    $total_feedback_display = 'DB Error';
    $total_pengguna_terdaftar_display = 'DB Error';
    $total_pendapatan_display = 'DB Error';
}
?>


<!-- Konten HTML Dashboard (tetap sama seperti yang Anda berikan sebelumnya) -->
<!-- ... (HTML dari <nav aria-label="breadcrumb"> sampai akhir) ... -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</li>
    </ol>
</nav>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard Ringkasan</h1>
</div>
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Artikel</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_artikel_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-newspaper fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/artikel/kelola_artikel.php" class="card-footer text-decoration-none text-primary small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pemesanan Tiket Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pemesanan_tiket_pending_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-ticket-alt fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/kelola_pemesanan.php?status=pending" class="card-footer text-decoration-none text-success small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pemesanan Sewa Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pemesanan_sewa_pending_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dolly-flatbed fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php?status=Dipesan" class="card-footer text-decoration-none text-info small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pendapatan (Success)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pendapatan_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/pembayaran/kelola_pembayaran.php?status_pembayaran=success" class="card-footer text-decoration-none text-primary small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Feedback</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_feedback_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/feedback/kelola_feedback.php" class="card-footer text-decoration-none text-secondary small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pengguna Terdaftar</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pengguna_terdaftar_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/users/kelola_users.php" class="card-footer text-decoration-none text-warning small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Item Alat Sewa</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_item_alat_sewa_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-tools fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL) ?>/alat_sewa/kelola_alat.php" class="card-footer text-decoration-none text-danger small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terkini (Contoh Statis)</h6>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-newspaper text-info me-2"></i> Artikel baru "Pesona Curug Landung" telah ditambahkan.</div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">1 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-ticket-alt text-success me-2"></i> Pemesanan Tiket #PT00128 untuk "Pemandian Air Panas" telah dikonfirmasi.</div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">3 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-dolly-flatbed text-primary me-2"></i> Pemesanan Sewa Alat #PSA0023 untuk "Tenda" telah dikonfirmasi.</div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">5 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-comments text-warning me-2"></i> Feedback baru diterima dari pengguna Budi Santoso.</div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">Kemarin</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-user-plus text-primary me-2"></i> Pengguna baru "anita_sari" telah berhasil terdaftar.</div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">2 hari lalu</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Tautan Cepat</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= e(ADMIN_URL) ?>/artikel/tambah_artikel.php" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle fa-fw me-2 text-success"></i>Tambah Artikel Baru</a>
                <a href="<?= e(ADMIN_URL) ?>/wisata/tambah_wisata.php" class="list-group-item list-group-item-action"><i class="fas fa-map-pin fa-fw me-2 text-info"></i>Tambah Destinasi Wisata</a>
                <a href="<?= e(ADMIN_URL) ?>/alat_sewa/tambah_alat.php" class="list-group-item list-group-item-action"><i class="fas fa-tools fa-fw me-2 text-secondary"></i>Tambah Alat Sewa</a>
                <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/kelola_pemesanan.php" class="list-group-item list-group-item-action"><i class="fas fa-tasks fa-fw me-2 text-primary"></i>Kelola Pemesanan Tiket</a>
                <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php" class="list-group-item list-group-item-action"><i class="fas fa-boxes-stacked fa-fw me-2 text-primary"></i>Kelola Pemesanan Sewa</a>
                <a href="<?= e(ADMIN_URL) ?>/users/kelola_users.php" class="list-group-item list-group-item-action"><i class="fas fa-users-cog fa-fw me-2 text-warning"></i>Manajemen Pengguna</a>
                <a href="<?= e(ADMIN_URL) ?>/pembayaran/kelola_pembayaran.php" class="list-group-item list-group-item-action"><i class="fas fa-credit-card fa-fw me-2 text-danger"></i>Kelola Pembayaran</a>
            </div>
        </div>
    </div>
</div>

<?php
if (!include_once __DIR__ . '/../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php.");
}
?>