<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\booking.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Booking.php'; // Atau BookingController jika Anda memisahkan logika

require_login(); // Hanya user yang login bisa booking

// Variabel untuk pre-fill dan error
$nama_wisata_input = '';
$tanggal_kunjungan_input = date('Y-m-d', strtotime('+1 day')); // Default besok
$jumlah_orang_input = 1;
// Data pengguna yang login akan diambil dari session

if (is_post()) {
    $nama_wisata_input = input('nama_wisata');
    $tanggal_kunjungan_input = input('tanggal_kunjungan');
    $jumlah_orang_input = (int)input('jumlah_orang');
    $catatan_input = input('catatan'); // Tambahkan field catatan jika perlu

    // Validasi dasar (tambahkan validasi lebih detail jika perlu)
    if (empty($nama_wisata_input) || empty($tanggal_kunjungan_input) || $jumlah_orang_input < 1) {
        set_flash_message('danger', 'Semua field wajib diisi dengan benar.');
    } else {
        $data_booking = [
            'user_id' => get_current_user_id(), // Ambil ID user yang login
            'nama_wisata' => $nama_wisata_input,
            'tanggal_kunjungan' => $tanggal_kunjungan_input,
            'jumlah_orang' => $jumlah_orang_input,
            'status' => 'pending' // Status awal booking
            // Anda bisa menambahkan 'catatan' => $catatan_input jika model mendukungnya
        ];

        if (Booking::create($data_booking)) { // Memanggil method static dari model Booking
            set_flash_message('success', 'Pemesanan Anda berhasil dikirim! Kami akan segera memprosesnya.');
            redirect('user/riwayat_booking.php'); // Arahkan ke halaman riwayat booking
        } else {
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pemesanan. Silakan coba lagi.');
            // Biarkan form ditampilkan kembali dengan data yang sudah diisi (kecuali jika ada error fatal)
        }
    }
    // Jika ada error validasi, akan redirect ke halaman ini lagi untuk menampilkan flash message
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'danger') {
        // Tidak perlu redirect lagi jika error sudah di-set, biarkan form tampil
    }
}

include_once __DIR__ . '/../template/header_user.php';
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0 text-center section-title" style="color:white; margin-bottom:0; margin-top:0;">Form Pemesanan Tiket Wisata</h2>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?= display_flash_message(); ?>
                        <p class="text-muted mb-4">Silakan isi detail pemesanan Anda di bawah ini. Booking atas nama <strong><?= e(get_current_user_name()) ?></strong>.</p>

                        <form action="<?= $base_url ?>user/booking.php" method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="nama_wisata" class="form-label fs-5">Pilih Destinasi Wisata</label>
                                <select class="form-select form-select-lg" id="nama_wisata" name="nama_wisata" required>
                                    <option value="" disabled <?= empty($nama_wisata_input) ? 'selected' : '' ?>>-- Pilih Destinasi --</option>
                                    <option value="Pemandian Air Panas Cilengkrang" <?= ($nama_wisata_input == 'Pemandian Air Panas Cilengkrang') ? 'selected' : '' ?>>Pemandian Air Panas Cilengkrang</option>
                                    <option value="Curug Cilengkrang" <?= ($nama_wisata_input == 'Curug Cilengkrang') ? 'selected' : '' ?>>Curug Cilengkrang</option>
                                    <option value="Area Camping Lembah Dewa" <?= ($nama_wisata_input == 'Area Camping Lembah Dewa') ? 'selected' : '' ?>>Area Camping Lembah Dewa</option>
                                    <!-- Tambahkan destinasi lain dari database jika perlu -->
                                </select>
                                <div class="invalid-feedback">Silakan pilih destinasi wisata.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="tanggal_kunjungan" class="form-label fs-5">Tanggal Kunjungan</label>
                                    <input type="date" class="form-control form-control-lg" id="tanggal_kunjungan" name="tanggal_kunjungan"
                                        value="<?= e($tanggal_kunjungan_input) ?>"
                                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                    <div class="invalid-feedback">Silakan pilih tanggal kunjungan yang valid (minimal besok).</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="jumlah_orang" class="form-label fs-5">Jumlah Orang</label>
                                    <input type="number" class="form-control form-control-lg" id="jumlah_orang" name="jumlah_orang"
                                        min="1" value="<?= e($jumlah_orang_input) ?>" required>
                                    <div class="invalid-feedback">Jumlah orang minimal 1.</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="catatan" class="form-label fs-5">Catatan Tambahan <span class="text-muted small">(Opsional)</span></label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Misalnya: permintaan khusus, alergi, dll."><?= e(input('catatan', '', 'post')) // Ambil dari post jika ada, untuk repopulate 
                                                                                                                                                            ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-3">
                                    <i class="fas fa-calendar-check me-2"></i>Kirim Pemesanan
                                </button>
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