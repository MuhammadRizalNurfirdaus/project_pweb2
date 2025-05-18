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
// Kita akan utamakan penggunaan Model secara langsung di sini untuk kelola,
// kecuali ada logika bisnis kompleks di Controller.
// Config.php sudah memastikan Model Wisata dimuat dan diinisialisasi.
if (class_exists('Wisata') && method_exists('Wisata', 'getAll')) {
  $data_source_ok = true;
}

if (!$data_source_ok) {
  error_log("FATAL ERROR di kelola_wisata.php: Model Wisata atau metode getAll() tidak tersedia.");
  set_flash_message('danger', 'Kesalahan sistem: Komponen inti untuk data destinasi wisata tidak dapat dimuat.');
  redirect(ADMIN_URL . 'dashboard.php');
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
  // Langsung panggil Model Wisata::getAll()
  $wisata_list = Wisata::getAll('nama ASC'); // Menggunakan nama kolom 'nama' dari DB untuk ORDER BY

  if ($wisata_list === false) { // Jika Model mengembalikan false karena error query
    $wisata_list = []; // Pastikan array untuk view
    $db_error_detail = Wisata::getLastError(); // Ambil error dari Model
    $error_wisata = "Gagal mengambil data destinasi wisata." . (!empty($db_error_detail) ? " Detail: " . e($db_error_detail) : " Periksa log server.");
    error_log("Error di kelola_wisata.php saat Wisata::getAll(): " . $error_wisata);
  }
} catch (Exception $e) {
  $error_wisata = "Terjadi exception saat mengambil data destinasi wisata: " . e($e->getMessage());
  error_log("Error di kelola_wisata.php saat mengambil data wisata (Exception): " . $e->getMessage());
  $wisata_list = [];
}

// Logging untuk debugging jika $wisata_list kosong padahal ada data di DB
if (empty($wisata_list) && !$error_wisata) {
  error_log("Info di kelola_wisata.php: \$wisata_list kosong setelah pengambilan data dan tidak ada error terdeteksi di halaman ini. Periksa Model Wisata::getAll() dan data di tabel 'wisata'.");
}
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi Wisata</li>
  </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Daftar Destinasi Wisata</h1>
  <div class="btn-toolbar mb-2 mb-md-0">
    <a href="<?= e(ADMIN_URL . 'wisata/tambah_wisata.php') ?>" class="btn btn-success">
      <i class="fas fa-plus me-1"></i> Tambah Destinasi Baru
    </a>
  </div>
</div>

<?php // display_flash_message() sudah dipanggil di header_admin.php 
?>

<?php if ($error_wisata && (!isset($_SESSION['flash_message']) || ($_SESSION['flash_message']['type'] ?? '') !== 'danger')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_wisata) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="card shadow mb-4">
  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Data Destinasi Tersedia</h6>
    <span class="badge bg-secondary rounded-pill"><?= is_array($wisata_list) ? count($wisata_list) : 0 ?> Destinasi</span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover" id="dataTableWisata" width="100%" cellspacing="0">
        <thead class="table-dark">
          <tr>
            <th style="width: 5%;" class="text-center">No.</th>
            <th style="width: 8%;" class="text-center">ID</th>
            <th>Nama Destinasi</th>
            <th>Deskripsi (Ringkasan)</th>
            <th style="width: 20%;">Lokasi</th>
            <th style="width: 15%;" class="text-center">Gambar</th>
            <th style="width: 15%;">Dibuat</th>
            <th style="width: 12%;" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($wisata_list)): ?>
            <?php $nomor = 1; ?>
            <?php foreach ($wisata_list as $item): ?>
              <tr>
                <td class="text-center"><?= $nomor++ ?></td>
                <td class="text-center"><?= e($item['id'] ?? 'N/A') ?></td>
                <?php // PERBAIKAN: Gunakan $item['nama'] bukan $item['nama_wisata'] 
                ?>
                <td><?= e($item['nama'] ?? 'Tanpa Nama') ?></td>
                <td><?= e(function_exists('excerpt') ? excerpt($item['deskripsi'] ?? '', 100) : mb_substr(strip_tags((string)($item['deskripsi'] ?? '')), 0, 100) . '...') ?></td>
                <td><?= e($item['lokasi'] ?? '-') ?></td>
                <td class="text-center">
                  <?php
                  $gambar_file_wisata = $item['gambar'] ?? '';
                  // Pastikan UPLOADS_WISATA_PATH dan UPLOADS_WISATA_URL terdefinisi di config.php
                  $gambar_path_wisata_fisik = defined('UPLOADS_WISATA_PATH') ? rtrim(UPLOADS_WISATA_PATH, '/\\') . DIRECTORY_SEPARATOR . $gambar_file_wisata : '';
                  $gambar_url_wisata_display = defined('UPLOADS_WISATA_URL') ? UPLOADS_WISATA_URL . rawurlencode($gambar_file_wisata) : (defined('BASE_URL') ? BASE_URL . 'public/uploads/wisata/' . rawurlencode($gambar_file_wisata) : '');
                  ?>
                  <?php if (!empty($gambar_file_wisata) && !empty($gambar_path_wisata_fisik) && file_exists($gambar_path_wisata_fisik) && is_file($gambar_path_wisata_fisik)): ?>
                    <img src="<?= e($gambar_url_wisata_display) ?>"
                      alt="<?= e($item['nama'] ?? 'Gambar Wisata') ?>" <?php // PERBAIKAN 
                                                                        ?>
                      class="img-thumbnail"
                      style="max-height: 70px; max-width: 100px; object-fit: cover; cursor: pointer;"
                      onclick="showImageModal('<?= e($gambar_url_wisata_display) ?>', '<?= e(addslashes($item['nama'] ?? 'Gambar Wisata')) ?>')"> <?php // PERBAIKAN 
                                                                                                                                                  ?>
                  <?php elseif (!empty($gambar_file_wisata)): ?>
                    <small class="text-danger" title="File tidak ditemukan. Path: <?= e($gambar_path_wisata_fisik) ?>"><i class="fas fa-image"></i> Tidak ada</small>
                  <?php else: ?>
                    <span class="text-muted fst-italic">N/A</span>
                  <?php endif; ?>
                </td>
                <td><?= e(function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($item['created_at'] ?? null, false, true) : ($item['created_at'] ?? '-')) ?></td>
                <td class="text-center">
                  <a href="<?= e(ADMIN_URL . 'wisata/edit_wisata.php?id=' . ($item['id'] ?? '')) ?>" class="btn btn-warning btn-sm my-1 mx-1" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="<?= e(ADMIN_URL . 'wisata/hapus_wisata.php?id=' . ($item['id'] ?? '')) ?>&csrf_token=<?= e(function_exists('generate_csrf_token') ? generate_csrf_token() : '') ?>"
                    class="btn btn-danger btn-sm my-1 mx-1" title="Hapus"
                    onclick="return confirm('Apakah Anda yakin ingin menghapus destinasi wisata \'<?= e(addslashes($item['nama'] ?? 'ini')) ?>\'? Ini juga akan menghapus jenis tiket yang terkait jika ada batasan.');"> <?php // PERBAIKAN 
                                                                                                                                                                                                                          ?>
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php elseif (!$error_wisata): ?>
            <tr>
              <td colspan="8" class="text-center py-4">
                <p class="mb-2">Belum ada destinasi wisata yang ditambahkan.</p>
                <a href="<?= e(ADMIN_URL . 'wisata/tambah_wisata.php') ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Destinasi Pertama
                </a>
              </td>
            </tr>
          <?php endif; ?>
          <?php if ($error_wisata && empty($wisata_list)): ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Gagal memuat data destinasi wisata.
                <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
                  <br><small>Detail: <?= e($error_wisata) ?></small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal untuk menampilkan gambar lebih besar -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imagePreviewModalLabel">Pratinjau Gambar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="imagePreviewSrc" class="img-fluid" alt="Pratinjau Gambar">
      </div>
    </div>
  </div>
</div>

<script>
  // Fungsi showImageModal (jika belum ada di file JS global yang dimuat footer_admin.php)
  if (typeof showImageModal === 'undefined') {
    function showImageModal(src, title) {
      const imagePreviewSrcEl = document.getElementById('imagePreviewSrc');
      const imagePreviewModalLabelEl = document.getElementById('imagePreviewModalLabel');
      const imagePreviewModalEl = document.getElementById('imagePreviewModal');
      if (imagePreviewSrcEl) imagePreviewSrcEl.src = src;
      if (imagePreviewModalLabelEl) imagePreviewModalLabelEl.textContent = title || 'Pratinjau Gambar';
      if (imagePreviewModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modalInstance = bootstrap.Modal.getInstance(imagePreviewModalEl);
        if (!modalInstance) {
          modalInstance = new bootstrap.Modal(imagePreviewModalEl);
        }
        modalInstance.show();
      }
    }
  }
  // Untuk DataTables (opsional)
  // document.addEventListener('DOMContentLoaded', function () {
  //     if (typeof $ !== 'undefined' && $.fn.DataTable) {
  //         $('#dataTableWisata').DataTable({"language": {"url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"}});
  //     }
  // });
</script>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>