<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\contact.php
// Atau bisa juga ini halaman publik, jadi tidak perlu require_login() jika publik
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Contact.php'; // Atau ContactController

// Jika ini halaman khusus user yang login, aktifkan baris di bawah
// require_login(); 

// Variabel untuk pre-fill dan error
$nama_input = is_logged_in() ? get_current_user_name() : ''; // Pre-fill jika user login
$email_input = is_logged_in() && isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; // Pre-fill jika user login
$pesan_input = '';

if (is_post()) {
    $nama_input = input('nama');
    $email_input = input('email');
    $pesan_input = input('pesan');

    if (empty($nama_input) || empty($email_input) || empty($pesan_input)) {
        set_flash_message('danger', 'Nama, Email, dan Pesan wajib diisi.');
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
    } else {
        $data_kontak = [
            'nama' => $nama_input,
            'email' => $email_input,
            'pesan' => $pesan_input
        ];
        if (Contact::create($data_kontak)) { // Memanggil method static dari model Contact
            set_flash_message('success', 'Pesan Anda telah berhasil dikirim! Kami akan segera merespons.');
            // Reset form fields after successful submission
            $nama_input = is_logged_in() ? get_current_user_name() : '';
            $email_input = is_logged_in() && isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
            $pesan_input = '';
            // Tidak redirect agar pesan sukses bisa dilihat di halaman yang sama
        } else {
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pesan. Silakan coba lagi.');
        }
    }
}

// Jika ini halaman user yang login, gunakan header_user.php
// Jika ini halaman kontak publik, gunakan header.php
// Untuk contoh ini, saya asumsikan ini bisa diakses publik juga, jadi pakai header.php biasa
// Namun, jika Anda meletakkannya di folder /user/, header_user.php lebih cocok.
// Untuk konsistensi dengan path file, mari gunakan header_user.php
include_once __DIR__ . '/../template/header_user.php'; // atau header.php jika ini halaman kontak publik
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="text-center mb-5">
                    <h1 class="section-title" style="margin-top:0;">Hubungi Kami</h1>
                    <p class="section-subtitle">Punya pertanyaan, saran, atau ingin berkolaborasi? Jangan ragu untuk menghubungi tim Cilengkrang Web Wisata.</p>
                </div>

                <?= display_flash_message(); ?>

                <div class="card shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <form action="<?= $base_url ?>user/contact.php" method="POST" novalidate class="needs-validation">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap Anda</label>
                                <input type="text" class="form-control form-control-lg" id="nama" name="nama" value="<?= e($nama_input) ?>" required>
                                <div class="invalid-feedback">Nama wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= e($email_input) ?>" required>
                                <div class="invalid-feedback">Format email tidak valid atau email wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pesan" class="form-label">Pesan Anda</label>
                                <textarea class="form-control form-control-lg" id="pesan" name="pesan" rows="5" required><?= e($pesan_input) ?></textarea>
                                <div class="invalid-feedback">Pesan wajib diisi.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-3">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Pesan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <h4>Informasi Kontak Lainnya</h4>
                    <p class="lead"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Alamat: Jl. Wisata Cilengkrang No. 1, Bandung, Jawa Barat</p>
                    <p class="lead"><i class="fas fa-phone-alt me-2 text-primary"></i>Telepon: (022) 123-4567</p>
                    <p class="lead"><i class="fas fa-envelope me-2 text-primary"></i>Email: info@cilengkrangwisata.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../template/footer.php';
?>
<script>
    // Script validasi Bootstrap dasar (jika belum ada di script.js global)
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