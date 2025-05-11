<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\dashboard.php

// 1. Selalu sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/dashboard.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}

// 2. Sertakan Model-model yang diperlukan untuk data dashboard
$models_loaded_successfully = true;
if (!@require_once __DIR__ . '/../models/Artikel.php') {
    error_log("ERROR: Gagal memuat models/Artikel.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
if (!@require_once __DIR__ . '/../models/Booking.php') { // Diasumsikan ada model ini
    error_log("ERROR: Gagal memuat models/Booking.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
if (!@require_once __DIR__ . '/../models/Feedback.php') { // Diasumsikan ada model ini
    error_log("ERROR: Gagal memuat models/Feedback.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
if (!@require_once __DIR__ . '/../models/User.php') { // Diasumsikan ada model ini
    error_log("ERROR: Gagal memuat models/User.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}

// 3. Sertakan header admin
// Pastikan header_admin.php ada di dalam folder template
// Jika tidak ada, tampilkan pesan error dan hentikan eksekusi
if (!@include_once __DIR__ . '/../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat admin/template/header_admin.php dari admin/dashboard.php. Path yang dicoba: " . __DIR__ . '/template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file.</div>";
    exit;
}

// 4. Pengambilan Data Dinamis untuk Kartu Statistik
$total_artikel_display = 'N/A';
$total_booking_pending_display = 'N/A';
$total_feedback_baru_display = 'N/A'; // "Baru" bisa berarti semua atau yang belum dibaca
$total_pengguna_terdaftar_display = 'N/A';

if (isset($conn) && $conn && $models_loaded_successfully) {
    // Total Artikel
    if (class_exists('Artikel')) {
        try {
            $artikel_model_dashboard = new Artikel($conn);
            $artikel_list_dashboard = $artikel_model_dashboard->getAll(); // Asumsi getAll() mengembalikan array
            $total_artikel_display = is_array($artikel_list_dashboard) ? count($artikel_list_dashboard) : 0;
        } catch (Throwable $e) {
            error_log("Error mengambil data Artikel untuk dashboard: " . $e->getMessage());
            $total_artikel_display = 'Error';
        }
    }

    // Booking Pending
    if (class_exists('Booking')) {
        try {
            // Jika Booking::countByStatus() adalah method static:
            // $total_booking_pending_display = Booking::countByStatus('pending', $conn); // Kirim $conn jika method static membutuhkannya

            // Atau jika Anda perlu mengambil semua dan menghitung:
            $all_bookings = Booking::getAll($conn); // Asumsi getAll adalah static dan mungkin butuh $conn
            if (is_array($all_bookings)) {
                $pending_count = 0;
                foreach ($all_bookings as $booking) {
                    if (isset($booking['status']) && strtolower($booking['status']) === 'pending') {
                        $pending_count++;
                    }
                }
                $total_booking_pending_display = $pending_count;
            } else {
                $total_booking_pending_display = 0;
            }
        } catch (Throwable $e) {
            error_log("Error mengambil data Booking untuk dashboard: " . $e->getMessage());
            $total_booking_pending_display = 'Error';
        }
    }

    // Feedback Baru (Total Feedback)
    if (class_exists('Feedback')) {
        try {
            // Jika Feedback::countAll() adalah method static:
            // $total_feedback_baru_display = Feedback::countAll($conn);

            // Atau jika Anda perlu mengambil semua dan menghitung:
            $all_feedback = Feedback::getAll($conn); // Asumsi getAll adalah static
            $total_feedback_baru_display = is_array($all_feedback) ? count($all_feedback) : 0;
        } catch (Throwable $e) {
            error_log("Error mengambil data Feedback untuk dashboard: " . $e->getMessage());
            $total_feedback_baru_display = 'Error';
        }
    }

    // Pengguna Terdaftar
    if (class_exists('User')) {
        try {
            // Anda perlu menambahkan method getAll() atau countAll() di User.php
            // Contoh jika ada User::countAll() static:
            // $total_pengguna_terdaftar_display = User::countAll($conn);

            // Untuk sementara, jika User::countAll() belum ada, kita bisa melakukan query langsung di sini (kurang ideal)
            // atau lebih baik tambahkan method ke User.php
            $result_user_count = mysqli_query($conn, "SELECT COUNT(id) as total_users FROM users");
            if ($result_user_count) {
                $row_user_count = mysqli_fetch_assoc($result_user_count);
                $total_pengguna_terdaftar_display = $row_user_count['total_users'] ?? 0;
            } else {
                $total_pengguna_terdaftar_display = 0;
                error_log("Error query hitung pengguna: " . mysqli_error($conn));
            }
        } catch (Throwable $e) {
            error_log("Error mengambil data User untuk dashboard: " . $e->getMessage());
            $total_pengguna_terdaftar_display = 'Error';
        }
    }
} else {
    if (!isset($conn) || !$conn) {
        error_log("Koneksi database tidak tersedia untuk data dashboard.");
    }
    if (!$models_loaded_successfully) {
        error_log("Satu atau lebih model gagal dimuat untuk data dashboard.");
    }
    // Biarkan nilai 'N/A' jika koneksi atau model gagal
}
?>

<!-- Konten Dashboard Admin Dimulai -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard Ringkasan</h1>
    <!-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-download fa-sm text-white-50"></i> Unduh Laporan
    </a> -->
</div>

<!-- Baris Kartu Statistik -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Artikel</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= e($total_artikel_display) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="card-footer text-decoration-none text-primary small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Booking Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= e($total_booking_pending_display) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/booking/kelola_booking.php" class="card-footer text-decoration-none text-success small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Feedback
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= e($total_feedback_baru_display) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/feedback/kelola_feedback.php" class="card-footer text-decoration-none text-info small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pengguna Terdaftar</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= e($total_pengguna_terdaftar_display) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/users/kelola_users.php" class="card-footer text-decoration-none text-warning small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Contoh Bagian Lain -->
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terkini (Contoh Statis)</h6>
                <!-- <a href="#" class="small">Lihat Semua</a> -->
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-newspaper text-info me-2"></i> Artikel baru "Pesona Curug Landung" telah ditambahkan.
                        </div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">1 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-check text-success me-2"></i> Booking #B00128 untuk "Pemandian Air Panas" telah dikonfirmasi.
                        </div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">3 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-comments text-warning me-2"></i> Feedback baru diterima dari pengguna Budi Santoso.
                        </div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">Kemarin</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-plus text-primary me-2"></i> Pengguna baru "anita_sari" telah berhasil terdaftar.
                        </div>
                        <span class="badge bg-light text-dark-emphasis rounded-pill small">2 hari lalu</span>
                    </li>
                </ul>
                <!-- <p class="text-center p-3 text-muted">Belum ada aktivitas terkini untuk ditampilkan.</p> -->
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Tautan Cepat</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle fa-fw me-2 text-success"></i>Tambah Artikel Baru</a>
                <a href="<?= $base_url ?>admin/wisata/tambah_wisata.php" class="list-group-item list-group-item-action"><i class="fas fa-map-pin fa-fw me-2 text-info"></i>Tambah Destinasi Wisata</a>
                <a href="<?= $base_url ?>admin/booking/kelola_booking.php" class="list-group-item list-group-item-action"><i class="fas fa-tasks fa-fw me-2 text-primary"></i>Kelola Semua Booking</a>
                <a href="<?= $base_url ?>admin/users/kelola_users.php" class="list-group-item list-group-item-action"><i class="fas fa-users-cog fa-fw me-2 text-warning"></i>Manajemen Pengguna</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-cogs fa-fw me-2 text-secondary"></i>Pengaturan Situs</a>
            </div>
        </div>
    </div>
</div>
<!-- Konten Dashboard Admin Selesai -->

<?php
/// 5. Sertakan footer admin
// Path yang BENAR dari admin/dashboard.php ke template/footer_admin.php adalah '../template/footer_admin.php'
if (!@include_once __DIR__ . '/../template/footer_admin.php') { // PERBAIKAN DI SINI
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/dashboard.php. Path yang dicoba: " . __DIR__ . '/../template/footer_admin.php');
}
?>