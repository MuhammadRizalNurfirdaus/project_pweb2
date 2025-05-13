<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\contact.php
// HALAMAN INI MENAMPILKAN FORMULIR KONTAK PUBLIK KEPADA PENGGUNA.

require_once __DIR__ . '/config/config.php'; // Path ke config.php
$page_title = "Hubungi Kami"; // Judul untuk halaman kontak

// Ambil data form dari session jika ada (untuk repopulate setelah gagal submit)
$form_data_nama = '';
$form_data_email = '';
$form_data_pesan = '';

if (isset($_SESSION['form_data_kontak'])) {
    $form_data_nama = e($_SESSION['form_data_kontak']['nama'] ?? '');
    $form_data_email = e($_SESSION['form_data_kontak']['email'] ?? '');
    $form_data_pesan = e($_SESSION['form_data_kontak']['pesan'] ?? '');
    unset($_SESSION['form_data_kontak']); // Hapus setelah diambil
}

include_once __DIR__ . '/template/header.php'; // Menggunakan header publik standar
?>

<div class="main-page-content"> <!-- Wrapper konten utama dari header.php -->

    <!-- Bagian Header Halaman Kontak -->
    <section class="page-header-section text-white d-flex align-items-center" style="background-image: linear-gradient(rgba(226, 205, 205, 0), rgba(0,0,0,0.5)), url('<?= $base_url ?>public/img/air2.jpg'); background-size: cover; background-position: center; min-height: 300px; padding-top: 100px; padding-bottom: 50px;">
        <div class="container text-center">
            <h1 class="display-4 fw-bolder" style="color: white;"> <?= e($page_title) ?> </h1>
           <p class="lead col-lg-10 mx-auto" style="color: white;">
    Kami selalu senang mendengar dari Anda. Apakah Anda memiliki pertanyaan, saran, masukan, atau ingin berkolaborasi? Jangan ragu untuk menghubungi kami melalui formulir di bawah ini atau detail kontak yang tersedia.
</p>


        </div>
    </section>

    <!-- Bagian Formulir Kontak dan Info Kontak -->
    <section class="section-padding" id="form-kontak-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 mb-5 mb-lg-0">
                    <?php
                    // Menampilkan flash message (pesan sukses/error dari handler)
                    if (function_exists('display_flash_message')) {
                        echo display_flash_message();
                    }
                    // Menampilkan pesan sukses khusus jika ada parameter status=sukses di URL dan tidak ada flash message lain
                    if (isset($_GET['status']) && $_GET['status'] === 'sukses' && !isset($_SESSION['flash_message'])) {
                        // Pesan ini sudah ditangani oleh display_flash_message jika dari redirect handler yang benar
                        // Namun, bisa sebagai fallback jika flash message tidak tampil
                        // set_flash_message('success', 'Pesan Anda telah berhasil dikirim! Kami akan segera meresponsnya.');
                        // echo display_flash_message();
                    }
                    ?>

                    <div class="card shadow-lg"> 
                        <div class="card-header bg-success  text-center">
                        <h4 class="mb-0 py-2 text-white">
                        <i class="fas fa-envelope me-2" style="color: white"></i>Kirimkan Pesan Anda
                        </h4>

                        </div>
                        <div class="card-body p-4 p-md-5">
                            <p class="text-muted mb-4 text-center">Lengkapi formulir di bawah ini dan tim kami akan segera menghubungi Anda.</p>
                            <form action="<?= $base_url ?>public/contact_form.php" method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="nama" class="form-label fs-5">Nama Lengkap Anda <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="nama" name="nama" value="<?= $form_data_nama ?>" placeholder="Masukkan nama lengkap Anda" required>
                                    <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label fs-5">Alamat Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= $form_data_email ?>" placeholder="cth: nama@email.com" required>
                                    <div class="invalid-feedback">Format email tidak valid atau email wajib diisi.</div>
                                </div>
                                <!--
                                <div class="mb-3">
                                    <label for="subjek" class="form-label fs-5">Subjek Pesan</label>
                                    <input type="text" class="form-control form-control-lg" id="subjek" name="subjek" placeholder="Subjek pesan Anda (opsional)">
                                </div>
                                -->
                                <div class="mb-4">
                                    <label for="pesan" class="form-label fs-5">Pesan Anda <span class="text-danger">*</span></label>
                                    <textarea class="form-control form-control-lg" id="pesan" name="pesan" rows="6" placeholder="Tuliskan pesan, pertanyaan, atau masukan Anda di sini..." required><?= $form_data_pesan ?></textarea>
                                    <div class="invalid-feedback">Pesan wajib diisi.</div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg py-3">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Pesan Sekarang
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Kontak Tambahan -->
            <div class="row mt-5 pt-5 text-center">
                <div class="col-12">
                    <h3 class="section-subtitle mb-4">Atau Hubungi Kami Melalui:</h3>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="contact-info-item p-4 rounded shadow-sm bg-light h-100">
                        <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                        <h5>Kunjungi Kami</h5>
                        <p class="text-muted">Desa Pajambon, RT/RW xx/xx<br>Kec. Kramatmulya, Kab. Kuningan<br>Jawa Barat, Indonesia Kode Pos 45553</p>
                        <a href="https://maps.google.com/?q=Lembah+Cilengkrang+Kuningan" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat Peta <i class="fas fa-external-link-alt ms-1"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="contact-info-item p-4 rounded shadow-sm bg-light h-100">
                        <i class="fas fa-phone-alt fa-3x text-primary mb-3"></i>
                        <h5>Telepon & WhatsApp</h5>
                        <p class="text-muted mb-1">Reservasi: <a href="tel:+6281234567890">(+62) 8774415865</a></p>
                        <p class="text-muted">Informasi: <a href="https://wa.me/6285712345678" target="_blank">(+62) 8774415865 (WA)</a></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-info-item p-4 rounded shadow-sm bg-light h-100">
                        <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                        <h5>Email Resmi</h5>
                        <p class="text-muted mb-1">Umum: <a href="mailto:info@cilengkrangwisata.com">info@cilengkrangwisata.com</a></p>
                        <p class="text-muted">Dukungan: <a href="mailto:support@cilengkrangwisata.com">support@cilengkrangwisata.com</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div> <!-- Penutup .main-page-content -->

<?php
include_once __DIR__ . '/template/footer.php'; // Menggunakan footer publik standar
?>

<script>
    // Script validasi Bootstrap dasar (jika belum ada di script.js global atau belum ter-include dengan benar)
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>