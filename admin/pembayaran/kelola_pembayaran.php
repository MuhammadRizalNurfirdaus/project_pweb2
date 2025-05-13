<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\kelola_pembayaran.php

// LANGKAH 1: Sertakan config.php (memuat $conn, helpers, auth_helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    $config_path = realpath(__DIR__ . '/../../config/config.php');
    $error_msg_config = "FATAL ERROR: Tidak dapat memuat file konfigurasi utama (config.php). Path: " . ($config_path ?: "Tidak valid/ada") . ". Periksa file dan log server.";
    error_log($error_msg_config);
    echo "<div style='font-family:Arial,sans-serif;border:2px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Tidak dapat memuat konfigurasi. Aplikasi tidak dapat berjalan.<br><small>Detail: " . htmlspecialchars($error_msg_config) . "</small></div>";
    exit;
}

// LANGKAH 2: Panggil fungsi otentikasi admin
// Pastikan fungsi ini ada di auth_helpers.php (dimuat oleh config.php)
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di kelola_pembayaran.php: Fungsi require_admin() tidak ditemukan.");
    // Fallback jika fungsi tidak ada
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan otentikasi sistem.');
        redirect('auth/login.php');
    } else {
        die("Error: Fungsi otentikasi tidak tersedia.");
    }
}

// LANGKAH 3: Sertakan Controller Pembayaran
// (Controller akan memanggil Model Pembayaran secara statis)
if (!require_once __DIR__ . '/../../controllers/PembayaranController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat PembayaranController.php dari kelola_pembayaran.php");
    set_flash_message('danger', 'Kesalahan sistem: Komponen pembayaran tidak dapat dimuat.');
    redirect('admin/dashboard.php');
}
// PemesananTiketController mungkin diperlukan jika aksi di sini akan mengupdate status tiket juga
// Ini sudah di-require oleh PembayaranController jika metode di sana memanggilnya.
// Jadi, tidak perlu di-require lagi di sini kecuali jika ada pemanggilan langsung.


// LANGKAH 4: Set judul halaman dan sertakan header admin
$pageTitle = "Kelola Pembayaran";
// Hapus '@' untuk melihat error include dengan jelas saat development
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    $header_path = realpath(__DIR__ . '/../../template/header_admin.php');
    $error_msg_header = "FATAL ERROR HALAMAN: Tidak dapat memuat file header admin (template/header_admin.php). Path: " . ($header_path ?: "Tidak valid/ada") . ". Periksa file dan log server.";
    error_log($error_msg_header);
    echo "<div style='font-family:Arial,sans-serif;border:2px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Error Kritis Tampilan</strong><br>Gagal memuat template header.<br><small>Detail: " . htmlspecialchars($error_msg_header) . "</small></div>";
    exit;
}
// KONTEN UTAMA HALAMAN DIMULAI DI SINI
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-credit-card"></i> Kelola Pembayaran</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Pembayaran
        <small class="text-muted fs-6">
            <?php
            $filter_status_pembayaran_url_for_title = isset($_GET['status_pembayaran']) ? trim(strtolower($_GET['status_pembayaran'])) : null;
            if ($filter_status_pembayaran_url_for_title) {
                echo "(Filter Status: " . e(ucfirst(str_replace('_', ' ', $filter_status_pembayaran_url_for_title))) . ")";
            }
            ?>
        </small>
    </h1>
</div>

<?php
// Pastikan fungsi display_flash_message() ada dari flash_message.php (dimuat oleh config.php)
if (function_exists('display_flash_message')) {
    display_flash_message();
} else {
    error_log("Peringatan di kelola_pembayaran.php: Fungsi display_flash_message() tidak ditemukan.");
}
?>

<?php
// LANGKAH 5: Pengambilan dan Pemfilteran Data menggunakan Controller (yang memanggil Model statis)
$semuaPembayaran = [];
$filter_status_pembayaran_url = isset($_GET['status_pembayaran']) ? trim(strtolower($_GET['status_pembayaran'])) : null;
$pesan_error_data_pembayaran = null;

// Pastikan koneksi $conn ada dan Controller serta metodenya ada
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    if (class_exists('PembayaranController') && method_exists('PembayaranController', 'getAllPembayaranForAdmin')) {
        try {
            $data_pembayaran_awal = PembayaranController::getAllPembayaranForAdmin(); // Pemanggilan metode statis Controller

            if ($filter_status_pembayaran_url && is_array($data_pembayaran_awal) && !empty($data_pembayaran_awal)) {
                $semuaPembayaran = array_filter($data_pembayaran_awal, function ($p) use ($filter_status_pembayaran_url) {
                    // Pastikan menggunakan nama kolom status yang benar dari hasil query Model
                    return isset($p['status_pembayaran']) && strtolower($p['status_pembayaran']) === $filter_status_pembayaran_url;
                });
            } else {
                $semuaPembayaran = $data_pembayaran_awal;
            }
        } catch (Throwable $e) {
            error_log("Error ambil data pembayaran di kelola_pembayaran.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $pesan_error_data_pembayaran = "Gagal memuat data pembayaran.";
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', $pesan_error_data_pembayaran);
            }
        }
    } else {
        $pesan_error_data_pembayaran = "Komponen sistem pembayaran (Controller/Metode) tidak ditemukan.";
        error_log($pesan_error_data_pembayaran);
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', $pesan_error_data_pembayaran);
        }
    }
} else {
    $pesan_error_data_pembayaran = 'Koneksi database tidak tersedia.';
    error_log("Koneksi database tidak tersedia di kelola_pembayaran.php.");
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', $pesan_error_data_pembayaran);
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Transaksi Pembayaran</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle" id="dataTablePembayaran">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width: 3%;">No.</th>
                        <th class="text-center" style="width: 5%;">ID Bayar</th>
                        <th style="width: 15%;">Kode Pemesanan Tiket</th>
                        <th style="width: 12%;">Metode</th>
                        <th class="text-end" style="width: 10%;">Jumlah</th>
                        <th class="text-center" style="width: 10%;">Status</th>
                        <th style="width: 15%;">Waktu Konfirmasi Bayar</th>
                        <th class="text-center" style="width: 8%;">Bukti</th>
                        <th style="width: 12%;">Tgl. Dibuat</th>
                        <th class="text-center" style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pesan_error_data_pembayaran) && !empty($semuaPembayaran) && is_array($semuaPembayaran)): ?>
                        <?php $nomor_urut = 1; ?>
                        <?php foreach ($semuaPembayaran as $pembayaran): ?>
                            <?php $status_pembayaran_display = strtolower($pembayaran['status_pembayaran'] ?? 'unknown'); ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut++ ?></td>
                                <td class="text-center"><strong><?= e($pembayaran['id']); ?></strong></td>
                                <td>
                                    <?php if (!empty($pembayaran['kode_pemesanan']) && isset($pembayaran['pemesanan_tiket_id'])): ?>
                                        <a href="<?= e(ADMIN_URL); ?>/pemesanan_tiket/detail_pemesanan.php?id=<?= e($pembayaran['pemesanan_tiket_id']); ?>" title="Lihat Detail Pemesanan Tiket">
                                            <?= e($pembayaran['kode_pemesanan']); ?>
                                        </a>
                                    <?php elseif (isset($pembayaran['pemesanan_tiket_id'])): ?>
                                        (Tiket ID: <?= e($pembayaran['pemesanan_tiket_id']); ?>)
                                    <?php else: echo '-';
                                    endif; ?>
                                </td>
                                <td><?= e($pembayaran['metode_pembayaran'] ?? '-'); ?></td>
                                <td class="text-end fw-bold"><?= e(formatRupiah($pembayaran['jumlah_dibayar'])); ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-<?= function_exists('getStatusBadgeClass') ? getStatusBadgeClass($status_pembayaran_display) : 'secondary'; ?>">
                                        <?= e(ucfirst(str_replace('_', ' ', $status_pembayaran_display))); ?>
                                    </span>
                                </td>
                                <td><?= !empty($pembayaran['waktu_pembayaran']) ? e(formatTanggalIndonesia($pembayaran['waktu_pembayaran'], true)) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                                        <?php $bukti_url = BASE_URL . 'public/uploads/bukti_pembayaran/' . rawurlencode(e($pembayaran['bukti_pembayaran'])); ?>
                                        <a href="<?= $bukti_url; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat Bukti">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    <?php else: echo '-';
                                    endif; ?>
                                </td>
                                <td><?= e(formatTanggalIndonesia($pembayaran['created_at'], true)); ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="<?= e(ADMIN_URL); ?>/pemesanan_tiket/detail_pemesanan.php?id=<?= e($pembayaran['pemesanan_tiket_id']); ?>" class="btn btn-info btn-sm" title="Lihat Detail Pemesanan">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah Status Pembayaran">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php $allowed_payment_statuses = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation', 'paid', 'confirmed']; ?>
                                            <?php foreach ($allowed_payment_statuses as $status_option): ?>
                                                <?php if (strtolower($status_option) != $status_pembayaran_display): ?>
                                                    <li>
                                                        <form action="<?= e(ADMIN_URL) ?>/pembayaran/proses_update_status_pembayaran.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_pembayaran" value="<?= e($pembayaran['id'] ?? '') ?>">
                                                            <input type="hidden" name="new_status" value="<?= e($status_option) ?>">
                                                            <button type="submit" name="update_status_pembayaran_submit" class="dropdown-item"
                                                                onclick="return confirm('Anda yakin ingin mengubah status pembayaran ID <?= e($pembayaran['id']) ?> menjadi \'<?= e(ucfirst(str_replace('_', ' ', $status_option))) ?>\'?')">
                                                                Tandai <?= e(ucfirst(str_replace('_', ' ', $status_option))) ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="mb-0 lead">
                                    <?php if ($pesan_error_data_pembayaran): echo e($pesan_error_data_pembayaran);
                                    elseif ($filter_status_pembayaran_url && empty($semuaPembayaran)): ?>
                                        Tidak ada data pembayaran dengan status "<?= e(ucfirst(str_replace('_', ' ', $filter_status_pembayaran_url))) ?>". <a href="<?= e(ADMIN_URL) ?>/pembayaran/kelola_pembayaran.php">Lihat semua</a>.
                                    <?php else: echo 'Belum ada data pembayaran.';
                                    endif; ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Fungsi getStatusBadgeClass() sudah ada di helpers.php (dimuat oleh config.php)
// Hapus '@' dari include_once footer untuk debugging
if (!include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari kelola_pembayaran.php.");
}
?>