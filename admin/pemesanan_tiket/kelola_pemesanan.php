<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\kelola_pemesanan.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/pemesanan_tiket/kelola_pemesanan.php");
    exit("Kesalahan konfigurasi server. Tidak dapat memuat file penting.");
}

// 2. Panggil fungsi otentikasi admin
require_admin();

// 3. Sertakan Controller PemesananTiket
// Controller akan membuat instance Model PemesananTiket dan memanggil metodenya secara non-statis
if (!require_once __DIR__ . '/../../controllers/PemesananTiketController.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat controllers/PemesananTiketController.php");
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket tidak dapat dimuat.');
    redirect('admin/dashboard.php');
}

// 4. Set judul halaman dan sertakan header admin
$pageTitle = "Kelola Pemesanan Tiket";
if (!include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php.");
    echo "<p style='color:red; font-family: sans-serif, Arial; padding: 20px;'>Error Kritis: Gagal memuat komponen header halaman admin.</p>";
    // exit;
}

// 5. Ambil data pemesanan tiket
$daftar_pemesanan = [];
$filter_status = isset($_GET['status']) ? trim(strtolower($_GET['status'])) : null; // Ambil dan bersihkan filter status

// Inisialisasi controller di sini karena akan digunakan
$pemesananTiketController = null;
if (class_exists('PemesananTiketController')) {
    // Jika controller Anda membutuhkan koneksi di constructor (misal untuk membuat instance model di sana)
    // $pemesananTiketController = new PemesananTiketController($conn);
    // Namun, jika semua metode controller statis dan membuat instance model di dalam metode, ini tidak perlu.
    // Untuk saat ini, kita asumsikan metode Controller adalah statis dan membuat instance Model di dalamnya.
}


if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error && class_exists('PemesananTiketController')) {
    try {
        // Panggil metode statis dari Controller yang akan mengambil data dari Model
        // Idealnya, Controller memiliki metode untuk mengambil data berdasarkan filter status
        if ($filter_status && method_exists('PemesananTiketController', 'getPemesananByStatusForAdmin')) {
            // $daftar_pemesanan = PemesananTiketController::getPemesananByStatusForAdmin($filter_status);
            // Jika belum ada metode spesifik, kita filter setelah getAll()
            $semua_pemesanan = PemesananTiketController::getAll(); // Ini memanggil Model secara non-statis di dalamnya
            if (is_array($semua_pemesanan)) {
                $daftar_pemesanan = array_filter($semua_pemesanan, function ($p) use ($filter_status) {
                    // PERBAIKAN DI SINI: Gunakan 'status'
                    return isset($p['status']) && strtolower($p['status']) === $filter_status;
                });
            } else {
                $daftar_pemesanan = [];
            }
        } else {
            $daftar_pemesanan = PemesananTiketController::getAll();
        }
    } catch (Throwable $e) {
        error_log("Error mengambil daftar pemesanan tiket di kelola_pemesanan.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        set_flash_message('danger', 'Gagal memuat daftar pemesanan tiket. Silakan coba lagi nanti.');
        $daftar_pemesanan = []; // Pastikan tetap array kosong jika error
    }
} elseif (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    set_flash_message('danger', 'Koneksi database tidak tersedia untuk memuat data.');
    error_log("Koneksi database tidak tersedia di kelola_pemesanan.php.");
} elseif (!class_exists('PemesananTiketController')) {
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket tidak ditemukan.');
    error_log("Controller PemesananTiketController tidak ditemukan.");
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-ticket-alt"></i> Kelola Pemesanan Tiket</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Pemesanan Tiket <?php if ($filter_status) echo "(Status: " . e(ucfirst($filter_status)) . ")" ?></h1>
</div>

<?php display_flash_message(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Data Pemesanan Tiket</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 3%;" class="text-center">No.</th>
                        <th scope="col" style="width: 5%;" class="text-center">ID</th>
                        <th scope="col" style="width: 15%;">Kode Pesan</th>
                        <th scope="col" style="width: 20%;">Nama Pemesan</th>
                        <th scope="col" style="width: 12%;">Tgl. Kunjungan</th>
                        <th scope="col" class="text-end" style="width: 12%;">Total Harga</th>
                        <th scope="col" class="text-center" style="width: 10%;">Status</th>
                        <th scope="col" style="width: 13%;">Tgl. Dibuat</th>
                        <th scope="col" style="width: 10%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_pemesanan) && is_array($daftar_pemesanan)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftar_pemesanan as $pemesanan): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <td class="text-center"><strong><?= e($pemesanan['id'] ?? '-') ?></strong></td>
                                <td><strong><?= e($pemesanan['kode_pemesanan'] ?? '-') ?></strong></td>
                                <td>
                                    <?php if (!empty($pemesanan['user_id']) && !empty($pemesanan['user_nama'])): ?>
                                        <i class="fas fa-user-check text-success me-1" title="Pengguna Terdaftar"></i>
                                        <?= e($pemesanan['user_nama']) ?>
                                        <?php if (!empty($pemesanan['user_email'])): ?>
                                            <br><small class="text-muted" title="<?= e($pemesanan['user_email']) ?>"><i class="fas fa-envelope fa-fw"></i> <?= e(mb_strimwidth($pemesanan['user_email'], 0, 20, "...")) ?></small>
                                        <?php endif; ?>
                                    <?php elseif (!empty($pemesanan['nama_pemesan_tamu'])): ?>
                                        <i class="fas fa-user-alt-slash text-muted me-1" title="Tamu"></i>
                                        <?= e($pemesanan['nama_pemesan_tamu']) ?>
                                        <?php if (!empty($pemesanan['email_pemesan_tamu'])): ?>
                                            <br><small class="text-muted" title="<?= e($pemesanan['email_pemesan_tamu']) ?>"><i class="fas fa-envelope fa-fw"></i> <?= e(mb_strimwidth($pemesanan['email_pemesan_tamu'], 0, 20, "...")) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($pemesanan['nohp_pemesan_tamu'])): ?>
                                            <br><small class="text-muted" title="<?= e($pemesanan['nohp_pemesan_tamu']) ?>"><i class="fas fa-phone fa-fw"></i> <?= e($pemesanan['nohp_pemesan_tamu']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tgl_kunjungan_formatted = '-';
                                    if (!empty($pemesanan['tanggal_kunjungan']) && $pemesanan['tanggal_kunjungan'] !== '0000-00-00') {
                                        try {
                                            $date_obj_kunjungan = new DateTime($pemesanan['tanggal_kunjungan']);
                                            $tgl_kunjungan_formatted = $date_obj_kunjungan->format('d M Y');
                                        } catch (Exception $e) { /* biarkan default */
                                        }
                                    }
                                    echo e($tgl_kunjungan_formatted);
                                    ?>
                                </td>
                                <td class="text-end fw-bold"><?= formatRupiah($pemesanan['total_harga_akhir'] ?? 0) ?></td>
                                <td class="text-center">
                                    <?php
                                    // PERBAIKAN DI SINI: Gunakan 'status'
                                    $status_pesan_raw = $pemesanan['status'] ?? 'unknown'; // Default jika tidak ada
                                    $status_pesan = strtolower($status_pesan_raw);
                                    $status_class = 'bg-secondary';
                                    if ($status_pesan == 'pending') $status_class = 'bg-warning text-dark';
                                    elseif ($status_pesan == 'waiting_payment') $status_class = 'bg-info text-dark';
                                    elseif ($status_pesan == 'paid') $status_class = 'bg-primary';
                                    elseif ($status_pesan == 'confirmed') $status_class = 'bg-success';
                                    elseif ($status_pesan == 'completed') $status_class = 'bg-dark';
                                    elseif ($status_pesan == 'cancelled') $status_class = 'bg-danger';
                                    elseif ($status_pesan == 'expired') $status_class = 'bg-light text-dark border';
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= e(ucfirst(str_replace('_', ' ', $status_pesan_raw))) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $tgl_dibuat_formatted = '-';
                                    if (!empty($pemesanan['created_at']) && $pemesanan['created_at'] !== '0000-00-00 00:00:00') {
                                        try {
                                            $date_obj_dibuat = new DateTime($pemesanan['created_at']);
                                            $tgl_dibuat_formatted = $date_obj_dibuat->format('d M Y, H:i');
                                        } catch (Exception $e) { /* biarkan default */
                                        }
                                    }
                                    echo e($tgl_dibuat_formatted);
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= e(ADMIN_URL) ?>/pemesanan_tiket/detail_pemesanan.php?id=<?= e($pemesanan['id'] ?? '') ?>" class="btn btn-primary btn-sm me-1 mb-1" title="Lihat Detail & Kelola Status">
                                        <i class="fas fa-eye"></i> <span class="d-none d-md-inline">Detail</span>
                                    </a>
                                    <form action="<?= e(ADMIN_URL) ?>/pemesanan_tiket/hapus_pemesanan.php" method="POST" style="display:inline;" onsubmit="return confirm('PERHATIAN: Menghapus pemesanan (<?= e(addslashes($pemesanan['kode_pemesanan'] ?? '')) ?>) juga akan menghapus detail terkait. Yakin?');">
                                        <input type="hidden" name="id_pemesanan" value="<?= e($pemesanan['id'] ?? '') ?>">
                                        <button type="submit" name="hapus_pemesanan_submit" class="btn btn-danger btn-sm mb-1" title="Hapus Pemesanan">
                                            <i class="fas fa-trash-alt"></i> <span class="d-none d-md-inline">Hapus</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <p class="mb-2 lead">
                                    <?php if ($filter_status): ?>
                                        Tidak ada data pemesanan tiket dengan status "<?= e(ucfirst($filter_status)) ?>".
                                    <?php else: ?>
                                        Belum ada data pemesanan tiket.
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
include_once __DIR__ . '/../../template/footer_admin.php';
?>