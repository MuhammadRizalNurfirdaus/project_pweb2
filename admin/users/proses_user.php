<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\users\proses_user.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_user.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di proses_user.php: Fungsi require_admin() tidak ditemukan.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia.");
}
require_admin();

// 3. Pastikan Model User dan metode yang dibutuhkan ada
// config.php seharusnya sudah memuat dan menginisialisasi Model User
if (
    !class_exists('User') ||
    !method_exists('User', 'register') ||
    !method_exists('User', 'update') ||
    !method_exists('User', 'findById') ||
    !method_exists('User', 'getLastError')
) {
    error_log("FATAL ERROR di proses_user.php: Kelas User atau metode penting tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data pengguna tidak dapat dimuat (MUSR_NF_PROC_CORE).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// 4. Validasi Metode Request
if (!is_post()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}

// 5. Ambil Aksi dari Form
$action = input('action', '', 'post'); // Fungsi input() dari helpers.php

// 6. Tentukan nama token CSRF yang konsisten (sama dengan yang di-generate di form)
$csrf_token_name_in_form = 'user_form_csrf_token'; // Ganti jika Anda menggunakan nama lain di generate_csrf_token_input()

// 7. Validasi CSRF Token
if (!function_exists('verify_csrf_token') || !verify_csrf_token($csrf_token_name_in_form, true, 'POST')) {
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi dari formulir.');

    // Tentukan URL redirect berdasarkan aksi sebelumnya untuk pengalaman pengguna yang lebih baik
    $redirect_back_url = ADMIN_URL . 'users/kelola_users.php'; // Default
    if ($action === 'tambah') {
        $redirect_back_url = ADMIN_URL . 'users/tambah_user.php';
    } elseif ($action === 'edit' && isset($_POST['user_id'])) {
        $redirect_back_url = ADMIN_URL . 'users/edit_user.php?id=' . (int)$_POST['user_id'];
    }
    error_log("Proses User - Kegagalan Verifikasi CSRF. Aksi: {$action}. Token diterima via POST['{$csrf_token_name_in_form}']: " . ($_POST[$csrf_token_name_in_form] ?? 'TIDAK ADA'));
    redirect($redirect_back_url);
    exit;
}

// 8. Ambil Data dari Form
$form_data = [
    'nama' => input('nama', null, 'post'),
    'nama_lengkap' => input('nama_lengkap', null, 'post'),
    'email' => input('email', null, 'post'),
    'password' => $_POST['password'] ?? null, // Ambil langsung dari POST agar tidak di-trim oleh input()
    'confirm_password' => $_POST['confirm_password'] ?? null, // Ambil langsung
    'no_hp' => input('no_hp', null, 'post'),
    'alamat' => input('alamat', null, 'post'),
    'role' => input('role', 'user', 'post'),
    'status_akun' => input('status_akun', 'aktif', 'post')
];

// ======================================================
// AKSI: TAMBAH PENGGUNA BARU
// ======================================================
if ($action === 'tambah') {
    $redirect_url_on_error = ADMIN_URL . 'users/tambah_user.php';
    // Simpan data form ke session untuk repopulasi jika terjadi error
    $_SESSION['flash_form_data_tambah_user'] = $form_data;

    // Validasi dasar input
    if (empty($form_data['nama']) || empty($form_data['email']) || empty($form_data['password']) || empty($form_data['confirm_password'])) {
        set_flash_message('danger', 'Nama Pengguna, Email, Password, dan Konfirmasi Password wajib diisi.');
        redirect($redirect_url_on_error);
        exit;
    }
    if ($form_data['password'] !== $form_data['confirm_password']) {
        set_flash_message('danger', 'Password dan Konfirmasi Password tidak cocok.');
        redirect($redirect_url_on_error);
        exit;
    }
    if (strlen($form_data['password']) < 6) {
        set_flash_message('danger', 'Password minimal 6 karakter.');
        redirect($redirect_url_on_error);
        exit;
    }

    $data_to_register = [
        'nama' => $form_data['nama'],
        'nama_lengkap' => $form_data['nama_lengkap'],
        'email' => $form_data['email'],
        'password' => $form_data['password'], // Model User::register() akan melakukan hashing
        'no_hp' => $form_data['no_hp'],
        'alamat' => $form_data['alamat'],
        'role' => $form_data['role'],
        'status_akun' => $form_data['status_akun']
        // Jika ada input file foto_profil, Anda perlu menghandlenya di sini atau di UserController
    ];

    $result = User::register($data_to_register);

    if (is_numeric($result) && $result > 0) { // Sukses, $result adalah user_id baru
        unset($_SESSION['flash_form_data_tambah_user']);
        set_flash_message('success', 'Pengguna baru "' . e($form_data['nama']) . '" berhasil ditambahkan.');
        redirect(ADMIN_URL . 'users/kelola_users.php');
    } elseif (is_string($result)) { // Model mengembalikan kode error spesifik
        $error_message = 'Gagal menambahkan pengguna: ';
        switch ($result) {
            case 'missing_nama_pengguna':
                $error_message .= 'Nama pengguna wajib diisi.';
                break;
            // ... (case lain dari User::register) ...
            case 'email_exists':
                $error_message .= 'Email sudah terdaftar. Gunakan email lain.';
                break;
            case 'password_short':
                $error_message .= 'Password minimal 6 karakter.';
                break;
            default:
                $error_message .= 'Terjadi kesalahan (' . e($result) . ').';
        }
        set_flash_message('danger', $error_message);
        redirect($redirect_url_on_error);
    } else { // Gagal umum dari Model (false)
        $model_error = User::getLastError();
        set_flash_message('danger', 'Gagal menambahkan pengguna baru karena kesalahan sistem. ' . ($model_error ? e($model_error) : ''));
        error_log("Gagal tambah user, User::register() false. Model Error: " . ($model_error ?? 'Tidak ada info') . " | Data: " . print_r($data_to_register, true));
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
        redirect(ADMIN_URL . 'users/kelola_users.php');
        exit;
    }
    $redirect_url_on_error = ADMIN_URL . 'users/edit_user.php?id=' . $user_id;
    $_SESSION['flash_form_data_edit_user_' . $user_id] = $form_data;

    // Validasi dasar input
    if (empty($form_data['nama']) || empty($form_data['email'])) {
        set_flash_message('danger', 'Nama Pengguna dan Email wajib diisi.');
        redirect($redirect_url_on_error);
        exit;
    }
    // Password hanya divalidasi jika diisi
    if (!empty($form_data['password'])) {
        if ($form_data['password'] !== $form_data['confirm_password']) {
            set_flash_message('danger', 'Password baru dan Konfirmasi Password tidak cocok.');
            redirect($redirect_url_on_error);
            exit;
        }
        if (strlen($form_data['password']) < 6) {
            set_flash_message('danger', 'Password baru minimal 6 karakter.');
            redirect($redirect_url_on_error);
            exit;
        }
    }

    // Logika pencegahan perubahan admin utama (ID 1)
    $current_admin_id = get_current_user_id();
    if ($user_id == 1) { // Jika yang diedit adalah admin utama
        $user_db_data = User::findById(1); // Ambil data asli dari DB
        if ($user_db_data) {
            if ($current_admin_id != 1 && ($form_data['role'] !== $user_db_data['role'] || $form_data['status_akun'] !== $user_db_data['status_akun'])) {
                set_flash_message('danger', 'Hanya admin utama (ID:1) yang dapat mengubah role atau status akunnya sendiri.');
                redirect(ADMIN_URL . '/users/kelola_users.php');
                exit;
            }
            // Jika admin utama mengedit dirinya sendiri, cegah perubahan role dari admin atau status dari aktif
            if ($current_admin_id == 1) {
                if (isset($form_data['role']) && $form_data['role'] !== 'admin') {
                    set_flash_message('warning', 'Role admin utama (ID:1) tidak dapat diubah dari "admin". Perubahan role diabaikan.');
                    $form_data['role'] = 'admin'; // Paksa kembali ke admin
                }
                if (isset($form_data['status_akun']) && $form_data['status_akun'] !== 'aktif') {
                    set_flash_message('warning', 'Status akun admin utama (ID:1) tidak dapat diubah dari "aktif". Perubahan status diabaikan.');
                    $form_data['status_akun'] = 'aktif'; // Paksa kembali ke aktif
                }
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
        // Jika ada fitur edit foto profil admin, handle di sini atau di UserController
    ];
    if (!empty($form_data['password'])) {
        $data_to_update['password'] = $form_data['password']; // Model User::update() akan hash
    }

    $result = User::update($data_to_update);

    if ($result === true) {
        unset($_SESSION['flash_form_data_edit_user_' . $user_id]);
        set_flash_message('success', 'Data pengguna "' . e($form_data['nama']) . '" berhasil diperbarui.');
        redirect(ADMIN_URL . 'users/kelola_users.php');
    } elseif (is_string($result)) { // Model mengembalikan kode error spesifik
        $error_message = 'Gagal memperbarui pengguna: ';
        switch ($result) {
            case 'email_exists':
                $error_message .= 'Email sudah digunakan oleh pengguna lain.';
                break;
            // ... (case lain dari User::update) ...
            default:
                $error_message .= 'Terjadi kesalahan (' . e($result) . ').';
        }
        set_flash_message('danger', $error_message);
        redirect($redirect_url_on_error);
    } else { // Gagal umum dari Model (false)
        $model_error = User::getLastError();
        set_flash_message('danger', 'Gagal memperbarui data pengguna karena kesalahan sistem. ' . ($model_error ? e($model_error) : ''));
        error_log("Gagal update user ID {$user_id}, User::update() false. Model Error: " . ($model_error ?? 'Tidak ada info') . " | Data: " . print_r($data_to_update, true));
        redirect($redirect_url_on_error);
    }
    exit;

    // ======================================================
    // AKSI TIDAK DIKENALI
    // ======================================================
} else {
    set_flash_message('danger', 'Aksi tidak valid atau tidak diketahui.');
    redirect(ADMIN_URL . 'users/kelola_users.php');
    exit;
}
