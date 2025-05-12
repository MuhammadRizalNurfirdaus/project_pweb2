<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jadwal_ketersediaan\kelola_jadwal.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JadwalKetersediaanTiketController.php';
// require_admin();

$page_title = "Kelola Jadwal Ketersediaan Tiket";
include_once __DIR__ . '/../../template/header_admin.php';

// Data sekarang akan diurutkan berdasarkan ID ASC dari Model
$daftar_jadwal = JadwalKetersediaanTiketController::getAll();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calendar-alt"></i> Kelola Jadwal Ketersediaan Tiket</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Jadwal Ketersediaan Tiket</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e($base_url) ?>admin/jadwal_ketersediaan/tambah_jadwal.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Jadwal Baru
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Jadwal Ketersediaan Tiket Tersedia</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 3%;">No.</th> <!-- BARU -->
                        <th scope="col" style="width: 5%;">ID Jadwal</th> <!-- Sebelumnya ID -->
                        <th scope="col" style="width: 22%;">Jenis Tiket</th>
                        <th scope="col" style="width: 12%;">Tanggal</th>
                        <th scope="col" class="text-center" style="width: 10%;">Total Kuota</th>
                        <th scope="col" class="text-center" style="width: 10%;">Kuota Saat Ini</th>
                        <th scope="col" class="text-center" style="width: 10%;">Status Jadwal</th>
                        <th scope="col" style="width: 10%;">Dibuat</th>
                        <th scope="col" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_jadwal) && is_array($daftar_jadwal)): ?>
                        <?php $nomor_urut_visual = 1; // Inisialisasi nomor urut visual 
                        ?>
                        <?php foreach ($daftar_jadwal as $jadwal): ?>
                            <tr class="<?= ($jadwal['aktif'] == 0) ? 'table-secondary text-muted' : '' ?><?= (strtotime($jadwal['tanggal']) < strtotime(date('Y-m-d')) && $jadwal['aktif'] == 1) ? ' table-warning' : '' ?>">
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td> <!-- BARU -->
                                <th scope="row"><?= e($jadwal['id']) ?></th>
                                <td>
                                    <?= e($jadwal['nama_layanan_display'] ?? 'N/A') ?> (<?= e($jadwal['tipe_hari'] ?? 'N/A') ?>)
                                    <br><small class="text-muted">ID Jenis Tiket: <?= e($jadwal['jenis_tiket_id']) ?></small>
                                </td>
                                <td><?= e(date('d M Y', strtotime($jadwal['tanggal']))) ?></td>
                                <td class="text-center"><?= e($jadwal['jumlah_total_tersedia']) ?></td>
                                <td class="text-center fw-bold <?= ($jadwal['jumlah_saat_ini_tersedia'] == 0 && $jadwal['aktif'] == 1) ? 'text-danger' : (($jadwal['jumlah_saat_ini_tersedia'] > 0) ? 'text-success' : '') ?>">
                                    <?= e($jadwal['jumlah_saat_ini_tersedia']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($jadwal['aktif'] == 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                        <?php if (strtotime($jadwal['tanggal']) < strtotime(date('Y-m-d'))): ?>
                                            <span class="badge bg-warning text-dark ms-1">Lampau</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d M Y, H:i', strtotime($jadwal['created_at']))) // Menampilkan jam juga 
                                    ?></td>
                                <td>
                                    <a href="<?= e($base_url) ?>admin/jadwal_ketersediaan/edit_jadwal.php?id=<?= e($jadwal['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Jadwal">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <!-- Tombol Hapus Fisik tidak diutamakan, admin bisa menonaktifkan jadwal -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4"> <!-- Colspan disesuaikan menjadi 9 -->
                                <p class="mb-2 lead">Belum ada jadwal ketersediaan tiket yang dibuat.</p>
                                <a href="<?= e($base_url) ?>admin/jadwal_ketersediaan/tambah_jadwal.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Buat Jadwal Ketersediaan Pertama
                                </a>
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