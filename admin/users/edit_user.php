<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\edit_user.php

if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Kesalahan konfigurasi server.");
}
require_admin();

if (!class_exists('User')) {
    require_once MODELS_PATH . '/User.php';
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

$user = User::findById($user_id);
if (!$user) {
    set_flash_message('danger', "Pengguna dengan ID {$user_id} tidak ditemukan.");
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

$pageTitle = "Edit Pengguna: " . e($user['nama_lengkap'] ?? $user['nama'] ?? 'N/A');
require_once ROOT_PATH . '/template/header_admin.php';

// Untuk repopulasi form jika ada error validasi dari proses_user.php
$input_nama = $_SESSION['flash_form_data']['nama_lengkap'] ?? ($user['nama_lengkap'] ?? $user['nama'] ?? '');
$input_email = $_SESSION['flash_form_data']['email'] ?? $user['email'];
$input_no_hp = $_SESSION['flash_form_data']['no_hp'] ?? $user['no_hp'];
$input_alamat = $_SESSION['flash_form_data']['alamat'] ?? $user['alamat'];
$input_role = $_SESSION['flash_form_data']['role'] ?? $user['role'];
$input_status_akun = $_SESSION['flash_form_data']['status_akun'] ?? ($user['status_akun'] ?? 'aktif');
unset($_SESSION['flash_form_data']);

$allowed_roles = ['user', 'admin', 'editor']; // Sesuaikan
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/users/kelola_users.php') ?>"><i class="fas fa-users-cog"></i> Kelola Pengguna</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-edit"></i> Edit Pengguna</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Formulir Edit Pengguna: <?= e($user['nama_lengkap'] ?? $user['nama'] ?? '') ?></h6>
    </div>
    <div class="card-body">
        <form action="<?= e(ADMIN_URL . '/users/proses_user.php') ?>" method="POST">
            <?php echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" value="<?= e($user_id) ?>">

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= e($input_nama) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($input_email) ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Baru (Opsional)</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6">
                <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter.</small>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
            </div>

            <div class="mb-3">
                <label for="no_hp" class="form-label">Nomor HP</label>
                <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= e($input_no_hp) ?>">
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3"><?= e($input_alamat) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                <select class="form-select" id="role" name="role" required <?= (get_current_user_id() == $user_id && $user_id == 1) ? 'disabled title="Role admin utama tidak dapat diubah"' : '' // Admin utama tidak bisa mengubah rolenya sendiri 
                                                                            ?>>
                    <?php foreach ($allowed_roles as $role_value): ?>
                        <option value="<?= e($role_value) ?>" <?= ($input_role === $role_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst($role_value)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (get_current_user_id() == $user_id && $user_id == 1): // Jika admin utama, tambahkan input hidden untuk role 
                ?>
                    <input type="hidden" name="role" value="<?= e($user['role']) ?>">
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="status_akun" class="form-label">Status Akun <span class="text-danger">*</span></label>
                <select class="form-select" id="status_akun" name="status_akun" required <?= (get_current_user_id() == $user_id && $user_id == 1) ? 'disabled title="Status admin utama tidak dapat diubah"' : '' ?>>
                    <option value="aktif" <?= ($input_status_akun === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                    <option value="non-aktif" <?= ($input_status_akun === 'non-aktif') ? 'selected' : '' ?>>Non-Aktif</option>
                </select>
                <?php if (get_current_user_id() == $user_id && $user_id == 1): ?>
                    <input type="hidden" name="status_akun" value="<?= e($user['status_akun'] ?? 'aktif') ?>">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Update Pengguna</button>
            <a href="<?= e(ADMIN_URL . '/users/kelola_users.php') ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>