<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\profil.php

// 1. Memuat file konfigurasi utama dan dependensi
if (!file_exists(__DIR__ . '/../config/config.php')) {
    // Penanganan error jika config.php tidak ditemukan
    $critical_error_cfg = "KRITIS profil.php: File konfigurasi utama (config.php) tidak ditemukan.";
    error_log($critical_error_cfg . " Path yang dicoba: " . realpath(__DIR__ . '/../config/config.php'));
    exit("<div style='font-family:Arial,sans-serif;border:1px solid red;padding:15px;margin:20px;background-color:#ffebee;color:#c62828;'><strong>Kesalahan Server Kritis</strong><br>Detail: " . htmlspecialchars($critical_error_cfg) . "</div>");
}
require_once __DIR__ . '/../config/config.php';

// 2. Pastikan Model User sudah dimuat dan diinisialisasi dengan benar oleh config.php
if (!class_exists('User')) {
    error_log("KRITIS profil.php: Kelas User tidak ditemukan. Pastikan model dimuat oleh config.php.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen pengguna tidak tersedia (ClsNF_Prof).');
    if (function_exists('redirect')) redirect('user/dashboard.php');
    else exit('Error sistem.');
    exit;
}
// Pengecekan koneksi DB Model User akan implisit saat User::findById() dipanggil.

// 3. Memastikan pengguna sudah login
require_login(); // Fungsi ini akan mengarahkan ke halaman login jika belum login

// 4. Mengambil data pengguna yang sedang login
$current_user_id = get_current_user_id(); // Asumsi fungsi ini ada dan mengembalikan ID user
$user_data = null;

if ($current_user_id && $current_user_id > 0) {
    if (method_exists('User', 'findById')) {
        $user_data = User::findById($current_user_id);
    } else {
        error_log("KRITIS profil.php: Metode User::findById() tidak ditemukan.");
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Tidak dapat mengambil data pengguna (MFNF_Prof).');
        // Tidak redirect langsung, mungkin tampilkan pesan error di halaman
    }
} else {
    error_log("KRITIS profil.php: Tidak bisa mendapatkan current_user_id yang valid setelah login.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Sesi Anda tidak valid. Silakan login kembali.');
    if (function_exists('logout_user')) logout_user();
    else session_destroy();
    if (function_exists('redirect')) redirect('auth/login.php');
    else exit('Sesi tidak valid.');
    exit;
}

if (!$user_data) { // Jika findById gagal atau user tidak ada
    error_log("PERINGATAN profil.php: Data pengguna tidak ditemukan di DB untuk User ID: " . ($current_user_id ?? 'Tidak diketahui') . ". Sesi mungkin kedaluwarsa atau pengguna telah dihapus. Atau masalah koneksi DB model.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Tidak dapat menemukan data profil Anda. Sesi Anda mungkin tidak valid atau ada masalah sistem. Silakan login kembali.');
    if (function_exists('logout_user')) logout_user();
    else session_destroy();
    if (function_exists('redirect')) redirect('auth/login.php');
    else exit('Data pengguna tidak ditemukan.');
    exit;
}

// 5. Menyiapkan judul halaman
$page_title = "Profil Saya - " . e($user_data['nama_lengkap'] ?? $user_data['nama'] ?? 'Pengguna');

// 6. Memuat header halaman pengguna
$header_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_user.php' : __DIR__ . '/../template/header_user.php';
if (file_exists($header_path)) {
    include_once $header_path;
} else {
    error_log("KRITIS profil.php: File header_user.php tidak ditemukan di '{$header_path}'.");
    exit("Kesalahan sistem: Komponen tampilan tidak lengkap (HdrP_NF).");
}
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profil Saya</h1>
                </div>
                <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                    <a href="<?= e(BASE_URL) ?>user/edit_profil.php" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Profil
                    </a>
                </div>
            </div>
        </div>

        <?= display_flash_message(); ?>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Informasi Akun</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 col-lg-3 text-center mb-3 mb-md-0">
                        <?php
                        $foto_profil_url = defined('ASSETS_URL') ? ASSETS_URL . 'img/avatar_default.png' : '../public/img/avatar_default.png'; // Default avatar
                        if (!empty($user_data['foto_profil']) && defined('UPLOADS_URL') && defined('UPLOADS_PROFIL_PATH')) {
                            $path_foto_fisik = rtrim(UPLOADS_PROFIL_PATH, '/') . '/' . $user_data['foto_profil'];
                            if (file_exists($path_foto_fisik)) {
                                $foto_profil_url = rtrim(UPLOADS_URL, '/') . '/profil/' . rawurlencode($user_data['foto_profil']);
                            } else {
                                error_log("PERINGATAN profil.php: File foto profil '{$user_data['foto_profil']}' tidak ditemukan di server untuk user ID {$current_user_id}.");
                            }
                        }
                        ?>
                        <img src="<?= e($foto_profil_url) ?>" alt="Foto Profil <?= e($user_data['nama'] ?? '') ?>" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #dee2e6;">
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Nama Lengkap:</div>
                            <div class="col-sm-8"><?= e($user_data['nama_lengkap'] ?? '-') ?></div>
                        </div>
                        <hr class="my-1">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Username/Nama Panggilan:</div>
                            <div class="col-sm-8"><?= e($user_data['nama'] ?? '-') ?></div>
                        </div>
                        <hr class="my-1">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Alamat Email:</div>
                            <div class="col-sm-8"><?= e($user_data['email'] ?? '-') ?></div>
                        </div>
                        <hr class="my-1">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Nomor HP:</div>
                            <div class="col-sm-8"><?= e($user_data['no_hp'] ?? 'Belum diisi') ?></div>
                        </div>
                        <hr class="my-1">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Alamat:</div>
                            <div class="col-sm-8"><?= !empty(trim($user_data['alamat'] ?? '')) ? nl2br(e($user_data['alamat'])) : 'Belum diisi' ?></div>
                        </div>
                        <hr class="my-1">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Role:</div>
                            <div class="col-sm-8 text-capitalize"><?= e($user_data['role'] ?? '-') ?></div>
                        </div>
                        <hr class="my-1">
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Status Akun:</div>
                            <div class="col-sm-8">
                                <?= getStatusBadgeClassHTML($user_data['status_akun'] ?? 'tidak diketahui') ?>
                            </div>
                        </div>
                        <hr class="my-1">
                        <div class="row">
                            <div class="col-sm-4 fw-bold">Tanggal Bergabung:</div>
                            <div class="col-sm-8">
                                <?= isset($user_data['created_at']) && function_exists('formatTanggalIndonesia') ? e(formatTanggalIndonesia($user_data['created_at'], true, true)) : (isset($user_data['created_at']) ? e(date('d M Y, H:i', strtotime($user_data['created_at']))) : '-') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?= e(BASE_URL) ?>user/ganti_password.php" class="btn btn-outline-secondary">
                <i class="fas fa-key me-1"></i> Ganti Password
            </a>
        </div>

    </div>
</div>

<?php
$footer_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer.php' : __DIR__ . '/../template/footer.php';
if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    error_log("KRITIS profil.php: File footer.php tidak ditemukan di '{$footer_path}'.");
}
?>