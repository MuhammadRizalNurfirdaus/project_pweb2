<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\proses_user.php

if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Kesalahan konfigurasi server.");
}
require_admin();

if (!class_exists('User')) {
    require_once MODELS_PATH . '/User.php';
}

if (!is_post()) {
    set_flash_message('danger', 'Akses tidak sah.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}

if (!verify_csrf_token()) {
    set_flash_message('danger', 'Permintaan tidak valid (CSRF token).');
    redirect(ADMIN_URL . '/users/kelola_users.php'); // Atau ke halaman sebelumnya
    exit;
}

$action = input('action', null, 'post');
$redirect_url_on_error = ADMIN_URL . '/users/kelola_users.php'; // Default

// Data dari form
$data = [
    'nama_lengkap' => input('nama_lengkap', null, 'post'),
    'email' => input('email', null, 'post'),
    'password' => input('password', null, 'post'), // Password mentah
    'confirm_password' => input('confirm_password', null, 'post'),
    'no_hp' => input('no_hp', null, 'post'),
    'alamat' => input('alamat', null, 'post'),
    'role' => input('role', 'user', 'post'),
    'status_akun' => input('status_akun', 'aktif', 'post')
];

// Simpan data ke session untuk repopulasi jika error
$_SESSION['flash_form_data'] = $data;


if ($action === 'tambah') {
    $redirect_url_on_error = ADMIN_URL . '/users/tambah_user.php';

    // Validasi Dasar
    if (empty($data['nama_lengkap']) || empty($data['email']) || empty($data['password']) || empty($data['confirm_password'])) {
        set_flash_message('danger', 'Nama, Email, Password, dan Konfirmasi Password wajib diisi.');
        redirect($redirect_url_on_error);
        exit;
    }
    if ($data['password'] !== $data['confirm_password']) {
        set_flash_message('danger', 'Password dan Konfirmasi Password tidak cocok.');
        redirect($redirect_url_on_error);
        exit;
    }
    // Validasi lain (email format, panjang password) akan ditangani oleh User::register

    $result = User::register([
        'nama' => $data['nama_lengkap'], // Sesuaikan key jika User::register mengharapkan 'nama'
        'email' => $data['email'],
        'password' => $data['password'],
        'no_hp' => $data['no_hp'],
        'alamat' => $data['alamat'],
        'role' => $data['role'],
        // 'status_akun' => $data['status_akun'] // User::register mungkin belum handle status_akun, perlu diupdate terpisah atau di User::register
    ]);

    if (is_numeric($result) && $result > 0) {
        // Jika User::register belum handle status_akun, update di sini
        if (isset($data['status_akun']) && method_exists('User', 'updateStatusAkun')) { // Buat metode updateStatusAkun di User.php
            User::updateStatusAkun($result, $data['status_akun']);
        }
        unset($_SESSION['flash_form_data']);
        set_flash_message('success', 'Pengguna baru berhasil ditambahkan.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
    } elseif ($result === 'email_exists') {
        set_flash_message('danger', 'Email sudah terdaftar.');
        redirect($redirect_url_on_error);
    } elseif ($result === 'password_short') {
        set_flash_message('danger', 'Password minimal 6 karakter.');
        redirect($redirect_url_on_error);
    } elseif ($result === 'email_invalid') {
        set_flash_message('danger', 'Format email tidak valid.');
        redirect($redirect_url_on_error);
    } else {
        set_flash_message('danger', 'Gagal menambahkan pengguna baru. Terjadi kesalahan.');
        error_log("Gagal tambah user, hasil dari User::register: " . print_r($result, true));
        redirect($redirect_url_on_error);
    }
    exit;
} elseif ($action === 'edit') {
    $user_id = input('user_id', 0, 'post');
    if ($user_id <= 0) {
        set_flash_message('danger', 'ID Pengguna tidak valid untuk diedit.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
        exit;
    }
    $redirect_url_on_error = ADMIN_URL . '/users/edit_user.php?id=' . $user_id;

    // Validasi Dasar
    if (empty($data['nama_lengkap']) || empty($data['email'])) {
        set_flash_message('danger', 'Nama dan Email wajib diisi.');
        redirect($redirect_url_on_error);
        exit;
    }
    if (!empty($data['password']) && ($data['password'] !== $data['confirm_password'])) {
        set_flash_message('danger', 'Password baru dan Konfirmasi Password tidak cocok.');
        redirect($redirect_url_on_error);
        exit;
    }
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        set_flash_message('danger', 'Password baru minimal 6 karakter.');
        redirect($redirect_url_on_error);
        exit;
    }

    // Admin utama (ID 1) tidak boleh mengubah role atau statusnya sendiri menjadi non-admin/non-aktif
    if ($user_id == 1 && get_current_user_id() == 1) {
        if (isset($data['role']) && $data['role'] !== 'admin') {
            set_flash_message('warning', 'Role admin utama tidak dapat diubah.');
            $data['role'] = 'admin'; // Paksa kembali ke admin
        }
        if (isset($data['status_akun']) && $data['status_akun'] !== 'aktif') {
            set_flash_message('warning', 'Status akun admin utama tidak dapat diubah menjadi non-aktif.');
            $data['status_akun'] = 'aktif'; // Paksa kembali ke aktif
        }
    }


    $update_data = [
        'id' => $user_id,
        'nama_lengkap' => $data['nama_lengkap'],
        'email' => $data['email'],
        'no_hp' => $data['no_hp'],
        'alamat' => $data['alamat'],
        'role' => $data['role'],
        'status_akun' => $data['status_akun'] // Pastikan User::update menangani ini
    ];
    if (!empty($data['password'])) {
        $update_data['password'] = $data['password']; // Password baru (mentah)
    }

    $result = User::update($update_data);

    if ($result === true) { // User::update sebaiknya return true/false
        unset($_SESSION['flash_form_data']);
        set_flash_message('success', 'Data pengguna berhasil diperbarui.');
        redirect(ADMIN_URL . '/users/kelola_users.php');
    } elseif ($result === false && isset($_SESSION['flash_message'])) {
        // Jika User::update set flash message (misal email duplikat), biarkan.
        redirect($redirect_url_on_error);
    } else {
        set_flash_message('danger', 'Gagal memperbarui data pengguna. Terjadi kesalahan.');
        error_log("Gagal update user ID {$user_id}, hasil dari User::update: " . print_r($result, true));
        redirect($redirect_url_on_error);
    }
    exit;
} else {
    set_flash_message('danger', 'Aksi tidak diketahui.');
    redirect(ADMIN_URL . '/users/kelola_users.php');
    exit;
}
