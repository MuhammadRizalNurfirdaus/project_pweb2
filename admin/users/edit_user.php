<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\edit_user.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di edit_user.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Model User
if (!class_exists('User')) {
    $userModelPath = MODELS_PATH . '/User.php';
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
    } else {
        error_log("FATAL ERROR di edit_user.php: Model User.php tidak ditemukan di " . $userModelPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
        exit;
    }
}

// 4. Ambil dan validasi user ID
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 5. Ambil data pengguna yang akan diedit
$user_to_edit = User::findById($user_id); // User::findById sudah di-set koneksinya via config.php

if (!$user_to_edit) {
    set_flash_message('danger', "Pengguna dengan ID {$user_id} tidak ditemukan.");
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 6. Set judul halaman
$pageTitle = "Edit Pengguna: " . e($user_to_edit['nama_lengkap'] ?? $user_to_edit['nama'] ?? 'N/A');

// 7. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 8. Data untuk repopulasi form
$session_form_data_key = 'flash_form_data_edit_user_' . $user_id;

$input_nama = $_SESSION[$session_form_data_key]['nama'] ?? $user_to_edit['nama'] ?? '';
$input_nama_lengkap = $_SESSION[$session_form_data_key]['nama_lengkap'] ?? $user_to_edit['nama_lengkap'] ?? '';
$input_email = $_SESSION[$session_form_data_key]['email'] ?? $user_to_edit['email'] ?? '';
$input_no_hp = $_SESSION[$session_form_data_key]['no_hp'] ?? $user_to_edit['no_hp'] ?? '';
$input_alamat = $_SESSION[$session_form_data_key]['alamat'] ?? $user_to_edit['alamat'] ?? '';
$input_role = $_SESSION[$session_form_data_key]['role'] ?? $user_to_edit['role'] ?? 'user';
$input_status_akun = $_SESSION[$session_form_data_key]['status_akun'] ?? $user_to_edit['status_akun'] ?? 'aktif';

unset($_SESSION[$session_form_data_key]);

// Ambil daftar role dan status yang diizinkan dari Model User jika tersedia
// PERBAIKAN: Fallback untuk $allowed_roles disesuaikan
$allowed_roles = (class_exists('User') && defined('User::ALLOWED_ROLES')) ? User::ALLOWED_ROLES : ['user', 'admin'];
$allowed_account_statuses = (class_exists('User') && defined('User::ALLOWED_ACCOUNT_STATUSES')) ? User::ALLOWED_ACCOUNT_STATUSES : ['aktif', 'non-aktif', 'diblokir'];

$is_editing_self = (get_current_user_id() == $user_id);
$is_primary_admin = ($user_id == 1);

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
        <h6 class="m-0 fw-bold text-primary">Formulir Edit Pengguna: <?= e($user_to_edit['nama_lengkap'] ?? $user_to_edit['nama']) ?></h6>
    </div>
    <div class="card-body">
        <form action="<?= e(ADMIN_URL . '/users/proses_user.php') ?>" method="POST">
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" value="<?= e($user_id) ?>">

            <div class="mb-3">
                <label for="nama" class="form-label">Nama (Username/Panggilan) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= e($input_nama) ?>" required>
            </div>

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= e($input_nama_lengkap) ?>">
                <small class="form-text text-muted">Kosongkan jika sama dengan Nama di atas.</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($input_email) ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Baru (Opsional)</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6" aria-describedby="passwordHelp">
                <small id="passwordHelp" class="form-text text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter.</small>
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
                <select class="form-select" id="role" name="role" required <?= ($is_primary_admin && $is_editing_self) ? 'disabled title="Role admin utama tidak dapat diubah"' : '' ?>>
                    <?php foreach ($allowed_roles as $role_value): ?>
                        <option value="<?= e($role_value) ?>" <?= ($input_role === $role_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst($role_value)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($is_primary_admin && $is_editing_self): ?>
                    <input type="hidden" name="role" value="<?= e($user_to_edit['role']) ?>">
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="status_akun" class="form-label">Status Akun <span class="text-danger">*</span></label>
                <select class="form-select" id="status_akun" name="status_akun" required <?= ($is_primary_admin && $is_editing_self) ? 'disabled title="Status admin utama tidak dapat diubah"' : '' ?>>
                    <?php foreach ($allowed_account_statuses as $status_value): ?>
                        <option value="<?= e($status_value) ?>" <?= ($input_status_akun === $status_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst(str_replace('_', ' ', $status_value))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($is_primary_admin && $is_editing_self): ?>
                    <input type="hidden" name="status_akun" value="<?= e($user_to_edit['status_akun'] ?? 'aktif') ?>">
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