<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\login.php
require_once __DIR__ . '/../config/config.php'; // Menyediakan $base_url, helpers, session, $conn
require_once __DIR__ . '/../controllers/AuthController.php'; // Controller untuk logika login

// Jika pengguna sudah login, arahkan ke dashboard yang sesuai
if (is_logged_in()) { // Fungsi dari auth_helpers.php
    if (is_admin()) { // Fungsi dari auth_helpers.php
        redirect('admin/dashboard.php'); // Fungsi dari helpers.php
    } else {
        redirect('user/dashboard.php'); // Asumsi ada user/dashboard.php
    }
}

$email_input = ''; // Untuk mengisi ulang form jika login gagal

if (is_post()) { // Fungsi dari helpers.php
    $email_input = input('email'); // Fungsi dari helpers.php
    $password = input('password'); // Fungsi dari helpers.php
    // Contoh password hash untuk testing login admin
    // Jika Anda ingin menggunakan password hash untuk testing, Anda bisa menggunakan:
    echo password_hash('admin12345', PASSWORD_DEFAULT);

    if (empty($email_input) || empty($password)) {
        set_flash_message('danger', 'Email dan password wajib diisi.'); // Fungsi dari flash_message.php
    } else {
        // AuthController::processLogin akan mencoba login dan set session jika berhasil
        if (AuthController::processLogin($email_input, $password)) {
            // Redirect berdasarkan role setelah login sukses (role di set di session oleh processLogin)
            if (is_admin()) {
                set_flash_message('success', 'Selamat datang kembali, Admin!');
                redirect('admin/dashboard.php');
            } else {
                set_flash_message('success', 'Login berhasil! Selamat datang.');
                redirect('user/dashboard.php'); // Atau ke halaman utama publik: redirect('');
            }
        } else {
            set_flash_message('danger', 'Kombinasi email atau password salah.');
        }
    }
    // Redirect ke halaman login lagi untuk menampilkan flash message dan membersihkan data POST
    // Ini membantu mencegah resubmission form jika pengguna me-refresh halaman setelah error.
    redirect('auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cilengkrang Web Wisata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $base_url ?>public/style.css"> <!-- Path ke style.css utama Anda -->
    <style>
        /* Anda bisa memindahkan style ini ke style.css jika ingin lebih terpusat */
        body {
            background-color: #e9ecef;
            /* Warna background netral */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .login-form-container {
            max-width: 420px;
            /* Sedikit lebih lebar */
            width: 100%;
            padding: 2.5rem;
            /* Padding lebih besar */
            background: var(--very-light-bg, white);
            /* Gunakan variabel CSS jika ada */
            border-radius: var(--border-radius-lg, 0.8rem);
            /* Radius lebih besar */
            box-shadow: var(--box-shadow-lg, 0 1rem 3rem rgba(0, 0, 0, .175));
        }

        .login-form-container img.logo {
            height: 80px;
            /* Ukuran logo */
            margin-bottom: 1.5rem;
        }

        .login-form-container h2 {
            color: var(--primary-darker, #256e48);
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="login-form-container">
        <div class="text-center mb-4">
            <img src="<?= $base_url ?>public/img/logo.png" alt="Logo Cilengkrang Wisata" class="logo">
            <h2 class="mt-2">Login Akun</h2>
        </div>

        <?= display_flash_message(); // Fungsi dari flash_message.php 
        ?>

        <form method="POST" action="<?= $base_url ?>auth/login.php" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control form-control-lg" value="<?= e($email_input) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control form-control-lg" required>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
            <p class="text-center small">Belum punya akun? <a href="<?= $base_url ?>auth/register.php">Daftar di sini</a></p>
            <p class="text-center small mt-1"><a href="<?= $base_url ?>">Kembali ke Beranda</a></p>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

</html>