<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jadwal_ketersediaan\tambah_jadwal.php

require_once __DIR__ . '/../../config/config.php';
// require_admin(); // Pastikan admin sudah login

// Memuat Model JenisTiket untuk mendapatkan daftar jenis tiket yang bisa dipilih
require_once __DIR__ . '/../../models/JenisTiket.php'; // Pastikan path ini benar

$page_title = "Tambah Jadwal Ketersediaan Tiket";
include_once __DIR__ . '/../../template/header_admin.php';

// Ambil semua jenis tiket yang AKTIF untuk ditampilkan di dropdown
$semua_jenis_tiket = JenisTiket::getAll(); // Asumsi method ini mengembalikan array
$daftar_jenis_tiket_aktif = [];
if (is_array($semua_jenis_tiket)) {
    foreach ($semua_jenis_tiket as $jt) {
        // Asumsikan ada kolom 'aktif' di tabel jenis_tiket
        if (isset($jt['aktif']) && $jt['aktif'] == 1) {
            $daftar_jenis_tiket_aktif[] = $jt;
        }
    }
}


// Ambil data form dari session untuk repopulasi jika ada error sebelumnya
$fs_jenis_tiket_id = $_SESSION['form_data_jadwal']['jenis_tiket_id'] ?? '';
$fs_tanggal = $_SESSION['form_data_jadwal']['tanggal'] ?? date('Y-m-d'); // Default hari ini
$fs_jumlah_total = $_SESSION['form_data_jadwal']['jumlah_total_tersedia'] ?? 100; // Default kuota awal
// Saat tambah baru, jumlah_saat_ini_tersedia biasanya sama dengan jumlah_total_tersedia
$fs_jumlah_saat_ini = $_SESSION['form_data_jadwal']['jumlah_saat_ini_tersedia'] ?? $fs_jumlah_total;
$fs_aktif = $_SESSION['form_data_jadwal']['aktif'] ?? 1; // Default jadwal aktif

unset($_SESSION['form_data_jadwal']); // Hapus data dari session setelah digunakan
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/jadwal_ketersediaan/kelola_jadwal.php"><i class="fas fa-calendar-alt"></i> Kelola Jadwal</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Jadwal Ketersediaan</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Form Tambah Jadwal Ketersediaan Tiket</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>

        <form action="<?= e($base_url) ?>admin/jadwal_ketersediaan/proses_tambah_jadwal.php" method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="jenis_tiket_id" class="form-label">Jenis Tiket <span class="text-danger">*</span></label>
                    <select class="form-select form-select-lg" id="jenis_tiket_id" name="jenis_tiket_id" required>
                        <option value="" disabled <?= empty($fs_jenis_tiket_id) ? 'selected' : '' ?>>-- Pilih Jenis Tiket --</option>
                        <?php if (!empty($daftar_jenis_tiket_aktif)): ?>
                            <?php foreach ($daftar_jenis_tiket_aktif as $jenis): ?>
                                <option value="<?= e($jenis['id']) ?>" <?= ($fs_jenis_tiket_id == $jenis['id']) ? 'selected' : '' ?>>
                                    <?= e($jenis['nama_layanan_display']) ?> (<?= e($jenis['tipe_hari']) ?>) - Rp <?= e(number_format($jenis['harga'])) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ada jenis tiket aktif. Silakan tambahkan jenis tiket terlebih dahulu.</option>
                        <?php endif; ?>
                    </select>
                    <div class="invalid-feedback">Silakan pilih jenis tiket.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tanggal" class="form-label">Tanggal Ketersediaan <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-lg" id="tanggal" name="tanggal" value="<?= e($fs_tanggal) ?>" min="<?= date('Y-m-d') // Minimal hari ini 
                                                                                                                                            ?>" required>
                    <div class="invalid-feedback">Silakan pilih tanggal yang valid (minimal hari ini).</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="jumlah_total_tersedia" class="form-label">Jumlah Total Tiket Tersedia <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jumlah_total_tersedia" name="jumlah_total_tersedia" value="<?= e($fs_jumlah_total) ?>" placeholder="Kuota tiket untuk tanggal ini" required min="0">
                    <div class="invalid-feedback">Jumlah total tiket wajib diisi (minimal 0).</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="jumlah_saat_ini_tersedia" class="form-label">Jumlah Saat Ini Tersedia <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jumlah_saat_ini_tersedia" name="jumlah_saat_ini_tersedia" value="<?= e($fs_jumlah_saat_ini) ?>" placeholder="Sisa tiket saat ini" required min="0">
                    <div class="invalid-feedback">Jumlah tiket saat ini wajib diisi (minimal 0, dan tidak boleh melebihi Jumlah Total).</div>
                    <small class="form-text text-muted">Saat membuat jadwal baru, ini biasanya sama dengan "Jumlah Total Tersedia".</small>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="hidden" name="aktif" value="0"> <!-- Fallback value jika checkbox tidak dicentang -->
                <input class="form-check-input" type="checkbox" value="1" id="aktif" name="aktif" <?= ($fs_aktif == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="aktif">
                    Aktifkan Jadwal Ini (Tiket Bisa Dipesan)
                </label>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <a href="<?= e($base_url) ?>admin/jadwal_ketersediaan/kelola_jadwal.php" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<?php
include_once __DIR__ . '/../../template/footer_admin.php';
?>
<script>
    // Script validasi Bootstrap dasar dan validasi custom untuk jumlah tiket
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                var jumlahTotalInput = form.querySelector('#jumlah_total_tersedia');
                var jumlahSaatIniInput = form.querySelector('#jumlah_saat_ini_tersedia');

                form.addEventListener('submit', function(event) {
                    if (jumlahTotalInput && jumlahSaatIniInput) {
                        if (parseInt(jumlahSaatIniInput.value) > parseInt(jumlahTotalInput.value)) {
                            jumlahSaatIniInput.setCustomValidity('Jumlah saat ini tidak boleh melebihi jumlah total tiket tersedia.');
                        } else {
                            jumlahSaatIniInput.setCustomValidity('');
                        }
                    }
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);

                if (jumlahTotalInput && jumlahSaatIniInput) {
                    jumlahTotalInput.addEventListener('input', function() {
                        // Jika admin mengubah total, dan jumlah saat ini belum diisi atau ingin disamakan
                        if (jumlahSaatIniInput.value === '' || !jumlahSaatIniInput.dataset.userEdited) {
                            // Untuk form tambah, defaultkan jumlah saat ini = jumlah total
                            // Anda bisa mengaktifkan baris di bawah jika ingin auto-fill
                            // jumlahSaatIniInput.value = this.value;
                        }
                    });
                    // Tandai jika user mengedit manual input jumlah saat ini untuk mencegah auto-fill
                    // jumlahSaatIniInput.addEventListener('input', function() {
                    //    this.dataset.userEdited = 'true';
                    // });
                }
            });
    })();
</script>