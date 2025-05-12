<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\kelola_alat.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/SewaAlatController.php';
// require_admin();

$page_title = "Kelola Alat Sewa";
include_once __DIR__ . '/../../template/header_admin.php';

// Data sudah diurutkan berdasarkan ID ASC dari Model SewaAlat::getAll()
$daftar_alat_sewa = SewaAlatController::getAll();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-tools"></i> Kelola Alat Sewa</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Alat Sewa</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= e($base_url) ?>admin/alat_sewa/tambah_alat.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Alat Sewa Baru
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Data Alat Sewa Tersedia</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 3%;">No.</th> <!-- Kolom Nomor Urut Visual -->
                        <th scope="col" style="width: 5%;">ID DB</th> <!-- Kolom ID Asli Database -->
                        <th scope="col" style="width: 20%;">Nama Item</th>
                        <th scope="col" style="width: 15%;">Kategori</th>
                        <th scope="col" class="text-end" style="width: 10%;">Harga Sewa</th>
                        <th scope="col" style="width: 12%;">Periode Harga</th>
                        <th scope="col" class="text-center" style="width: 7%;">Stok</th>
                        <th scope="col" class="text-center" style="width: 10%;">Gambar</th>
                        <th scope="col" style="width: 8%;">Kondisi</th>
                        <th scope="col" style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_alat_sewa) && is_array($daftar_alat_sewa)): ?>
                        <?php $nomor_urut_visual = 1; // Inisialisasi nomor urut visual 
                        ?>
                        <?php foreach ($daftar_alat_sewa as $alat): ?>
                            <tr>
                                <td class="text-center"><?= $nomor_urut_visual++ ?></td>
                                <th scope="row"><?= e($alat['id']) ?></th>
                                <td><?= e($alat['nama_item']) ?></td>
                                <td><?= e($alat['kategori_alat'] ?? 'N/A') ?></td>
                                <td class="text-end">Rp <?= e(number_format($alat['harga_sewa'], 0, ',', '.')) ?></td>
                                <td><?= e($alat['durasi_harga_sewa']) ?> <?= e($alat['satuan_durasi_harga']) ?></td>
                                <td class="text-center"><?= e($alat['stok_tersedia']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $gambar_path_on_server = (defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH . '/' : __DIR__ . "/../../public/uploads/alat_sewa/") . ($alat['gambar_alat'] ?? '');
                                    $gambar_url = isset($base_url) ? $base_url . "public/uploads/alat_sewa/" . e($alat['gambar_alat'] ?? '') : '#';
                                    ?>
                                    <?php if (!empty($alat['gambar_alat']) && file_exists($gambar_server_path)): ?>
                                        <img src="<?= $gambar_url ?>?t=<?= time() ?>"
                                            alt="Gambar <?= e($alat['nama_item']) ?>" class="img-thumbnail" style="width: 70px; height: auto; max-height:50px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">Tanpa Gambar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $kondisi_class = 'bg-secondary'; // Default
                                    switch (strtolower($alat['kondisi_alat'] ?? '')) {
                                        case 'baik':
                                            $kondisi_class = 'bg-success';
                                            break;
                                        case 'rusak ringan':
                                            $kondisi_class = 'bg-warning text-dark';
                                            break;
                                        case 'perlu perbaikan':
                                            $kondisi_class = 'bg-danger';
                                            break;
                                        case 'hilang':
                                            $kondisi_class = 'bg-dark';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $kondisi_class ?>"><?= e($alat['kondisi_alat'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <a href="<?= e($base_url) ?>admin/alat_sewa/edit_alat.php?id=<?= e($alat['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Alat Sewa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= e($base_url) ?>admin/alat_sewa/hapus_alat.php?id=<?= e($alat['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Alat Sewa"
                                        onclick="return confirm('PERHATIAN: Menghapus alat ini akan gagal jika masih ada pemesanan aktif yang menggunakannya. Yakin ingin mencoba menghapus alat \" <?= e(addslashes($alat['nama_item'])) ?>\"?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4"> <!-- Colspan disesuaikan menjadi 10 -->
                                <p class="mb-2 lead">Belum ada data alat sewa yang ditambahkan.</p>
                                <a href="<?= e($base_url) ?>admin/alat_sewa/tambah_alat.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Alat Sewa Pertama
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