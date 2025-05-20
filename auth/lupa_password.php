<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\lupa_password.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di lupa_password.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Jika sudah login, tidak perlu akses halaman ini
if (function_exists('is_logged_in') && is_logged_in()) {
    if (function_exists('redirect') && defined('BASE_URL')) {
        redirect(is_admin() ? ADMIN_URL . 'dashboard.php' : (is_user() ? USER_URL . 'dashboard.php' : BASE_URL));
    }
    exit;
}

// 3. Pastikan AuthController dan User Model ada (jika diperlukan untuk validasi email di sini)
// Untuk contoh ini, kita asumsikan proses validasi dan pengiriman email akan ada di prosesor form ini.
if (!class_exists('User') || !method_exists('User', 'findByEmail')) {
    error_log("ERROR di lupa_password.php: Komponen User Model tidak lengkap.");
    // Tidak perlu exit fatal, biarkan form tampil tapi proses mungkin gagal
}


$page_title = "Lupa Password";
$input_email_lupa = ''; // Untuk repopulasi

// 4. Proses form jika metode POST
if (is_post()) {
    // Verifikasi CSRF Token
    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token_lupa_password', true, 'POST')) {
        set_flash_message('danger', 'Sesi tidak valid atau permintaan kedaluwarsa. Silakan coba lagi.');
    } else {
        $email = input('email_lupa_password', '', 'POST');
        $input_email_lupa = $email; // Untuk repopulasi

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('danger', 'Silakan masukkan alamat email yang valid.');
        } else {
            // Cek apakah email terdaftar
            $user_exists = User::findByEmail($email); // Asumsi User Model ada metode ini

            if ($user_exists) {
                // TODO: Implementasi Logika Lupa Password Sebenarnya:
                // 1. Generate token reset yang unik dan aman.
                // 2. Simpan token ini ke database (misalnya tabel password_resets) 
                //    bersama user_id dan timestamp kadaluarsa (misalnya, 1 jam).
                // 3. Buat URL reset password dengan token tersebut (misal: BASE_URL . 'auth/reset_password.php?token=TOKEN_ANDA').
                // 4. Kirim email ke $email berisi URL reset tersebut.
                //    Anda mungkin butuh library seperti PHPMailer.

                // Untuk sekarang, kita hanya tampilkan pesan sukses (dummy)
                // GANTI INI DENGAN LOGIKA PENGIRIMAN EMAIL AKTUAL
                $dummy_reset_link = BASE_URL . 'auth/reset_password.php?token=DUMMYTOKEN123&email=' . rawurlencode($email);
                error_log("LUPA_PASSWORD_REQUEST: Email {$email} ditemukan. Link reset (dummy): {$dummy_reset_link}");

                set_flash_message('success', "Jika email Anda terdaftar, instruksi untuk mereset password telah dikirim ke <strong>" . e($email) . "</strong>. Silakan periksa inbox (dan folder spam) Anda.");
                // Kosongkan field email setelah sukses kirim (atau berhasil request)
                $input_email_lupa = '';
                // Anda bisa redirect ke halaman login atau halaman konfirmasi setelah ini,
                // atau biarkan pesan flash tampil di halaman yang sama.
                // redirect(AUTH_URL . 'login.php');
                // exit;

            } else {
                // Meskipun email tidak ditemukan, untuk keamanan, kita bisa menampilkan pesan yang sama
                // agar penyerang tidak tahu email mana yang valid/tidak.
                set_flash_message('success', "Jika email Anda terdaftar, instruksi untuk mereset password telah dikirim. Silakan periksa inbox (dan folder spam) Anda.");
                error_log("LUPA_PASSWORD_REQUEST: Email {$email} TIDAK ditemukan, tapi pesan generik ditampilkan.");
                $input_email_lupa = '';
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
        <h2>Lupa Password Anda?</h2>
        <p class="lead">Masukkan alamat email Anda di bawah ini dan kami akan mengirimkan instruksi untuk mereset password Anda.</p>

        <?php display_flash_message(); ?>

        <form action="<?= e(AUTH_URL . 'lupa_password.php') ?>" method="POST" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input('csrf_token_lupa_password'); ?>

            <div class="mb-3 text-start">
                <label for="email_lupa_password" class="form-label">Alamat Email Terdaftar</label>
                <input type="email" class="form-control" id="email_lupa_password" name="email_lupa_password" value="<?= e($input_email_lupa) ?>" required autofocus>
                <div class="invalid-feedback">
                    Alamat email wajib diisi dengan format yang benar.
                </div>
            </div>

            <button type="submit" class="btn btn-submit-auth mt-3">Kirim Instruksi Reset</button>
        </form>

        <div class="extra-links">
            Ingat password Anda? <a href="<?= e(AUTH_URL . 'login.php') ?>">Login di sini</a>
            <br>
            <a href="<?= e(BASE_URL) ?>" class="mt-2 d-inline-block"><i class="fas fa-home me-1"></i>Kembali ke Beranda</a>
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