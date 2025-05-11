<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\contact\kelola_contact.php

// 1. Sertakan konfigurasi utama dan Controller
require_once __DIR__ . '/../../config/config.php'; // Naik dua level ke config
require_once __DIR__ . '/../../controllers/ContactController.php'; // Naik dua level ke controllers

// require_admin(); // Pastikan hanya admin yang bisa akses

$page_title = "Kelola Pesan Kontak";

// 2. Sertakan header admin dengan path yang benar
// Path dari admin/contact/ ke template/ adalah ../../template/
if (!@include_once __DIR__ . '/../../template/header_admin.php') {
    http_response_code(500);
    error_log("FATAL: Gagal memuat template/header_admin.php dari admin/contact/kelola_contact.php.");
    echo "<div style='color:red;font-family:sans-serif;padding:20px;'>Error: Header admin tidak dapat dimuat. Periksa path file.</div>";
    exit;
}

// 3. Ambil data kontak melalui Controller
// Asumsi ada method getAll() di ContactController yang memanggil Model
$daftar_kontak = ContactController::getAll(); // Ini akan mengembalikan array

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-envelope-open-text"></i> Kelola Pesan Kontak</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Pesan Kontak Masuk</h1>
    <!-- Tidak ada tombol tambah untuk pesan kontak biasanya -->
</div>

<?php
// Menampilkan flash message (jika ada, misal setelah menghapus pesan)
if (function_exists('display_flash_message')) {
    echo display_flash_message();
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-inbox me-2"></i>Daftar Pesan Diterima</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;">ID</th>
                        <th scope="col" style="width: 20%;">Nama Pengirim</th>
                        <th scope="col" style="width: 20%;">Email</th>
                        <th scope="col">Pesan</th>
                        <th scope="col" style="width: 15%;">Tanggal Diterima</th>
                        <th scope="col" style="width: 10%;">Aksi</th> <!-- Kolom Aksi ditambahkan -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_kontak) && is_array($daftar_kontak)): ?>
                        <?php foreach ($daftar_kontak as $kontak): ?>
                            <tr>
                                <th scope="row"><?= e($kontak['id']) ?></th>
                                <td><?= e($kontak['nama']) ?></td>
                                <td><a href="mailto:<?= e($kontak['email']) ?>"><?= e($kontak['email']) ?></a></td>
                                <td><?= nl2br(e($kontak['pesan'])) // nl2br untuk mempertahankan baris baru 
                                    ?></td>
                                <td><?= e(date('d M Y, H:i', strtotime($kontak['created_at']))) ?></td>
                                <td>
                                    <!-- Tambahkan tombol hapus jika diperlukan -->
                                    <a href="<?= $base_url ?>admin/contact/hapus_contact.php?id=<?= e($kontak['id']) ?>" class="btn btn-danger btn-sm" title="Hapus Pesan"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus pesan dari <?= e(addslashes($kontak['nama'])) ?> ini?')">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </a>
                                    <!-- Anda juga bisa menambahkan tombol "Tandai Sudah Dibaca" atau "Balas" jika ada fungsionalitasnya -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4"> <!-- Colspan disesuaikan menjadi 6 -->
                                <p class="mb-2 lead">Belum ada pesan kontak yang diterima.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 4. Sertakan footer admin dengan path yang benar
// Path dari admin/contact/ ke template/ adalah ../../template/
if (!@include_once __DIR__ . '/../../template/footer_admin.php') {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/contact/kelola_contact.php.");
}
?>