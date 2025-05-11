<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\kelola_wisata.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/WisataController.php';
// require_admin();

$page_title = "Kelola Destinasi Wisata";
include_once __DIR__ . '/../../template/header_admin.php';

// Model Wisata::getAll() sudah mengurutkan berdasarkan ID ASC (atau kriteria lain yang Anda set)
$daftar_wisata = WisataController::getAll();

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi Wisata</li>
  </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">Daftar Destinasi Wisata</h1>
  <div class="btn-toolbar mb-2 mb-md-0">
    <a href="<?= $base_url ?>admin/wisata/tambah_wisata.php" class="btn btn-sm btn-success shadow-sm">
      <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Destinasi Baru
    </a>
  </div>
</div>

<?php
if (function_exists('display_flash_message')) {
  echo display_flash_message();
}
?>

<div class="card shadow-sm">
  <div class="card-header bg-light">
    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Data Destinasi Tersedia</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped">
        <thead class="table-dark">
          <tr>
            <th scope="col" style="width: 3%;">No.</th> <!-- Kolom Nomor Urut Visual -->
            <th scope="col" style="width: 5%;">ID</th> <!-- Kolom ID Asli Database -->
            <th scope="col" style="width: 20%;">Nama Destinasi</th> <!-- Kolom Nama Destinasi -->
            <th scope="col" style="width: 27%;">Deskripsi (Ringkasan)</th> <!-- Kolom Deskripsi -->
            <th scope="col" style="width: 15%;">Lokasi</th> <!-- Kolom Lokasi -->
            <th scope="col" class="text-center" style="width: 10%;">Gambar</th> <!-- Kolom Gambar -->
            <th scope="col" style="width: 10%;">Dibuat</th> <!-- Kolom Tanggal Dibuat -->
            <th scope="col" style="width: 10%;">Aksi</th> <!-- Kolom Aksi -->
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($daftar_wisata) && is_array($daftar_wisata)): ?>
            <?php $nomor_urut = 1; // Inisialisasi nomor urut 
            ?>
            <?php foreach ($daftar_wisata as $wisata): ?>
              <tr>
                <td class="text-center"><?= $nomor_urut++ ?></td> <!-- Tampilkan dan increment nomor urut -->
                <th scope="row"><?= e($wisata['id']) ?></th> <!-- Tampilkan ID asli DB -->
                <td><?= e($wisata['nama_wisata']) ?></td>
                <td><?= e(substr(strip_tags($wisata['deskripsi'] ?? ''), 0, 100)) ?><?= (strlen(strip_tags($wisata['deskripsi'] ?? '')) > 100 ? '...' : '') ?></td>
                <td><?= e($wisata['lokasi'] ?? 'N/A') ?></td>
                <td class="text-center">
                  <?php
                  $gambar_server_path = defined('UPLOADS_WISATA_PATH') ? UPLOADS_WISATA_PATH . '/' . ($wisata['gambar'] ?? '') : __DIR__ . "/../../public/uploads/wisata/" . ($wisata['gambar'] ?? '');
                  ?>
                  <?php if (!empty($wisata['gambar']) && file_exists($gambar_server_path)): ?>
                    <img src="<?= $base_url ?>public/uploads/wisata/<?= e($wisata['gambar']) ?>?t=<?= time() ?>"
                      alt="Gambar <?= e($wisata['nama_wisata']) ?>" class="img-thumbnail" style="width: 100px; height: auto; max-height:70px; object-fit: cover;">
                  <?php else: ?>
                    <span class="text-muted small">Tanpa Gambar</span>
                  <?php endif; ?>
                </td>
                <td><?= e(date('d M Y', strtotime($wisata['created_at'] ?? 'now'))) ?></td>
                <td>
                  <a href="<?= $base_url ?>admin/wisata/edit_wisata.php?id=<?= e($wisata['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Destinasi">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <a href="<?= $base_url ?>admin/wisata/hapus_wisata.php?id=<?= e($wisata['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Destinasi"
                    onclick="return confirm('Apakah Anda yakin ingin menghapus destinasi \" <?= e(addslashes($wisata['nama_wisata'])) ?>\" ini secara permanen?')">
                    <i class="fas fa-trash-alt"></i> Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4"> <!-- Colspan disesuaikan menjadi 8 -->
                <p class="mb-2 lead">Belum ada destinasi wisata yang ditambahkan.</p>
                <a href="<?= $base_url ?>admin/wisata/tambah_wisata.php" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus"></i> Tambah Destinasi Pertama Anda
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