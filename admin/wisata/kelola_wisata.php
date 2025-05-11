<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\kelola_wisata.php

// 1. Sertakan konfigurasi utama
if (!@require_once __DIR__ . '/../../config/config.php') {
  http_response_code(500);
  error_log("FATAL: Gagal memuat config.php dari admin/wisata/kelola_wisata.php");
  exit("Terjadi kesalahan kritis pada server. Mohon coba lagi nanti.");
}

// 2. Sertakan header admin
$page_title = "Kelola Data Wisata";
if (!@include_once __DIR__ . '/../../template/header_admin.php') {
  http_response_code(500);
  error_log("FATAL: Gagal memuat template/header_admin.php dari admin/wisata/kelola_wisata.php.");
  echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat.</div>";
  exit;
}

// 3. Ambil data wisata dari database
$query_wisata = null;
$daftar_wisata = [];

if (isset($conn) && $conn) {
  try {
    // HAPUS 'harga' dari SELECT karena tidak ada di tabel
    $sql = "SELECT id, nama, deskripsi, gambar, lokasi, created_at FROM wisata ORDER BY created_at DESC";
    $query_wisata = mysqli_query($conn, $sql);

    if ($query_wisata) {
      while ($row = mysqli_fetch_assoc($query_wisata)) {
        $daftar_wisata[] = $row;
      }
    } else {
      error_log("MySQLi Query Error (Wisata getAll): " . mysqli_error($conn));
      set_flash_message('danger', 'Gagal memuat data wisata: ' . mysqli_error($conn));
    }
  } catch (Throwable $e) { // Menangkap semua jenis error/exception
    error_log("Error mengambil data wisata di kelola_wisata.php: " . $e->getMessage());
    set_flash_message('danger', 'Terjadi kesalahan saat memuat data wisata.');
  }
} else {
  set_flash_message('danger', 'Koneksi database tidak tersedia untuk memuat data wisata.');
  error_log("Koneksi database tidak tersedia di kelola_wisata.php");
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-map-marked-alt"></i> Kelola Wisata</li>
  </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">Kelola Data Destinasi Wisata</h1>
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
    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Destinasi Wisata</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped">
        <thead class="table-dark">
          <tr>
            <th scope="col" style="width: 5%;">ID</th>
            <th scope="col" style="width: 25%;">Nama</th>
            <th scope="col">Deskripsi (Ringkasan)</th>
            <th scope="col" style="width: 15%;">Lokasi</th>
            <th scope="col" style="width: 10%;">Gambar</th>
            <th scope="col" style="width: 10%;">Dibuat</th>
            <th scope="col" style="width: 15%;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($daftar_wisata)): ?>
            <?php foreach ($daftar_wisata as $data): ?>
              <tr>
                <td><?= e($data['id']) ?></td>
                <td><?= e($data['nama']) ?></td>
                <td><?= nl2br(e(substr($data['deskripsi'], 0, 100))) . (strlen($data['deskripsi']) > 100 ? "..." : "") ?></td>
                <td><?= e($data['lokasi']) ?></td>
                <td>
                  <?php if (!empty($data['gambar'])): ?>
                    <img src="<?= $base_url ?>public/img/<?= e($data['gambar']) ?>"
                      class="img-thumbnail"
                      width="100"
                      alt="Gambar <?= e($data['nama']) ?>"
                      style="max-height: 70px; object-fit: cover;">
                  <?php else: ?>
                    <span class="text-muted">Tidak ada</span>
                  <?php endif; ?>
                </td>
                <td><?= e(date('d M Y', strtotime($data['created_at']))) ?></td>
                <td>
                  <a href="<?= $base_url ?>admin/wisata/edit_wisata.php?id=<?= e($data['id']) ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Edit Data Wisata">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <a href="<?= $base_url ?>admin/wisata/hapus_wisata.php?id=<?= e($data['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Data Wisata"
                    onclick="return confirm('Yakin ingin menghapus data wisata \'<?= e(addslashes($data['nama'])) ?>\' ini?')">
                    <i class="fas fa-trash-alt"></i> Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <!-- Sesuaikan colspan karena satu kolom dihapus -->
              <td colspan="7" class="text-center py-4">
                <p class="mb-1 lead">Belum ada data destinasi wisata.</p>
                <p class="mb-0">Anda bisa mulai dengan <a href="<?= $base_url ?>admin/wisata/tambah_wisata.php" class="alert-link">menambahkan destinasi baru</a>.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
if (!@include_once __DIR__ . '/../../template/footer_admin.php') {
  error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/wisata/kelola_wisata.php.");
}
?>