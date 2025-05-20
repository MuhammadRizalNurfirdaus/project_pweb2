<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\AuthController.php

/**
 * AuthController
 * Bertanggung jawab untuk logika otentikasi pengguna (login, logout, dll.).
 * Berinteraksi dengan Model User.
 *
 * PENTING:
 * - Diasumsikan config.php (memuat $conn, helpers.php, flash_message.php, auth_helpers.php) sudah dimuat.
 * - Diasumsikan Model User.php sudah dimuat dan User::init() atau User::setDbConnection() sudah dipanggil di config.php.
 */

// Pengecekan class User (config.php seharusnya sudah memuat ini)
if (!class_exists('User')) {
    error_log("FATAL ERROR di AuthController: Model User tidak ditemukan atau belum dimuat oleh config.php.");
    if (function_exists('set_flash_message')) {
        set_flash_message('danger', 'Kesalahan sistem: Komponen otentikasi inti tidak tersedia (MUSR_NF_AUTHCTRL).');
    }

    // Menentukan URL redirect jika terjadi error fatal
    $redirect_target_on_fatal_error = './index.php'; // Fallback paling dasar jika BASE_URL juga tidak ada
    if (defined('BASE_URL')) {
        $redirect_target_on_fatal_error = BASE_URL; // Default ke Beranda jika BASE_URL ada
    }
    // Jika Anda memiliki halaman error khusus dan konstanta ERROR_PAGE_URL terdefinisi di config.php
    if (defined('ERROR_PAGE_URL')) {
        // Pastikan ERROR_PAGE_URL adalah URL yang valid atau path relatif dari BASE_URL
        $error_page_url_val = ERROR_PAGE_URL;
        // Jika ERROR_PAGE_URL adalah path relatif, gabungkan dengan BASE_URL
        // Ini adalah contoh, sesuaikan dengan bagaimana Anda mendefinisikan ERROR_PAGE_URL
        if (strpos($error_page_url_val, 'http') !== 0 && defined('BASE_URL')) {
            $error_page_url_val = rtrim(BASE_URL, '/') . '/' . ltrim($error_page_url_val, '/');
        }
        $redirect_target_on_fatal_error = $error_page_url_val . (strpos($error_page_url_val, '?') === false ? '?' : '&') . 'code=MUSR_NF_AUTHCTRL';
    }

    if (function_exists('redirect')) {
        redirect($redirect_target_on_fatal_error);
    } else {
        // Fallback header redirect jika fungsi redirect tidak ada
        header('Location: ' . $redirect_target_on_fatal_error);
    }
    // Hentikan eksekusi jika komponen inti hilang
    exit("Kesalahan Kritis: Model User tidak dapat dimuat. Aplikasi tidak dapat melanjutkan.");
}


class AuthController
{
    /**
     * Memproses upaya login pengguna.
     * @param string $email Email yang dimasukkan pengguna.
     * @param string $password Password mentah yang dimasukkan pengguna.
     * @return bool|string True jika login berhasil, false jika gagal umum, atau string kode error dari Model.
     *                     Flash message akan diatur oleh metode ini atau Model User jika terjadi kegagalan.
     */
    public static function processLogin($email, $password)
    {
        // Validasi input dasar di Controller
        if (empty($email) || empty($password)) {
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', 'Email dan password wajib diisi.');
            }
            error_log("AuthController::processLogin() - Input email atau password kosong.");
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', 'Format email tidak valid.');
            }
            error_log("AuthController::processLogin() - Format email tidak valid: " . $email);
            return false;
        }

        // Pastikan metode login di Model User tersedia
        if (!method_exists('User', 'login')) {
            error_log("AuthController::processLogin() - Metode User::login() tidak ditemukan.");
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', 'Kesalahan sistem: Fungsi otentikasi tidak tersedia (M01_ULOGIN_NF).');
            }
            return false;
        }

        // Panggil metode login dari Model User
        $login_attempt_result = User::login($email, $password);

        if (is_array($login_attempt_result)) {
            // User::login() mengembalikan array data pengguna, berarti kredensial cocok.
            // Periksa status akun.
            if (isset($login_attempt_result['status_akun']) && strtolower($login_attempt_result['status_akun']) !== 'aktif') {
                $status_display = htmlspecialchars(ucfirst(str_replace('_', ' ', $login_attempt_result['status_akun'])));
                $message = "Login gagal. Akun Anda saat ini berstatus '{$status_display}'. Silakan hubungi administrator.";
                if (function_exists('set_flash_message')) {
                    set_flash_message('warning', $message);
                }
                error_log("AuthController::processLogin() - Login Gagal: Akun status '{$login_attempt_result['status_akun']}' untuk email: " . $email);
                return false;
            }

            // Login berhasil dan akun aktif, atur variabel-variabel session
            if (session_status() == PHP_SESSION_NONE) {
                error_log("Peringatan di AuthController::processLogin(): Session belum dimulai, memulai darurat.");
                if (!headers_sent($file_login, $line_login)) {
                    session_start();
                } else {
                    error_log("KRITIS AuthController::processLogin(): Tidak bisa memulai session karena header sudah terkirim dari {$file_login}:{$line_login}.");
                    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sesi server. Tidak dapat login.');
                    return false;
                }
            }
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$login_attempt_result['id'];
            $_SESSION['user_nama'] = $login_attempt_result['nama'] ?? 'Pengguna';
            $_SESSION['user_nama_lengkap'] = $login_attempt_result['nama_lengkap'] ?? $_SESSION['user_nama'];
            $_SESSION['user_email'] = $login_attempt_result['email'];
            $_SESSION['user_role'] = strtolower($login_attempt_result['role'] ?? 'user');
            $_SESSION['user_foto_profil'] = $login_attempt_result['foto_profil'] ?? null;
            $_SESSION['is_loggedin'] = true;

            unset($_SESSION['flash_form_data_login']);

            error_log("INFO: Login BERHASIL - User ID " . $_SESSION['user_id'] . " (" . $_SESSION['user_email'] . ") role: " . $_SESSION['user_role']);
            return true;
        } elseif (is_string($login_attempt_result)) {
            $error_message_display = 'Kombinasi email atau password salah.';
            switch ($login_attempt_result) {
                case 'login_invalid_email':
                case 'login_empty_password':
                    $model_err = User::getLastError();
                    $error_message_display = $model_err ?: $error_message_display;
                    break;
                case 'login_failed_credentials':
                    // Pesan default sudah cukup
                    break;
                case 'account_not_active':
                    $error_message_display = 'Akun Anda tidak aktif. Silakan hubungi administrator.';
                    break;
                case 'account_blocked':
                    $error_message_display = 'Akun Anda telah diblokir. Silakan hubungi administrator.';
                    break;
                case 'account_status_unknown':
                    $error_message_display = 'Status akun Anda tidak diketahui. Silakan hubungi administrator.';
                    break;
                default:
                    $error_message_display = "Terjadi kesalahan otentikasi (" . e($login_attempt_result) . ").";
            }
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', $error_message_display);
            }
            error_log("AuthController::processLogin() - Login Gagal (Model return string): '{$login_attempt_result}' untuk email: " . $email);
            return false;
        } else {
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                $model_error = User::getLastError();
                $display_error = $model_error ? e($model_error) : 'Terjadi kesalahan pada sistem.';
                set_flash_message('danger', 'Login gagal. ' . $display_error . ' Silakan coba lagi nanti.');
            }
            error_log("AuthController::processLogin() - Login Gagal (User::login mengembalikan false/tipe tidak dikenal) untuk email: " . $email . ". Model Error: " . (User::getLastError() ?? 'Tidak ada info'));
            return false;
        }
    }

    public static function processLogout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            if (!headers_sent($file_logout, $line_logout)) {
                session_start();
            } else {
                error_log("Peringatan di AuthController::processLogout(): Session tidak aktif dan header sudah terkirim. Output dimulai di {$file_logout}:{$line_logout}. Logout mungkin tidak sempurna.");
                $logout_fallback_url = (defined('AUTH_URL') ? rtrim(AUTH_URL, '/') : (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/auth' : '/auth')) . '/login.php?status=logout_err_session';

                if (function_exists('redirect')) {
                    redirect($logout_fallback_url);
                } else {
                    header('Location: ' . $logout_fallback_url);
                    exit;
                }
            }
        }

        $user_id_logging = $_SESSION['user_id'] ?? 'Tidak diketahui';
        $user_email_logging = $_SESSION['user_email'] ?? 'Tidak diketahui';

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        error_log("INFO: Logout BERHASIL - User ID {$user_id_logging} ({$user_email_logging}) telah logout.");

        $logout_redirect_url = (defined('AUTH_URL') ? rtrim(AUTH_URL, '/') : (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/auth' : '/auth')) . '/login.php?status=logout_success';
        if (function_exists('redirect')) {
            redirect($logout_redirect_url);
        } else {
            header('Location: ' . $logout_redirect_url);
            exit;
        }
    }
}
