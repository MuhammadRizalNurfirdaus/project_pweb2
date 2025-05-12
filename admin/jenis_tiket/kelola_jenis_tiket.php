<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\kelola_jenis_tiket.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JenisTiketController.php';
// require_admin();

$page_title = "Kelola Jenis Tiket";
include_once __DIR__ . '/../../template/header_admin.php';

// Data sudah diurutkan berdasarkan ID ASC dari Model JenisTiket::getAll()
$daftar_jenis_tiket = JenisTiketController::getAll();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tags"></i> Kelola Jenis Tiket</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Jenis Tiket</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e($base_url) ?>admin/jenis_tiket/tambah_jenis_tiket.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Jenis Tiket Baru
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Jenis Tiket Tersedia</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 3%;">No.</th> <!-- Kolom Nomor Urut Visual -->
                        <th scope="col" style="width: 5%;">ID DB</th> <!-- Kolom ID Asli Database -->
                        <th scope="col" style="width: 20%;">Nama Layanan</th>
                        <th scope="col" style="width: 15%;">Tipe Hari</th>
                        <th scope="col" class="text-end" style="width: 12%;">Harga (Rp)</th>
                        <th scope="col">Destinasi Terkait</th>
                        <th scope="col" class="text-center" style="width: 10%;">Status Aktif</th>
                        <th scope="col" style="width: 10%;">Dibuat</th>
                        <th scope="col" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_jenis_tiket) && is_array($daftar_jenis_tiket)): ?>
                        <?php $nomor_urut_visual = 1; // Inisialisasi nomor urut visual 
                        ?>
                        <?php foreach ($daftar_jenis_tiket as $jenis): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <th scope="row"><?= e($jenis['id']) ?></th>
                                <td><?= e($jenis['nama_layanan_display']) ?></td>
                                <td><?= e($jenis['tipe_hari']) ?></td>
                                <td class="text-end"><?= e(number_format($jenis['harga'], 0, ',', '.')) ?></td>
                                <td><?= e($jenis['nama_wisata_terkait'] ?? 'Umum/Tidak Spesifik') ?></td>
                                <td class="text-center">
                                    <?php if ($jenis['aktif'] == 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d M Y', strtotime($jenis['created_at']))) ?></td>
                                <td>
                                    <a href="<?= e($base_url) ?>admin/jenis_tiket/edit_jenis_tiket.php?id=<?= e($jenis['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Jenis Tiket">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?= e($base_url) ?>admin/jenis_tiket/hapus_jenis_tiket.php?id=<?= e($jenis['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Jenis Tiket"
                                        onclick="return confirm('PERHATIAN: Menghapus jenis tiket ini bisa gagal jika masih digunakan dalam pemesanan. Yakin ingin mencoba menghapus jenis tiket \" <?= e(addslashes($jenis['nama_layanan_display'] . ' - ' . $jenis['tipe_hari'])) ?>\"?')">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4"> <!-- Colspan disesuaikan menjadi 9 -->
                                <p class="mb-2 lead">Belum ada jenis tiket yang ditambahkan.</p>
                                <a href="<?= e($base_url) ?>admin/jenis_tiket/tambah_jenis_tiket.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Jenis Tiket Pertama
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