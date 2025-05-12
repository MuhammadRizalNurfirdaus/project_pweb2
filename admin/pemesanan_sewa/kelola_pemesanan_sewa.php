<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\kelola_pemesanan_sewa.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari kelola_pemesanan_sewa.php");
    exit("Kesalahan Server: Konfigurasi tidak dapat dimuat.");
}

// 2. Otentikasi Admin
require_admin();
$pageTitle = "Manajemen Pemesanan Sewa Alat";
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari kelola_pemesanan_sewa.php.");
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px;'>Error Kritis: Gagal memuat komponen header halaman admin.</p>";
    exit;
}
// 3. Sertakan Controller yang diperlukan
if (!require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat PemesananSewaAlatController.php dari kelola_pemesanan_sewa.php");
    set_flash_message('danger', 'Kesalahan sistem: Komponen pemesanan tidak dapat dimuat.');
    redirect('admin/dashboard.php');
}

// 4. Pengambilan dan Pemfilteran Data
$daftarPemesananSewa = [];
$filter_status_sewa_url = isset($_GET['status']) ? trim(strtolower($_GET['status'])) : null;
$page_subtitle = '';
$pesan_error_saat_ambil_data = null;

if (class_exists('PemesananSewaAlatController') && method_exists('PemesananSewaAlatController', 'getAllPemesananSewaForAdmin')) {
    try {
        $semua_data_pemesanan_sewa = PemesananSewaAlatController::getAllPemesananSewaForAdmin(); // Ini akan memanggil model yg sudah di-JOIN

        if ($filter_status_sewa_url && is_array($semua_data_pemesanan_sewa) && !empty($semua_data_pemesanan_sewa)) {
            $daftarPemesananSewa = array_filter($semua_data_pemesanan_sewa, function ($ps) use ($filter_status_sewa_url) {
                return isset($ps['status_item_sewa']) && strtolower($ps['status_item_sewa']) === $filter_status_sewa_url;
            });
            if (!empty($filter_status_sewa_url)) {
                $page_subtitle = "(Filter Status: " . e(ucfirst($filter_status_sewa_url)) . ")";
            }
        } else {
            $daftarPemesananSewa = $semua_data_pemesanan_sewa;
        }
    } catch (Throwable $e) {
        error_log("Error di kelola_pemesanan_sewa.php saat mengambil data: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        $pesan_error_saat_ambil_data = 'Terjadi kesalahan saat mencoba memuat data pemesanan sewa.';
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', $pesan_error_saat_ambil_data);
        }
    }
} else {
    $pesan_error_saat_ambil_data = 'Kesalahan sistem: Controller atau metode untuk Pemesanan Sewa tidak ditemukan.';
    error_log($pesan_error_saat_ambil_data);
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', $pesan_error_saat_ambil_data);
    }
}

// 5. Set judul halaman dan sertakan header admin
$pageTitle = "Manajemen Pemesanan Sewa Alat";
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari kelola_pemesanan_sewa.php.");
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px;'>Error Kritis: Gagal memuat komponen header halaman admin.</p>";
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
    <h1 class="h2">Manajemen Pemesanan Sewa Alat <small class="text-muted fs-5"><?= e($page_subtitle) ?></small></h1>
</div>

<?php display_flash_message(); ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Daftar Pemesanan Sewa Alat</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle" id="dataTablePemesananSewa" width="100%" cellspacing="0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" class="text-center" style="width:3%;">No.</th>
                        <th scope="col" class="text-center" style="width:5%;">ID Sewa</th>
                        <th scope="col" style="width:20%;">Pemesan (Tiket)</th>
                        <th scope="col" style="width:15%;">Alat Disewa</th>
                        <th scope="col" class="text-center" style="width:5%;">Jml</th>
                        <th scope="col" style="width:15%;">Periode Sewa</th>
                        <th scope="col" class="text-end" style="width:10%;">Total</th>
                        <th scope="col" class="text-center" style="width:10%;">Status</th>
                        <th scope="col" style="width:10%;">Tanggal Pesan Sewa</th> <!-- Perubahan Nama Kolom -->
                        <th scope="col" class="text-center" style="width:12%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pesan_error_saat_ambil_data) && !empty($daftarPemesananSewa) && is_array($daftarPemesananSewa)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftarPemesananSewa as $pesanan): ?>
                            <?php
                            $status_item = strtolower($pesanan['status_item_sewa'] ?? 'unknown');
                            $rowClass = '';
                            if (in_array($status_item, ['hilang', 'rusak', 'dibatalkan'])) {
                                $rowClass = 'table-danger';
                            } elseif ($status_item == 'dikembalikan') {
                                $rowClass = 'table-success';
                            } elseif ($status_item == 'diambil') {
                                $rowClass = 'table-info';
                            }
                            ?>
                            <tr class="<?= e($rowClass) ?>">
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <td class="text-center"><strong><?= e($pesanan['id'] ?? '-') ?></strong></td>
                                <td>
                                    <?php
                                    // Menampilkan nama pemesan dari data JOIN yang diambil oleh Model PemesananSewaAlat::getAll()
                                    // 'nama_pemesan' adalah alias dari COALESCE(u.nama, pt.nama_pemesan_tamu)
                                    // 'id_user_pemesan_tiket' adalah alias dari pt.user_id
                                    $nama_pemesan_tiket_display = $pesanan['nama_pemesan'] ?? 'N/A';
                                    $id_user_pemesan_tiket_display = $pesanan['id_user_pemesan_tiket'] ?? null;

                                    if ($id_user_pemesan_tiket_display) {
                                        echo '<i class="fas fa-user-check text-success me-1" title="Pengguna Terdaftar (Pemesanan Tiket)"></i> ';
                                        echo e($nama_pemesan_tiket_display);
                                        echo ' <small class="text-muted d-block">(User ID: ' . e($id_user_pemesan_tiket_display) . ')</small>';
                                    } elseif (!empty($nama_pemesan_tiket_display) && $nama_pemesan_tiket_display !== 'N/A') { // Cek jika nama tamu ada
                                        echo '<i class="fas fa-user-alt-slash text-muted me-1" title="Tamu (Pemesanan Tiket)"></i> ';
                                        echo e($nama_pemesan_tiket_display);
                                    } else {
                                        echo '<span class="text-muted">Pemesan Tiket Tidak Diketahui</span>';
                                    }

                                    // Tampilkan Kode Pemesanan Tiket jika ada
                                    // 'kode_pemesanan_tiket' adalah alias dari pt.kode_pemesanan
                                    if (isset($pesanan['kode_pemesanan_tiket']) && !empty($pesanan['kode_pemesanan_tiket']) && isset($pesanan['pemesanan_tiket_id'])) {
                                        echo '<small class="text-muted d-block">Kode Tiket: <a href="' . e(ADMIN_URL) . '/pemesanan_tiket/detail_pemesanan.php?id=' . e($pesanan['pemesanan_tiket_id']) . '" title="Lihat Detail Pemesanan Tiket">' . e($pesanan['kode_pemesanan_tiket']) . '</a></small>';
                                    } elseif (isset($pesanan['pemesanan_tiket_id']) && !empty($pesanan['pemesanan_tiket_id'])) {
                                        echo '<small class="text-muted d-block">Tiket ID: ' . e($pesanan['pemesanan_tiket_id']) . '</small>';
                                    }
                                    ?>
                                </td>
                                <td><?= e($pesanan['nama_alat'] ?? 'N/A') ?> <br><small class="text-muted">(Alat ID: <?= e($pesanan['sewa_alat_id'] ?? '-') ?>)</small></td>
                                <td class="text-center"><?= e($pesanan['jumlah'] ?? '0') ?></td>
                                <td>
                                    <small>Mulai: <?= e(formatTanggalIndonesia($pesanan['tanggal_mulai_sewa'] ?? '', true)) ?></small><br>
                                    <small>Akhir: <?= e(formatTanggalIndonesia($pesanan['tanggal_akhir_sewa_rencana'] ?? '', true)) ?></small>
                                </td>
                                <td class="text-end fw-bold"><?= formatRupiah($pesanan['total_harga_item'] ?? 0) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo getSewaStatusBadgeClass($status_item); ?>">
                                        <?= e(ucfirst($status_item)) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= e(formatTanggalIndonesia($pesanan['created_at'] ?? '', false)) ?>
                                </td>
                                <td class="text-center">
                                    <form action="<?= e(ADMIN_URL) ?>/pemesanan_sewa/proses_update_status_sewa.php" method="POST" style="display:inline-block; width:100%;">
                                        <input type="hidden" name="pemesanan_id" value="<?= e($pesanan['id'] ?? '') ?>">
                                        <div class="input-group input-group-sm mb-1">
                                            <select name="new_status" class="form-select form-select-sm" aria-label="Ubah Status">
                                                <option value="">-- Status --</option>
                                                <?php $allowed_status_sewa = ['Dipesan', 'Diambil', 'Dikembalikan', 'Hilang', 'Rusak', 'Dibatalkan']; ?>
                                                <?php foreach ($allowed_status_sewa as $status_option): ?>
                                                    <?php if (strtolower($status_option) != $status_item): ?>
                                                        <option value="<?= e($status_option) ?>"><?= e($status_option) ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Update Status"
                                                onclick="return confirm('Anda yakin ingin mengubah status item sewa ini menjadi \'' + (this.form.new_status.value ? this.form.new_status.options[this.form.new_status.selectedIndex].text : '[Pilih Status Dulu]') + '\'?')">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="mb-0 lead">
                                    <?php if ($pesan_error_saat_ambil_data): ?>
                                        <?= e($pesan_error_saat_ambil_data) ?>
                                    <?php elseif ($filter_status_sewa_url && empty($daftarPemesananSewa)): ?>
                                        Tidak ada data pemesanan sewa alat dengan status "<?= e(ucfirst($filter_status_sewa_url)) ?>". <a href="<?= e(ADMIN_URL) ?>/pemesanan_sewa/kelola_pemesanan_sewa.php">Lihat semua</a>.
                                    <?php else: ?>
                                        Belum ada data pemesanan sewa alat.
                                    <?php endif; ?>
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
if (!function_exists('getSewaStatusBadgeClass')) {
    function getSewaStatusBadgeClass($status)
    {
        switch (strtolower($status)) {
            case 'dipesan':
                return 'secondary';
            case 'diambil':
                return 'info text-dark';
            case 'dikembalikan':
                return 'success';
            case 'hilang':
            case 'rusak':
            case 'dibatalkan':
                return 'danger';
            default:
                return 'light text-dark';
        }
    }
}
include_once __DIR__ . '/../../template/footer_admin.php';
?>