<?php
function require_login($redirect_url = 'auth/login.php')
{
    global $base_url;
    if (!isset($_SESSION['is_loggedin']) || $_SESSION['is_loggedin'] !== true) {
        set_flash_message('warning', 'Anda harus login untuk mengakses halaman ini.');
        redirect($redirect_url);
    }
}

function require_admin($redirect_url = 'auth/login.php')
{
    global $base_url;
    if (!isset($_SESSION['is_loggedin']) || $_SESSION['is_loggedin'] !== true) {
        set_flash_message('warning', 'Anda harus login untuk mengakses halaman ini.');
        redirect($redirect_url);
    } elseif (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        set_flash_message('danger', 'Akses ditolak. Anda tidak memiliki izin admin.');
        redirect(''); // Redirect to homepage
    }
}

function is_logged_in()
{
    return isset($_SESSION['is_loggedin']) && $_SESSION['is_loggedin'] === true;
}

function is_admin()
{
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function get_current_user_id()
{
    return is_logged_in() && isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function get_current_user_name()
{
    return is_logged_in() && isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : null;
}

function get_current_user_role()
{
    return is_logged_in() && isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}
