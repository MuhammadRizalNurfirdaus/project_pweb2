<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\reset_password_user.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di reset_password_user.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Pastikan Model User dan metode yang dibutuhkan ada
if (!class_exists('User') || !method_exists('User', 'findById') || !method_exists('User', 'updatePassword') || !method_exists('User', 'getLastError')) {
    error_log("FATAL ERROR di reset_password_user.php: Model User atau metode penting tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat (MUSR_RP_NF).');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 4. Ambil dan Validasi ID Pengguna dari URL
$user_id_to_reset = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($user_id_to_reset <= 0) {
    set_flash_message('danger', 'ID Pengguna tidak valid untuk reset password.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 5. Validasi CSRF Token dari URL
// Nama token di URL adalah 'csrf_token'
$csrf_token_name_in_url = 'csrf_token';
if (!function_exists('verify_csrf_token') || !verify_csrf_token($csrf_token_name_in_url, true, 'GET')) { // true untuk unset, 'GET' karena dari URL
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi dari halaman Kelola Pengguna.');
    error_log("Reset Password User - Kegagalan Verifikasi CSRF (GET). Token diterima: " . ($_GET[$csrf_token_name_in_url] ?? 'TIDAK ADA'));
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 6. Ambil data pengguna yang akan direset passwordnya
$user_data = User::findById($user_id_to_reset);
if (!$user_data) {
    set_flash_message('danger', "Pengguna dengan ID {$user_id_to_reset} tidak ditemukan.");
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 7. Proteksi
$current_admin_id = get_current_user_id();
if ($user_id_to_reset === 1 && $current_admin_id !== 1) {
    set_flash_message('danger', 'Password admin utama (ID 1) tidak dapat direset oleh admin lain.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}
if ($current_admin_id === $user_id_to_reset && $user_id_to_reset !== 1) {
    set_flash_message('warning', 'Untuk mengubah password Anda sendiri, silakan gunakan menu Profil > Ganti Password.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 8. Proses form jika metode POST
if (is_post()) {
    // Validasi CSRF Token untuk POST dari form ini
    $csrf_token_name_form_post = 'csrf_token_reset_password_form'; // Nama token di form reset
    if (!function_exists('verify_csrf_token') || !verify_csrf_token($csrf_token_name_form_post, true, 'POST')) {
        set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF). Silakan coba lagi.');
        redirect(ADMIN_URL . 'users/reset_password_user.php?id=' . $user_id_to_reset);
        exit;
    }

    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (empty($new_password) || empty($confirm_new_password)) {
        set_flash_message('danger', 'Password Baru dan Konfirmasi Password Baru wajib diisi.');
    } elseif (strlen($new_password) < 6) {
        set_flash_message('danger', 'Password Baru minimal 6 karakter.');
    } elseif ($new_password !== $confirm_new_password) {
        set_flash_message('danger', 'Password Baru dan Konfirmasi Password Baru tidak cocok.');
    } else {
        if (User::updatePassword($user_id_to_reset, $new_password)) {
            set_flash_message('success', 'Password untuk pengguna "' . e($user_data['nama_lengkap'] ?? $user_data['nama']) . '" berhasil direset.');
            error_log("ADMIN ACTION: Password untuk user ID {$user_id_to_reset} (" . ($user_data['email'] ?? '') . ") direset oleh Admin ID: {$current_admin_id}.");
            redirect(ADMIN_URL . 'users/kelola_users.php');
            exit;
        } else {
            $model_error = User::getLastError();
            set_flash_message('danger', 'Gagal mereset password pengguna. ' . ($model_error ? e($model_error) : 'Kesalahan sistem.'));
            error_log("Gagal reset password user ID {$user_id_to_reset} oleh Admin ID {$current_admin_id}. Model Error: " . ($model_error ?? 'Tidak ada info'));
        }
    }
    redirect(ADMIN_URL . 'users/reset_password_user.php?id=' . $user_id_to_reset);
    exit;
}

$pageTitle = "Reset Password Pengguna: " . e($user_data['nama_lengkap'] ?? $user_data['nama']);
require_once ROOT_PATH . '/template/header_admin.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'users/kelola_users.php') ?>"><i class="fas fa-users-cog"></i> Kelola Pengguna</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-key"></i> Reset Password</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Reset Password untuk Pengguna: <?= e($user_data['nama_lengkap'] ?? $user_data['nama']) ?> (<?= e($user_data['email']) ?>)</h6>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>
        <p class="mb-3 text-muted">Masukkan password baru untuk pengguna ini. Disarankan untuk memberitahukan password baru ini kepada pengguna yang bersangkutan.</p>

        <form action="<?= e(ADMIN_URL . 'users/reset_password_user.php?id=' . $user_id_to_reset) ?>" method="POST" class="needs-validation" novalidate>
            <?php
            // CSRF Token untuk form POST ini
            if (function_exists('generate_csrf_token_input')) {
                echo generate_csrf_token_input('csrf_token_reset_password_form');
            }
            ?>

            <div class="mb-3">
                <label for="new_password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" aria-describedby="passwordHelpResetAdmin">
                <small id="passwordHelpResetAdmin" class="form-text text-muted">Minimal 6 karakter.</small>
                <div class="invalid-feedback">Password baru wajib diisi (minimal 6 karakter).</div>
            </div>

            <div class="mb-3">
                <label for="confirm_new_password" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                <div class="invalid-feedback">Konfirmasi password baru wajib diisi dan harus cocok.</div>
            </div>

            <button type="submit" name="submit_reset_password" class="btn btn-primary"><i class="fas fa-save me-2"></i>Reset Password</button>
            <a href="<?= e(ADMIN_URL . 'users/kelola_users.php') ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>
<script>
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    const password = form.querySelector('#new_password');
                    const confirmPassword = form.querySelector('#confirm_new_password');

                    if (password && confirmPassword && password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Password Baru dan Konfirmasi Password Baru tidak cocok.');
                    } else if (confirmPassword) {
                        confirmPassword.setCustomValidity('');
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