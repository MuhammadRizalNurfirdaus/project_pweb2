<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\tambah_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di tambah_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di tambah_jenis_tiket.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// 3. Pastikan Model yang relevan ada
if (!class_exists('Wisata') || !method_exists('Wisata', 'getAll')) {
    error_log("PERINGATAN di tambah_jenis_tiket.php: Model Wisata atau metode getAll tidak ditemukan.");
}
if (!class_exists('JenisTiket')) {
    error_log("PERINGATAN di tambah_jenis_tiket.php: Model JenisTiket tidak ditemukan.");
}

// 4. Set judul halaman (SEBELUM include header)
$pageTitle = "Tambah Jenis Tiket Baru";

// 5. Sertakan header admin
$header_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : ROOT_PATH . '/template/header_admin.php';
if (!file_exists($header_admin_path)) {
    error_log("FATAL ERROR di tambah_jenis_tiket.php: File template header admin tidak ditemukan. Path: " . $header_admin_path);
    exit("Error Kritis: Komponen tampilan header tidak dapat dimuat.");
}
require_once $header_admin_path;

// 6. Data untuk repopulasi form
$session_form_data_key = 'flash_form_data_tambah_jenis_tiket';
$input_nama_layanan = $_SESSION[$session_form_data_key]['nama_layanan_display'] ?? '';
$input_tipe_hari = $_SESSION[$session_form_data_key]['tipe_hari'] ?? '';
$input_harga = $_SESSION[$session_form_data_key]['harga'] ?? '';
$input_deskripsi = $_SESSION[$session_form_data_key]['deskripsi'] ?? '';
$input_wisata_id = $_SESSION[$session_form_data_key]['wisata_id'] ?? '';
$input_aktif = $_SESSION[$session_form_data_key]['aktif'] ?? 1;

if (isset($_SESSION[$session_form_data_key])) {
    unset($_SESSION[$session_form_data_key]);
}

$allowed_tipe_hari_form = (defined('JenisTiket::ALLOWED_TIPE_HARI')) ? JenisTiket::ALLOWED_TIPE_HARI : ['Hari Kerja', 'Hari Libur', 'Semua Hari'];

$daftar_wisata = [];
if (class_exists('Wisata') && method_exists('Wisata', 'getAll')) {
    $daftar_wisata = Wisata::getAll('nama ASC');
    if ($daftar_wisata === false) $daftar_wisata = [];
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php') ?>"><i class="fas fa-tags"></i> Kelola Jenis Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Jenis Tiket</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Tambah Jenis Tiket Baru</h1>
    <a href="<?= e(ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Formulir Tambah Jenis Tiket Baru</h5>
    </div>
    <div class="card-body">
        <?php // display_flash_message() sudah dipanggil di header_admin.php 
        ?>

        <?php // PERBAIKAN PADA ACTION FORM 
        ?>
        <form action="<?= e(ADMIN_URL . 'jenis_tiket/proses_tambah_jenis_tiket.php') ?>" method="POST" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="tambah">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_layanan_display" class="form-label">Nama Layanan Tiket <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_layanan_display" name="nama_layanan_display" value="<?= e($input_nama_layanan) ?>" placeholder="Contoh: Tiket Masuk Regular, Tiket Camping Malam" required>
                    <div class="invalid-feedback">Nama layanan tiket wajib diisi.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tipe_hari" class="form-label">Tipe Hari <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipe_hari" name="tipe_hari" required>
                        <option value="" <?= empty($input_tipe_hari) ? 'selected' : '' ?>>-- Pilih Tipe Hari --</option>
                        <?php foreach ($allowed_tipe_hari_form as $tipe_value): ?>
                            <option value="<?= e($tipe_value) ?>" <?= ($input_tipe_hari === $tipe_value) ? 'selected' : '' ?>><?= e($tipe_value) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Silakan pilih tipe hari.</div>
                </div>
            </div>

            <div class="mb-3">
                <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?= e($input_harga) ?>" placeholder="Contoh: 25000" required min="0" step="500">
                <div class="invalid-feedback">Harga wajib diisi dan harus angka non-negatif.</div>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi Singkat <span class="text-muted">(Opsional)</span></label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Jelaskan singkat mengenai jenis tiket ini..."><?= e($input_deskripsi) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="wisata_id" class="form-label">Terkait Destinasi Wisata <span class="text-muted">(Opsional)</span></label>
                <select class="form-select" id="wisata_id" name="wisata_id">
                    <option value="">-- Tidak Terkait Langsung / Berlaku Umum --</option>
                    <?php if (!empty($daftar_wisata) && is_array($daftar_wisata)): ?>
                        <?php foreach ($daftar_wisata as $wisata_item): ?>
                            <option value="<?= e($wisata_item['id']) ?>" <?= ($input_wisata_id == $wisata_item['id']) ? 'selected' : '' ?>>
                                <?= e($wisata_item['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Data destinasi tidak tersedia</option>
                    <?php endif; ?>
                </select>
                <small class="form-text text-muted">Pilih jika jenis tiket ini hanya berlaku untuk destinasi tertentu.</small>
            </div>

            <div class="mb-3 form-check">
                <input type="hidden" name="aktif" value="0">
                <input class="form-check-input" type="checkbox" value="1" id="aktif" name="aktif" <?= ($input_aktif == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="aktif">
                    Aktifkan Jenis Tiket Ini (Bisa Dipesan)
                </label>
            </div>

            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= e(ADMIN_URL . 'jenis_tiket/kelola_jenis_tiket.php') ?>" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" name="submit_tambah_jenis_tiket" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Jenis Tiket</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>
<script>
    // Script validasi Bootstrap dasar (sudah baik)
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