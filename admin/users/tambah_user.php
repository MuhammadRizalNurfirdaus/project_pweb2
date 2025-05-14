<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\tambah_user.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di tambah_user.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Model User jika belum dimuat (untuk mengakses konstanta)
if (!class_exists('User')) {
    $userModelPath = MODELS_PATH . '/User.php';
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
    } else {
        error_log("PERINGATAN di tambah_user.php: Model User.php tidak ditemukan. Menggunakan daftar role/status default.");
    }
}

// 4. Set judul halaman
$pageTitle = "Tambah Pengguna Baru";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 6. Data untuk repopulasi form
$session_form_data_key = 'flash_form_data_tambah_user';
$input_nama = $_SESSION[$session_form_data_key]['nama'] ?? '';
$input_nama_lengkap = $_SESSION[$session_form_data_key]['nama_lengkap'] ?? '';
$input_email = $_SESSION[$session_form_data_key]['email'] ?? '';
$input_no_hp = $_SESSION[$session_form_data_key]['no_hp'] ?? '';
$input_alamat = $_SESSION[$session_form_data_key]['alamat'] ?? '';
$input_role = $_SESSION[$session_form_data_key]['role'] ?? 'user';
$input_status_akun = $_SESSION[$session_form_data_key]['status_akun'] ?? 'aktif';

unset($_SESSION[$session_form_data_key]);

// Ambil daftar role dan status yang diizinkan dari Model User jika tersedia
// PERBAIKAN: Fallback untuk $allowed_roles disesuaikan
$allowed_roles = (class_exists('User') && defined('User::ALLOWED_ROLES')) ? User::ALLOWED_ROLES : ['user', 'admin'];
$allowed_account_statuses = (class_exists('User') && defined('User::ALLOWED_ACCOUNT_STATUSES')) ? User::ALLOWED_ACCOUNT_STATUSES : ['aktif', 'non-aktif', 'diblokir'];

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
        <h6 class="m-0 fw-bold text-primary">Formulir Tambah Pengguna Baru</h6>
    </div>
    <div class="card-body">
        <form action="<?= e(ADMIN_URL . '/users/proses_user.php') ?>" method="POST" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="tambah">

            <div class="mb-3">
                <label for="nama" class="form-label">Nama (Username/Login) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= e($input_nama) ?>" required>
                <small class="form-text text-muted">Digunakan untuk identifikasi singkat.</small>
            </div>

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= e($input_nama_lengkap) ?>">
                <small class="form-text text-muted">Nama lengkap formal pengguna (opsional).</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($input_email) ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6" aria-describedby="passwordHelp">
                <small id="passwordHelp" class="form-text text-muted">Minimal 6 karakter.</small>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
            </div>

            <div class="mb-3">
                <label for="no_hp" class="form-label">Nomor HP</label>
                <input type="tel" class="form-control" id="no_hp" name="no_hp" value="<?= e($input_no_hp) ?>" placeholder="Contoh: 08123456789">
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
                    <?php foreach ($allowed_account_statuses as $status_value): ?>
                        <option value="<?= e($status_value) ?>" <?= ($input_status_akun === $status_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst(str_replace('_', ' ', $status_value))) ?>
                        </option>
                    <?php endforeach; ?>
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