<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\login.php

// 1. Load Konfigurasi Utama (akan memuat helper, memulai session, dll.)
if (!file_exists(__DIR__ . '/../config/config.php')) {
    // Pesan error dasar jika config tidak ada
    $error_message = "KRITIS login.php: File konfigurasi utama (config.php) tidak ditemukan.";
    error_log($error_message . " Path yang dicoba: " . realpath(__DIR__ . '/../config/config.php'));
    exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Komponen inti aplikasi tidak dapat dimuat. (Kode: CFG_LOAD_FAIL_LOGIN)</div>");
}
require_once __DIR__ . '/../config/config.php';

// 2. Pastikan AuthController sudah dimuat (config.php seharusnya sudah melakukannya)
if (!class_exists('AuthController')) {
    // Ini adalah fallback jika autoloading atau pemuatan di config.php gagal.
    if (defined('CONTROLLERS_PATH') && file_exists(CONTROLLERS_PATH . '/AuthController.php')) {
        require_once CONTROLLERS_PATH . '/AuthController.php';
        if (!class_exists('AuthController')) {
            error_log("KRITIS login.php: AuthController.php dimuat tapi class AuthController tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem otentikasi (ACNF_LOGIN). Silakan coba lagi nanti.');
            if (function_exists('redirect')) redirect(defined('BASE_URL') ? BASE_URL : './'); // Redirect ke beranda
            exit;
        }
    } else {
        error_log("KRITIS login.php: AuthController.php tidak ditemukan atau CONTROLLERS_PATH tidak terdefinisi.");
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem otentikasi (ACPF_LOGIN). Silakan coba lagi nanti.');
        if (function_exists('redirect')) redirect(defined('BASE_URL') ? BASE_URL : './');
        exit;
    }
}

// 3. Logika Halaman

// Jika pengguna sudah login, arahkan ke dashboard yang sesuai
if (function_exists('is_logged_in') && is_logged_in()) {
    $destination_path = (function_exists('is_admin') && is_admin())
        ? (defined('ADMIN_URL') ? ADMIN_URL . 'dashboard.php' : BASE_URL . 'admin/dashboard.php')
        : (defined('USER_URL') ? USER_URL . 'dashboard.php' : BASE_URL . 'user/dashboard.php');
    if (function_exists('redirect')) redirect($destination_path);
    exit;
}

$email_input_val = '';
if (isset($_SESSION['login_form_email'])) {
    $email_input_val = function_exists('e') ? e($_SESSION['login_form_email']) : htmlspecialchars($_SESSION['login_form_email'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['login_form_email']);
}

$redirect_url_after_login = function_exists('input') ? input('redirect_to', 'GET') : ($_GET['redirect_to'] ?? null);


if (function_exists('is_post') && is_post()) {
    // Verifikasi CSRF Token
    if (function_exists('verify_csrf_token') && function_exists('generate_csrf_token_input') && !verify_csrf_token()) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF). Silakan coba lagi.');
        $redirect_params = $redirect_url_after_login ? '?redirect_to=' . rawurlencode($redirect_url_after_login) : '';
        if (function_exists('redirect')) redirect((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'login.php' . $redirect_params);
        exit;
    }

    $email_from_post = function_exists('input') ? input('email') : trim($_POST['email'] ?? '');
    $password_from_post = $_POST['password'] ?? ''; // Password tidak di-trim

    $_SESSION['login_form_email'] = $email_from_post; // Simpan untuk repopulate jika gagal

    $login_valid = true;
    if (empty($email_from_post)) {
        if (function_exists('set_flash_message')) set_flash_message('warning', 'Alamat email wajib diisi.');
        $login_valid = false;
    }
    if (empty($password_from_post) && $login_valid) { // Hanya tampilkan jika email diisi
        if (function_exists('set_flash_message')) set_flash_message('warning', 'Password wajib diisi.');
        $login_valid = false;
    }

    if ($login_valid) {
        $login_result = AuthController::processLogin($email_from_post, $password_from_post);

        if ($login_result === true) { // Ekspektasi: processLogin mengembalikan true jika sukses
            unset($_SESSION['login_form_email']);

            $default_user_dashboard = (defined('USER_URL') ? USER_URL : BASE_URL . 'user/') . 'dashboard.php';
            $default_admin_dashboard = (defined('ADMIN_URL') ? ADMIN_URL : BASE_URL . 'admin/') . 'dashboard.php';
            $final_redirect_target = $default_user_dashboard;

            if (function_exists('is_admin') && is_admin()) {
                $final_redirect_target = $default_admin_dashboard;
                if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) { // Hanya set jika AuthController belum
                    set_flash_message('success', 'Selamat datang kembali, Admin!');
                }
            } else {
                if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                    set_flash_message('success', 'Login berhasil! Selamat datang.');
                }
            }

            if (!empty($redirect_url_after_login)) {
                // Validasi sederhana untuk mencegah open redirect
                if (
                    strpos($redirect_url_after_login, 'http') !== 0 &&
                    strpos($redirect_url_after_login, '//') !== 0 &&
                    strpos($redirect_url_after_login, '..') === false &&
                    strlen($redirect_url_after_login) < 255 // Batasi panjang
                ) {
                    // Pastikan redirect_url_after_login adalah path relatif dari BASE_URL
                    // atau URL absolut yang aman (jika Anda punya logika validasi URL absolut)
                    // Untuk keamanan, pastikan ini adalah path relatif dalam aplikasi Anda.
                    $final_redirect_target = BASE_URL . ltrim($redirect_url_after_login, '/');
                } else {
                    error_log("Login.php: Potensi open redirect dicegah. redirect_to: " . $redirect_url_after_login);
                    // Redirect ke dashboard default jika redirect_to tidak aman
                }
            }

            if (function_exists('redirect')) redirect($final_redirect_target);
            exit;
        } else {
            // AuthController::processLogin seharusnya sudah set_flash_message jika gagal dengan pesan spesifik.
            // Jika tidak ada flash message dari AuthController, berikan pesan fallback.
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                // Pesan $login_result bisa berupa string kode error dari User::login() atau AuthController
                if (is_string($login_result) && !empty($login_result)) {
                    // Anda bisa memetakan $login_result ke pesan yang lebih ramah pengguna di sini jika perlu
                    // Contoh: if ($login_result === 'account_blocked') set_flash_message('danger', 'Akun Anda telah diblokir.');
                    // Untuk sekarang, tampilkan saja jika itu string error
                    set_flash_message('danger', 'Login gagal: ' . e($login_result));
                } else {
                    set_flash_message('danger', 'Login gagal. Terjadi kesalahan pada sistem. Silakan coba lagi nanti.');
                }
            }
        }
    }
    // Jika validasi gagal atau login gagal, redirect kembali ke halaman login
    $redirect_params_on_fail = $redirect_url_after_login ? '?redirect_to=' . rawurlencode($redirect_url_after_login) : '';
    if (function_exists('redirect')) redirect((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'login.php' . $redirect_params_on_fail);
    exit;
}

$pageTitle = "Login - " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Cilengkrang Web Wisata");
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
    $apple_touch_icon_url = defined('ASSETS_URL') ? ASSETS_URL . 'img/logo_apple_touch.png' : (defined('BASE_URL') ? BASE_URL . 'public/img/logo_apple_touch.png' : '');
    if ($favicon_url): ?>
        <link rel="icon" href="<?= e($favicon_url) ?>" type="image/x-icon"><?php endif;
                                                                        if ($apple_touch_icon_url): ?>
        <link rel="apple-touch-icon" href="<?= e($apple_touch_icon_url) ?>"><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #eef2f7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding-top: 20px;
            padding-bottom: 70px;
            font-family: 'Roboto', 'Open Sans', 'Segoe UI', sans-serif;
            position: relative;
        }

        .login-form-container {
            max-width: 420px;
            width: 100%;
            padding: 2.5rem;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .login-form-container .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-form-container .logo-container img {
            max-height: 60px;
        }

        .login-form-container h2 {
            color: #256e48;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            font-size: 1.7rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.3rem;
        }

        .form-control-lg {
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.2);
        }

        .btn-primary {
            background-color: #2E8B57;
            border-color: #2E8B57;
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #256e48;
            border-color: #256e48;
        }

        .text-center a {
            color: #2E8B57;
            font-weight: 500;
            text-decoration: none;
        }

        .text-center a:hover {
            color: #256e48;
            text-decoration: underline;
        }

        .footer-text-container {
            text-align: center;
            color: #6c757d;
            font-size: 0.85em;
            padding: 15px 0;
            width: 100%;
            position: absolute;
            bottom: 0;
        }

        .alert {
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <div class="login-form-container">
        <div class="logo-container">
            <?php
            $logo_src = defined('ASSETS_URL') ? ASSETS_URL . 'img/logo.png' : (defined('BASE_URL') ? BASE_URL . 'public/img/logo.png' : '');
            $nama_situs_alt = defined('NAMA_SITUS') ? e(NAMA_SITUS) : 'Cilengkrang Web Wisata';
            if ($logo_src): ?>
                <a href="<?= e(defined('BASE_URL') ? BASE_URL : './') ?>"><img src="<?= e($logo_src) ?>" alt="Logo <?= $nama_situs_alt ?>"></a>
            <?php endif; ?>
        </div>
        <h2>Login Akun</h2>

        <?= function_exists('display_flash_message') ? display_flash_message() : '' ?>

        <form method="POST" action="<?= e((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'login.php' . ($redirect_url_after_login ? '?redirect_to=' . rawurlencode($redirect_url_after_login) : '')) ?>" novalidate>
            <?= function_exists('generate_csrf_token_input') ? generate_csrf_token_input() : '' ?>
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control form-control-lg" value="<?= $email_input_val ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control form-control-lg" required>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
            <p class="text-center small mb-2">Belum punya akun? <a href="<?= e((defined('AUTH_URL') ? AUTH_URL : BASE_URL . 'auth/') . 'register.php') ?>">Daftar di sini</a></p>
            <p class="text-center small"><a href="<?= e(defined('BASE_URL') ? BASE_URL : './') ?>"><i class="fas fa-home me-1"></i>Kembali ke Beranda</a></p>
        </form>
    </div>

    <div class="footer-text-container">
        Hak Cipta © <?= date("Y"); ?> <?= defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Cilengkrang Web Wisata" ?>. Semua hak dilindungi.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>