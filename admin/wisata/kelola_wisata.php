<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\kelola_wisata.php

// 1. Sertakan konfigurasi
if (!require_once __DIR__ . '/../../config/config.php') {
  http_response_code(503);
  error_log("FATAL ERROR di kelola_wisata.php: Gagal memuat config.php.");
  exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Validasi ketersediaan Controller atau Model Wisata
$data_source_ok = false;
$use_controller_for_wisata = class_exists('WisataController') && method_exists('WisataController', 'getAllForAdmin');
$use_model_for_wisata = class_exists('Wisata') && method_exists('Wisata', 'getAll');

if ($use_controller_for_wisata || $use_model_for_wisata) {
  $data_source_ok = true;
}

if (!$data_source_ok) {
  error_log("FATAL ERROR di kelola_wisata.php: Tidak dapat menemukan metode untuk mengambil data wisata (Controller atau Model).");
  set_flash_message('danger', 'Kesalahan sistem: Komponen inti untuk data destinasi wisata tidak dapat dimuat.');
  redirect(ADMIN_URL . '/dashboard.php');
  exit;
}

// 4. Set judul halaman
$pageTitle = "Kelola Destinasi Wisata";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Ambil semua data destinasi wisata
$wisata_list = [];
$error_wisata = null;

try {
  if ($use_controller_for_wisata) {
    $wisata_list = WisataController::getAllForAdmin();
  } else {
    $wisata_list = Wisata::getAll('nama ASC'); // Menggunakan nama kolom 'nama' dari DB untuk ORDER BY
  }

  if ($wisata_list === false) {
    $wisata_list = [];
    $db_error_detail = '';
    if (class_exists('Wisata') && method_exists('Wisata', 'getLastError')) {
      $db_error_detail = Wisata::getLastError();
    }
    $error_wisata = "Gagal mengambil data destinasi wisata." . (!empty($db_error_detail) ? " Detail: " . $db_error_detail : " Periksa log server.");
    error_log("Error di kelola_wisata.php saat mengambil data wisata: " . $error_wisata);
  }
} catch (Exception $e) {
  $error_wisata = "Terjadi exception saat mengambil data destinasi wisata: " . $e->getMessage();
  error_log("Error di kelola_wisata.php saat mengambil data wisata (Exception): " . $e->getMessage());
  $wisata_list = [];
}

// Logging untuk debugging jika $wisata_list kosong padahal ada data di DB
if (empty($wisata_list) && !$error_wisata) {
  // Anda bisa menambahkan pengecekan di sini apakah ada data di DB secara manual jika perlu,
  // tapi log dari Model::getAll() seharusnya sudah memberi petunjuk jika query gagal.
  error_log("Info di kelola_wisata.php: \$wisata_list kosong setelah pengambilan data dan tidak ada error terdeteksi di halaman ini. Periksa log Model Wisata.");
}

?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi Wisata</li>
  </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Daftar Destinasi Wisata</h1>
  <div class="btn-toolbar mb-2 mb-md-0">
    <a href="<?= e(ADMIN_URL . '/wisata/tambah_wisata.php') ?>" class="btn btn-success">
      <i class="fas fa-plus me-1"></i> Tambah Destinasi Baru
    </a>
  </div>
</div>

<?php // display_flash_message() sudah dipanggil di header_admin.php 
?>

<?php if ($error_wisata): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_wisata) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Data Destinasi Tersedia</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover" id="dataTableWisata" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th style="width: 5%;">No.</th>
            <th style="width: 10%;">ID</th>
            <th>Nama Destinasi</th>
            <th>Deskripsi (Ringkasan)</th>
            <th style="width: 15%;">Lokasi</th>
            <th style="width: 15%;">Gambar</th>
            <th style="width: 10%;">Dibuat</th>
            <th style="width: 15%;" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($wisata_list)): ?>
            <?php $nomor = 1; ?>
            <?php foreach ($wisata_list as $item): ?>
              <tr>
                <td><?= $nomor++ ?></td>
                <td><?= e($item['id']) ?></td>
                <td><?= e($item['nama_wisata']) // Key ini berasal dari alias 'nama AS nama_wisata' di Model 
                    ?></td>
                <td><?= e(excerpt($item['deskripsi'] ?? '', 100)) ?></td>
                <td><?= e($item['lokasi'] ?? '-') ?></td>
                <td>
                  <?php
                  $gambar_file_wisata = $item['gambar'] ?? '';
                  $gambar_path_wisata_fisik = defined('UPLOADS_WISATA_PATH') ? (rtrim(UPLOADS_WISATA_PATH, '/\\') . DIRECTORY_SEPARATOR . $gambar_file_wisata) : '';
                  $gambar_url_wisata_display = defined('BASE_URL') ? (BASE_URL . 'public/uploads/wisata/' . $gambar_file_wisata) : '';
                  ?>
                  <?php if (!empty($gambar_file_wisata) && !empty($gambar_path_wisata_fisik) && file_exists($gambar_path_wisata_fisik) && is_file($gambar_path_wisata_fisik)): ?>
                    <img src="<?= e($gambar_url_wisata_display) ?>"
                      alt="<?= e($item['nama_wisata']) ?>"
                      class="img-thumbnail"
                      style="max-height: 80px; max-width: 120px; object-fit: cover;">
                    <?php // PERBAIKAN: max-height dan max-width diubah 
                    ?>
                  <?php elseif (!empty($gambar_file_wisata)): ?>
                    <small class="text-danger" title="Path dicek: <?= e($gambar_path_wisata_fisik) ?>"><i class="fas fa-image"></i> File: <?= e($gambar_file_wisata) ?> tidak ada.</small>
                  <?php else: ?>
                    <span class="text-muted fst-italic">N/A</span>
                  <?php endif; ?>
                </td>
                <td><?= e(formatTanggalIndonesia($item['created_at'] ?? null, false, true)) ?></td>
                <td class="text-center">
                  <a href="<?= e(ADMIN_URL . '/wisata/edit_wisata.php?id=' . $item['id']) ?>" class="btn btn-warning btn-sm my-1" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="<?= e(ADMIN_URL . '/wisata/hapus_wisata.php?id=' . $item['id']) ?>&csrf_token=<?= e(generate_csrf_token()) ?>"
                    class="btn btn-danger btn-sm my-1" title="Hapus"
                    onclick="return confirm('Apakah Anda yakin ingin menghapus destinasi wisata \'<?= e(addslashes($item['nama_wisata'])) ?>\'?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4">
                <p class="mb-2">Belum ada destinasi wisata yang ditambahkan.</p>
                <a href="<?= e(ADMIN_URL . '/wisata/tambah_wisata.php') ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Destinasi Pertama
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
require_once ROOT_PATH . '/template/footer_admin.php';
?>