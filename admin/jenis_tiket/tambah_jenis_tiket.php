<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\tambah_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di tambah_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Model yang mungkin diperlukan
// Model JenisTiket untuk konstanta ALLOWED_TIPE_HARI
if (!class_exists('JenisTiket')) {
    $jenisTiketModelPath = MODELS_PATH . '/JenisTiket.php'; // MODELS_PATH dari config.php
    if (file_exists($jenisTiketModelPath)) {
        require_once $jenisTiketModelPath;
    } else {
        error_log("PERINGATAN di tambah_jenis_tiket.php: Model JenisTiket.php tidak ditemukan. Menggunakan daftar tipe hari default.");
    }
}
// Model Wisata untuk dropdown destinasi
if (!class_exists('Wisata')) {
    $wisataModelPath = MODELS_PATH . '/Wisata.php';
    if (file_exists($wisataModelPath)) {
        require_once $wisataModelPath;
    } else {
        error_log("PERINGATAN di tambah_jenis_tiket.php: Model Wisata.php tidak ditemukan. Dropdown destinasi mungkin kosong.");
    }
}


// 4. Set judul halaman
$pageTitle = "Tambah Jenis Tiket Baru";

// 5. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php'; // ROOT_PATH dari config.php

// 6. Data untuk repopulasi form jika ada error validasi
$session_form_data_key = 'flash_form_data_tambah_jenis_tiket'; // Key session yang lebih spesifik
$input_nama_layanan = $_SESSION[$session_form_data_key]['nama_layanan_display'] ?? '';
$input_tipe_hari = $_SESSION[$session_form_data_key]['tipe_hari'] ?? '';
$input_harga = $_SESSION[$session_form_data_key]['harga'] ?? '';
$input_deskripsi = $_SESSION[$session_form_data_key]['deskripsi'] ?? '';
$input_wisata_id = $_SESSION[$session_form_data_key]['wisata_id'] ?? ''; // Default string kosong jika tidak ada
$input_aktif = $_SESSION[$session_form_data_key]['aktif'] ?? 1; // Default aktif saat tambah

unset($_SESSION[$session_form_data_key]); // Hapus flash data setelah digunakan

// Ambil daftar tipe hari yang diizinkan dari Model JenisTiket jika tersedia
$allowed_tipe_hari_form = (class_exists('JenisTiket') && defined('JenisTiket::ALLOWED_TIPE_HARI')) ? JenisTiket::ALLOWED_TIPE_HARI : ['Hari Kerja', 'Hari Libur', 'Semua Hari'];

// Ambil daftar wisata untuk dropdown
$daftar_wisata = [];
if (class_exists('Wisata') && method_exists('Wisata', 'getAll')) {
    $daftar_wisata = Wisata::getAll('nama_wisata ASC'); // Asumsi Model Wisata mengembalikan dengan key 'nama_wisata'
    if ($daftar_wisata === false) { // Penanganan jika query gagal
        $daftar_wisata = [];
        error_log("Gagal mengambil daftar wisata di tambah_jenis_tiket.php. Error: " . (method_exists('Wisata', 'getLastError') ? Wisata::getLastError() : 'Tidak diketahui'));
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php') ?>"><i class="fas fa-tags"></i> Kelola Jenis Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Jenis Tiket</li>
    </ol>
</nav>

<div class="card shadow mb-4"> <!-- Menggunakan mb-4, bukan shadow-sm -->
    <div class="card-header py-3 bg-primary text-white"> <!-- Disesuaikan dengan tema admin -->
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Formulir Tambah Jenis Tiket Baru</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); // Dipanggil sekali saja, idealnya di header atau di sini 
        ?>

        <form action="<?= e(ADMIN_URL . '/jenis_tiket/proses_jenis_tiket.php') ?>" method="POST" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="tambah">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_layanan_display" class="form-label">Nama Layanan Tiket <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_layanan_display" name="nama_layanan_display" value="<?= e($input_nama_layanan) ?>" placeholder="Contoh: Tiket Masuk Wisata Utama" required>
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
                <input type="number" class="form-control" id="harga" name="harga" value="<?= e($input_harga) ?>" placeholder="Contoh: 20000" required min="0">
                <div class="invalid-feedback">Harga wajib diisi dan harus angka non-negatif.</div>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi Singkat (Opsional)</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Jelaskan singkat mengenai jenis tiket ini..."><?= e($input_deskripsi) // Jika deskripsi boleh HTML, JANGAN e() di sini, tapi saat output di view lain 
                                                                                                                                                    ?></textarea>
            </div>

            <div class="mb-3">
                <label for="wisata_id" class="form-label">Terkait Destinasi Wisata (Opsional)</label>
                <select class="form-select" id="wisata_id" name="wisata_id">
                    <option value="">-- Tidak Terkait Langsung / Berlaku Umum --</option>
                    <?php if (!empty($daftar_wisata)): ?>
                        <?php foreach ($daftar_wisata as $wisata_item): ?>
                            <option value="<?= e($wisata_item['id']) ?>" <?= ($input_wisata_id == $wisata_item['id']) ? 'selected' : '' ?>>
                                <?= e($wisata_item['nama_wisata']) // Asumsi key 'nama_wisata' 
                                ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak ada data destinasi wisata</option>
                    <?php endif; ?>
                </select>
                <small class="form-text text-muted">Pilih jika jenis tiket ini hanya berlaku untuk destinasi tertentu.</small>
            </div>

            <div class="mb-3 form-check">
                <input type="hidden" name="aktif" value="0"> <!-- Fallback jika checkbox tidak dikirim -->
                <input class="form-check-input" type="checkbox" value="1" id="aktif" name="aktif" <?= ($input_aktif == 1 || $input_aktif === null) ? 'checked' : '' // Default checked untuk form tambah 
                                                                                                    ?>>
                <label class="form-check-label" for="aktif">
                    Aktifkan Jenis Tiket Ini (Bisa Dipesan)
                </label>
            </div>

            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= e(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php') ?>" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Jenis Tiket</button>
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