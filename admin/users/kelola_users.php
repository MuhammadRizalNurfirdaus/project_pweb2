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

// 3. Sertakan Model User
if (!class_exists('User')) {
    $userModelPath = MODELS_PATH . '/User.php';
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
        // Jika User::setDbConnection belum dipanggil di config.php, dan $conn tersedia:
        // if (isset($conn) && $conn instanceof mysqli && method_exists('User', 'setDbConnection')) {
        //     User::setDbConnection($conn);
        // }
    } else {
        error_log("FATAL ERROR di kelola_users.php: Model User.php tidak ditemukan di " . $userModelPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat.');
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
    if (method_exists('User', 'getAll')) {
        $users = User::getAll();
        if ($users === false) {
            $error_users = "Gagal mengambil data pengguna. " . (method_exists('User', 'getLastError') ? User::getLastError() : 'Error tidak diketahui dari model.');
            $users = [];
        }
    } else {
        $error_users = "Metode User::getAll() tidak ditemukan.";
        error_log("Error di kelola_users.php: Metode User::getAll() tidak ada.");
        $users = []; // Inisialisasi sebagai array kosong jika metode tidak ada
    }
} catch (Exception $e) {
    $error_users = "Terjadi exception saat mengambil data pengguna: " . $e->getMessage();
    error_log("Error di kelola_users.php saat User::getAll(): " . $e->getMessage());
    $users = [];
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
    <a href="<?= e(ADMIN_URL . '/users/tambah_user.php') ?>" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Tambah Pengguna Baru
    </a>
</div>

<?php // display_flash_message() sudah dipanggil di header_admin.php 
?>

<?php if ($error_users): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_users) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Data Pengguna Terdaftar</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTableUsers" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e($user['id']) ?></td>
                                <td><?= e($user['nama'] ?? 'N/A') ?></td>
                                <td><?= e($user['nama_lengkap'] ?? '-') ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td><?= e($user['no_hp'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $role_class = 'primary'; // Default untuk 'user'
                                    if (strtolower($user['role']) === 'admin') {
                                        $role_class = 'danger';
                                    }
                                    // Anda bisa menambahkan kondisi lain jika ada role lain
                                    // elseif (strtolower($user['role']) === 'editor') {
                                    //    $role_class = 'warning text-dark';
                                    // }
                                    ?>
                                    <span class="badge rounded-pill bg-<?= e($role_class) ?>">
                                        <?= e(ucfirst($user['role'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= e(strtolower($user['status_akun'] ?? 'aktif') === 'aktif' ? 'success' : 'secondary') ?>">
                                        <?= e(ucfirst($user['status_akun'] ?? 'Aktif')) ?>
                                    </span>
                                </td>
                                <td><?= e(formatTanggalIndonesia($user['created_at'] ?? null, true, true)) ?></td>
                                <td>
                                    <a href="<?= e(ADMIN_URL . '/users/edit_user.php?id=' . $user['id']) ?>" class="btn btn-warning btn-sm my-1" title="Edit Pengguna">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php // Tombol reset password (Anda perlu membuat halaman/logika untuk ini) 
                                    ?>
                                    <a href="<?= e(ADMIN_URL . '/users/reset_password_user.php?id=' . $user['id']) ?>&csrf_token=<?= e(generate_csrf_token()) // Perlu CSRF untuk aksi ini 
                                                                                                                                    ?>"
                                        class="btn btn-info btn-sm my-1" title="Reset Password"
                                        onclick="return confirm('Apakah Anda yakin ingin mereset password untuk pengguna <?= e($user['nama_lengkap'] ?? $user['nama'] ?? 'ini') ?>? Password baru akan digenerate atau dikirim ke email pengguna (tergantung implementasi).');">
                                        <i class="fas fa-key"></i>
                                    </a>

                                    <?php if (get_current_user_id() != $user['id'] && $user['id'] != 1): // Admin utama (ID 1) & diri sendiri tidak bisa dihapus 
                                    ?>
                                        <a href="<?= e(ADMIN_URL . '/users/hapus_user.php?id=' . $user['id']) ?>&csrf_token=<?= e(generate_csrf_token()) ?>"
                                            class="btn btn-danger btn-sm my-1" title="Hapus Pengguna"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna \'<?= e($user['nama_lengkap'] ?? $user['nama'] ?? 'ini') ?>\'? Tindakan ini tidak dapat diurungkan.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">Belum ada data pengguna.</td>
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