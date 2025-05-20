<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\edit_user.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di edit_user.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Model User
if (!class_exists('User') || !method_exists('User', 'findById') || !defined('User::ALLOWED_ROLES') || !defined('User::ALLOWED_ACCOUNT_STATUSES')) {
    error_log("FATAL ERROR di edit_user.php: Kelas User, metode findById, atau konstanta tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat (MUSR_FID_NF_EDIT).');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 4. Ambil dan validasi user ID
$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id_to_edit <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid untuk diedit.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 5. Ambil data pengguna yang akan diedit
$user_to_edit = User::findById($user_id_to_edit);

if (!$user_to_edit) {
    set_flash_message('danger', "Pengguna dengan ID {$user_id_to_edit} tidak ditemukan.");
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 6. Set judul halaman
$pageTitle = "Edit Pengguna: " . e($user_to_edit['nama_lengkap'] ?? $user_to_edit['nama'] ?? 'ID: ' . $user_id_to_edit);

// 7. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 8. Data untuk repopulasi form
$session_form_data_key = 'flash_form_data_edit_user_' . $user_id_to_edit;

$input_nama = $_SESSION[$session_form_data_key]['nama'] ?? $user_to_edit['nama'] ?? '';
$input_nama_lengkap = $_SESSION[$session_form_data_key]['nama_lengkap'] ?? $user_to_edit['nama_lengkap'] ?? '';
$input_email = $_SESSION[$session_form_data_key]['email'] ?? $user_to_edit['email'] ?? '';
$input_no_hp = $_SESSION[$session_form_data_key]['no_hp'] ?? $user_to_edit['no_hp'] ?? '';
$input_alamat = $_SESSION[$session_form_data_key]['alamat'] ?? $user_to_edit['alamat'] ?? '';
$input_role = $_SESSION[$session_form_data_key]['role'] ?? $user_to_edit['role'] ?? 'user';
$input_status_akun = $_SESSION[$session_form_data_key]['status_akun'] ?? $user_to_edit['status_akun'] ?? 'aktif';

if (isset($_SESSION[$session_form_data_key])) {
    unset($_SESSION[$session_form_data_key]);
}

$allowed_roles = User::ALLOWED_ROLES;
$allowed_account_statuses = User::ALLOWED_ACCOUNT_STATUSES;

$current_user_logged_in_id = get_current_user_id();
$is_editing_self = ($current_user_logged_in_id == $user_id_to_edit);
$is_primary_admin_being_edited = ($user_id_to_edit == 1); // Asumsi ID 1 adalah admin utama

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'users/kelola_users.php') ?>"><i class="fas fa-users-cog"></i> Kelola Pengguna</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-edit"></i> Edit Pengguna: <?= e($user_to_edit['nama'] ?? '') ?></li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Formulir Edit Pengguna: <?= e($user_to_edit['nama_lengkap'] ?? $user_to_edit['nama']) ?></h6>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>
        <form action="<?= e(ADMIN_URL . 'users/proses_user.php') ?>" method="POST" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" value="<?= e($user_id_to_edit) ?>">

            <div class="mb-3">
                <label for="nama" class="form-label">Nama (Username/Login) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= e($input_nama) ?>" required>
                <div class="invalid-feedback">Nama pengguna wajib diisi.</div>
            </div>

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= e($input_nama_lengkap) ?>">
                <small class="form-text text-muted">Nama lengkap formal pengguna (opsional).</small>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($input_email) ?>" required>
                <div class="invalid-feedback">Alamat email wajib diisi dengan format yang benar.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Baru (Opsional)</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6" aria-describedby="passwordHelpEdit">
                <small id="passwordHelpEdit" class="form-text text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter.</small>
                <div class="invalid-feedback">Password baru minimal 6 karakter.</div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                <div class="invalid-feedback">Konfirmasi password baru wajib diisi dan harus cocok jika password baru diisi.</div>
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
                <select class="form-select" id="role" name="role" required <?= ($is_primary_admin_being_edited && $current_user_logged_in_id == 1) ? 'disabled title="Role admin utama tidak dapat diubah."' : '' ?>>
                    <?php foreach ($allowed_roles as $role_value): ?>
                        <option value="<?= e($role_value) ?>" <?= ($input_role === $role_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst($role_value)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($is_primary_admin_being_edited && $current_user_logged_in_id == 1): // Jika admin utama diedit oleh dirinya sendiri, kirim nilai asli agar tidak error 
                ?>
                    <input type="hidden" name="role" value="<?= e($user_to_edit['role']) ?>">
                    <small class="form-text text-muted">Role admin utama tidak dapat diubah.</small>
                <?php endif; ?>
                <div class="invalid-feedback">Pilih role pengguna.</div>
            </div>

            <div class="mb-3">
                <label for="status_akun" class="form-label">Status Akun <span class="text-danger">*</span></label>
                <select class="form-select" id="status_akun" name="status_akun" required <?= ($is_primary_admin_being_edited && $current_user_logged_in_id == 1) ? 'disabled title="Status akun admin utama tidak dapat diubah."' : '' ?>>
                    <?php foreach ($allowed_account_statuses as $status_value): ?>
                        <option value="<?= e($status_value) ?>" <?= ($input_status_akun === $status_value) ? 'selected' : '' ?>>
                            <?= e(ucfirst(str_replace('_', ' ', $status_value))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($is_primary_admin_being_edited && $current_user_logged_in_id == 1): ?>
                    <input type="hidden" name="status_akun" value="<?= e($user_to_edit['status_akun']) ?>">
                    <small class="form-text text-muted">Status akun admin utama tidak dapat diubah.</small>
                <?php endif; ?>
                <div class="invalid-feedback">Pilih status akun.</div>
            </div>

            <button type="submit" name="submit_edit_user" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Pengguna</button>
            <a href="<?= e(ADMIN_URL . 'users/kelola_users.php') ?>" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Batal</a>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>

<script>
    // Script validasi Bootstrap dasar
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    const password = form.querySelector('#password');
                    const confirmPassword = form.querySelector('#confirm_password');

                    // Hanya validasi konfirmasi jika password baru diisi
                    if (password && password.value !== "" && confirmPassword && password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Password Baru dan Konfirmasi Password Baru tidak cocok.');
                    } else if (confirmPassword) {
                        confirmPassword.setCustomValidity('');
                    }
                    // Pastikan konfirmasi password wajib diisi HANYA jika password baru diisi
                    if (password && password.value !== "" && confirmPassword && confirmPassword.value === "") {
                        confirmPassword.setCustomValidity('Konfirmasi password baru wajib diisi jika password baru diisi.');
                    } else if (password && password.value === "" && confirmPassword) {
                        confirmPassword.setCustomValidity(''); // Tidak wajib jika password baru kosong
                    }


                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })();
</script>