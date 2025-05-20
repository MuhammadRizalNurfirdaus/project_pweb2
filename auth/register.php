<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\register.php

// 1. Load Konfigurasi Utama (config.php akan menangani session_start)
if (!file_exists(__DIR__ . '/../config/config.php')) {
    $error_message = "KRITIS register.php: File konfigurasi utama (config.php) tidak ditemukan.";
    error_log($error_message . " Path yang dicoba: " . realpath(__DIR__ . '/../config/config.php'));
    exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Komponen inti aplikasi tidak dapat dimuat. Mohon hubungi administrator. (Kode: CFG_LOAD_FAIL_REG)</div>");
}
require_once __DIR__ . '/../config/config.php';

// 2. Pastikan Model User sudah dimuat dan koneksi DB sudah diset oleh config.php
if (!class_exists('User')) {
    $critical_error_user_model = "KRITIS register.php: Model User tidak ditemukan setelah config.php dimuat.";
    error_log($critical_error_user_model);
    if (function_exists('set_flash_message') && function_exists('redirect') && defined('BASE_URL')) {
        set_flash_message('danger', 'Kesalahan sistem registrasi (UMNF_REG). Silakan coba lagi nanti.');
        redirect(BASE_URL);
    } else {
        exit("Kesalahan sistem registrasi (UMNF_REG).");
    }
}

// 3. Logika Halaman

// Redirect jika sudah login
if (function_exists('is_logged_in') && is_logged_in()) {
    $destination_path = (function_exists('is_admin') && is_admin()) ? (defined('ADMIN_URL') ? ADMIN_URL . 'dashboard.php' : BASE_URL . 'admin/dashboard.php') : (defined('USER_URL') ? USER_URL . 'dashboard.php' : BASE_URL . 'user/dashboard.php');
    if (function_exists('redirect')) redirect($destination_path);
    exit;
}

// Initialize variables for form pre-filling
$nama_pengguna_input_val = ''; // Untuk input 'Nama Pengguna'
$nama_lengkap_input_val = '';  // Untuk input 'Nama Lengkap'
$email_input_val = '';
$no_hp_input_val = '';
$alamat_input_val = '';

// Repopulate form from session if validation failed
if (isset($_SESSION['register_form_data'])) {
    $formData = $_SESSION['register_form_data'];
    $nama_pengguna_input_val = function_exists('e') ? e($formData['nama_pengguna'] ?? '') : htmlspecialchars($formData['nama_pengguna'] ?? '', ENT_QUOTES, 'UTF-8');
    $nama_lengkap_input_val = function_exists('e') ? e($formData['nama_lengkap'] ?? '') : htmlspecialchars($formData['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8');
    $email_input_val = function_exists('e') ? e($formData['email'] ?? '') : htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $no_hp_input_val = function_exists('e') ? e($formData['no_hp'] ?? '') : htmlspecialchars($formData['no_hp'] ?? '', ENT_QUOTES, 'UTF-8');
    $alamat_input_val = function_exists('e') ? e($formData['alamat'] ?? '') : htmlspecialchars($formData['alamat'] ?? '', ENT_QUOTES, 'UTF-8');
    unset($_SESSION['register_form_data']);
}


if (function_exists('is_post') && is_post()) {
    // Verifikasi CSRF Token
    if (function_exists('verify_csrf_token') && !verify_csrf_token()) {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF). Silakan coba lagi.');
        if (function_exists('redirect')) redirect(defined('AUTH_URL') ? AUTH_URL . 'register.php' : 'register.php');
        exit;
    }

    // Ambil data dari POST
    $nama_pengguna_from_post = function_exists('input') ? input('nama_pengguna') : trim($_POST['nama_pengguna'] ?? '');
    $nama_lengkap_from_post = function_exists('input') ? input('nama_lengkap') : trim($_POST['nama_lengkap'] ?? '');
    $email_from_post = function_exists('input') ? input('email') : trim($_POST['email'] ?? '');
    $password_from_post = $_POST['password'] ?? '';
    $confirm_password_from_post = $_POST['confirm_password'] ?? '';
    $no_hp_from_post = function_exists('input') ? input('no_hp') : trim($_POST['no_hp'] ?? '');
    $alamat_from_post = function_exists('input') ? input('alamat') : trim($_POST['alamat'] ?? '');

    $foto_profil_db_filename = null;

    $_SESSION['register_form_data'] = [
        'nama_pengguna' => $nama_pengguna_from_post,
        'nama_lengkap' => $nama_lengkap_from_post,
        'email' => $email_from_post,
        'no_hp' => $no_hp_from_post,
        'alamat' => $alamat_from_post,
    ];

    $errors = [];
    if (empty($nama_pengguna_from_post)) $errors[] = 'Nama Pengguna wajib diisi.';
    // Tambahkan validasi tambahan untuk nama_pengguna jika perlu (misal: panjang, karakter alfanumerik, unik jika ini adalah username)
    // Contoh: if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $nama_pengguna_from_post)) $errors[] = 'Nama Pengguna hanya boleh berisi huruf, angka, underscore, dan 3-20 karakter.';

    if (empty($nama_lengkap_from_post)) $errors[] = 'Nama Lengkap wajib diisi.';

    if (empty($email_from_post)) $errors[] = 'Alamat Email wajib diisi.';
    elseif (!filter_var($email_from_post, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format alamat email tidak valid.';
    if (empty($password_from_post)) $errors[] = 'Password wajib diisi.';
    elseif (strlen($password_from_post) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password_from_post !== $confirm_password_from_post) $errors[] = 'Password dan Konfirmasi Password tidak cocok.';
    if (!empty($no_hp_from_post) && !preg_match('/^[0-9]{10,15}$/', $no_hp_from_post)) $errors[] = 'Format Nomor HP tidak valid (hanya angka, 10-15 digit).';

    // Validasi dan proses upload foto profil (logika ini tetap sama)
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_profil'];
        if (!defined('UPLOADS_PROFIL_PATH')) {
            $errors[] = 'Konfigurasi path upload profil tidak ditemukan. Hubungi administrator.';
            error_log("KRITIS register.php: Konstanta UPLOADS_PROFIL_PATH tidak terdefinisi.");
        } else {
            $target_dir_absolute = rtrim(UPLOADS_PROFIL_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (!is_dir($target_dir_absolute) || !is_writable($target_dir_absolute)) {
                $errors[] = 'Direktori upload profil tidak ada atau tidak dapat ditulis. Hubungi administrator.';
                error_log("KRITIS register.php: Direktori upload profil ('{$target_dir_absolute}') tidak ada atau tidak writable.");
            }
        }

        if (empty($errors)) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_file_size = 2 * 1024 * 1024; // 2MB
            $file_type = mime_content_type($file['tmp_name']);
            $file_size = $file['size'];

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = 'Format foto profil tidak valid. Hanya JPG, PNG, atau GIF yang diizinkan.';
            } elseif ($file_size > $max_file_size) {
                $errors[] = 'Ukuran foto profil terlalu besar. Maksimal 2MB.';
            } else {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $errors[] = 'Ekstensi file foto profil tidak diizinkan.';
                } else {
                    $new_file_name = uniqid('user_') . '_' . time() . '.' . $file_extension;
                    $target_file_absolute = $target_dir_absolute . $new_file_name;
                    if (move_uploaded_file($file['tmp_name'], $target_file_absolute)) {
                        $foto_profil_db_filename = $new_file_name;
                    } else {
                        $errors[] = 'Gagal mengunggah foto profil. Silakan coba lagi.';
                        error_log("Upload Gagal: Tidak bisa memindahkan file '{$file['tmp_name']}' ke '{$target_file_absolute}'. Error PHP: " . ($file['error'] ?? 'Tidak ada info error PHP'));
                    }
                }
            }
        }
    } elseif (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors_map = [ /* ... peta error ... */];
        $error_code = $_FILES['foto_profil']['error'];
        $errors[] = 'Gagal mengunggah foto profil: ' . ($upload_errors_map[$error_code] ?? 'Error tidak diketahui (kode: ' . $error_code . ')');
    }

    if (!empty($errors)) {
        if (function_exists('set_flash_message')) set_flash_message('danger', implode('<br>', $errors));
    } else {
        $data_to_register = [
            'nama' => $nama_pengguna_from_post, // Untuk kolom `nama` (username/display name)
            'nama_lengkap' => $nama_lengkap_from_post, // Untuk kolom `nama_lengkap`
            'email' => $email_from_post,
            'password' => $password_from_post,
            'no_hp' => !empty($no_hp_from_post) ? $no_hp_from_post : null,
            'alamat' => !empty($alamat_from_post) ? $alamat_from_post : null,
            'foto_profil' => $foto_profil_db_filename,
            'role' => 'user',
            'status_akun' => 'aktif'
        ];

        $registration_result = User::register($data_to_register);

        if ($registration_result === 'email_exists') {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Alamat email sudah terdaftar. Silakan gunakan email lain atau login.');
        } elseif ($registration_result === 'username_exists') { // Jika model User::register() bisa mendeteksi username duplikat
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Nama Pengguna sudah digunakan. Silakan pilih nama lain.');
        } elseif (is_numeric($registration_result) && $registration_result > 0) {
            unset($_SESSION['register_form_data']);
            if (function_exists('set_flash_message')) set_flash_message('success', 'Registrasi berhasil! Akun Anda telah dibuat. Silakan login.');
            if (function_exists('redirect')) redirect(defined('AUTH_URL') ? AUTH_URL . 'login.php' : 'login.php');
            exit;
        } else {
            // ... (penanganan error registrasi lainnya tetap sama) ...
            $model_error_msg = method_exists('User', 'getLastError') ? User::getLastError() : null;
            $final_error_msg = 'Registrasi gagal. ';
            $default_model_no_db_error_pattern = '/^Tidak ada error database spesifik yang dilaporkan dari model User\.$/';
            $koneksi_problem_pattern = '/koneksi database|koneksi belum diset/i';

            if (is_string($registration_result) && !empty($registration_result)) {
                $final_error_msg .= e($registration_result);
            } elseif ($model_error_msg && !preg_match($default_model_no_db_error_pattern, $model_error_msg) && !preg_match($koneksi_problem_pattern, $model_error_msg)) {
                $final_error_msg .= 'Detail dari sistem: ' . e($model_error_msg);
            } elseif ($model_error_msg && preg_match($koneksi_problem_pattern, $model_error_msg)) {
                $final_error_msg .= 'Terjadi masalah koneksi internal.';
            } else {
                $final_error_msg .= 'Terjadi kesalahan yang tidak diketahui. Silakan coba lagi nanti atau hubungi dukungan.';
            }
            if (function_exists('set_flash_message')) set_flash_message('danger', $final_error_msg);
            error_log("Registrasi User Gagal. Hasil dari User::register(): " . print_r($registration_result, true) . ". Pesan dari User::getLastError(): [" . ($model_error_msg ?? 'N/A') . "]. Data dikirim: " . print_r($data_to_register, true));
        }
    }
    if (function_exists('redirect')) redirect(defined('AUTH_URL') ? AUTH_URL . 'register.php' : 'register.php');
    exit;
}

$pageTitle = "Registrasi Akun - " . (defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Cilengkrang Web Wisata");
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
        /* ... CSS tetap sama ... */
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
            max-width: 580px;
            width: 100%;
            margin: 20px auto;
            padding: 30px 35px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .register-container .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .register-container .logo-container img {
            max-height: 60px;
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #256e48;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            font-weight: 700;
            font-size: 1.7rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.3rem;
        }

        .form-control {
            border-radius: 0.3rem;
            padding: 0.7rem 0.9rem;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.2);
        }

        .btn-success {
            background-color: #2E8B57;
            border-color: #2E8B57;
            padding: 0.7rem;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .btn-success:hover,
        .btn-success:focus {
            background-color: #256e48;
            border-color: #256e48;
        }

        .footer-text-container {
            text-align: center;
            color: #6c757d;
            font-size: 0.85em;
            margin-top: 30px;
            width: 100%;
            padding-bottom: 15px;
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

        .alert {
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="logo-container">
            <?php
            $logo_src = defined('ASSETS_URL') ? ASSETS_URL . 'img/logo.png' : (defined('BASE_URL') ? BASE_URL . 'public/img/logo.png' : '');
            $nama_situs_alt = defined('NAMA_SITUS') ? e(NAMA_SITUS) : 'Cilengkrang Web Wisata';
            if ($logo_src): ?><a href="<?= e(defined('BASE_URL') ? BASE_URL : './') ?>"><img src="<?= e($logo_src) ?>" alt="Logo <?= $nama_situs_alt ?>"></a><?php endif; ?>
        </div>
        <h2>Buat Akun Baru</h2>
        <?= function_exists('display_flash_message') ? display_flash_message() : '' ?>

        <form action="<?= e(defined('AUTH_URL') ? AUTH_URL . 'register.php' : (defined('BASE_URL') ? BASE_URL . 'auth/register.php' : 'register.php')) ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?= function_exists('generate_csrf_token_input') ? generate_csrf_token_input() : '' ?>

            <div class="mb-3">
                <label for="nama_pengguna" class="form-label">Nama Pengguna <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_pengguna" name="nama_pengguna" value="<?= $nama_pengguna_input_val ?>" required placeholder="Username atau nama tampilan singkat">
                <div class="invalid-feedback">Nama Pengguna wajib diisi.</div>
            </div>

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= $nama_lengkap_input_val ?>" required placeholder="Nama sesuai identitas">
                <div class="invalid-feedback">Nama Lengkap wajib diisi.</div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $email_input_val ?>" required>
                <div class="invalid-feedback">Format email tidak valid atau wajib diisi.</div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    <small class="form-text text-muted">Minimal 6 karakter.</small>
                    <div class="invalid-feedback">Password minimal 6 karakter.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    <div class="invalid-feedback">Konfirmasi password wajib diisi dan cocok.</div>
                </div>
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">Nomor HP <span class="text-muted">(Opsional)</span></label>
                <input type="tel" class="form-control" id="no_hp" name="no_hp" value="<?= $no_hp_input_val ?>" placeholder="Contoh: 081234567890" pattern="^[0-9]{10,15}$">
                <div class="invalid-feedback">Format Nomor HP tidak valid (10-15 digit angka).</div>
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat <span class="text-muted">(Opsional)</span></label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Masukkan alamat lengkap Anda"><?= $alamat_input_val ?></textarea>
            </div>
            <div class="mb-3">
                <label for="foto_profil" class="form-label">Foto Profil <span class="text-muted">(Opsional)</span></label>
                <input type="file" class="form-control" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/gif">
                <small class="form-text text-muted">Format: JPG, PNG, GIF. Maksimal 2MB.</small>
                <div class="invalid-feedback">Pilih file foto profil yang valid.</div>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 mt-2">Daftar Akun</button>
        </form>
        <div class="text-center mt-3">
            <p class="mb-1">Sudah punya akun? <a href="<?= e(defined('AUTH_URL') ? AUTH_URL . 'login.php' : (defined('BASE_URL') ? BASE_URL . 'auth/login.php' : 'login.php')) ?>">Login di sini</a></p>
            <p><a href="<?= e(defined('BASE_URL') ? BASE_URL : './') ?>"><i class="fas fa-home me-1"></i>Kembali ke Beranda</a></p>
        </div>
    </div>

    <div class="footer-text-container">
        Hak Cipta © <?= date("Y"); ?> <?= defined('NAMA_SITUS') ? e(NAMA_SITUS) : "Cilengkrang Web Wisata" ?>. Semua hak dilindungi.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ... JavaScript validasi Bootstrap tetap sama ... */
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            // event.preventDefault()
                            // event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>

</html>