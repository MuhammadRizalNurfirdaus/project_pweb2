<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\tambah_user.php

if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Kesalahan konfigurasi server.");
}
require_admin();

$pageTitle = "Tambah Pengguna Baru";
require_once ROOT_PATH . '/template/header_admin.php';

// Untuk repopulasi form jika ada error validasi dari proses_user.php
$input_nama = $_SESSION['flash_form_data']['nama_lengkap'] ?? '';
$input_email = $_SESSION['flash_form_data']['email'] ?? '';
$input_no_hp = $_SESSION['flash_form_data']['no_hp'] ?? '';
$input_alamat = $_SESSION['flash_form_data']['alamat'] ?? '';
$input_role = $_SESSION['flash_form_data']['role'] ?? 'user';
unset($_SESSION['flash_form_data']);

$allowed_roles = ['user', 'admin', 'editor']; // Sesuaikan dengan role yang ada
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/users/kelola_users.php') ?>"><i class="fas fa-users-cog"></i> Kelola Pengguna</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-plus"></i> Tambah Pengguna</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Formulir Tambah Pengguna Baru</h6>
    </div>
    <div class="card-body">
        <form action="<?= e(ADMIN_URL . '/users/proses_user.php') ?>" method="POST">
            <?php echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="tambah">

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= e($input_nama) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($input_email) ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <small class="form-text text-muted">Minimal 6 karakter. Akan di-hash secara otomatis.</small>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
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
                <select class="form-select" id="role" name="role" required>
                    <?php foreach ($allowed_roles as $role_value): ?>
                        <option value="<?= e($role_value) ?>" <?= ($input_role === $role_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst($role_value)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="status_akun" class="form-label">Status Akun <span class="text-danger">*</span></label>
                <select class="form-select" id="status_akun" name="status_akun" required>
                    <option value="aktif" selected>Aktif</option>
                    <option value="non-aktif">Non-Aktif</option>
                </select>
            </div>


            <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            <a href="<?= e(ADMIN_URL . '/users/kelola_users.php') ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>