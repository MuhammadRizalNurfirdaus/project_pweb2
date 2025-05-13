<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\proses_user.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_user.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Model User (diasumsikan User::setDbConnection sudah dipanggil di config.php)
if (!class_exists('User')) {
    $userModelPath = MODELS_PATH . '/User.php';
    if (file_exists($userModelPath)) {
        require_once $userModelPath;
        // Panggil User::setDbConnection($conn) di sini jika belum di config.php (kurang ideal)
    } else {
        error_log("FATAL ERROR di proses_user.php: Model User.php tidak ditemukan.");
        set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}

// 4. Validasi Metode Request & CSRF Token
if (!is_post()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// Sekarang memanggil verify_csrf_token dengan null (untuk mengambil dari $_POST) dan true (untuk unset)
if (!function_exists('verify_csrf_token') || !verify_csrf_token(null, true)) {
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi.');
    redirect($_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/users/kelola_users.php');
    exit;
}

// 5. Ambil Aksi dan Data dari Form
$action = input('action', '', 'post');

$form_data = [
    'nama' => input('nama', null, 'post'),
    'nama_lengkap' => input('nama_lengkap', null, 'post'),
    'email' => input('email', null, 'post'),
    'password' => input('password', null, 'post'),
    'confirm_password' => input('confirm_password', null, 'post'),
    'no_hp' => input('no_hp', null, 'post'),
    'alamat' => input('alamat', null, 'post'),
    'role' => input('role', 'user', 'post'),
    'status_akun' => input('status_akun', 'aktif', 'post')
];

// ======================================================
// AKSI: TAMBAH PENGGUNA BARU
// ======================================================
if ($action === 'tambah') {
    $redirect_url_on_error = ADMIN_URL . '/users/tambah_user.php';
    $_SESSION['flash_form_data_tambah_user'] = $form_data; // Key session spesifik

    if (empty($form_data['nama']) || empty($form_data['email']) || empty($form_data['password']) || empty($form_data['confirm_password'])) {
        set_flash_message('danger', 'Nama, Email, Password, dan Konfirmasi Password wajib diisi.');
        redirect($redirect_url_on_error);
        exit;
    }
    if ($form_data['password'] !== $form_data['confirm_password']) {
        set_flash_message('danger', 'Password dan Konfirmasi Password tidak cocok.');
        redirect($redirect_url_on_error);
        exit;
    }

    $data_to_register = [
        'nama' => $form_data['nama'],
        'nama_lengkap' => $form_data['nama_lengkap'],
        'email' => $form_data['email'],
        'password' => $form_data['password'],
        'no_hp' => $form_data['no_hp'],
        'alamat' => $form_data['alamat'],
        'role' => $form_data['role'],
        'status_akun' => $form_data['status_akun']
    ];

    $result = User::register($data_to_register);

    if (is_numeric($result) && $result > 0) {
        unset($_SESSION['flash_form_data_tambah_user']);
        set_flash_message('success', 'Pengguna baru berhasil ditambahkan.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
    } elseif (is_string($result)) {
        $error_message = 'Gagal menambahkan pengguna: ';
        switch ($result) {
            case 'missing_fields':
                $error_message .= 'Field wajib tidak lengkap.';
                break;
            case 'email_exists':
                $error_message .= 'Email sudah terdaftar.';
                break;
            case 'password_short':
                $error_message .= 'Password minimal 6 karakter.';
                break;
            case 'email_invalid':
                $error_message .= 'Format email tidak valid.';
                break;
            default:
                $error_message .= 'Terjadi kesalahan (' . htmlspecialchars($result) . ').';
        }
        set_flash_message('danger', $error_message);
        redirect($redirect_url_on_error);
    } else {
        set_flash_message('danger', 'Gagal menambahkan pengguna baru karena kesalahan sistem.');
        error_log("Gagal tambah user, User::register() false. DB Error: " . User::getLastError());
        redirect($redirect_url_on_error);
    }
    exit;

    // ======================================================
    // AKSI: EDIT PENGGUNA
    // ======================================================
} elseif ($action === 'edit') {
    $user_id = (int)input('user_id', 0, 'post');
    if ($user_id <= 0) {
        set_flash_message('danger', 'ID Pengguna tidak valid untuk diedit.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
        exit;
    }
    $redirect_url_on_error = ADMIN_URL . '/users/edit_user.php?id=' . $user_id;
    $_SESSION['flash_form_data_edit_user_' . $user_id] = $form_data; // Key session spesifik

    if (empty($form_data['nama']) || empty($form_data['email'])) {
        set_flash_message('danger', 'Nama dan Email wajib diisi.');
        redirect($redirect_url_on_error);
        exit;
    }
    if (!empty($form_data['password']) && ($form_data['password'] !== $form_data['confirm_password'])) {
        set_flash_message('danger', 'Password baru dan Konfirmasi Password tidak cocok.');
        redirect($redirect_url_on_error);
        exit;
    }
    if (!empty($form_data['password']) && strlen($form_data['password']) < 6) {
        set_flash_message('danger', 'Password baru minimal 6 karakter.');
        redirect($redirect_url_on_error);
        exit;
    }

    $current_admin_id = get_current_user_id();
    if ($user_id == 1 && $current_admin_id == 1) {
        $user_db_data = User::findById(1);
        if ($user_db_data) {
            if (isset($form_data['role']) && $form_data['role'] !== 'admin') {
                set_flash_message('warning', 'Role admin utama tidak dapat diubah.');
                $form_data['role'] = $user_db_data['role'];
            }
            if (isset($form_data['status_akun']) && $form_data['status_akun'] !== 'aktif') {
                set_flash_message('warning', 'Status akun admin utama tidak dapat diubah menjadi non-aktif.');
                $form_data['status_akun'] = $user_db_data['status_akun'];
            }
        }
    }

    $data_to_update = [
        'id' => $user_id,
        'nama' => $form_data['nama'],
        'nama_lengkap' => $form_data['nama_lengkap'],
        'email' => $form_data['email'],
        'no_hp' => $form_data['no_hp'],
        'alamat' => $form_data['alamat'],
        'role' => $form_data['role'],
        'status_akun' => $form_data['status_akun']
    ];
    if (!empty($form_data['password'])) {
        $data_to_update['password'] = $form_data['password'];
    }

    $result = User::update($data_to_update);

    if ($result === true) {
        unset($_SESSION['flash_form_data_edit_user_' . $user_id]);
        set_flash_message('success', 'Data pengguna berhasil diperbarui.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
    } elseif ($result === 'email_exists') {
        // Pesan flash sudah di-set oleh Model User::update()
        redirect($redirect_url_on_error);
    } else {
        set_flash_message('danger', 'Gagal memperbarui data pengguna. Kesalahan sistem.');
        error_log("Gagal update user ID {$user_id}, User::update() false. DB Error: " . User::getLastError());
        redirect($redirect_url_on_error);
    }
    exit;

    // ======================================================
    // AKSI TIDAK DIKENALI
    // ======================================================
} else {
    set_flash_message('danger', 'Aksi tidak valid atau tidak diketahui.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}
