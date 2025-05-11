<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\register.php

// 1. Include Config and Model
require_once __DIR__ . '/../config/config.php'; // Provides $conn, $base_url, and all helpers
require_once __DIR__ . '/../models/User.php';    // Load the User Model

// 2. Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

// Initialize variables for form pre-filling and repopulation after redirect
$nama_input_val = ''; // Gunakan nama variabel berbeda untuk value di form
$email_input_val = '';
$no_hp_input_val = '';
$alamat_input_val = '';

// Repopulate form from session if validation failed on previous attempt and redirected
if (isset($_SESSION['flash_form_data_register'])) { // Gunakan key session yang unik untuk register
    $nama_input_val = e($_SESSION['flash_form_data_register']['nama'] ?? '');
    $email_input_val = e($_SESSION['flash_form_data_register']['email'] ?? '');
    $no_hp_input_val = e($_SESSION['flash_form_data_register']['no_hp'] ?? '');
    $alamat_input_val = e($_SESSION['flash_form_data_register']['alamat'] ?? '');
    unset($_SESSION['flash_form_data_register']); // Clear after use
}


// 3. Process POST Request (if form submitted)
if (is_post()) {
    $nama_from_post = input('nama');
    $email_from_post = input('email');
    $password_from_post = input('password');
    $confirm_password_from_post = input('confirm_password');
    $no_hp_from_post = input('no_hp');
    $alamat_from_post = input('alamat');

    // Store input in session for repopulation in case of redirect
    $_SESSION['flash_form_data_register'] = [
        'nama' => $nama_from_post,
        'email' => $email_from_post,
        'no_hp' => $no_hp_from_post,
        'alamat' => $alamat_from_post,
    ];

    // Validations
    if (empty($nama_from_post) || empty($email_from_post) || empty($password_from_post) || empty($confirm_password_from_post)) {
        set_flash_message('danger', 'Nama Lengkap, Email, Password, dan Konfirmasi Password wajib diisi.');
    } elseif (!filter_var($email_from_post, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
    } elseif (strlen($password_from_post) < 6) {
        set_flash_message('danger', 'Password minimal 6 karakter.');
    } elseif ($password_from_post !== $confirm_password_from_post) {
        set_flash_message('danger', 'Password dan Konfirmasi Password tidak cocok.');
    } else {
        $data_to_register = [
            'nama' => $nama_from_post,
            'email' => $email_from_post,
            'password' => $password_from_post, // Kirim password mentah, User::register() akan menghashnya
            'no_hp' => $no_hp_from_post,
            'alamat' => $alamat_from_post
        ];

        $registration_result = User::register($data_to_register);

        if ($registration_result === 'email_exists') {
            set_flash_message('danger', 'Email sudah terdaftar. Silakan gunakan email lain atau login.');
        } elseif ($registration_result === 'password_short') {
            set_flash_message('danger', 'Password minimal 6 karakter.');
        } elseif ($registration_result === 'email_invalid') {
            set_flash_message('danger', 'Format email tidak valid.');
        } elseif ($registration_result !== false && is_numeric($registration_result)) {
            unset($_SESSION['flash_form_data_register']);
            set_flash_message('success', 'Registrasi berhasil! Akun Anda telah dibuat. Silakan login.');
            redirect('auth/login.php');
        } else {
            set_flash_message('danger', 'Registrasi gagal. Terjadi kesalahan internal. Silakan coba lagi nanti.');
            error_log("Hasil registrasi tidak diketahui atau error: " . print_r($registration_result, true) . " untuk data: " . print_r($data_to_register, true));
        }
    }

    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'danger') {
        redirect('auth/register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - Cilengkrang Web Wisata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="<?= $base_url ?>public/img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= $base_url ?>public/img/logo_apple_touch.png">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #eef2f7;
            padding: 40px 15px;
            font-family: 'Roboto', 'Open Sans', 'Segoe UI', sans-serif;
        }

        .register-container {
            max-width: 550px;
            width: 100%;
            margin: 20px auto;
            padding: 35px 40px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .register-container .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }

        .register-container .logo-container img {
            max-height: 70px;
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #256e48;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            font-weight: 700;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
        }

        .form-control {
            border-radius: 0.3rem;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.25rem rgba(46, 139, 87, 0.25);
        }

        .btn-success {
            background-color: #2E8B57;
            border-color: #2E8B57;
            padding: 0.75rem;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .btn-success:hover {
            background-color: #256e48;
            border-color: #256e48;
        }

        .footer-text-container {
            /* Mengganti .footer-text menjadi container */
            text-align: center;
            color: #6c757d;
            margin-top: 30px;
            font-size: 0.9em;
            width: 100%;
            /* Agar bisa di-center */
        }

        .text-center a {
            color: #2E8B57;
            font-weight: 500;
        }

        .text-center a:hover {
            color: #256e48;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="logo-container">
            <a href="<?= $base_url ?>"><img src="<?= $base_url ?>public/img/logo.png" alt="Logo Cilengkrang Web Wisata"></a>
        </div>
        <h2>Buat Akun Baru</h2>

        <?php echo display_flash_message(); ?>

        <form action="<?= e($base_url . 'auth/register.php') ?>" method="post" novalidate>
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= $nama_input_val ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $email_input_val ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <small class="form-text text-muted">Minimal 6 karakter.</small>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">Nomor HP <span class="text-muted">(Opsional)</span></label>
                <input type="tel" class="form-control" id="no_hp" name="no_hp" value="<?= $no_hp_input_val ?>" pattern="[0-9]{10,15}">
                <small class="form-text text-muted">Contoh: 081234567890</small>
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat <span class="text-muted">(Opsional)</span></label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3"><?= $alamat_input_val ?></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 mt-3">Daftar Akun</button>
        </form>
        <div class="text-center mt-4">
            <p>Sudah punya akun? <a href="<?= $base_url ?>auth/login.php">Login di sini</a></p>
            <p class="mt-2"><a href="<?= $base_url ?>"><i class="fas fa-home me-1"></i>Kembali ke Beranda</a></p>
        </div>
    </div>
    <div class="footer-text-container"> <!-- Menggunakan container untuk footer -->
        Hak Cipta © <?= date("Y"); ?> Cilengkrang Web Wisata. Semua hak dilindungi.
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>

</html>