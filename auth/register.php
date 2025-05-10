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
        redirect('user/dashboard.php'); // Or your main user area/homepage
    }
}

// Initialize variables for form pre-filling
$nama_input = '';
$email_input = '';
$no_hp_input = '';
$alamat_input = '';

// 3. Process POST Request (if form submitted)
if (is_post()) { // Using helper
    $nama_input = input('nama');
    $email_input = input('email');
    $password_input = input('password'); // Plain password
    $confirm_password_input = input('confirm_password');
    $no_hp_input = input('no_hp');
    $alamat_input = input('alamat');

    // Validations
    if (empty($nama_input) || empty($email_input) || empty($password_input) || empty($confirm_password_input)) {
        set_flash_message('danger', 'Nama, Email, Password, dan Konfirmasi Password wajib diisi.');
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
    } elseif (strlen($password_input) < 6) {
        set_flash_message('danger', 'Password minimal 6 karakter.');
    } elseif ($password_input !== $confirm_password_input) {
        set_flash_message('danger', 'Password dan Konfirmasi Password tidak cocok.');
    } else {
        $data_to_register = [
            'nama' => $nama_input,
            'email' => $email_input,
            'password' => $password_input, // Pass plain password, model will hash
            'no_hp' => $no_hp_input,
            'alamat' => $alamat_input
        ];

        $registration_result = User::register($data_to_register); // User::register uses global $conn

        if ($registration_result === 'email_exists') {
            set_flash_message('danger', 'Email sudah terdaftar. Silakan gunakan email lain atau login.');
        } elseif ($registration_result !== false && is_numeric($registration_result)) { // Got a user ID back
            set_flash_message('success', 'Registrasi berhasil! Silakan login.');
            redirect('auth/login.php'); // Redirect to login page
        } else { // General registration failure
            set_flash_message('danger', 'Registrasi gagal. Silakan coba lagi nanti.');
        }
    }
    // If there was an error and no redirect, the form re-displays with pre-filled values (except passwords)
    // and the flash message will be shown.
    // To make sure flash message appears correctly if no redirect for error on current page,
    // you might need to redirect to the same page to clear POST data.
    // For example: if (isset($_SESSION['flash_message'])) redirect('auth/register.php');
    // However, it's often better to let the form re-display and show errors directly.
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - Cilengkrang Web Wisata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
            padding: 20px 0;
        }

        .register-container {
            max-width: 500px;
            width: 100%;
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .footer-text {
            text-align: center;
            color: #6c757d;
            margin-top: 20px;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h2>Buat Akun Baru</h2>

        <?php
        // 4. Display Flash Message
        echo display_flash_message();
        ?>

        <form action="<?= $base_url ?>auth/register.php" method="post">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= e($nama_input) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($email_input) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <small class="form-text text-muted">Minimal 6 karakter.</small>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">Nomor HP (Opsional)</label>
                <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= e($no_hp_input) ?>">
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat (Opsional)</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3"><?= e($alamat_input) ?></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100">Daftar Akun</button>
        </form>
        <div class="text-center mt-3">
            <p>Sudah punya akun? <a href="<?= $base_url ?>auth/login.php">Login di sini</a></p>
            <p><a href="<?= $base_url ?>">Kembali ke Beranda</a></p>
        </div>
    </div>
    <div class="footer-text">© <?= date("Y"); ?> Cilengkrang Web Wisata</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>