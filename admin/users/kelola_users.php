<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\kelola_users.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di kelola_users.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di kelola_users.php: Fungsi require_admin() tidak ditemukan.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia. (Error Code: ADM_AUTH_NF_KUSR)");
}
require_admin();

// 3. Pastikan Model User dan metode yang dibutuhkan ada
if (!class_exists('User') || !method_exists('User', 'getAll') || !method_exists('User', 'getLastError')) {
    error_log("FATAL ERROR di kelola_users.php: Model User atau metode getAll/getLastError tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat (MUSR_GA_NF_KUSR).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// 4. Set judul halaman
$pageTitle = "Kelola Pengguna";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Ambil semua data pengguna
$users = [];
$error_message_fetch_users = null;

try {
    $users = User::getAll('id ASC');
    if ($users === false) {
        $users = [];
        $modelError = User::getLastError();
        $error_message_fetch_users = "Gagal mengambil data pengguna dari database.";
        if ($modelError) {
            $error_message_fetch_users .= " Detail Sistem: " . e($modelError);
        }
        error_log("Error di kelola_users.php saat User::getAll() mengembalikan false: " . $error_message_fetch_users);
    }
} catch (Exception $e) {
    $users = [];
    $error_message_fetch_users = "Terjadi kesalahan teknis saat mengambil data pengguna: " . e($e->getMessage());
    error_log("Exception di kelola_users.php saat User::getAll(): " . $e->getMessage());
}

$current_user_logged_in_id = get_current_user_id();

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-users-cog"></i> Kelola Pengguna</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Daftar Pengguna</h1>
    <a href="<?= e(ADMIN_URL . 'users/tambah_user.php') ?>" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Tambah Pengguna Baru
    </a>
</div>

<?php display_flash_message(); ?>

<?php if ($error_message_fetch_users && empty($users)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error_message_fetch_users) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-users me-2"></i>Data Pengguna Terdaftar</h6>
        <span class="badge bg-info rounded-pill"><?= count($users) ?> Pengguna</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTableUsers" width="100%" cellspacing="0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Nama (Username)</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th>Tanggal Daftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php
                        // Generate CSRF token sekali di luar loop untuk digunakan di semua link aksi GET
                        // Fungsi generate_csrf_token() akan membuat atau mengambil dari session
                        $csrf_token_for_get_actions = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
                        ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="text-center align-middle"><?= e($user['id']) ?></td>
                                <td class="align-middle"><?= e($user['nama'] ?? 'N/A') ?></td>
                                <td class="align-middle"><?= e($user['nama_lengkap'] ?? '-') ?></td>
                                <td class="align-middle"><?= e($user['email']) ?></td>
                                <td class="align-middle"><?= e($user['no_hp'] ?? '-') ?></td>
                                <td class="align-middle">
                                    <?php
                                    $role_class = 'primary';
                                    $user_role_lower = strtolower($user['role'] ?? '');
                                    if ($user_role_lower === 'admin') {
                                        $role_class = 'danger';
                                    } elseif ($user_role_lower === 'user') {
                                        $role_class = 'success';
                                    }
                                    ?>
                                    <span class="badge rounded-pill bg-<?= e($role_class) ?>">
                                        <?= e(ucfirst($user['role'] ?? 'Tidak Diketahui')) ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <?= getStatusBadgeClassHTML($user['status_akun'] ?? 'tidak diketahui', 'Tidak Diketahui') ?>
                                </td>
                                <td class="align-middle"><?= e(formatTanggalIndonesia($user['created_at'] ?? null, true, false)) ?></td>
                                <td class="text-center align-middle">
                                    <a href="<?= e(ADMIN_URL . 'users/edit_user.php?id=' . $user['id']) ?>" class="btn btn-warning btn-sm my-1" title="Edit Pengguna">
                                        <i class="fas fa-edit fa-xs"></i>
                                    </a>

                                    <a href="<?= e(ADMIN_URL . 'users/reset_password_user.php?id=' . $user['id']) ?>&csrf_token=<?= e($csrf_token_for_get_actions) ?>"
                                        class="btn btn-info btn-sm my-1" title="Reset Password Pengguna"
                                        onclick="return confirm('Apakah Anda yakin ingin mereset password untuk pengguna \'<?= e(addslashes($user['nama_lengkap'] ?? $user['nama'] ?? 'ini')) ?>\'?');">
                                        <i class="fas fa-key fa-xs"></i>
                                    </a>

                                    <?php
                                    if ($current_user_logged_in_id != $user['id'] && $user['id'] != 1):
                                    ?>
                                        <a href="<?= e(ADMIN_URL . 'users/hapus_user.php?id=' . $user['id']) ?>&csrf_token=<?= e($csrf_token_for_get_actions) ?>"
                                            class="btn btn-danger btn-sm my-1" title="Hapus Pengguna"
                                            onclick="return confirm('PERHATIAN: Apakah Anda yakin ingin menghapus pengguna \'<?= e(addslashes($user['nama_lengkap'] ?? $user['nama'] ?? 'ini')) ?>\'? Tindakan ini tidak dapat diurungkan dan mungkin mempengaruhi data terkait.');">
                                            <i class="fas fa-trash fa-xs"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <?php if ($error_message_fetch_users): ?>
                                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= e($error_message_fetch_users) ?></p>
                                <?php else: ?>
                                    <p>Belum ada data pengguna yang terdaftar.</p>
                                    <a href="<?= e(ADMIN_URL . 'users/tambah_user.php') ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Tambah Pengguna Pertama
                                    </a>
                                <?php endif; ?>
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