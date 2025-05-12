<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\auth_helpers.php

// Pastikan session sudah dimulai (config.php seharusnya sudah melakukannya sebelum include file ini)
if (session_status() == PHP_SESSION_NONE) {
    // Ini sebagai fallback jika config.php belum memulai session,
    // tapi idealnya session_start() hanya dipanggil sekali di awal (misal di config.php).
    session_start();
    error_log("Peringatan di auth_helpers.php: session_start() dipanggil di sini. Seharusnya sudah dimulai oleh config.php.");
}

// Memastikan konstanta dan fungsi dasar dari file lain sudah tersedia.
// Ini lebih ke pengecekan defensif; config.php seharusnya sudah memuatnya.
if (!defined('BASE_URL')) {
    error_log("FATAL ERROR di auth_helpers.php: Konstanta BASE_URL tidak terdefinisi. Pastikan config.php dimuat dengan benar sebelum file ini.");
    // Dalam kasus nyata, Anda mungkin ingin menampilkan halaman error atau menghentikan eksekusi.
    // Untuk contoh ini, kita akan exit agar error jelas.
    exit("Kesalahan konfigurasi server: BASE_URL tidak ditemukan. Fungsi otentikasi tidak dapat berjalan.");
}
if (!function_exists('redirect')) {
    error_log("FATAL ERROR di auth_helpers.php: Fungsi redirect() tidak terdefinisi. Pastikan helpers.php dimuat sebelum auth_helpers.php.");
    exit("Kesalahan konfigurasi server: Fungsi redirect() tidak ditemukan.");
}
if (!function_exists('set_flash_message')) {
    error_log("FATAL ERROR di auth_helpers.php: Fungsi set_flash_message() tidak terdefinisi. Pastikan flash_message.php dimuat.");
    exit("Kesalahan konfigurasi server: Fungsi set_flash_message() tidak ditemukan.");
}


/**
 * Memeriksa apakah pengguna sudah login berdasarkan keberadaan 'user_id' di session.
 * @return bool True jika pengguna login, false jika tidak.
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        // Indikator utama login adalah adanya user_id di session
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Memeriksa apakah pengguna yang login adalah admin.
 * @return bool True jika admin, false jika tidak atau belum login.
 */
if (!function_exists('is_admin')) {
    function is_admin()
    {
        // Pastikan sudah login dulu sebelum cek role
        return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

/**
 * Memeriksa apakah pengguna yang login adalah user biasa (bukan admin).
 * @return bool True jika user biasa, false jika admin, tidak ada role, atau belum login.
 */
if (!function_exists('is_user')) {
    function is_user()
    {
        return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
    }
}

/**
 * Mengambil ID pengguna yang sedang login.
 * @return int|null ID pengguna atau null jika tidak login.
 */
if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return is_logged_in() && isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

/**
 * Mengambil nama pengguna yang sedang login.
 * Pastikan $_SESSION['user_nama'] (atau key yang sesuai) diset saat login.
 * @return string Nama pengguna atau string kosong jika tidak login/tidak ada nama.
 */
if (!function_exists('get_current_user_name')) {
    function get_current_user_name()
    {
        // Sesuaikan 'user_nama' dengan key session yang Anda gunakan untuk nama pengguna
        return is_logged_in() && isset($_SESSION['user_nama']) ? (string)$_SESSION['user_nama'] : '';
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
            // Simpan URL halaman saat ini agar bisa kembali setelah login
            $current_page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $_SESSION['redirect_url_after_login'] = str_replace(BASE_URL, '', $current_page_url); // Simpan path relatif

            set_flash_message('warning', 'Anda harus login untuk mengakses halaman ini.');
            redirect($redirect_to_login_page); // Fungsi redirect() akan melakukan exit
        }
    }
}

/**
 * Mewajibkan pengguna untuk login sebagai admin.
 * Jika tidak login, redirect ke halaman login.
 * Jika login tapi bukan admin, redirect ke dashboard user (atau halaman lain yang sesuai).
 * Fungsi ini akan menghentikan eksekusi skrip jika kondisi tidak terpenuhi.
 * @param string $redirect_to_login_page Path ke halaman login relatif terhadap BASE_URL.
 * @param string $redirect_to_non_admin_page Path ke halaman tujuan jika login tapi bukan admin.
 */
if (!function_exists('require_admin')) {
    function require_admin($redirect_to_login_page = 'auth/login.php', $redirect_to_non_admin_page = 'user/dashboard.php')
    {
        // Cek apakah sudah login
        if (!is_logged_in()) {
            // Simpan URL saat ini agar bisa kembali ke halaman admin setelah login
            $current_page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            // Simpan path relatif agar redirect() berfungsi dengan benar
            $_SESSION['redirect_url_after_login'] = str_replace(BASE_URL, '', $current_page_url);

            set_flash_message('warning', 'Akses ditolak. Anda harus login sebagai administrator untuk mengakses halaman ini.');
            redirect($redirect_to_login_page); // Fungsi redirect() akan melakukan exit
        }
        // Jika sudah login, cek apakah rolenya admin
        elseif (!is_admin()) {
            set_flash_message('danger', 'Akses ditolak. Halaman ini hanya untuk administrator.');
            redirect($redirect_to_non_admin_page); // Fungsi redirect() akan melakukan exit
        }
        // Jika sudah login DAN adalah admin, maka tidak terjadi apa-apa (skrip akan melanjutkan eksekusi)
    }
}


/**
 * Mengarahkan pengguna ke dashboard jika sudah login.
 * Fungsi ini biasanya dipanggil di halaman login atau register untuk mencegah
 * pengguna yang sudah login mengakses halaman tersebut lagi.
 */
if (!function_exists('redirect_if_logged_in')) {
    function redirect_if_logged_in()
    {
        if (is_logged_in()) {
            if (is_admin()) {
                redirect('admin/dashboard.php'); // Path relatif terhadap BASE_URL
            } else {
                redirect('user/dashboard.php');  // Path relatif terhadap BASE_URL
            }
            // redirect() sudah ada exit
        }
    }
}
