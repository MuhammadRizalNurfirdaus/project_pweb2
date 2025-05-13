<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\kelola_pemesanan_sewa.php

// LANGKAH 1: Pastikan config.php dimuat PERTAMA dan TANPA ERROR
// Pastikan config.php includes helpers.php EARLY, and defines BASE_URL and ADMIN_URL correctly.
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    $config_path = realpath(__DIR__ . '/../../config/config.php');
    $error_msg_config = "FATAL ERROR: Tidak dapat memuat file konfigurasi utama (config.php). Path: " . ($config_path ?: "Tidak valid/ada") . ". Periksa file dan log server.";
    error_log($error_msg_config);
    // It's better to use htmlspecialchars for displaying error messages if they might contain user input or special chars.
    echo "<div style='font-family:Arial,sans-serif;border:2px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Tidak dapat memuat konfigurasi. Aplikasi tidak dapat berjalan.<br><small>Detail: " . htmlspecialchars($error_msg_config, ENT_QUOTES, 'UTF-8') . "</small></div>";
    exit;
}

// LANGKAH 2: Panggil Otentikasi Admin
// This function might call redirect(). Ensure helpers.php (with corrected redirect) is loaded via config.php.
if (function_exists('require_admin')) {
    try {
        require_admin();
    } catch (Throwable $e) { // Catch potential errors from require_admin
        error_log("ERROR di kelola_pemesanan_sewa.php saat memanggil require_admin(): " . $e->getMessage());
        if (function_exists('set_flash_message') && function_exists('redirect')) {
            set_flash_message('danger', 'Sesi tidak valid atau terjadi kesalahan otentikasi.');
            redirect('auth/login.php'); // This should use the corrected redirect
        } else {
            http_response_code(403);
            die("Error: Akses ditolak atau fungsi otentikasi tidak tersedia.");
        }
    }
} else {
    error_log("FATAL ERROR di kelola_pemesanan_sewa.php: Fungsi require_admin() tidak ditemukan.");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem otentikasi.');
        redirect('auth/login.php'); // This should use the corrected redirect
    } else {
        http_response_code(500);
        die("Error: Fungsi otentikasi sistem tidak tersedia.");
    }
}

// LANGKAH 3: Sertakan Controller yang diperlukan
if (!require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php') {
    http_response_code(500);
    $controller_path = realpath(__DIR__ . '/../../controllers/PemesananSewaAlatController.php');
    $error_msg_controller = "FATAL: Gagal memuat PemesananSewaAlatController.php. Path: " . ($controller_path ?: "Tidak valid/ada");
    error_log($error_msg_controller . " dari kelola_pemesanan_sewa.php");
    if (function_exists('set_flash_message') && function_exists('redirect')) {
        set_flash_message('danger', 'Kesalahan sistem: Komponen pemesanan tidak dapat dimuat.');
        redirect('admin/dashboard.php'); // This should use the corrected redirect
    } else {
        die("Error: Komponen penting tidak dapat dimuat.");
    }
}

// LANGKAH 4: Set judul halaman dan SERTAKAN HEADER ADMIN
$pageTitle = "Manajemen Pemesanan Sewa Alat";
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    $header_path = realpath(__DIR__ . '/../../template/header_admin.php');
    $error_msg_header = "FATAL ERROR HALAMAN: Tidak dapat memuat file header admin (template/header_admin.php). Path: " . ($header_path ?: "Tidak valid/ada") . ". Periksa file dan log server.";
    error_log($error_msg_header);
    echo "<div style='font-family:Arial,sans-serif;border:2px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Error Kritis Tampilan</strong><br>Gagal memuat template header.<br><small>Detail: " . htmlspecialchars($error_msg_header, ENT_QUOTES, 'UTF-8') . "</small></div>";
    // Consider if exit is appropriate here or if footer should still be attempted. For fatal display errors, exit is often best.
    if (function_exists('include_once') && @include_once __DIR__ . '/../../template/footer_admin.php') {
        // Attempt to load footer for consistent page structure if possible
    }
    exit;
}
?>

<!-- Konten Utama Dimulai -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-boxes-stacked"></i> Kelola Pemesanan Sewa</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Pemesanan Sewa Alat
        <small class="text-muted fs-6">
            <?php
            // Sanitize GET parameters
            $filter_status_sewa_url_for_title = isset($_GET['status']) ? htmlspecialchars(trim(strtolower($_GET['status'])), ENT_QUOTES, 'UTF-8') : null;
            if ($filter_status_sewa_url_for_title) {
                echo "(Filter Status: " . e(ucfirst($filter_status_sewa_url_for_title)) . ")"; // e() already does htmlspecialchars
            }
            ?>
        </small>
    </h1>
</div>

<?php
if (function_exists('display_flash_message')) {
    display_flash_message();
} else {
    error_log("Peringatan: Fungsi display_flash_message() tidak ditemukan di kelola_pemesanan_sewa.php.");
}
?>

<?php
// Pengambilan Data
$daftarPemesananSewa = [];
$filter_status_sewa_url = isset($_GET['status']) ? trim(strtolower($_GET['status'])) : null; // Already sanitized for title, can reuse or re-sanitize if needed for DB
$pesan_error_saat_ambil_data = null;

if (isset($conn) && $conn instanceof mysqli && $conn->connect_error === null) { // Check connect_error explicitly for null for clarity
    if (class_exists('PemesananSewaAlatController') && method_exists('PemesananSewaAlatController', 'getAllPemesananSewaForAdmin')) {
        try {
            $semua_data_pemesanan_sewa = PemesananSewaAlatController::getAllPemesananSewaForAdmin(); // Assuming controller needs $conn
            if ($filter_status_sewa_url && is_array($semua_data_pemesanan_sewa) && !empty($semua_data_pemesanan_sewa)) {
                $daftarPemesananSewa = array_filter($semua_data_pemesanan_sewa, function ($ps) use ($filter_status_sewa_url) {
                    return isset($ps['status_item_sewa']) && strtolower(trim((string)$ps['status_item_sewa'])) === $filter_status_sewa_url;
                });
            } else {
                $daftarPemesananSewa = $semua_data_pemesanan_sewa;
            }
        } catch (Throwable $e) { // Catch any type of error/exception
            $pesan_error_saat_ambil_data = "Terjadi kesalahan saat mengambil data pemesanan: " . $e->getMessage();
            error_log("ERROR getAllPemesananSewaForAdmin: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        }
    } else {
        $pesan_error_saat_ambil_data = "Komponen untuk mengambil data pemesanan tidak tersedia (controller/method missing).";
        error_log("FATAL: PemesananSewaAlatController atau method getAllPemesananSewaForAdmin tidak ditemukan.");
    }
} else {
    $db_error_msg = isset($conn) ? $conn->connect_error : "Objek koneksi tidak valid.";
    $pesan_error_saat_ambil_data = "Tidak dapat terhubung ke database untuk mengambil data pemesanan. Detail: " . $db_error_msg;
    error_log("FATAL: Gagal koneksi database di kelola_pemesanan_sewa.php. Error: " . $db_error_msg);
}

if ($pesan_error_saat_ambil_data && function_exists('set_flash_message')) {
    // set_flash_message('danger', $pesan_error_saat_ambil_data); // Display as flash
    // Or display directly:
    echo '<div class="alert alert-danger" role="alert">' . e($pesan_error_saat_ambil_data) . '</div>';
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Pemesanan Sewa Alat</h6>
        <!-- Optional: Add filter dropdown or refresh button here -->
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle" id="dataTablePemesananSewa" style="width:100%;">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width:3%;">No.</th>
                        <th class="text-center" style="width:5%;">ID Sewa</th>
                        <th style="width:18%;">Pemesan (Info Tiket)</th>
                        <th style="width:15%;">Alat Disewa</th>
                        <th class="text-center" style="width:5%;">Jml</th>
                        <th style="width:17%;">Periode Sewa</th>
                        <th class="text-end" style="width:8%;">Total</th>
                        <th class="text-center" style="width:10%;">Status</th>
                        <th style="width:9%;">Tgl. Pesan</th>
                        <th class="text-center" style="width:10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pesan_error_saat_ambil_data) && !empty($daftarPemesananSewa) && is_array($daftarPemesananSewa)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftarPemesananSewa as $pesanan): ?>
                            <?php
                            $status_item_display = strtolower(trim($pesanan['status_item_sewa'] ?? 'tidak diketahui'));
                            $rowClass = '';
                            if (in_array($status_item_display, ['hilang', 'rusak', 'dibatalkan'])) {
                                $rowClass = 'table-danger';
                            } elseif ($status_item_display == 'dikembalikan') {
                                $rowClass = 'table-success';
                            } elseif ($status_item_display == 'diambil') {
                                $rowClass = 'table-info';
                            }
                            // Ensure $pesanan['id'] exists for links and forms
                            $pesanan_id_safe = e($pesanan['id'] ?? '');
                            ?>
                            <tr class="<?= e($rowClass) ?>">
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <td class="text-center"><strong><?= e($pesanan['id'] ?? '-') ?></strong></td>
                                <td>
                                    <?php
                                    $nama_pemesan_tiket_display = $pesanan['nama_pemesan'] ?? 'N/A';
                                    $id_user_pemesan_tiket_display = $pesanan['id_user_pemesan_tiket'] ?? null;
                                    if ($id_user_pemesan_tiket_display) {
                                        echo '<i class="fas fa-user-check text-success me-1" title="Pengguna Terdaftar"></i> ';
                                    } else {
                                        echo '<i class="fas fa-user-alt-slash text-muted me-1" title="Tamu"></i> ';
                                    }
                                    echo e($nama_pemesan_tiket_display);
                                    if ($id_user_pemesan_tiket_display) {
                                        echo ' <small class="text-muted d-block">(User ID: ' . e($id_user_pemesan_tiket_display) . ')</small>';
                                    }
                                    if (isset($pesanan['kode_pemesanan_tiket'], $pesanan['pemesanan_tiket_id']) && !empty($pesanan['kode_pemesanan_tiket'])) {
                                        echo '<small class="text-muted d-block">Tiket: <a href="' . e(ADMIN_URL) . '/pemesanan_tiket/detail_pemesanan.php?id=' . e($pesanan['pemesanan_tiket_id']) . '">' . e($pesanan['kode_pemesanan_tiket']) . '</a></small>';
                                    } elseif (isset($pesanan['pemesanan_tiket_id'])) {
                                        echo '<small class="text-muted d-block">Tiket ID: ' . e($pesanan['pemesanan_ tiket_id']) . '</small>';
                                    }
                                    ?>
                                </td>
                                <td><?= e($pesanan['nama_alat'] ?? 'N/A') ?> <br><small class="text-muted">(Alat ID: <?= e($pesanan['sewa_alat_id'] ?? '-') ?>)</small></td>
                                <td class="text-center"><?= e($pesanan['jumlah'] ?? '0') ?></td>
                                <td>
                                    <small>Mulai: <?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($pesanan['tanggal_mulai_sewa'] ?? null, true) : ($pesanan['tanggal_mulai_sewa'] ?? '-')) ?></small><br>
                                    <small>Akhir: <?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($pesanan['tanggal_akhir_sewa_rencana'] ?? null, true) : ($pesanan['tanggal_akhir_sewa_rencana'] ?? '-')) ?></small>
                                </td>
                                <td class="text-end fw-bold"><?= function_exists('formatRupiah') ? formatRupiah($pesanan['total_harga_item'] ?? 0) : e($pesanan['total_harga_item'] ?? 0) ?></td>
                                <td class="text-center">
                                    <?php if (function_exists('getSewaStatusBadgeClass')): ?>
                                        <span class="badge rounded-pill bg-<?php echo getSewaStatusBadgeClass($status_item_display); ?>">
                                            <?= e(ucfirst($status_item_display)) ?>
                                        </span>
                                    <?php else:
                                        echo e(ucfirst($status_item_display));
                                        error_log("Peringatan: Fungsi getSewaStatusBadgeClass() tidak ditemukan.");
                                    endif; ?>
                                </td>
                                <td><?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($pesanan['created_at'] ?? null, false) : ($pesanan['created_at'] ?? '-')) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/detail_pemesanan_sewa.php?id=<?= $pesanan_id_safe ?>" class="btn btn-info btn-sm" title="Detail & Edit Catatan/Denda">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah Status">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php $allowed_status_sewa = ['Dipesan', 'Diambil', 'Dikembalikan', 'Hilang', 'Rusak', 'Dibatalkan']; ?>
                                            <?php foreach ($allowed_status_sewa as $status_option): ?>
                                                <?php if (strtolower($status_option) !== $status_item_display): // Use !== for strict comparison 
                                                ?>
                                                    <li>
                                                        <form action="<?= e(ADMIN_URL) ?>/pemesanan_sewa/proses_update_status_sewa.php" method="POST" class="d-inline m-0">
                                                            <input type="hidden" name="pemesanan_id" value="<?= $pesanan_id_safe ?>">
                                                            <input type="hidden" name="new_status" value="<?= e($status_option) ?>">
                                                            <button type="submit" name="update_status_sewa_submit" class="dropdown-item"
                                                                onclick="return confirm('Anda yakin ingin mengubah status pemesanan #<?= $pesanan_id_safe ?> menjadi <?= e($status_option) ?>?')">
                                                                Tandai <?= e($status_option) ?>
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
                    <?php elseif (empty($pesan_error_saat_ambil_data)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php if ($filter_status_sewa_url): ?>
                                    Tidak ada data pemesanan sewa dengan status "<?= e(ucfirst($filter_status_sewa_url)) ?>". <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php">Lihat semua status</a>.
                                <?php else: ?>
                                    Belum ada data pemesanan sewa yang tersedia.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; // End check for $daftarPemesananSewa and no error 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Hapus '@' dari include_once footer untuk debugging jika masih ada masalah, tapi sebaiknya path diverifikasi
if (!include_once __DIR__ . '/../../template/footer_admin.php') {
    $footer_path = realpath(__DIR__ . '/../../template/footer_admin.php');
    $error_msg_footer = "ERROR HALAMAN: Gagal memuat template/footer_admin.php. Path: " . ($footer_path ?: "Tidak valid/ada") . ".";
    error_log($error_msg_footer . " dari kelola_pemesanan_sewa.php.");
    echo "<div style='font-family:Arial,sans-serif;border:1px solid orange;padding:10px;margin:20px;background-color:#fff3e0;color:#e65100;'><strong>Peringatan Tampilan</strong><br>Gagal memuat template footer.<br><small>Detail: " . htmlspecialchars($error_msg_footer, ENT_QUOTES, 'UTF-8') . "</small></div>";
}
?>