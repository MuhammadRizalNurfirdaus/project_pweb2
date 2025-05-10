<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\AuthController.php
// Skrip pemanggil (login.php) HARUS sudah include config.php
require_once __DIR__ . '/../models/User.php'; // Model User

class AuthController
{
    public static function processLogin($email, $password)
    {
        $user = User::login($email, $password); // Memanggil method static dari model

        if ($user) {
            // Login berhasil, set session variables
            session_regenerate_id(true); // Keamanan
            $_SESSION['is_loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function processLogout()
    {
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
        session_destroy();
    }
}
