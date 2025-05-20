<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\reset_password.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di reset_password.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Jika sudah login, tidak perlu akses halaman ini
if (function_exists('is_logged_in') && is_logged_in()) {
    if (function_exists('redirect') && defined('BASE_URL')) {
        redirect(is_admin() ? ADMIN_URL . 'dashboard.php' : (is_user() ? USER_URL . 'dashboard.php' : BASE_URL));
    }
    exit;
}

// 3. Pastikan AuthController dan User Model ada (jika diperlukan untuk validasi token dan update password)
if (!class_exists('User') || !method_exists('User', 'updatePasswordByToken')) { // Asumsi ada metode ini di User Model
    error_log("ERROR di reset_password.php: Komponen User Model atau metode updatePasswordByToken tidak lengkap.");
    // Tampilkan pesan error atau redirect
    set_flash_message('danger', 'Kesalahan sistem: Fitur reset password tidak dapat diproses saat ini.');
    if (function_exists('redirect') && defined('AUTH_URL')) redirect(AUTH_URL . 'login.php');
    exit;
}


$page_title = "Reset Password";
$token = input('token', null, 'GET');
$email_from_url = input('email', null, 'GET'); // Opsional, bisa juga diambil dari token di DB

$valid_token = false; // Flag untuk menandakan apakah token valid

// TODO: Implementasi Validasi Token Sebenarnya:
// 1. Ambil token dari URL.
// 2. Cek apakah token ada di database (misalnya, di tabel password_resets).
// 3. Cek apakah token belum kadaluarsa.
// 4. Cek apakah token cocok dengan email (jika email juga disimpan bersama token).
// Jika semua valid, set $valid_token = true;
// Jika tidak valid, tampilkan pesan error dan jangan tampilkan form reset.

// Untuk contoh ini, kita anggap token 'DUMMYTOKEN123' selalu valid jika ada.
if ($token === 'DUMMYTOKEN123' && !empty($email_from_url)) { // Ini hanya untuk demo
    $valid_token = true;
    error_log("RESET_PASSWORD_FORM: Token DUMMY valid diterima untuk email: " . $email_from_url);
} else {
    set_flash_message('danger', 'Token reset password tidak valid, kedaluwarsa, atau sudah digunakan. Silakan minta link reset baru jika perlu.');
    error_log("RESET_PASSWORD_FORM: Token TIDAK VALID atau tidak ada. Token: '{$token}', Email: '{$email_from_url}'");
    // Pertimbangkan untuk redirect ke halaman lupa password lagi jika token tidak valid
    // redirect(AUTH_URL . 'lupa_password.php');
    // exit;
}


// 4. Proses form jika metode POST dan token valid
if (is_post() && $valid_token) {
    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token_reset_password', true, 'POST')) {
        set_flash_message('danger', 'Sesi tidak valid atau permintaan kedaluwarsa. Silakan coba lagi.');
    } else {
        $password_baru = input('password_baru', '', 'POST'); // Jangan trim
        $konfirmasi_password_baru = input('confirm_password_baru', '', 'POST'); // Jangan trim
        $hidden_token = input('reset_token', '', 'POST'); // Ambil token dari hidden input
        $hidden_email = input('reset_email', '', 'POST'); // Ambil email dari hidden input

        if (empty($password_baru) || empty($konfirmasi_password_baru)) {
            set_flash_message('danger', 'Password baru dan konfirmasinya wajib diisi.');
        } elseif (strlen($password_baru) < 6) {
            set_flash_message('danger', 'Password baru minimal 6 karakter.');
        } elseif ($password_baru !== $konfirmasi_password_baru) {
            set_flash_message('danger', 'Password baru dan konfirmasi password tidak cocok.');
        } elseif ($hidden_token !== $token || $hidden_email !== $email_from_url) { // Validasi ulang token & email dari form
            set_flash_message('danger', 'Terjadi kesalahan validasi. Silakan coba lagi dari link email.');
            error_log("RESET_PASSWORD_PROCESS: Token/Email dari form tidak cocok dengan URL. Form Token: {$hidden_token}, URL Token: {$token}");
        } else {
            // TODO: Implementasi Logika Update Password Sebenarnya di User Model:
            // Buat metode di User Model, misalnya: User::updatePasswordByValidToken($token, $email_from_url, $password_baru)
            // Metode ini akan:
            // 1. Verifikasi ulang token dan email di DB (pastikan belum dipakai/kadaluarsa).
            // 2. Jika valid, hash password baru.
            // 3. Update password pengguna di tabel 'users'.
            // 4. Hapus atau tandai token reset sebagai sudah digunakan di tabel 'password_resets'.
            // 5. Kembalikan true jika berhasil, atau false/string error jika gagal.

            // Untuk sekarang, kita anggap berhasil (dummy)
            $update_sukses = true; // Ganti ini dengan hasil dari User::updatePasswordByValidToken()

            if ($update_sukses) {
                set_flash_message('success', 'Password Anda telah berhasil direset. Silakan login dengan password baru Anda.');
                error_log("RESET_PASSWORD_PROCESS: Password berhasil direset untuk email: " . $email_from_url);
                if (function_exists('redirect') && defined('AUTH_URL')) redirect(AUTH_URL . 'login.php');
                exit;
            } else {
                set_flash_message('danger', 'Gagal mereset password. Silakan coba lagi atau hubungi administrator.');
                error_log("RESET_PASSWORD_PROCESS: Gagal update password untuk email: " . $email_from_url . ". Error dari Model: " . (User::getLastError() ?? 'Tidak ada info'));
            }
        }
    }
}

// --- Mulai Output HTML ---
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(NAMA_SITUS) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Salin CSS dari halaman login.php atau buat CSS terpusat */
        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }

        .auth-container {
            background-color: #ffffff;
            padding: 2.5rem 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .auth-logo img {
            max-width: 100px;
            margin-bottom: 1rem;
        }

        .auth-container h2 {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .auth-container p.lead {
            margin-bottom: 1.5rem;
            color: #666;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.85rem 1rem;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: #04A777;
            box-shadow: 0 0 0 0.2rem rgba(4, 167, 119, 0.25);
        }

        .btn-submit-auth {
            background-color: #04A777;
            border-color: #04A777;
            padding: 0.85rem;
            font-weight: 500;
            border-radius: 8px;
            width: 100%;
            transition: background-color 0.2s ease-in-out;
            color: white;
        }

        .btn-submit-auth:hover {
            background-color: #038a63;
            border-color: #038a63;
        }

        .extra-links {
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        .extra-links a {
            color: #04A777;
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-logo">
            <a href="<?= e(BASE_URL) ?>"><img src="<?= e(ASSETS_URL . 'img/logo.png') ?>" alt="Logo <?= e(NAMA_SITUS) ?>"></a>
        </div>
        <h2>Reset Password Anda</h2>

        <?php display_flash_message(); ?>

        <?php if ($valid_token): ?>
            <p class="lead">Masukkan password baru Anda di bawah ini untuk email: <strong><?= e($email_from_url) ?></strong></p>
            <form action="<?= e(AUTH_URL . 'reset_password.php?token=' . rawurlencode($token) . '&email=' . rawurlencode($email_from_url)) ?>" method="POST" class="needs-validation" novalidate>
                <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input('csrf_token_reset_password'); ?>
                <input type="hidden" name="reset_token" value="<?= e($token) ?>">
                <input type="hidden" name="reset_email" value="<?= e($email_from_url) ?>">

                <div class="mb-3 text-start">
                    <label for="password_baru" class="form-label">Password Baru</label>
                    <input type="password" class="form-control" id="password_baru" name="password_baru" required minlength="6" aria-describedby="passwordHelpReset">
                    <small id="passwordHelpReset" class="form-text text-muted">Minimal 6 karakter.</small>
                    <div class="invalid-feedback">Password baru wajib diisi (minimal 6 karakter).</div>
                </div>

                <div class="mb-3 text-start">
                    <label for="confirm_password_baru" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="confirm_password_baru" name="confirm_password_baru" required minlength="6">
                    <div class="invalid-feedback">Konfirmasi password baru wajib diisi dan harus cocok.</div>
                </div>

                <button type="submit" class="btn btn-submit-auth mt-3">Reset Password</button>
            </form>
        <?php else: ?>
            <p class="lead text-danger">Link reset password ini tidak valid atau sudah kedaluwarsa.</p>
        <?php endif; ?>

        <div class="extra-links">
            Kembali ke <a href="<?= e(AUTH_URL . 'login.php') ?>">Halaman Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        const password = form.querySelector('#password_baru');
                        const confirmPassword = form.querySelector('#confirm_password_baru');
                        if (password && confirmPassword && password.value !== confirmPassword.value) {
                            confirmPassword.setCustomValidity('Password Baru dan Konfirmasi Password tidak cocok.');
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
</body>

</html>