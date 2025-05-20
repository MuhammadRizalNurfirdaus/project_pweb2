<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\login.php

// 1. Load Konfigurasi Utama
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di login.php: Gagal memuat config.php.");
    exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Komponen inti aplikasi tidak dapat dimuat. (Kode: CFG_LOAD_FAIL_LOGIN)</div>");
}

// 2. Pastikan AuthController sudah dimuat
if (!class_exists('AuthController')) {
    error_log("KRITIS login.php: AuthController tidak ditemukan setelah config.php dimuat.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem otentikasi (ACNF_LOGIN_FINAL). Silakan coba lagi nanti.');
    if (function_exists('redirect')) redirect(defined('BASE_URL') ? BASE_URL : './');
    exit;
}

// 3. Logika Halaman
if (function_exists('is_logged_in') && is_logged_in()) {
    $destination_path = (function_exists('is_admin') && is_admin())
        ? (defined('ADMIN_URL') ? ADMIN_URL . 'dashboard.php' : BASE_URL . 'admin/dashboard.php')
        : (defined('USER_URL') ? USER_URL . 'dashboard.php' : BASE_URL . 'user/dashboard.php');
    if (function_exists('redirect')) redirect($destination_path);
    exit;
}

// Ambil email dari session jika ada (untuk repopulasi setelah gagal login)
// Nama session yang lebih spesifik untuk form login
$email_input_val = $_SESSION['login_form_data']['email'] ?? '';
// Hapus data repopulasi jika halaman dimuat ulang (GET request) setelah percobaan POST yang gagal
// Ini agar jika user refresh halaman login, field email tidak otomatis terisi dari percobaan gagal sebelumnya,
// kecuali jika memang ada flash message dari server yang mengindikasikan kegagalan.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['login_form_data'])) {
    // Hanya unset jika tidak ada flash message error yang baru saja diset untuk ditampilkan di halaman ini
    // Karena jika ada flash message, kita ingin email tetap ada untuk kemudahan user.
    // Jika AuthController atau blok validasi di sini set flash message, email akan tetap ada karena disimpan lagi saat POST.
    // Ini lebih untuk kasus user refresh manual halaman login.
    // Jika ingin selalu ada setelah gagal POST, jangan unset di sini.
}


$redirect_url_after_login = function_exists('input') ? input('redirect_to', null, 'GET') : ($_GET['redirect_to'] ?? null);
$csrf_token_name = 'csrf_token_login'; // Gunakan nama yang konsisten

if (function_exists('is_post') && is_post()) {
    // Simpan data POST ke session untuk repopulasi jika ada error dan redirect
    $_SESSION['login_form_data'] = $_POST;

    if (function_exists('verify_csrf_token') && function_exists('generate_csrf_token_input') && !verify_csrf_token($csrf_token_name, true, 'POST')) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF). Silakan coba lagi.');
        $redirect_params = $redirect_url_after_login ? '?redirect_to=' . rawurlencode($redirect_url_after_login) : '';
        if (function_exists('redirect')) redirect((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'login.php' . $redirect_params);
        exit;
    }

    $email_from_post = trim($_POST['email'] ?? ''); // Trim di sini
    $password_from_post = $_POST['password'] ?? ''; // Password tidak di-trim

    // Validasi dasar
    $login_valid = true;
    if (empty($email_from_post) || !filter_var($email_from_post, FILTER_VALIDATE_EMAIL)) {
        if (function_exists('set_flash_message')) set_flash_message('warning', 'Alamat email wajib diisi dengan format yang benar.');
        $login_valid = false;
    }
    if (empty($password_from_post) && $login_valid) {
        if (function_exists('set_flash_message')) set_flash_message('warning', 'Password wajib diisi.');
        $login_valid = false;
    }

    if ($login_valid) {
        $login_result = AuthController::processLogin($email_from_post, $password_from_post);

        if ($login_result === true) {
            unset($_SESSION['login_form_data']); // Hapus data form dari session HANYA jika login sukses

            $default_user_dashboard = (defined('USER_URL') ? USER_URL : BASE_URL . 'user/') . 'dashboard.php';
            $default_admin_dashboard = (defined('ADMIN_URL') ? ADMIN_URL : BASE_URL . 'admin/') . 'dashboard.php';
            $final_redirect_target = $default_user_dashboard;

            if (function_exists('is_admin') && is_admin()) {
                $final_redirect_target = $default_admin_dashboard;
            }
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('success', 'Login berhasil! Selamat datang ' . (function_exists('get_current_user_name') ? e(get_current_user_name()) : '') . '.');
            }

            if (!empty($redirect_url_after_login)) {
                if (
                    strpos($redirect_url_after_login, 'http') !== 0 &&
                    strpos($redirect_url_after_login, '//') !== 0 &&
                    strpos($redirect_url_after_login, '..') === false &&
                    strlen($redirect_url_after_login) < 255
                ) {
                    $final_redirect_target = BASE_URL . ltrim($redirect_url_after_login, '/');
                } else {
                    error_log("Login.php: Potensi open redirect dicegah. redirect_to: " . $redirect_url_after_login);
                }
            }
            if (function_exists('redirect')) redirect($final_redirect_target);
            exit;
        }
        // Jika login_result bukan true, AuthController sudah set flash message.
        // Redirect kembali ke halaman login untuk menampilkan pesan.
    }
    // Jika $login_valid false (validasi input dasar gagal) atau login gagal
    $redirect_params_on_fail = $redirect_url_after_login ? '?redirect_to=' . rawurlencode($redirect_url_after_login) : '';
    if (function_exists('redirect')) redirect((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'login.php' . $redirect_params_on_fail);
    exit;
}

// Ambil email dari session untuk repopulasi (setelah redirect karena gagal POST)
$email_input_val = function_exists('e') ? e($_SESSION['login_form_data']['email'] ?? '') : htmlspecialchars($_SESSION['login_form_data']['email'] ?? '', ENT_QUOTES, 'UTF-8');
// Penting: Hanya unset JIKA kita yakin ini bukan hasil redirect dari kegagalan POST di request yang sama.
// Biasanya, jika ada flash message, kita ingin data repopulasi tetap ada.
// Untuk kasus CSRF gagal atau validasi gagal, redirect akan terjadi, dan saat halaman ini dimuat ulang,
// $_SESSION['login_form_data'] akan ada.
// Jika login berhasil, session ini akan di-unset.
// Jadi, tidak perlu unset di sini untuk GET request.

$pageTitle = "Masuk Akun - " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Cilengkrang Web Wisata");
$theme_color_primary = "#28a745";
$theme_color_hover = "#218838";
$theme_color_focus_ring = "rgba(40, 167, 69, 0.25)";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php
    $favicon_url = defined('ASSETS_URL') ? ASSETS_URL . 'img/favicon.ico' : (defined('BASE_URL') ? BASE_URL . 'public/img/favicon.ico' : '');
    if ($favicon_url): ?>
        <link rel="icon" href="<?= e($favicon_url) ?>" type="image/x-icon"><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Lora:wght@600&display=swap" rel="stylesheet">
    <style>
        :root {
            --theme-color-primary: <?= $theme_color_primary ?>;
            --theme-color-hover: <?= $theme_color_hover ?>;
            --theme-color-focus-ring: <?= $theme_color_focus_ring ?>;
        }

        body {
            background-color: #e9f5e9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            font-family: 'Nunito', sans-serif;
        }

        .login-wrapper {
            max-width: 450px;
            width: 100%;
        }

        .login-container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .login-logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-logo-container img {
            max-height: 75px;
        }

        .login-container h1 {
            font-family: 'Lora', serif;
            font-weight: 600;
            font-size: 2rem;
            color: var(--theme-color-primary);
            margin-bottom: 0.75rem;
            text-align: center;
        }

        .login-container .lead-text {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.8rem 1rem;
            border: 1px solid #ced4da;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--theme-color-primary);
            box-shadow: 0 0 0 0.2rem var(--theme-color-focus-ring);
        }

        .form-check-label {
            font-size: 0.85rem;
            color: #555;
        }

        .forgot-password-link {
            font-size: 0.85rem;
            color: var(--theme-color-primary);
            text-decoration: none;
        }

        .forgot-password-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            background-color: var(--theme-color-primary);
            border-color: var(--theme-color-primary);
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
        }

        .btn-login:hover {
            background-color: var(--theme-color-hover);
            border-color: var(--theme-color-hover);
        }

        .signup-link-container {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #555;
        }

        .signup-link-container a {
            color: var(--theme-color-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .signup-link-container a:hover {
            text-decoration: underline;
        }

        .back-to-home-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.85rem;
        }

        .back-to-home-link a {
            color: #6c757d;
            text-decoration: none;
        }

        .back-to-home-link a:hover {
            color: var(--theme-color-primary);
        }

        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-logo-container">
                <?php
                $logo_src = defined('ASSETS_URL') ? ASSETS_URL . 'img/logo.png' : (defined('BASE_URL') ? BASE_URL . 'public/img/logo.png' : '');
                $nama_situs_alt = defined('NAMA_SITUS') ? e(NAMA_SITUS) : 'Cilengkrang Web Wisata';
                if ($logo_src && file_exists(ROOT_PATH . '/public/img/logo.png')):
                ?>
                    <a href="<?= e(defined('BASE_URL') ? BASE_URL : './') ?>"><img src="<?= e($logo_src) ?>" alt="Logo <?= $nama_situs_alt ?>"></a>
                <?php else: ?>
                    <i class="fas fa-hiking fa-3x text-secondary mb-2"></i>
                <?php endif; ?>
            </div>
            <h1>Selamat Datang!</h1>
            <p class="lead-text">Masuk untuk memulai petualangan Anda di <?= e(NAMA_SITUS) ?>.</p>

            <?= function_exists('display_flash_message') ? display_flash_message() : '' ?>

            <form method="POST" action="<?= e((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'login.php' . ($redirect_url_after_login ? '?redirect_to=' . rawurlencode($redirect_url_after_login) : '')) ?>" class="needs-validation" novalidate>
                <?php
                // PENTING: Pastikan fungsi ini menghasilkan input dengan nama yang benar
                if (function_exists('generate_csrf_token_input')) {
                    echo generate_csrf_token_input($csrf_token_name); // Menggunakan nama token yang konsisten
                }
                ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="email" class="form-control form-control-lg" value="<?= e($email_input_val) ?>" placeholder="contoh@email.com" required autofocus>
                    <div class="invalid-feedback">Masukkan alamat email yang valid.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Masukkan password Anda" required>
                    <div class="invalid-feedback">Password wajib diisi.</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="remember" id="rememberMe" name="remember_me" style="border-color: #bbb;">
                        <label class="form-check-label" for="rememberMe">
                            Ingat Saya
                        </label>
                    </div>
                    <a href="<?= e((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'lupa_password.php') ?>" class="forgot-password-link">Lupa Password?</a>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">Masuk</button>
                </div>
            </form>

            <div class="signup-link-container">
                Baru di <?= e(NAMA_SITUS) ?>? <a href="<?= e((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'register.php') ?>">Buat Akun</a>
            </div>
        </div>
        <div class="back-to-home-link">
            <a href="<?= e(defined('BASE_URL') ? BASE_URL : './') ?>"><i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>

</html>