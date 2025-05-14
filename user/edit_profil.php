<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\edit_profil.php

// 1. Memuat file konfigurasi utama dan dependensi
require_once __DIR__ . '/../config/config.php';

// 2. Pastikan Controller User sudah dimuat
if (!class_exists('UserController')) {
    if (defined('CONTROLLERS_PATH') && file_exists(CONTROLLERS_PATH . '/UserController.php')) {
        require_once CONTROLLERS_PATH . '/UserController.php';
    }
    if (!class_exists('UserController')) { // Cek lagi
        error_log("KRITIS edit_profil.php: UserController tidak dapat dimuat.");
        set_flash_message('danger', 'Kesalahan sistem: Komponen pengguna tidak tersedia (UCNF_EP).');
        redirect('user/dashboard.php');
        exit;
    }
}
// Pastikan Model User juga tersedia jika diperlukan langsung (meskipun controller yang utama)
if (!class_exists('User')) {
    if (defined('MODELS_PATH') && file_exists(MODELS_PATH . '/User.php')) {
        require_once MODELS_PATH . '/User.php';
    }
}


// 3. Memastikan pengguna sudah login
require_login();
$current_user_id = get_current_user_id();

// 4. Ambil data pengguna saat ini untuk mengisi form
$user = UserController::getUserDataById($current_user_id);

if (!$user) {
    set_flash_message('danger', 'Data pengguna tidak ditemukan. Silakan login kembali.');
    if (function_exists('logout_user')) logout_user();
    else session_destroy();
    redirect('auth/login.php');
    exit;
}

// 5. Inisialisasi variabel form
// Prioritaskan data dari session (jika ada error sebelumnya), lalu dari database
$form_data = $_SESSION['edit_profil_form_data'] ?? $user;

$nama_lengkap_input = $form_data['nama_lengkap'] ?? '';
$nama_input = $form_data['nama'] ?? '';
$email_input = $form_data['email'] ?? '';
$no_hp_input = $form_data['no_hp'] ?? '';
$alamat_input = $form_data['alamat'] ?? '';
$foto_profil_saat_ini = $user['foto_profil'] ?? null; // Untuk menampilkan foto saat ini

if (isset($_SESSION['edit_profil_form_data'])) {
    unset($_SESSION['edit_profil_form_data']); // Hapus setelah digunakan
}

// 6. Proses form jika POST request
if (is_post()) {
    if (verify_csrf_token()) {
        $data_from_form = [
            'nama_lengkap' => input('nama_lengkap'),
            'nama' => input('nama'), // Ini adalah username/nama panggilan
            'email' => input('email'),
            'no_hp' => input('no_hp'),
            'alamat' => input('alamat'),
            'hapus_foto_profil' => input('hapus_foto_profil') // Checkbox untuk hapus foto
        ];

        // Data file foto profil
        $file_input_data = isset($_FILES['foto_profil_baru']) && $_FILES['foto_profil_baru']['error'] === UPLOAD_ERR_OK
            ? $_FILES['foto_profil_baru']
            : null;

        // Panggil controller untuk memproses update
        $update_result = UserController::processUpdateProfile($current_user_id, $data_from_form, $file_input_data);

        if ($update_result === true) {
            // $_SESSION['edit_profil_form_data'] sudah di-unset oleh controller jika sukses
            redirect('user/profil.php');
            exit;
        } elseif ($update_result === 'email_exists') {
            // Pesan error sudah di-set oleh controller, data form sudah di-repopulate oleh controller via session
            // Biarkan redirect di bawah yang menangani
        } else {
            // Pesan error umum sudah di-set oleh controller, data form sudah di-repopulate
        }
    } else {
        set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF).');
    }
    // Jika ada error validasi di controller atau CSRF gagal, redirect kembali untuk menampilkan pesan
    redirect('user/edit_profil.php');
    exit;
}

// 7. Set judul halaman dan muat header
$page_title = "Edit Profil Saya";
$header_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_user.php' : __DIR__ . '/../template/header_user.php';
if (file_exists($header_path)) {
    include_once $header_path;
} else {
    error_log("KRITIS edit_profil.php: File header_user.php tidak ditemukan di '{$header_path}'.");
    exit("Kesalahan sistem: Komponen tampilan tidak lengkap (HdrEP_NF).");
}
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <h1 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profil</h1>
                </div>
            </div>
        </div>

        <?= display_flash_message(); ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= e(BASE_URL) ?>user/edit_profil.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= generate_csrf_token_input(); ?>

                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <label for="foto_profil_baru" class="form-label">Foto Profil</label>
                            <?php
                            $avatar_url = defined('ASSETS_URL') ? ASSETS_URL . 'img/avatar_default.png' : '../public/img/avatar_default.png';
                            if (!empty($foto_profil_saat_ini) && defined('UPLOADS_URL') && defined('UPLOADS_PROFIL_PATH')) {
                                $path_foto_fisik_current = rtrim(UPLOADS_PROFIL_PATH, '/') . '/' . $foto_profil_saat_ini;
                                if (file_exists($path_foto_fisik_current)) {
                                    $avatar_url = rtrim(UPLOADS_URL, '/') . '/profil/' . rawurlencode($foto_profil_saat_ini) . '?t=' . time(); // Tambahkan timestamp untuk cache busting
                                }
                            }
                            ?>
                            <img src="<?= e($avatar_url) ?>" alt="Foto Profil" id="preview_foto_profil" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                            <input type="file" class="form-control form-control-sm" id="foto_profil_baru" name="foto_profil_baru" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah. Max 2MB (JPG, PNG, GIF).</small>
                            <?php if (!empty($foto_profil_saat_ini)): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="hapus_foto_profil" name="hapus_foto_profil">
                                    <label class="form-check-label" for="hapus_foto_profil">
                                        Hapus foto profil saat ini
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-9">
                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="nama_lengkap" name="nama_lengkap" value="<?= e($nama_lengkap_input) ?>" required>
                                <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                            </div>

                            <div class="mb-3">
                                <label for="nama" class="form-label">Username/Nama Panggilan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="nama" name="nama" value="<?= e($nama_input) ?>" required>
                                <div class="invalid-feedback">Username/Nama Panggilan wajib diisi.</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= e($email_input) ?>" required>
                                <div class="invalid-feedback">Format email tidak valid atau wajib diisi.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor HP <span class="text-muted">(Opsional)</span></label>
                        <input type="tel" class="form-control form-control-lg" id="no_hp" name="no_hp" value="<?= e($no_hp_input) ?>" placeholder="Contoh: 081234567890" pattern="[0-9]{10,15}">
                        <div class="invalid-feedback">Format nomor HP tidak valid (10-15 digit angka).</div>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat <span class="text-muted">(Opsional)</span></label>
                        <textarea class="form-control form-control-lg" id="alamat" name="alamat" rows="3"><?= e($alamat_input) ?></textarea>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-lg px-5 me-2"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                        <a href="<?= e(BASE_URL) ?>user/profil.php" class="btn btn-secondary btn-lg">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$footer_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer.php' : __DIR__ . '/../template/footer.php';
if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    error_log("KRITIS edit_profil.php: File footer.php tidak ditemukan di '{$footer_path}'.");
}
?>
<script>
    // Script validasi Bootstrap dasar
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })();

    // Script untuk preview gambar foto profil
    const fotoProfilInput = document.getElementById('foto_profil_baru');
    const previewFotoProfil = document.getElementById('preview_foto_profil');
    const hapusFotoCheckbox = document.getElementById('hapus_foto_profil');
    const originalAvatarUrl = '<?= e($avatar_url) ?>'; // Simpan URL avatar asli

    if (fotoProfilInput && previewFotoProfil) {
        fotoProfilInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewFotoProfil.src = e.target.result;
                }
                reader.readAsDataURL(file);
                if (hapusFotoCheckbox) hapusFotoCheckbox.checked = false; // Uncheck hapus jika ada file baru
            } else {
                // Jika file dibatalkan, kembalikan ke foto asli (jika tidak ada preview dari file sebelumnya)
                // atau biarkan jika sudah ada preview dari file baru yang dipilih sebelumnya lalu dibatalkan
                // Untuk kesederhanaan, kita bisa kembalikan ke original jika input kosong dan tidak ada file yang dipilih
                if (!this.files || !this.files[0]) { // jika benar-benar tidak ada file yang dipilih
                    previewFotoProfil.src = originalAvatarUrl;
                }
            }
        });
    }
    if (hapusFotoCheckbox && previewFotoProfil) {
        hapusFotoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                previewFotoProfil.src = '<?= defined("ASSETS_URL") ? ASSETS_URL . "img/avatar_default.png" : "../public/img/avatar_default.png" ?>'; // Ganti ke default avatar
                if (fotoProfilInput) fotoProfilInput.value = ""; // Kosongkan input file
            } else {
                // Jika uncheck, dan input file kosong, kembalikan ke foto asli
                if (fotoProfilInput && (!fotoProfilInput.files || !fotoProfilInput.files[0])) {
                    previewFotoProfil.src = originalAvatarUrl;
                }
                // Jika ada file dipilih di input, preview sudah dihandle oleh event listener input file
            }
        });
    }
</script>