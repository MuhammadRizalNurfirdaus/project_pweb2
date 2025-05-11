<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\AuthController.php
// Skrip pemanggil (login.php atau logout.php) HARUS sudah include config.php
require_once __DIR__ . '/../models/User.php'; // Memanggil Model User

class AuthController
{
    /**
     * Memproses upaya login.
     * @param string $email Email yang dimasukkan pengguna.
     * @param string $password Password mentah yang dimasukkan pengguna.
     * @return bool True jika login berhasil, false jika gagal.
     */
    public static function processLogin($email, $password)
    {
        // Memanggil method login dari Model User
        $user = User::login($email, $password);

        if ($user) {
            // Login berhasil, set variabel-variabel session
            session_regenerate_id(true); // Regenerasi ID session untuk keamanan
            $_SESSION['is_loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        // Jika User::login() mengembalikan false (email tidak ada atau password salah)
        return false;
    }

    /**
     * Memproses logout pengguna.
     */
    public static function processLogout()
    {
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
    }
}
