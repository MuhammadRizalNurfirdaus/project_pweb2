<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\AuthController.php

/**
 * AuthController
 * Bertanggung jawab untuk logika otentikasi pengguna (login, logout, dll.).
 * Berinteraksi dengan Model User.
 * 
 * PENTING:
 * - Diasumsikan config.php (memuat $conn, helpers.php, flash_message.php, auth_helpers.php) sudah dimuat.
 * - Diasumsikan Model User.php sudah dimuat dan User::setDbConnection($conn) sudah dipanggil.
 */

// Jika tidak menggunakan autoloader dan config.php tidak memuat semua model, baris ini diperlukan.
// Namun, jika User.php dimuat oleh config.php atau autoloader, baris ini bisa dihapus.
if (!class_exists('User')) { // Hanya muat jika belum ada
    $userModelPath = __DIR__ . '/../models/User.php';
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
        // PENTING: Jika config.php tidak memanggil User::setDbConnection($conn),
        // dan Anda memuat User.php di sini, Anda mungkin perlu memanggilnya di sini juga.
        // Namun, ini bukan praktik terbaik. Idealnya, semua setup model ada di config.php.
        // global $conn; // Hanya jika diperlukan dan $conn adalah global
        // if (isset($conn) && $conn instanceof mysqli && method_exists('User', 'setDbConnection')) {
        //     User::setDbConnection($conn);
        // }
    } else {
        error_log("FATAL ERROR di AuthController: Model User.php tidak ditemukan.");
        // Ini akan menyebabkan error di metode lain jika User tidak bisa dimuat.
    }
}


class AuthController
{
    /**
     * Memproses upaya login pengguna.
     * @param string $email Email yang dimasukkan pengguna.
     * @param string $password Password mentah yang dimasukkan pengguna.
     * @return bool True jika login berhasil dan session diatur, false jika gagal.
     *              Flash message akan diatur oleh metode ini jika terjadi kegagalan spesifik.
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

        // Pastikan Model User dan metode login tersedia
        if (!class_exists('User') || !method_exists('User', 'login')) {
            error_log("AuthController::processLogin() - Model User atau metode login tidak ditemukan.");
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', 'Kesalahan sistem: Komponen otentikasi tidak siap (M01).');
            }
            return false;
        }

        // Panggil metode login dari Model User
        $user_data_or_error = User::login($email, $password);

        if (is_array($user_data_or_error)) {
            // User::login() mengembalikan data pengguna, berarti email dan password cocok.
            // Sekarang periksa status akun.
            if (isset($user_data_or_error['status_akun']) && strtolower($user_data_or_error['status_akun']) !== 'aktif') {
                $status_display = htmlspecialchars(ucfirst(str_replace('_', ' ', $user_data_or_error['status_akun'])));
                $message = "Login gagal. Akun Anda saat ini berstatus '{$status_display}'. Silakan hubungi administrator.";
                if (function_exists('set_flash_message')) {
                    set_flash_message('warning', $message);
                }
                error_log("AuthController::processLogin() - Login Gagal: Akun tidak aktif ('{$user_data_or_error['status_akun']}') untuk email - " . $email);
                return false;
            }

            // Login berhasil dan akun aktif, atur variabel-variabel session
            if (session_status() == PHP_SESSION_NONE) {
                // Ini seharusnya tidak terjadi jika config.php sudah benar
                error_log("Peringatan di AuthController::processLogin(): Session belum dimulai, memulai darurat.");
                session_start();
            }
            session_regenerate_id(true); // Regenerasi ID session untuk keamanan

            $_SESSION['user_id'] = (int)$user_data_or_error['id'];
            // Gunakan nama kolom yang konsisten (misal 'nama_lengkap' dari User Model)
            $_SESSION['user_nama_lengkap'] = $user_data_or_error['nama_lengkap'] ?? ($user_data_or_error['nama'] ?? 'Pengguna');
            $_SESSION['user_email'] = $user_data_or_error['email'];
            $_SESSION['user_role'] = strtolower($user_data_or_error['role']);
            // Tambahkan data lain yang mungkin berguna di session
            $_SESSION['user_no_hp'] = $user_data_or_error['no_hp'] ?? null;
            $_SESSION['is_loggedin'] = true; // Set setelah semua data user diset

            // Hapus data form dari session login jika ada (untuk repopulasi)
            unset($_SESSION['flash_form_data_login']);

            error_log("INFO: User ID " . $_SESSION['user_id'] . " (" . $_SESSION['user_email'] . ") berhasil login dengan role " . $_SESSION['user_role'] . ".");
            return true;
        } elseif (is_string($user_data_or_error)) {
            // User::login() mengembalikan string kode error
            $error_message_display = 'Kombinasi email atau password salah.'; // Pesan default
            switch ($user_data_or_error) {
                case 'not_found':
                    $error_message_display = 'Email yang Anda masukkan tidak terdaftar.';
                    break;
                case 'wrong_password':
                    $error_message_display = 'Password yang Anda masukkan salah.';
                    break;
                case 'inactive_account': // Ini seharusnya sudah ditangani di blok array, tapi sebagai fallback
                    $error_message_display = 'Akun Anda tidak aktif. Silakan hubungi administrator.';
                    break;
            }
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', $error_message_display);
            }
            error_log("AuthController::processLogin() - Login Gagal dengan kode: {$user_data_or_error} untuk email: " . $email);
            return false;
        } else {
            // User::login() mengembalikan false (kemungkinan error DB di Model atau kasus lain)
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) { // Hanya set jika Model belum set
                set_flash_message('danger', 'Login gagal. Terjadi kesalahan pada sistem. Silakan coba lagi nanti.');
            }
            error_log("AuthController::processLogin() - Login Gagal (User::login mengembalikan false) untuk email: " . $email);
            return false;
        }
    }

    /**
     * Memproses logout pengguna.
     * Session akan dihancurkan dan pengguna diarahkan.
     */
    public static function processLogout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            // Jika session belum ada, tidak ada yang perlu di-logout, tapi ini aneh.
            // Untuk keamanan, coba mulai jika bisa, agar bisa dihancurkan.
            if (!headers_sent()) session_start();
            else {
                error_log("Peringatan di AuthController::processLogout(): Session tidak aktif dan header sudah terkirim. Logout mungkin tidak sempurna.");
                // Langsung redirect saja jika tidak bisa memulai session untuk dihancurkan
                if (function_exists('redirect') && defined('BASE_URL')) {
                    redirect('auth/login.php?status=logout_err_session');
                } else {
                    header('Location: /Cilengkrang-Web-Wisata/auth/login.php?status=logout_err_session'); // Ganti dengan path absolut fallback Anda
                    exit;
                }
            }
        }

        // Hapus semua variabel session
        $_SESSION = array();

        // Hapus cookie session jika digunakan
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000, // Waktu di masa lalu untuk menghapus cookie
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Hancurkan session
        session_destroy();

        // Redirect ke halaman login atau halaman utama
        // Flash message setelah session_destroy() tidak akan persisten.
        // Biasanya, pesan logout ditampilkan di halaman login melalui parameter URL.
        if (function_exists('redirect') && defined('BASE_URL')) {
            redirect('auth/login.php?status=logout_success'); // Menggunakan redirect dari helpers
        } else {
            // Fallback jika fungsi redirect tidak ada (seharusnya tidak terjadi jika config dimuat)
            // Sesuaikan path absolut ini jika BASE_URL tidak tersedia
            $fallback_logout_url = (defined('BASE_URL') ? BASE_URL : '/Cilengkrang-Web-Wisata/') . 'auth/login.php?status=logout_success';
            header('Location: ' . $fallback_logout_url);
            exit;
        }
    }

    // Anda bisa menambahkan metode lain di sini, seperti processRegistration, forgotPassword, resetPassword, dll.

} // End of class AuthController