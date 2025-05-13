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
 * - Konstanta BASE_URL harus sudah terdefinisi di config.php.
 */

// Pengecekan session sudah dimulai atau belum (seharusnya sudah oleh config.php)
if (session_status() == PHP_SESSION_NONE) {
    // Ini adalah kondisi error kritis jika config.php tidak memulai session.
    // Fungsi-fungsi di sini tidak akan bekerja dengan benar.
    error_log("KRITIKAL di auth_helpers.php: Session belum dimulai. Fungsi otentikasi tidak akan bekerja. Pastikan config.php memanggil session_start().");
    // Pertimbangkan untuk exit atau throw exception di sini jika session adalah keharusan absolut untuk file ini
    // exit("Kesalahan konfigurasi server: Sesi aplikasi tidak aktif."); 
}

// Pengecekan dependensi penting
if (!defined('BASE_URL')) {
    error_log("FATAL ERROR di auth_helpers.php: Konstanta BASE_URL tidak terdefinisi.");
    exit("Kesalahan konfigurasi server kritis: BASE_URL tidak ditemukan.");
}
if (!function_exists('redirect')) {
    error_log("FATAL ERROR di auth_helpers.php: Fungsi redirect() tidak terdefinisi.");
    exit("Kesalahan konfigurasi server kritis: Fungsi redirect() tidak ditemukan.");
}
if (!function_exists('set_flash_message')) {
    error_log("FATAL ERROR di auth_helpers.php: Fungsi set_flash_message() tidak terdefinisi.");
    exit("Kesalahan konfigurasi server kritis: Fungsi set_flash_message() tidak ditemukan.");
}

/**
 * Memeriksa apakah pengguna sudah login.
 * @return bool True jika login, false jika tidak.
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        // Pastikan session aktif sebelum mengakses $_SESSION
        if (session_status() == PHP_SESSION_NONE) return false;
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
    }
}

/**
 * Memeriksa apakah pengguna yang login adalah admin.
 * @return bool True jika admin, false jika tidak atau belum login.
 */
if (!function_exists('is_admin')) {
    function is_admin()
    {
        if (session_status() == PHP_SESSION_NONE) return false;
        return is_logged_in() && isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
    }
}

/**
 * Memeriksa apakah pengguna yang login adalah user biasa (bukan admin).
 * @return bool True jika user biasa, false jika admin, tidak ada role, atau belum login.
 */
if (!function_exists('is_user')) {
    function is_user()
    {
        if (session_status() == PHP_SESSION_NONE) return false;
        return is_logged_in() && isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'user';
    }
}

/**
 * Mengambil ID pengguna yang sedang login.
 * @return int|null ID pengguna atau null jika tidak login.
 */
if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        if (session_status() == PHP_SESSION_NONE) return null;
        return is_logged_in() && isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

/**
 * Mengambil nama pengguna yang sedang login.
 * Pastikan $_SESSION['user_nama_lengkap'] (atau key yang sesuai) diset saat login.
 * @return string Nama pengguna atau string kosong jika tidak login/tidak ada nama.
 */
if (!function_exists('get_current_user_name')) {
    function get_current_user_name() // Mengganti nama key session menjadi lebih umum
    {
        if (session_status() == PHP_SESSION_NONE) return '';
        // Sesuaikan 'user_nama_lengkap' dengan key session yang Anda gunakan untuk nama pengguna
        return is_logged_in() && isset($_SESSION['user_nama_lengkap']) ? (string)$_SESSION['user_nama_lengkap'] : (is_logged_in() && isset($_SESSION['user_nama']) ? (string)$_SESSION['user_nama'] : ''); // Fallback jika pakai 'user_nama'
    }
}

/**
 * Mengambil data pengguna tertentu dari session.
 * @param string $key Kunci data di session (misal: 'user_email', 'user_role').
 * @param mixed $default Nilai default jika kunci tidak ditemukan.
 * @return mixed Data pengguna atau nilai default.
 */
if (!function_exists('get_current_user_data')) {
    function get_current_user_data($key, $default = null)
    {
        if (session_status() == PHP_SESSION_NONE) return $default;
        return is_logged_in() && isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
}


/**
 * Mewajibkan pengguna untuk login (role apapun). Jika tidak, redirect ke halaman login.
 * @param string $redirect_to_login_page Path ke halaman login relatif terhadap BASE_URL.
 */
if (!function_exists('require_login')) {
    function require_login($redirect_to_login_page = 'auth/login.php')
    {
        if (!is_logged_in()) {
            // Simpan URL halaman saat ini (path relatif) untuk redirect setelah login
            $relative_current_page = ltrim(str_replace(rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/'), '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
            $_SESSION['redirect_url_after_login'] = $relative_current_page . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

            set_flash_message('warning', 'Anda harus login untuk mengakses halaman ini.');
            redirect($redirect_to_login_page); // redirect() akan melakukan exit
        }
    }
}

/**
 * Mewajibkan pengguna untuk login sebagai admin.
 * Fungsi ini akan menghentikan eksekusi skrip jika kondisi tidak terpenuhi.
 * @param string $redirect_to_login_page Path ke halaman login.
 * @param string|null $redirect_to_non_admin_page Path jika login tapi bukan admin (null untuk fallback ke user/dashboard.php atau /).
 */
if (!function_exists('require_admin')) {
    function require_admin($redirect_to_login_page = 'auth/login.php', $redirect_to_non_admin_page = null)
    {
        if (!is_logged_in()) {
            $relative_current_page = ltrim(str_replace(rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/'), '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
            $_SESSION['redirect_url_after_login'] = $relative_current_page . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

            set_flash_message('warning', 'Akses ditolak. Anda harus login sebagai administrator.');
            redirect($redirect_to_login_page);
        } elseif (!is_admin()) {
            set_flash_message('danger', 'Akses ditolak! Halaman ini hanya untuk Administrator.');
            // Jika $redirect_to_non_admin_page null, tentukan fallback
            $fallback_redirect = $redirect_to_non_admin_page ?? (is_user() ? USER_URL . '/dashboard.php' : BASE_URL);
            // Pastikan path relatif jika bukan URL penuh
            if (strpos($fallback_redirect, BASE_URL) !== 0 && !preg_match('#^https?://#i', $fallback_redirect)) {
                $fallback_redirect = ltrim($fallback_redirect, '/');
            }
            redirect($fallback_redirect);
        }
    }
}

/**
 * Mengarahkan pengguna ke dashboard jika sudah login.
 * Biasanya dipanggil di halaman login atau register.
 */
if (!function_exists('redirect_if_logged_in')) {
    function redirect_if_logged_in()
    {
        if (is_logged_in()) {
            if (is_admin()) {
                redirect(ADMIN_URL . '/dashboard.php'); // Menggunakan konstanta ADMIN_URL
            } else {
                redirect(USER_URL . '/dashboard.php');  // Menggunakan konstanta USER_URL
            }
        }
    }
}

// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.