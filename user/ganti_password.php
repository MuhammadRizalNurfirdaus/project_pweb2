<?php
require_once __DIR__ . '/../config/config.php';
// Muat UserController
require_once CONTROLLERS_PATH . '/UserController.php'; // Pastikan path ini benar

require_login();
$user_id = get_current_user_id();
$user_email = ''; // Ambil email pengguna saat ini untuk verifikasi password lama
$currentUser = UserController::getUserDataById($user_id);
if ($currentUser) {
    $user_email = $currentUser['email'];
} else {
    // Seharusnya tidak terjadi jika sudah login, tapi sebagai pengaman
    set_flash_message('danger', 'Sesi tidak valid. Silakan login kembali.');
    logout_user();
    redirect('auth/login.php');
    exit;
}


if (is_post()) {
    if (verify_csrf_token()) {
        $password_lama = input('password_lama');
        $password_baru = input('password_baru');
        $konfirmasi_password_baru = input('konfirmasi_password_baru');

        if (UserController::processChangePassword($user_id, $user_email, $password_lama, $password_baru, $konfirmasi_password_baru)) {
            // Opsional: Logout pengguna dan arahkan ke login agar mereka login dengan password baru
            // logout_user();
            // set_flash_message('success', 'Password berhasil diperbarui. Silakan login kembali dengan password baru Anda.');
            // redirect('auth/login.php');

            // Atau langsung redirect ke profil dengan pesan sukses
            set_flash_message('success', 'Password berhasil diperbarui.');
            redirect('user/profil.php');
            exit;
        }
    } else {
        set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir (CSRF).');
    }
    // Jika gagal, redirect kembali untuk menampilkan pesan error
    redirect('user/ganti_password.php');
    exit;
}

$page_title = "Ganti Password";
include_once VIEWS_PATH . '/header_user.php'; // Gunakan konstanta path jika ada
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <h1><i class="fas fa-key me-2"></i>Ganti Password</h1>
                </div>
            </div>
        </div>

        <?= display_flash_message(); ?>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form action="<?= e($base_url) ?>user/ganti_password.php" method="POST" class="needs-validation" novalidate>
                            <?= generate_csrf_token_input(); ?>

                            <div class="mb-3">
                                <label for="password_lama" class="form-label">Password Lama <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                                <div class="invalid-feedback">Password lama wajib diisi.</div>
                            </div>

                            <div class="mb-3">
                                <label for="password_baru" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_baru" name="password_baru" required minlength="6">
                                <small class="form-text text-muted">Minimal 6 karakter.</small>
                                <div class="invalid-feedback">Password baru minimal 6 karakter.</div>
                            </div>

                            <div class="mb-3">
                                <label for="konfirmasi_password_baru" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="konfirmasi_password_baru" name="konfirmasi_password_baru" required minlength="6">
                                <div class="invalid-feedback">Konfirmasi password baru wajib diisi dan cocok.</div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary px-4 me-2">Simpan Password Baru</button>
                                <a href="<?= e($base_url) ?>user/profil.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../template/footer.php';
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
    })()
</script>