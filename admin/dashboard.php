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
    if (!function_exists('require_admin')) {
        throw new Exception("Fungsi require_admin() tidak ditemukan.");
    }
    require_admin();
} catch (Exception $e) {
    error_log("EXCEPTION saat require_admin() di admin/dashboard.php: " . $e->getMessage());
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak. Sesi tidak valid atau Anda belum login sebagai admin.');
    if (function_exists('redirect') && defined('AUTH_URL')) redirect(AUTH_URL . 'login.php');
    else exit('Akses ditolak.');
}

// LANGKAH 3: Set Judul Halaman
$pageTitle = "Dashboard Admin - " . e(defined('NAMA_SITUS') ? NAMA_SITUS : "Cilengkrang Web Wisata");

// LANGKAH 4: Sertakan Header Admin (VIEWS_PATH sudah dari config.php)
$header_admin_path = defined('ROOT_PATH') ? ROOT_PATH . '/template/header_admin.php' : dirname(__DIR__) . '/template/header_admin.php';
if (!file_exists($header_admin_path)) {
    error_log("FATAL ERROR di admin/dashboard.php: File template header_admin.php tidak ditemukan di " . htmlspecialchars($header_admin_path));
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<p style='color:red;font-family:sans-serif;padding:20px;'>Error Kritis: Gagal memuat komponen header halaman admin. Path yang dicari: " . htmlspecialchars($header_admin_path) . ".</p>";
    echo "</body></html>";
    exit;
}
require_once $header_admin_path;


// --- Pengambilan Data Dinamis untuk Kartu Statistik ---
$stats_data = [
    'total_artikel' => 'N/A',
    'total_pemesanan_tiket_pending' => 'N/A',
    'total_pemesanan_sewa_pending' => 'N/A',
    'total_item_alat_sewa' => 'N/A',
    'total_feedback' => 'N/A',
    'total_pengguna_terdaftar' => 'N/A',
    'total_pendapatan' => 'N/A'
];
$error_ambil_statistik = false;

if (isset($conn) && $conn instanceof mysqli && $conn->connect_error === null) {
    if (!function_exists('get_stat_from_model_dashboard')) {
        function get_stat_from_model_dashboard($model_class_name, $method_name, $param = null)
        {
            $na_value = 'N/A (Data)';
            $db_error_value = 'Error DB';
            $model_missing_value = 'N/A (Komponen)';
            if (class_exists($model_class_name) && method_exists($model_class_name, $method_name)) {
                try {
                    $reflectionMethod = new ReflectionMethod($model_class_name, $method_name);
                    $args = ($param !== null) ? [$param] : [];
                    $result = $reflectionMethod->invokeArgs(null, $args);
                    if (is_numeric($result)) return (strtolower($method_name) === 'gettotalrevenue') ? (float)$result : (int)$result;
                    if ($result === null && strtolower($method_name) === 'gettotalrevenue') return 0.0;
                    error_log("Dashboard Statistik: {$model_class_name}::{$method_name} return non-numerik/null: " . print_r($result, true));
                    return $na_value;
                } catch (Throwable $e) {
                    error_log("Dashboard Statistik Exception {$model_class_name}::{$method_name}: " . $e->getMessage());
                    return $db_error_value;
                }
            } else {
                error_log("Dashboard Peringatan: Model/Metode {$model_class_name}::{$method_name} tidak tersedia.");
                return $model_missing_value;
            }
        }
    }

    $stats_data['total_artikel'] = get_stat_from_model_dashboard('Artikel', 'countAll');
    $stats_data['total_pemesanan_tiket_pending'] = get_stat_from_model_dashboard('PemesananTiket', 'countByStatus', 'pending');
    $stats_data['total_pemesanan_sewa_pending'] = get_stat_from_model_dashboard('PemesananSewaAlat', 'countByStatus', 'Dipesan');
    $stats_data['total_item_alat_sewa'] = get_stat_from_model_dashboard('SewaAlat', 'countAll');
    $stats_data['total_feedback'] = get_stat_from_model_dashboard('Feedback', 'countAll');
    $stats_data['total_pengguna_terdaftar'] = get_stat_from_model_dashboard('User', 'countAll');
    // Pastikan Pembayaran::SUCCESSFUL_PAYMENT_STATUSES ada di Model Pembayaran
    $pendapatan_raw = get_stat_from_model_dashboard('Pembayaran', 'getTotalRevenue', (defined('Pembayaran::SUCCESSFUL_PAYMENT_STATUSES') ? Pembayaran::SUCCESSFUL_PAYMENT_STATUSES : ['success', 'paid', 'confirmed']));
    $stats_data['total_pendapatan'] = is_numeric($pendapatan_raw) ? formatRupiah($pendapatan_raw) : ($pendapatan_raw ?: 'Rp 0');

    foreach ($stats_data as $key => $value) {
        if (strpos((string)$value, 'N/A') !== false || strpos((string)$value, 'Error') !== false) {
            $error_ambil_statistik = true;
        }
    }
} else {
    error_log("KRITIKAL di admin/dashboard.php: Koneksi database tidak valid/tersedia untuk statistik.");
    $error_ambil_statistik = true;
    foreach (array_keys($stats_data) as $key) {
        $stats_data[$key] = ($key === 'total_pendapatan') ? 'DB Error (Rp)' : 'DB Error';
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</li>
    </ol>
</nav>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Dashboard Ringkasan</h1>
</div>

<?php
$global_flash_message = $_SESSION['flash_message'] ?? null;
if ($error_ambil_statistik && (!$global_flash_message || ($global_flash_message['type'] ?? '') !== 'danger')) :
?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Beberapa data statistik mungkin tidak dapat ditampilkan dengan benar. Silakan periksa log server untuk detail.
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
            <a href="<?= e(ADMIN_URL . 'artikel/kelola_artikel.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
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
            <a href="<?= e(ADMIN_URL . 'pemesanan_tiket/kelola_pemesanan.php?status=pending') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
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
                    <div class="col-auto"><i class="fas fa-box-open fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . 'pemesanan_sewa/kelola_pemesanan_sewa.php?status=Dipesan') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
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
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Pendapatan (Lunas)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= e($stats_data['total_pendapatan']) ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                </div>
            </div>
            <a href="<?= e(ADMIN_URL . 'pembayaran/kelola_pembayaran.php?status_pembayaran=success') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
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
            <a href="<?= e(ADMIN_URL . 'feedback/kelola_feedback.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
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
            <a href="<?= e(ADMIN_URL . 'users/kelola_users.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
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
            <a href="<?= e(ADMIN_URL . 'alat_sewa/kelola_alat.php') ?>" class="card-footer text-decoration-none small d-flex justify-content-between align-items-center">
                <span>Lihat Detail</span> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <?php
    $current_card_count_actual = 7;
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
                <h6 class="m-0 fw-bold text-primary">Aktivitas Terkini</h6>
                <a href="#" id="refreshAktivitas" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Refresh Aktivitas"><i class="fas fa-sync-alt"></i></a>
            </div>
            <div class="card-body" style="max-height: 350px; overflow-y: auto;" id="aktivitasTerkiniContainer">
                <p class="text-muted text-center py-3">Memuat aktivitas...</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light border-bottom">
                <h6 class="m-0 fw-bold text-primary">Tautan Cepat</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= e(ADMIN_URL . 'artikel/tambah_artikel.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle fa-fw me-2 text-success"></i>Tambah Artikel Baru</a>
                <a href="<?= e(ADMIN_URL . 'wisata/tambah_wisata.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-map-pin fa-fw me-2 text-info"></i>Tambah Destinasi Wisata</a>
                <a href="<?= e(ADMIN_URL . 'alat_sewa/tambah_alat.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-tools fa-fw me-2 text-secondary"></i>Tambah Alat Sewa</a>
                <a href="<?= e(ADMIN_URL . 'pemesanan_tiket/kelola_pemesanan.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-tasks fa-fw me-2 text-primary"></i>Kelola Pemesanan Tiket</a>
                <a href="<?= e(ADMIN_URL . 'pemesanan_sewa/kelola_pemesanan_sewa.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-box-open fa-fw me-2 text-primary"></i>Kelola Pemesanan Sewa</a>
                <a href="<?= e(ADMIN_URL . 'users/kelola_users.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-users-cog fa-fw me-2 text-warning"></i>Manajemen Pengguna</a>
                <a href="<?= e(ADMIN_URL . 'pembayaran/kelola_pembayaran.php') ?>" class="list-group-item list-group-item-action"><i class="fas fa-credit-card fa-fw me-2 text-danger"></i>Kelola Pembayaran</a>
            </div>
        </div>
    </div>
</div>

<?php
$footer_admin_path = ROOT_PATH . '/template/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("ERROR di admin/dashboard.php: Gagal memuat template footer dari " . htmlspecialchars($footer_admin_path));
} else {
    require_once $footer_admin_path;
}
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const aktivitasContainer = document.getElementById('aktivitasTerkiniContainer');
        const refreshButton = document.getElementById('refreshAktivitas');

        function escapeHtml(unsafe) {
            if (unsafe === null || typeof unsafe === 'undefined') return '';
            return unsafe.toString()
                .replace(/&/g, "&")
                .replace(/</g, "<")
                .replace(/>/g, ">")
                .replace(/"/g, "")
                .replace(/'/g, "'"); // atau '
        }

        function fetchAktivitas() {
            if (!aktivitasContainer) {
                console.error("Elemen aktivitasTerkiniContainer tidak ditemukan.");
                return;
            }
            aktivitasContainer.innerHTML = '<p class="text-muted text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Memuat aktivitas...</p>';

            const ajaxUrl = '<?= e(defined("ADMIN_URL") ? ADMIN_URL : "") ?>aktivitas.php';
            console.log("Fetching aktivitas from:", ajaxUrl);

            fetch(ajaxUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response not ok (' + response.status + ')');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Data aktivitas diterima:", data);
                    if (data && Array.isArray(data) && data.length > 0) {
                        let html = '<ul class="list-group list-group-flush">';
                        data.forEach(item => {
                            let itemLink = (item.link && item.link !== '#') ? escapeHtml(item.link) : '#';
                            let itemClass = (item.link && item.link !== '#') ? 'list-group-item-action' : '';

                            let ikon = item.ikon || 'fas fa-info-circle text-muted';
                            let teks = item.teks || 'Aktivitas tidak diketahui';
                            let waktu_detail = item.waktu_detail || '';
                            let waktu_singkat = item.waktu_singkat || 'Baru saja';

                            // Membuat elemen <a> hanya jika link valid, jika tidak <li>
                            if (item.link && item.link !== '#') {
                                html += `<a href="${itemLink}" class="list-group-item ${itemClass} d-flex justify-content-between align-items-center flex-wrap" style="text-decoration: none; color: inherit;">
                                            <div><i class="${escapeHtml(ikon)} me-2"></i> ${escapeHtml(teks)}</div>
                                            <span class="badge bg-light text-dark border rounded-pill small" title="${escapeHtml(waktu_detail)}">${escapeHtml(waktu_singkat)}</span>
                                         </a>`;
                            } else {
                                html += `<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                            <div><i class="${escapeHtml(ikon)} me-2"></i> ${escapeHtml(teks)}</div>
                                            <span class="badge bg-light text-dark border rounded-pill small" title="${escapeHtml(waktu_detail)}">${escapeHtml(waktu_singkat)}</span>
                                         </li>`;
                            }
                        });
                        html += '</ul>';
                        aktivitasContainer.innerHTML = html;
                    } else if (data && Array.isArray(data) && data.length === 0) {
                        aktivitasContainer.innerHTML = '<p class="text-muted text-center py-3">Belum ada aktivitas terkini.</p>';
                    } else {
                        aktivitasContainer.innerHTML = '<p class="text-danger text-center py-3">Gagal memuat format data aktivitas yang benar.</p>';
                        console.error("Format data aktivitas tidak sesuai:", data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching aktivitas:', error);
                    aktivitasContainer.innerHTML = `<p class="text-danger text-center py-3">Gagal memuat aktivitas. ${escapeHtml(error.message)}. Coba refresh.</p>`;
                });
        }

        fetchAktivitas();

        if (refreshButton) {
            refreshButton.addEventListener('click', function(e) {
                e.preventDefault();
                fetchAktivitas();
            });
        }
        // setInterval(fetchAktivitas, 60000); 
    });
</script>