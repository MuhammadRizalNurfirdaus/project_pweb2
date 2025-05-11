<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\auth_helpers.php

/**
 * Memeriksa apakah pengguna sudah login.
 * @return bool True jika pengguna login, false jika tidak.
 */
function is_logged_in()
{
    return isset($_SESSION['is_loggedin']) && $_SESSION['is_loggedin'] === true;
}

/**
 * Memeriksa apakah pengguna yang login adalah admin.
 * @return bool True jika admin, false jika tidak.
 */
function is_admin()
{
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Mengambil ID pengguna yang sedang login.
 * @return int|null ID pengguna atau null jika tidak login.
 */
function get_current_user_id()
{
    return is_logged_in() && isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Mengambil nama pengguna yang sedang login.
 * @return string|null Nama pengguna atau string kosong jika tidak login/tidak ada nama.
 */
function get_current_user_name()
{
    return is_logged_in() && isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : '';
}

/**
 * Mewajibkan pengguna untuk login. Jika tidak, redirect ke halaman login.
 * @param string $redirect_to_login_page Path ke halaman login relatif terhadap base_url.
 */
function require_login($redirect_to_login_page = 'auth/login.php')
{
    if (!is_logged_in()) {
        set_flash_message('danger', 'Anda harus login untuk mengakses halaman ini.');
        redirect($redirect_to_login_page); // Menggunakan fungsi redirect dari helpers.php
    }
}

/**
 * Mewajibkan pengguna untuk login sebagai admin. Jika tidak, redirect.
 * @param string $redirect_to_login_page Path ke halaman login.
 * @param string $redirect_to_dashboard_user Path ke dashboard user jika bukan admin.
 */
function require_admin($redirect_to_login_page = 'auth/login.php', $redirect_to_dashboard_user = 'user/dashboard.php')
{
    if (!is_logged_in()) {
        set_flash_message('danger', 'Akses ditolak. Anda harus login sebagai admin.');
        redirect($redirect_to_login_page);
    } elseif (!is_admin()) {
        set_flash_message('danger', 'Akses ditolak. Halaman ini hanya untuk administrator.');
        redirect($redirect_to_dashboard_user); // Atau ke halaman lain yang sesuai
    }
}

// Tidak perlu tag PHP penutup jika file ini hanya berisi kode PHP
// 
