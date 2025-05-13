<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\dashboard.php

// LANGKAH 1: Muat Konfigurasi Utama (HARUS paling atas)
if (!require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di admin/dashboard.php: Gagal memuat config/config.php.");
    $errorMessage = (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) ? "Gagal memuat config.php." : "Kesalahan konfigurasi server.";
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan. " . $errorMessage);
}

// LANGKAH 2: Pastikan Hanya Admin yang Bisa Akses Halaman Ini
try {
    require_admin();
} catch (Exception $e) {
    error_log("EXCEPTION saat require_admin() di admin/dashboard.php: " . $e->getMessage());
    set_flash_message('danger', 'Terjadi kesalahan otentikasi internal.');
    redirect(AUTH_URL . '/login.php'); // Menggunakan AUTH_URL
    exit;
}

// LANGKAH 3: Set Judul Halaman
$pageTitle = "Dashboard Admin - " . e(defined('NAMA_SITUS') ? NAMA_SITUS : "Cilengkrang Web Wisata");

// LANGKAH 4: Sertakan SEMUA Model yang diperlukan untuk statistik
// Diasumsikan MODELS_PATH didefinisikan di config.php dan model memiliki metode setDbConnection jika diperlukan
if (!defined('MODELS_PATH')) {
    error_log("FATAL ERROR di admin/dashboard.php: Konstanta MODELS_PATH tidak terdefinisi.");
    set_flash_message('danger', 'Kesalahan konfigurasi sistem: Path model tidak ditemukan.');
    if (!headers_sent()) redirect(BASE_URL);
    http_response_code(500);
    exit("Kesalahan konfigurasi sistem internal.");
}

$models_to_load = ['Artikel', 'PemesananTiket', 'SewaAlat', 'PemesananSewaAlat', 'Feedback', 'User', 'Pembayaran'];
foreach ($models_to_load as $model_name) {
    $model_path = MODELS_PATH . '/' . $model_name . '.php';
    if (file_exists($model_path)) {
        require_once $model_path;
        // Jika model Anda memerlukan setup koneksi DB statis:
        // if (class_exists($model_name) && method_exists($model_name, 'setDbConnection') && isset($conn)) {
        //     $model_name::setDbConnection($conn);
        // }
    } else {
        error_log("PERINGATAN di admin/dashboard.php: File model {$model_name}.php tidak ditemukan di {$model_path}. Statistik terkait mungkin tidak akurat.");
    }
}

// LANGKAH 5: Sertakan Header Admin
$header_admin_path = ROOT_PATH . '/template/header_admin.php';
if (!file_exists($header_admin_path)) {
    error_log("FATAL ERROR di admin/dashboard.php: File template header_admin.php tidak ditemukan di " . htmlspecialchars($header_admin_path));
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<p style='color:red;font-family:sans-serif;padding:20px;'>Error Kritis: Gagal memuat komponen header halaman admin. Path yang dicari: " . htmlspecialchars($header_admin_path) . ".</p>";
    echo "</body></html>";
    exit;
}
require_once $header_admin_path; // header_admin.php akan memanggil display_flash_message()


// --- Pengambilan Data Dinamis untuk Kartu Statistik ---
$stats_data = [
    'total_artikel' => '-',
    'total_pemesanan_tiket_pending' => '-',
    'total_pemesanan_sewa_pending' => '-',
    'total_item_alat_sewa' => '-',
    'total_feedback' => '-',
    'total_pengguna_terdaftar' => '-',
    'total_pendapatan' => 'Rp 0'
];
$error_ambil_statistik = false;

if (isset($conn) && $conn instanceof mysqli && $conn->connect_error === null) {

    // Fungsi helper lokal untuk dashboard ini
    function get_stat_from_model_dashboard($model_class_name, $method_name, $param = null)
    {
        // Tidak perlu 'global $conn;' jika model sudah di-set koneksinya atau mengambilnya sendiri
        $na_value = 'N/A';
        $db_error_value = 'Error DB';
        $data_error_value = 'Data Error';

        if (class_exists($model_class_name) && method_exists($model_class_name, $method_name)) {
            try {
                // Asumsi metode statistik adalah statis.
                // Jika model Anda tidak memerlukan $conn sebagai argumen eksplisit, hapus $conn dari pemanggilan.
                // Contoh: $result = ($param === null) ? $model_class_name::$method_name() : $model_class_name::$method_name($param);

                $reflectionMethod = new ReflectionMethod($model_class_name, $method_name);
                $args = [];
                if ($param !== null) { // Hanya tambahkan param jika ada
                    $args[] = $param;
                }
                $result = $reflectionMethod->invokeArgs(null, $args); // Memanggil metode statis dengan argumen

                if (is_numeric($result)) {
                    return (int)$result;
                } elseif ($result === null && strtolower($method_name) === 'gettotalrevenue') {
                    return 0;
                }
                error_log("Dashboard Statistik Info: {$model_class_name}::{$method_name} mengembalikan nilai non-numerik/tidak terduga: " . print_r($result, true));
                return $data_error_value;
            } catch (Throwable $e) {
                error_log("Dashboard Statistik Exception {$model_class_name}::{$method_name}: " . $e->getMessage() . " di " . $e->getFile() . ":" . $e->getLine());
                return $db_error_value;
            }
        } else {
            error_log("Dashboard Peringatan: Model/Metode {$model_class_name}::{$method_name} tidak tersedia.");
            return $na_value;
        }
    }

    $stats_data['total_artikel'] = get_stat_from_model_dashboard('Artikel', 'countAll');
    $stats_data['total_pemesanan_tiket_pending'] = get_stat_from_model_dashboard('PemesananTiket', 'countByStatus', 'pending');
    $stats_data['total_pemesanan_sewa_pending'] = get_stat_from_model_dashboard('PemesananSewaAlat', 'countByStatus', 'Dipesan');
    $stats_data['total_item_alat_sewa'] = get_stat_from_model_dashboard('SewaAlat', 'countAll');
    $stats_data['total_feedback'] = get_stat_from_model_dashboard('Feedback', 'countAll');
    $stats_data['total_pengguna_terdaftar'] = get_stat_from_model_dashboard('User', 'countAll');

    $pendapatan_raw = get_stat_from_model_dashboard('Pembayaran', 'getTotalRevenue', ['success', 'paid', 'confirmed']);
    if (is_numeric($pendapatan_raw)) {
        $stats_data['total_pendapatan'] = formatRupiah($pendapatan_raw);
    } elseif (in_array($pendapatan_raw, ['Error DB', 'N/A', 'Data Error'], true)) {
        $stats_data['total_pendapatan'] = $pendapatan_raw;
    } else {
        $stats_data['total_pendapatan'] = 'Rp 0'; // Default
    }

    foreach ($stats_data as $key => $value) {
        if (in_array($value, ['Error DB', 'Data Error', 'N/A'], true)) {
            $error_ambil_statistik = true;
            $stats_data[$key] = ($value === 'N/A') ? 'N/A (Model/Method)' : $value; // Tambahkan detail N/A
            // Tidak perlu break agar semua error N/A bisa ditandai
        }
    }
} else {
    error_log("KRITIKAL di admin/dashboard.php: Koneksi database tidak valid/tersedia untuk statistik.");
    $error_ambil_statistik = true;
    // Set pesan error untuk semua statistik
    foreach (array_keys($stats_data) as $key) {
        $stats_data[$key] = ($key === 'total_pendapatan') ? 'DB Error (Rp)' : 'DB Error';
    }
    // Flash message akan ditangani oleh header_admin.php jika config gagal atau koneksi gagal di awal
}
?>

<!-- Konten HTML Dashboard -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</li>
    </ol>
</nav>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Dashboard Ringkasan</h1>
</div>

<?php
// Tampilkan alert jika ada masalah spesifik dalam pengambilan data statistik
// Dan jika belum ada flash message error global terkait koneksi atau konfigurasi.
$display_stat_alert = $error_ambil_statistik;
if (isset($_SESSION['flash_message'])) { // Periksa apakah flash message ada (jangan di-unset di sini)
    $flash = $_SESSION['flash_message'];
    if (
        $flash['type'] === 'danger' &&
        (strpos(strtolower($flash['message']), 'koneksi database') !== false ||
            strpos(strtolower($flash['message']), 'konfigurasi sistem') !== false ||
            strpos(strtolower($flash['message']), 'gagal memuat') !== false)
    ) {
        $display_stat_alert = false; // Jangan tampilkan alert ini jika sudah ada flash error global
    }
}

if ($display_stat_alert):
?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Beberapa data statistik mungkin tidak dapat ditampilkan dengan benar karena masalah pengambilan data atau komponen yang hilang. Silakan periksa log server.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Kartu Total Artikel -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Artikel</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_artikel']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-newspaper fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/artikel/kelola_artikel.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Pemesanan Tiket Pending -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Tiket Pending</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_pemesanan_tiket_pending']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-ticket-alt fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php?status=pending') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Pemesanan Sewa Pending -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Sewa Pending</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_pemesanan_sewa_pending']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dolly-flatbed fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/pemesanan_sewa/kelola_pemesanan_sewa.php?status=Dipesan') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Total Pendapatan -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Pendapatan (Success)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_pendapatan']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/pembayaran/kelola_pembayaran.php?status_pembayaran=success') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Total Feedback -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Total Feedback</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_feedback']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/feedback/kelola_feedback.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Pengguna Terdaftar -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pengguna Terdaftar</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_pengguna_terdaftar']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/users/kelola_users.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Total Item Alat Sewa -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Item Alat Sewa</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_item_alat_sewa']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-tools fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . '/alat_sewa/kelola_alat.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Kartu Kosong untuk Alignment (jika jumlah kartu ganjil atau kurang dari kelipatan 4) -->
    <?php
    $current_card_count_actual = 7; // Jumlah kartu statistik yang aktif ditampilkan
    $cards_per_row = 4;
    $remainder = $current_card_count_actual % $cards_per_row;
    if ($remainder !== 0):
        $empty_cards_needed = $cards_per_row - $remainder;
        for ($i = 0; $i < $empty_cards_needed; $i++):
    ?>
            <div class="col-xl-3 col-md-6 mb-4 invisible"></div>
    <?php
        endfor;
    endif;
    ?>
</div>

<!-- Baris untuk Aktivitas Terkini dan Tautan Cepat -->
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light border-bottom">
                <h6 class="m-0 fw-bold text-primary">Aktivitas Terkini (Contoh Statis)</h6>
            </div>
            <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-newspaper text-info me-2"></i> Artikel baru "Pesona Curug Landung" telah ditambahkan.</div>
                        <span class="badge bg-light text-dark border rounded-pill small">1 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-ticket-alt text-success me-2"></i> Pemesanan Tiket #PT00128 untuk "Pemandian Air Panas" telah dikonfirmasi.</div>
                        <span class="badge bg-light text-dark border rounded-pill small">3 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-dolly-flatbed text-primary me-2"></i> Pemesanan Sewa Alat #PSA0023 untuk "Tenda" telah dikonfirmasi.</div>
                        <span class="badge bg-light text-dark border rounded-pill small">5 jam lalu</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-comments text-warning me-2"></i> Feedback baru diterima dari pengguna Budi Santoso.</div>
                        <span class="badge bg-light text-dark border rounded-pill small">Kemarin</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-user-plus text-primary me-2"></i> Pengguna baru "anita_sari" telah berhasil terdaftar.</div>
                        <span class="badge bg-light text-dark border rounded-pill small">2 hari lalu</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light border-bottom">
                <h6 class="m-0 fw-bold text-primary">Tautan Cepat</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= e(ADMIN_URL . '/artikel/tambah_artikel.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle fa-fw me-2 text-success"></i>Tambah Artikel Baru</a>
                <a href="<?= e(ADMIN_URL . '/wisata/tambah_wisata.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-map-pin fa-fw me-2 text-info"></i>Tambah Destinasi Wisata</a>
                <a href="<?= e(ADMIN_URL . '/alat_sewa/tambah_alat.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-tools fa-fw me-2 text-secondary"></i>Tambah Alat Sewa</a>
                <a href="<?= e(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-tasks fa-fw me-2 text-primary"></i>Kelola Pemesanan Tiket</a>
                <a href="<?= e(ADMIN_URL . '/pemesanan_sewa/kelola_pemesanan_sewa.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-boxes-stacked fa-fw me-2 text-primary"></i>Kelola Pemesanan Sewa</a>
                <a href="<?= e(ADMIN_URL . '/users/kelola_users.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-users-cog fa-fw me-2 text-warning"></i>Manajemen Pengguna</a>
                <a href="<?= e(ADMIN_URL . '/pembayaran/kelola_pembayaran.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-credit-card fa-fw me-2 text-danger"></i>Kelola Pembayaran</a>
            </div>
        </div>
    </div>
</div>

<?php
$footer_admin_path = ROOT_PATH . '/template/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("ERROR di admin/dashboard.php: Gagal memuat template footer dari " . htmlspecialchars($footer_admin_path));
    // Konten utama sudah ditampilkan, jadi hanya log error
} else {
    require_once $footer_admin_path;
}
?>