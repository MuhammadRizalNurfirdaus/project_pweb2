<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\kelola_pemesanan.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PemesananTiketController.php'; // Menggunakan controller yang sudah diupdate
require_admin(); // Pastikan hanya admin yang bisa akses

$page_title = "Kelola Pemesanan Tiket";
include_once __DIR__ . '/../../template/header_admin.php';

// Mengambil semua data header pemesanan tiket melalui controller
// Model PemesananTiket::getAll() akan mengurutkan berdasarkan created_at DESC, id DESC
$daftar_pemesanan = PemesananTiketController::getAll();

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-ticket-alt"></i> Kelola Pemesanan Tiket</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Pemesanan Tiket</h1>
    <!-- Tombol untuk admin membuat pemesanan tiket manual (jika ada fungsionalitasnya) -->
    <!-- 
    <a href="<?= e($base_url) ?>admin/pemesanan_tiket/tambah_pemesanan_manual.php" class="btn btn-sm btn-success shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pemesanan Manual
    </a> 
    -->
</div>

<?php
// Menampilkan flash message (jika ada)
if (function_exists('display_flash_message')) {
    echo display_flash_message();
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Data Pemesanan Tiket</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 3%;">No.</th>
                        <th scope="col" style="width: 5%;">ID Pesan</th>
                        <th scope="col" style="width: 15%;">Kode Pesan</th>
                        <th scope="col" style="width: 20%;">Nama Pemesan</th>
                        <th scope="col" style="width: 12%;">Tgl. Kunjungan</th>
                        <th scope="col" class="text-end" style="width: 12%;">Total Harga</th>
                        <th scope="col" class="text-center" style="width: 10%;">Status Pesan</th>
                        <th scope="col" style="width: 13%;">Tgl. Dibuat</th>
                        <th scope="col" style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_pemesanan) && is_array($daftar_pemesanan)): ?>
                        <?php $nomor_urut_visual = 1; ?>
                        <?php foreach ($daftar_pemesanan as $pemesanan): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <th scope="row"><?= e($pemesanan['id']) ?></th>
                                <td><strong><?= e($pemesanan['kode_pemesanan']) ?></strong></td>
                                <td>
                                    <?php if (!empty($pemesanan['user_id']) && !empty($pemesanan['user_nama'])): ?>
                                        <i class="fas fa-user-check text-success me-1" title="Pengguna Terdaftar"></i>
                                        <?= e($pemesanan['user_nama']) ?>
                                        <?php if (!empty($pemesanan['user_email'])): ?>
                                            <br><small class="text-muted"><?= e($pemesanan['user_email']) ?></small>
                                        <?php endif; ?>
                                    <?php elseif (!empty($pemesanan['nama_pemesan_tamu'])): ?>
                                        <i class="fas fa-user-alt-slash text-muted me-1" title="Tamu"></i>
                                        <?= e($pemesanan['nama_pemesan_tamu']) ?>
                                        <?php if (!empty($pemesanan['email_pemesan_tamu'])): ?>
                                            <br><small class="text-muted"><?= e($pemesanan['email_pemesan_tamu']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d M Y', strtotime($pemesanan['tanggal_kunjungan']))) ?></td>
                                <td class="text-end fw-bold">Rp <?= e(number_format($pemesanan['total_harga_akhir'], 0, ',', '.')) ?></td>
                                <td class="text-center">
                                    <?php
                                    $status_pesan = strtolower($pemesanan['status_pemesanan']);
                                    $status_class = 'bg-secondary'; // Default
                                    if ($status_pesan == 'pending') $status_class = 'bg-warning text-dark';
                                    elseif ($status_pesan == 'waiting_payment') $status_class = 'bg-info text-dark';
                                    elseif ($status_pesan == 'paid') $status_class = 'bg-primary';
                                    elseif ($status_pesan == 'confirmed') $status_class = 'bg-success';
                                    elseif ($status_pesan == 'completed') $status_class = 'bg-dark';
                                    elseif ($status_pesan == 'cancelled') $status_class = 'bg-danger';
                                    elseif ($status_pesan == 'expired') $status_class = 'bg-light text-dark border';
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= e(ucfirst(str_replace('_', ' ', $pemesanan['status_pemesanan']))) ?></span>
                                </td>
                                <td><?= e(date('d M Y, H:i', strtotime($pemesanan['created_at']))) ?></td>
                                <td>
                                    <a href="<?= e($base_url) ?>admin/pemesanan_tiket/detail_pemesanan.php?id=<?= e($pemesanan['id']) ?>" class="btn btn-primary btn-sm me-1 mb-1" title="Lihat Detail & Kelola Status">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <a href="<?= e($base_url) ?>admin/pemesanan_tiket/hapus_pemesanan.php?id=<?= e($pemesanan['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Pemesanan"
                                        onclick="return confirm('PERHATIAN: Menghapus pemesanan (<?= e(addslashes($pemesanan['kode_pemesanan'])) ?>) juga akan menghapus detail tiket, sewa, dan pembayaran terkait jika ON DELETE CASCADE aktif. Yakin?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4"> <!-- Colspan disesuaikan -->
                                <p class="mb-2 lead">Belum ada data pemesanan tiket.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal tidak lagi di sini, detail dan update status akan ada di halaman detail_pemesanan.php -->

<?php
include_once __DIR__ . '/../../template/footer_admin.php';
?>