<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\pemesanan_tiket.php
// GANTI NAMA FILE INI DARI booking.php MENJADI pemesanan_tiket.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/PemesananTiket.php'; // Mengganti model Booking.php

require_login(); // Hanya user yang login bisa melakukan pemesanan

// Variabel untuk pre-fill dan error
$nama_destinasi_input = ''; // Sesuai dengan kolom DB: nama_destinasi
$tanggal_kunjungan_input = date('Y-m-d', strtotime('+1 day')); // Default besok
$jumlah_item_input = 1; // Sesuai dengan kolom DB: jumlah_item
// Data pengguna yang login akan diambil dari session

if (is_post()) {
    $nama_destinasi_input = input('nama_destinasi'); // Menyesuaikan nama field form jika perlu
    $tanggal_kunjungan_input = input('tanggal_kunjungan');
    $jumlah_item_input = (int)input('jumlah_item'); // Menyesuaikan nama field form jika perlu
    // $catatan_input = input('catatan'); // Catatan tidak ada di tabel pemesanan_tiket

    // Validasi dasar (tambahkan validasi lebih detail jika perlu)
    if (empty($nama_destinasi_input) || empty($tanggal_kunjungan_input) || $jumlah_item_input < 1) {
        set_flash_message('danger', 'Semua field wajib diisi dengan benar (Destinasi, Tanggal, Jumlah).');
    } else {
        // Persiapan data untuk disimpan ke tabel pemesanan_tiket
        $data_pemesanan = [
            'user_id' => get_current_user_id(),
            'nama_destinasi' => $nama_destinasi_input,
            'tanggal_kunjungan' => $tanggal_kunjungan_input,
            'jumlah_item' => $jumlah_item_input,
            'nama_item' => 'Tiket Masuk ' . $nama_destinasi_input, // Contoh default untuk nama_item
            'jenis_pemesanan' => 'Online', // Contoh default untuk jenis_pemesanan
            'status' => 'pending', // Status awal pemesanan
            // 'total_harga' idealnya dihitung di Model berdasarkan jumlah_item dan harga tiket destinasi
        ];

        // Memanggil method static dari model PemesananTiket
        if (PemesananTiket::create($data_pemesanan)) {
            set_flash_message('success', 'Pemesanan tiket Anda berhasil dikirim! Kami akan segera memprosesnya.');
            redirect('user/riwayat_pemesanan.php'); // Arahkan ke halaman riwayat pemesanan
        } else {
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pemesanan tiket. Silakan coba lagi.');
        }
    }

    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'danger') {
        // Biarkan form tampil dengan data yang sudah diisi
    }
}

$page_title = "Form Pemesanan Tiket"; // Update judul halaman
include_once __DIR__ . '/../template/header_user.php'; // Header sudah diupdate sebelumnya
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
                        <p class="text-muted mb-4">Silakan isi detail pemesanan Anda di bawah ini. Pemesanan atas nama <strong><?= e(get_current_user_name()) ?></strong>.</p>

                        <form action="<?= $base_url ?>user/pemesanan_tiket.php" method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="nama_destinasi" class="form-label fs-5">Pilih Destinasi Wisata</label>
                                <select class="form-select form-select-lg" id="nama_destinasi" name="nama_destinasi" required>
                                    <option value="" disabled <?= empty($nama_destinasi_input) ? 'selected' : '' ?>>-- Pilih Destinasi --</option>
                                    <?php
                                    // Idealnya, daftar destinasi diambil dari tabel 'wisata'
                                    // Untuk contoh, kita gunakan yang sudah ada, pastikan value konsisten
                                    $opsi_destinasi = [
                                        "Pemandian Air Panas Cilengkrang",
                                        "Curug Cilengkrang",
                                        "Area Camping Lembah Dewa",
                                        // Ambil dari tabel `wisata` kolom `nama_wisata`
                                        // Contoh jika ada data dari DB:
                                        // $wisata_list = Wisata::getAll(); // Asumsi method ada
                                        // foreach($wisata_list as $w) {
                                        // echo "<option value=\"".e($w['nama_wisata'])."\" ".($nama_destinasi_input == $w['nama_wisata'] ? 'selected' : '').">".e($w['nama_wisata'])."</option>";
                                        // }
                                    ];
                                    foreach ($opsi_destinasi as $destinasi) :
                                    ?>
                                        <option value="<?= e($destinasi) ?>" <?= ($nama_destinasi_input == $destinasi) ? 'selected' : '' ?>><?= e($destinasi) ?></option>
                                    <?php endforeach; ?>
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
                                    <!-- Mengganti nama field ke jumlah_item agar konsisten dengan form -->
                                    <label for="jumlah_item" class="form-label fs-5">Jumlah Tiket (Orang)</label>
                                    <input type="number" class="form-control form-control-lg" id="jumlah_item" name="jumlah_item"
                                        min="1" value="<?= e($jumlah_item_input) ?>" required>
                                    <div class="invalid-feedback">Jumlah tiket minimal 1.</div>
                                </div>
                            </div>

                            <!-- Catatan dihapus karena tidak ada kolomnya di tabel pemesanan_tiket -->
                            <!-- <div class="mb-4">
                                <label for="catatan" class="form-label fs-5">Catatan Tambahan <span class="text-muted small">(Opsional)</span></label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Misalnya: permintaan khusus, alergi, dll."><?= e(input('catatan', '', 'post')) ?></textarea>
                            </div> -->

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-3">
                                    <i class="fas fa-calendar-check me-2"></i>Kirim Pemesanan Tiket
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