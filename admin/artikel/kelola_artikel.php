<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\kelola_artikel.php

// 1. Sertakan config.php dan Model Artikel
require_once __DIR__ . '/../../config/config.php'; // Menyediakan $base_url, helpers, session, $conn
require_once __DIR__ . '/../../models/Artikel.php';  // Model Artikel

// 2. Sertakan header admin (header_admin.php akan memanggil require_admin())
include_once __DIR__ . '/../template/header_admin.php';

// 3. Inisialisasi Model dan ambil data artikel
$artikel_model = new Artikel($conn); // $conn dari config.php
$daftar_artikel = $artikel_model->getAll(); // Mengambil semua artikel
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Daftar Artikel</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Artikel Baru
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover table-striped">
        <thead class="table-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">Judul</th>
                <th scope="col">Isi (Ringkasan)</th>
                <th scope="col">Gambar</th>
                <th scope="col">Tanggal Publikasi</th>
                <th scope="col" style="width: 15%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($daftar_artikel)): ?>
                <?php $nomor = 1; ?>
                <?php foreach ($daftar_artikel as $artikel): ?>
                    <tr>
                        <th scope="row"><?= $nomor++ ?></th>
                        <td><?= e($artikel['judul']) ?></td>
                        <td><?= e(substr(strip_tags($artikel['isi']), 0, 120)) ?>...</td>
                        <td>
                            <?php if (!empty($artikel['gambar'])): ?>
                                <img src="<?= $base_url ?>public/uploads/artikel/<?= e($artikel['gambar']) ?>"
                                    alt="Gambar <?= e($artikel['judul']) ?>" class="img-thumbnail" style="width: 100px; height: auto; object-fit: cover;">
                            <?php else: ?>
                                <span class="text-muted">Tanpa Gambar</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(date('d F Y, H:i', strtotime($artikel['created_at']))) ?> WIB</td>
                        <td>
                            <a href="<?= $base_url ?>admin/artikel/edit_artikel.php?id=<?= e($artikel['id']) ?>" class="btn btn-warning btn-sm me-1" title="Edit Artikel">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="<?= $base_url ?>admin/artikel/hapus_artikel.php?id=<?= e($artikel['id']) ?>" class="btn btn-danger btn-sm" title="Hapus Artikel"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus artikel \" <?= e(addslashes($artikel['judul'])) ?>\" ini secara permanen?')">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <p class="mb-2">Belum ada artikel yang ditambahkan.</p>
                        <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Buat Artikel Pertama Anda
                        </a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<!-- Akhir Konten Kelola Artikel -->

<?php
// 3. Sertakan footer admin
include_once __DIR__ . '/../template/footer_admin.php';
?>