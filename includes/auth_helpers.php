<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\auth_helpers.php

/**
 * File Helper Otentikasi & Otorisasi - Cilengkrang Web Wisata
 * 
 * Berisi fungsi-fungsi bantuan terkait otentikasi dan otorisasi pengguna.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memanggil session_start() di paling awal.
 * - Diasumsikan config.php sudah memuat helpers.php (untuk redirect(), e()) 
 *   dan flash_message.php (untuk set_flash_message()) SEBELUM file ini.
 * - Konstanta BASE_URL, ADMIN_URL, USER_URL harus sudah terdefinisi di config.php.
 */

if (session_status() == PHP_SESSION_NONE) {
    error_log("KRITIKAL auth_helpers.php: Session belum dimulai. Fungsi otentikasi mungkin tidak bekerja dengan benar.");
    // Pertimbangkan untuk memulai sesi di sini jika belum, meskipun idealnya di config.php
    // if (!headers_sent()) { session_start(); } 
}

// Pengecekan dependensi dasar
if (!defined('BASE_URL')) {
    error_log("FATAL ERROR auth_helpers.php: Konstanta BASE_URL tidak terdefinisi.");
    // exit("Kesalahan konfigurasi: BASE_URL tidak ditemukan."); // Mungkin terlalu drastis untuk helper
}
if (!function_exists('redirect')) {
    error_log("FATAL ERROR auth_helpers.php: Fungsi redirect() tidak terdefinisi.");
}
if (!function_exists('set_flash_message')) {
    error_log("FATAL ERROR auth_helpers.php: Fungsi set_flash_message() tidak terdefinisi.");
}


if (!function_exists('is_logged_in')) {
    /** Memeriksa apakah pengguna sudah login. */
    function is_logged_in()
    {
        if (session_status() == PHP_SESSION_NONE) return false;
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    /** Memeriksa apakah pengguna yang login adalah admin. */
    function is_admin()
    {
        if (session_status() == PHP_SESSION_NONE) return false;
        return is_logged_in() && isset($_SESSION['user_role']) && strtolower((string)$_SESSION['user_role']) === 'admin';
    }
}

if (!function_exists('is_user')) {
    /** Memeriksa apakah pengguna yang login adalah user biasa. */
    function is_user()
    {
        if (session_status() == PHP_SESSION_NONE) return false;
        return is_logged_in() && isset($_SESSION['user_role']) && strtolower((string)$_SESSION['user_role']) === 'user';
    }
}

if (!function_exists('get_current_user_id')) {
    /** Mengambil ID pengguna yang sedang login. */
    function get_current_user_id()
    {
        if (session_status() == PHP_SESSION_NONE) return null;
        return (is_logged_in() && isset($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : null;
    }
}

if (!function_exists('get_current_user_name')) {
    /** Mengambil nama pengguna yang sedang login. */
    function get_current_user_name()
    {
        if (session_status() == PHP_SESSION_NONE || !is_logged_in()) return 'Pengguna'; // Default jika tidak login
        return isset($_SESSION['user_nama_lengkap']) && !empty(trim((string)$_SESSION['user_nama_lengkap']))
            ? (string)$_SESSION['user_nama_lengkap']
            : (isset($_SESSION['user_nama']) && !empty(trim((string)$_SESSION['user_nama']))
                ? (string)$_SESSION['user_nama']
                : 'Pengguna');
    }
}

if (!function_exists('get_current_user_data')) {
    /** Mengambil data pengguna tertentu dari session. */
    function get_current_user_data($key, $default = null)
    {
        if (session_status() == PHP_SESSION_NONE || !is_logged_in()) return $default;
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('logout_user')) {
    /**
     * Melakukan logout pengguna dengan menghancurkan sesi.
     * PENTING: Fungsi ini hanya menghancurkan sesi, redirect harus dilakukan oleh skrip pemanggil.
     */
    function logout_user()
    {
        if (session_status() == PHP_SESSION_NONE) {
            // Jika sesi belum dimulai, tidak banyak yang bisa dilakukan selain mencoba memulainya
            // untuk membersihkan cookie, tapi ini berisiko jika output sudah terkirim.
            // Biasanya, config.php sudah memastikan sesi dimulai.
            error_log("logout_user: Dipanggil saat sesi belum aktif.");
            if (!headers_sent()) session_start();
            else return false; // Gagal jika header terkirim
        }

        // Hapus semua variabel sesi.
        $_SESSION = array();

        // Jika diinginkan, hapus juga cookie sesi.
        // Catatan: Ini akan menghancurkan sesi, dan bukan hanya data sesi!
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

        // Akhirnya, hancurkan sesi.
        if (session_status() == PHP_SESSION_ACTIVE) { // Hanya destroy jika aktif
            session_destroy();
        }
        error_log("logout_user: Sesi dihancurkan.");
        return true;
    }
}


if (!function_exists('require_login')) {
    /** Mewajibkan pengguna untuk login. */
    function require_login($redirect_to_login_page = 'auth/login.php')
    {
        if (!is_logged_in()) {
            if (session_status() == PHP_SESSION_ACTIVE && defined('BASE_URL')) {
                $current_request_uri = $_SERVER['REQUEST_URI'] ?? '';
                $base_path_for_redirect = ltrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
                $current_path = ltrim(strtok($current_request_uri, '?'), '/');

                if (!empty($base_path_for_redirect) && strpos($current_path, $base_path_for_redirect) === 0) {
                    $current_path = ltrim(substr($current_path, strlen($base_path_for_redirect)), '/');
                }
                $_SESSION['redirect_url_after_login'] = $current_path . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
                error_log("require_login: redirect_url_after_login diset ke: " . $_SESSION['redirect_url_after_login']);
            }
            if (function_exists('set_flash_message')) set_flash_message('warning', 'Anda harus login untuk mengakses halaman ini.');
            if (function_exists('redirect')) redirect($redirect_to_login_page);
            else exit('Akses ditolak.');
        }
    }
}

if (!function_exists('require_admin')) {
    /** Mewajibkan pengguna untuk login sebagai admin. */
    function require_admin($redirect_to_login_page = 'auth/login.php', $redirect_to_non_admin_page = null)
    {
        if (!is_logged_in()) {
            if (session_status() == PHP_SESSION_ACTIVE && defined('BASE_URL')) {
                // ... (logika set redirect_url_after_login sama seperti di require_login)
                $current_request_uri = $_SERVER['REQUEST_URI'] ?? '';
                $base_path_for_redirect = ltrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
                $current_path = ltrim(strtok($current_request_uri, '?'), '/');
                if (!empty($base_path_for_redirect) && strpos($current_path, $base_path_for_redirect) === 0) {
                    $current_path = ltrim(substr($current_path, strlen($base_path_for_redirect)), '/');
                }
                $_SESSION['redirect_url_after_login'] = $current_path . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            }
            if (function_exists('set_flash_message')) set_flash_message('warning', 'Akses ditolak. Anda harus login sebagai administrator.');
            if (function_exists('redirect')) redirect($redirect_to_login_page);
            else exit('Akses ditolak.');
        } elseif (!is_admin()) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak! Halaman ini hanya untuk Administrator.');
            $fallback_redirect = $redirect_to_non_admin_page;
            if ($fallback_redirect === null) {
                $fallback_redirect = (is_user() && defined('USER_URL')) ? USER_URL . '/dashboard.php' : (defined('BASE_URL') ? BASE_URL : 'index.php');
            }
            if (function_exists('redirect')) redirect($fallback_redirect);
            else exit('Akses ditolak.');
        }
    }
}

if (!function_exists('redirect_if_logged_in')) {
    /** Mengarahkan pengguna ke dashboard jika sudah login. */
    function redirect_if_logged_in()
    {
        if (is_logged_in()) {
            $destination = defined('BASE_URL') ? BASE_URL : '/'; // Default aman
            if (is_admin() && defined('ADMIN_URL')) {
                $destination = ADMIN_URL . '/dashboard.php';
            } elseif (defined('USER_URL')) {
                $destination = USER_URL . '/dashboard.php';
            } else {
                error_log("PERINGATAN redirect_if_logged_in: ADMIN_URL atau USER_URL tidak terdefinisi. Mengarahkan ke BASE_URL atau root.");
            }
            if (function_exists('redirect')) redirect($destination);
        }
    }
}

// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.
