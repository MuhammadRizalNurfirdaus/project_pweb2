<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\dashboard.php

// 1. Selalu sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat config.php dari admin/dashboard.php");
    exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}

// 2. Sertakan Model-model yang diperlukan untuk data dashboard
$models_loaded_successfully = true; // Anggap sukses kecuali ada yang gagal
if (!@require_once __DIR__ . '/../models/Artikel.php') {
    error_log("ERROR: Gagal memuat models/Artikel.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
if (!@require_once __DIR__ . '/../models/PemesananTiket.php') {
    error_log("ERROR: Gagal memuat models/PemesananTiket.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
// === PERBAIKAN: TAMBAHKAN REQUIRE_ONCE UNTUK MODEL SEWA ALAT ===
if (!@require_once __DIR__ . '/../models/SewaAlat.php') {
    error_log("ERROR: Gagal memuat models/SewaAlat.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
if (!@require_once __DIR__ . '/../models/PemesananSewaAlat.php') {
    error_log("ERROR: Gagal memuat models/PemesananSewaAlat.php dari admin/dashboard.php");
    // Jika model ini belum sepenuhnya siap, Anda bisa mengomentari baris $models_loaded_successfully = false;
    // dan mengandalkan fallback query manual di bawah, tapi idealnya model ini ada.
    $models_loaded_successfully = false;
}
// ===============================================================
if (!@require_once __DIR__ . '/../models/Feedback.php') {
    error_log("ERROR: Gagal memuat models/Feedback.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}
if (!@require_once __DIR__ . '/../models/User.php') {
    error_log("ERROR: Gagal memuat models/User.php dari admin/dashboard.php");
    $models_loaded_successfully = false;
}

// 3. Sertakan header admin
// Pastikan path ini benar. Dari admin/dashboard.php ke template/ adalah ../template/
if (!@include_once __DIR__ . '/../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/dashboard.php. Path yang dicoba: " . __DIR__ . '/../template/header_admin.php');
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file.</div>";
    exit;
}

// 4. Pengambilan Data Dinamis untuk Kartu Statistik
$total_artikel_display = 'N/A';
$total_pemesanan_tiket_pending_display = 'N/A'; // Nama sudah benar
$total_pemesanan_sewa_pending_display = 'N/A'; // Variabel untuk statistik sewa
$total_item_alat_sewa_display = 'N/A'; // Variabel untuk statistik alat
$total_feedback_baru_display = 'N/A';
$total_pengguna_terdaftar_display = 'N/A';

if (isset($conn) && $conn) { // Kita akan cek class_exists untuk setiap model sebelum digunakan

    // Total Artikel
    if (class_exists('Artikel') && method_exists('Artikel', 'getAll')) {
        try {
            // Jika getAll() di model Anda tidak memerlukan $conn sebagai parameter karena menggunakan global $conn
            $artikel_list_dashboard = Artikel::getAll();
            $total_artikel_display = is_array($artikel_list_dashboard) ? count($artikel_list_dashboard) : 0;
        } catch (Throwable $e) {
            error_log("Error mengambil data Artikel untuk dashboard: " . $e->getMessage());
            $total_artikel_display = 'Error';
        }
    } else {
        error_log("Model Artikel atau method getAll tidak ditemukan.");
    }

    // Pemesanan Tiket Pending
    if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'countByStatus')) {
        try {
            $total_pemesanan_tiket_pending_display = PemesananTiket::countByStatus('pending');
        } catch (Throwable $e) {
            error_log("Error mengambil data Pemesanan Tiket Pending: " . $e->getMessage());
            $total_pemesanan_tiket_pending_display = 'Error';
        }
    } else {
        error_log("Model PemesananTiket atau method countByStatus tidak ditemukan. Mencoba getAll...");
        // Fallback jika countByStatus tidak ada, tapi getAll ada
        if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'getAll')) {
            $all_pemesanan_tiket = PemesananTiket::getAll();
            if (is_array($all_pemesanan_tiket)) {
                $pending_count = 0;
                foreach ($all_pemesanan_tiket as $pemesanan) {
                    if (isset($pemesanan['status']) && strtolower($pemesanan['status']) === 'pending') {
                        $pending_count++;
                    }
                }
                $total_pemesanan_tiket_pending_display = $pending_count;
            } else {
                $total_pemesanan_tiket_pending_display = 0;
            }
        } else {
            error_log("Model PemesananTiket atau method yang diperlukan (getAll/countByStatus) tidak ditemukan.");
        }
    }

    // Pemesanan Sewa Alat Pending (BARU)
    if (class_exists('PemesananSewaAlat') && method_exists('PemesananSewaAlat', 'countByStatus')) {
        try {
            $total_pemesanan_sewa_pending_display = PemesananSewaAlat::countByStatus('Dipesan'); // Sesuaikan status jika perlu
        } catch (Throwable $e) {
            error_log("Error mengambil data Pemesanan Sewa Pending: " . $e->getMessage());
            $total_pemesanan_sewa_pending_display = 'Error';
        }
    } else {
        error_log("Model PemesananSewaAlat atau method countByStatus tidak ditemukan. Menggunakan query manual fallback.");
        // Fallback query manual
        $query_sewa_pending = "SELECT COUNT(id) as total FROM pemesanan_sewa_alat WHERE status_item_sewa = 'Dipesan'";
        $result_sewa_pending = mysqli_query($conn, $query_sewa_pending);
        if ($result_sewa_pending) {
            $row_sewa_pending = mysqli_fetch_assoc($result_sewa_pending);
            $total_pemesanan_sewa_pending_display = $row_sewa_pending['total'] ?? 0;
        } else {
            error_log("Error query hitung pemesanan sewa pending: " . mysqli_error($conn));
            // $total_pemesanan_sewa_pending_display tetap N/A
        }
    }

    // Total Item Alat Sewa (BARU)
    if (class_exists('SewaAlat') && method_exists('SewaAlat', 'getAll')) {
        try {
            $all_alat_sewa = SewaAlat::getAll();
            $total_item_alat_sewa_display = is_array($all_alat_sewa) ? count($all_alat_sewa) : 0;
        } catch (Throwable $e) {
            error_log("Error mengambil data Total Item Alat Sewa: " . $e->getMessage());
            $total_item_alat_sewa_display = 'Error';
        }
    } else {
        error_log("Model SewaAlat atau method getAll tidak ditemukan.");
    }

    // Feedback Baru (Total Feedback)
    if (class_exists('Feedback') && method_exists('Feedback', 'getAll')) {
        try {
            $all_feedback = Feedback::getAll();
            $total_feedback_baru_display = is_array($all_feedback) ? count($all_feedback) : 0;
        } catch (Throwable $e) {
            error_log("Error mengambil data Feedback: " . $e->getMessage());
            $total_feedback_baru_display = 'Error';
        }
    } else {
        error_log("Model Feedback atau method getAll tidak ditemukan.");
    }

    // Pengguna Terdaftar
    if (class_exists('User') && method_exists('User', 'countAll')) { // Idealnya ada method countAll di Model User
        // try {
        //     $total_pengguna_terdaftar_display = User::countAll();
        // } catch (Throwable $e) { /* ... */ }
    } else { // Fallback jika tidak ada User::countAll()
        $result_user_count = mysqli_query($conn, "SELECT COUNT(id) as total_users FROM users");
        if ($result_user_count) {
            $row_user_count = mysqli_fetch_assoc($result_user_count);
            $total_pengguna_terdaftar_display = $row_user_count['total_users'] ?? 0;
        } else {
            error_log("Error query hitung pengguna: " . mysqli_error($conn));
            // $total_pengguna_terdaftar_display tetap N/A
        }
    }
} else {
    if (!isset($conn) || !$conn) {
        error_log("Koneksi database tidak tersedia untuk data dashboard.");
    }
    // Tidak perlu lagi cek $models_loaded_successfully di sini karena sudah ada fallback per model
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
</div>

<!-- Baris Kartu Statistik -->
<div class="row">
    <!-- Total Artikel -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card border-start-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Artikel</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_artikel_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-newspaper fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="card-footer text-decoration-none text-primary small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Pemesanan Tiket Pending -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card border-start-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pemesanan Tiket Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pemesanan_tiket_pending_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-ticket-alt fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/pemesanan_tiket/kelola_pemesanan.php" class="card-footer text-decoration-none text-success small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Pemesanan Sewa Pending (BARU) -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card border-start-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pemesanan Sewa Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pemesanan_sewa_pending_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dolly-flatbed fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/pemesanan_sewa/kelola_pemesanan_sewa.php" class="card-footer text-decoration-none text-info small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Feedback -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card border-start-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Feedback</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_feedback_baru_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/feedback/kelola_feedback.php" class="card-footer text-decoration-none text-secondary small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Pengguna Terdaftar -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card border-start-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pengguna Terdaftar</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_pengguna_terdaftar_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/users/kelola_users.php" class="card-footer text-decoration-none text-warning small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Item Alat Sewa (BARU) -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card border-start-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Item Alat Sewa</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= e($total_item_alat_sewa_display) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-tools fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= $base_url ?>admin/alat_sewa/kelola_alat.php" class="card-footer text-decoration-none text-danger small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

</div>


<!-- Baris Konten Lainnya (Aktivitas & Tautan Cepat) -->
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
                <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle fa-fw me-2 text-success"></i>Tambah Artikel Baru</a>
                <a href="<?= $base_url ?>admin/wisata/tambah_wisata.php" class="list-group-item list-group-item-action"><i class="fas fa-map-pin fa-fw me-2 text-info"></i>Tambah Destinasi Wisata</a>
                <a href="<?= $base_url ?>admin/alat_sewa/tambah_alat.php" class="list-group-item list-group-item-action"><i class="fas fa-tools fa-fw me-2 text-secondary"></i>Tambah Alat Sewa</a>
                <a href="<?= $base_url ?>admin/pemesanan_tiket/kelola_pemesanan.php" class="list-group-item list-group-item-action"><i class="fas fa-tasks fa-fw me-2 text-primary"></i>Kelola Pemesanan Tiket</a>
                <a href="<?= $base_url ?>admin/pemesanan_sewa/kelola_pemesanan_sewa.php" class="list-group-item list-group-item-action"><i class="fas fa-dolly-box fa-fw me-2 text-primary"></i>Kelola Pemesanan Sewa</a>
                <a href="<?= $base_url ?>admin/users/kelola_users.php" class="list-group-item list-group-item-action"><i class="fas fa-users-cog fa-fw me-2 text-warning"></i>Manajemen Pengguna</a>
            </div>
        </div>
    </div>
</div>
<!-- Konten Dashboard Admin Selesai -->

<?php
// 5. Sertakan footer admin
if (!@include_once __DIR__ . '/../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/dashboard.php.");
}
?>