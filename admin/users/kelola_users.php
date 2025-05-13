<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\kelola_users.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_users.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Model User (diasumsikan User::setDbConnection sudah dipanggil di config.php)
if (!class_exists('User')) {
    $userModelPath = MODELS_PATH . '/User.php';
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
        // Jika Anda tidak yakin config.php sudah memanggil User::setDbConnection($conn)
        // Panggil di sini sebagai fallback, tapi idealnya hanya sekali di config.
        // if (isset($conn) && $conn instanceof mysqli && method_exists('User', 'setDbConnection')) {
        //     User::setDbConnection($conn);
        // }
    } else {
        error_log("FATAL ERROR di kelola_users.php: Model User.php tidak ditemukan.");
        set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak ditemukan.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}


// 4. Set judul halaman
$pageTitle = "Kelola Pengguna";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Ambil semua data pengguna
$users = [];
$error_users = null;
try {
    $users = User::getAll();
    if ($users === false) { // User::getAll() bisa return false jika query gagal total
        $error_users = "Gagal mengambil data pengguna. " . User::getLastError();
        $users = []; // Pastikan $users adalah array
    }
} catch (Exception $e) {
    $error_users = "Terjadi exception saat mengambil data pengguna: " . $e->getMessage();
    error_log("Error di kelola_users.php saat User::getAll(): " . $e->getMessage());
    $users = []; // Pastikan $users adalah array
}

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-users-cog"></i> Kelola Pengguna</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Daftar Pengguna</h1>
    <a href="<?= e(ADMIN_URL . '/users/tambah_user.php') ?>" class="btn btn-success"> <!-- Dihilangkan btn-icon-split jika tidak pakai CSS spesifiknya -->
        <i class="fas fa-plus me-1"></i> Tambah Pengguna Baru
    </a>
</div>

<?php // display_flash_message() sudah dipanggil di header_admin.php 
?>

<?php if ($error_users): ?>
    <div class="alert alert-danger"><?= e($error_users) ?></div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Data Pengguna Terdaftar</h6> <!-- Menggunakan fw-bold dari Bootstrap 5 -->
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTableUsers" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th> <!-- PERBAIKAN: Dari "Nama Lengkap" -->
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Role</th>
                        <!-- <th>Status Akun</th>  DIKOMENTARI JIKA KOLOM TIDAK ADA -->
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e($user['id']) ?></td>
                                <td><?= e($user['nama'] ?? 'N/A') ?></td> <!-- PERBAIKAN: Menggunakan 'nama' -->
                                <td><?= e($user['email']) ?></td>
                                <td><?= e($user['no_hp'] ?? '-') ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= e($user['role'] === 'admin' ? 'danger' : 'primary') ?>">
                                        <?= e(ucfirst($user['role'])) ?>
                                    </span>
                                </td>
                                <!-- Jika Anda menambahkan kolom status_akun di DB dan Model: -->
                                <!--
                                <td>
                                    <span class="badge rounded-pill bg-<?= e(($user['status_akun'] ?? 'aktif') === 'aktif' ? 'success' : 'warning text-dark') ?>">
                                        <?= e(ucfirst($user['status_akun'] ?? 'Aktif')) ?>
                                    </span>
                                </td>
                                -->
                                <td><?= e(formatTanggalIndonesia($user['created_at'] ?? null, true)) ?></td>
                                <td>
                                    <a href="<?= e(ADMIN_URL . '/users/edit_user.php?id=' . $user['id']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (get_current_user_id() != $user['id'] && $user['id'] != 1): // Admin utama (ID 1) juga tidak bisa dihapus 
                                    ?>
                                        <a href="<?= e(ADMIN_URL . '/users/hapus_user.php?id=' . $user['id']) ?>&csrf_token=<?= e(generate_csrf_token()) ?>"
                                            class="btn btn-danger btn-sm" title="Hapus"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna <?= e($user['nama'] ?? '') ?>? Tindakan ini tidak dapat diurungkan.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <!-- PERBAIKAN: colspan disesuaikan jika kolom status_akun dihilangkan -->
                            <td colspan="7" class="text-center">Belum ada data pengguna.</td>
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