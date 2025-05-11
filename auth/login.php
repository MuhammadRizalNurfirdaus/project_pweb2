<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\login.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Jika pengguna sudah login, arahkan ke dashboard yang sesuai
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$email_input_val = ''; // Untuk mengisi ulang form jika login gagal
// Repopulate email dari session jika login gagal pada attempt sebelumnya dan di-redirect
if (isset($_SESSION['flash_form_data_login']['email'])) { // Gunakan key session yang unik untuk login
    $email_input_val = e($_SESSION['flash_form_data_login']['email']);
    unset($_SESSION['flash_form_data_login']['email']); // Hapus setelah digunakan
}


if (is_post()) {
    $email_from_post = input('email');
    $password_from_post = input('password');

    // Simpan email ke session untuk repopulation jika login gagal dan redirect
    $_SESSION['flash_form_data_login']['email'] = $email_from_post;

    if (empty($email_from_post) || empty($password_from_post)) {
        set_flash_message('danger', 'Email dan password wajib diisi.');
        redirect('auth/login.php');
    } else {
        // AuthController::processLogin akan mencoba login dan set session jika berhasil
        if (AuthController::processLogin($email_from_post, $password_from_post)) {
            unset($_SESSION['flash_form_data_login']); // Hapus data form dari session jika login berhasil
            // Redirect berdasarkan role setelah login sukses (role di set di session oleh processLogin)
            if (is_admin()) {
                set_flash_message('success', 'Selamat datang kembali, Admin!');
                redirect('admin/dashboard.php');
            } else {
                set_flash_message('success', 'Login berhasil! Selamat datang.');
                redirect('user/dashboard.php');
            }
        } else {
            set_flash_message('danger', 'Kombinasi email atau password salah.');
            redirect('auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cilengkrang Web Wisata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="<?= $base_url ?>public/img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= $base_url ?>public/img/logo_apple_touch.png">
    <!-- Tidak perlu link ke style.css utama jika style login spesifik dan mandiri -->
    <style>
        body {
            background-color: #eef2f7;
            /* Warna background sama dengan register */
            display: flex;
            flex-direction: column;
            /* Untuk footer */
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding-top: 20px;
            padding-bottom: 20px;
            font-family: 'Roboto', 'Open Sans', 'Segoe UI', sans-serif;
        }

        .login-form-container {
            max-width: 430px;
            /* Sedikit lebih lebar dari register */
            width: 100%;
            padding: 2.8rem;
            /* Padding lebih besar */
            background: #ffffff;
            /* var(--very-light-bg) */
            border-radius: 12px;
            /* var(--border-radius-lg) */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            /* Shadow sama dengan register */
            margin-bottom: 30px;
            /* Jarak ke footer */
        }

        .login-form-container .logo-container {
            /* Sama dengan register */
            text-align: center;
            margin-bottom: 25px;
        }

        .login-form-container .logo-container img {
            max-height: 70px;
        }

        .login-form-container h2 {
            color: #256e48;
            /* var(--primary-darker) */
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            /* var(--medium-text) */
        }

        .form-control-lg {
            /* Bootstrap default sudah cukup baik, ini hanya contoh jika ingin override */
            padding: 0.8rem 1.1rem;
            font-size: 1.05rem;
            /* Sedikit lebih besar */
        }

        .form-control:focus {
            border-color: #2E8B57;
            /* var(--primary-color) */
            box-shadow: 0 0 0 0.25rem rgba(46, 139, 87, 0.25);
        }

        .btn-primary {
            background-color: #2E8B57;
            /* var(--primary-color) */
            border-color: #2E8B57;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            background-color: #256e48;
            /* var(--primary-darker) */
            border-color: #256e48;
        }

        .text-center a {
            color: #2E8B57;
            /* var(--primary-color) */
            font-weight: 500;
        }

        .text-center a:hover {
            color: #256e48;
            /* var(--primary-darker) */
        }

        .footer-text-container {
            /* Sama dengan register */
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="login-form-container">
        <div class="logo-container">
            <a href="<?= $base_url ?>"><img src="<?= $base_url ?>public/img/logo.png" alt="Logo Cilengkrang Wisata"></a>
        </div>
        <h2>Login Akun</h2>

        <?= display_flash_message(); ?>

        <form method="POST" action="<?= e($base_url . 'auth/login.php') ?>" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control form-control-lg" value="<?= $email_input_val ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control form-control-lg" required>
            </div>
            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
            <p class="text-center small">Belum punya akun? <a href="<?= $base_url ?>auth/register.php">Daftar di sini</a></p>
            <p class="text-center small mt-2"><a href="<?= $base_url ?>"><i class="fas fa-home me-1"></i>Kembali ke Beranda</a></p>
        </form>
    </div>
    <div class="footer-text-container">
        Hak Cipta © <?= date("Y"); ?> Cilengkrang Web Wisata. Semua hak dilindungi.
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>

</html>