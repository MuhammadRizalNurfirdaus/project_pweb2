<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\edit_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di edit_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan Controller dan Model yang relevan
// Diasumsikan config.php sudah memuatnya dan menginisialisasi Model
$controllerPath = CONTROLLERS_PATH . '/JenisTiketController.php';
if (!class_exists('JenisTiketController')) {
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        error_log("FATAL ERROR di edit_jenis_tiket.php: File JenisTiketController.php tidak ditemukan di " . $controllerPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}
// Model Wisata untuk dropdown destinasi (jika diaktifkan)
if (!class_exists('Wisata') && file_exists(MODELS_PATH . '/Wisata.php')) {
    require_once MODELS_PATH . '/Wisata.php';
}
// Model JenisTiket untuk konstanta (jika ada)
if (!class_exists('JenisTiket') && file_exists(MODELS_PATH . '/JenisTiket.php')) {
    require_once MODELS_PATH . '/JenisTiket.php';
}


// 4. Validasi ID dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    set_flash_message('danger', 'ID Jenis Tiket tidak valid.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}
$id_jenis_tiket = (int)$_GET['id'];

// 5. Ambil data jenis tiket yang akan diedit melalui Controller
if (!method_exists('JenisTiketController', 'getById')) {
    error_log("FATAL ERROR di edit_jenis_tiket.php: Metode JenisTiketController::getById() tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi pengambilan data jenis tiket tidak tersedia.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}
$jenis_tiket_to_edit = JenisTiketController::getById($id_jenis_tiket);

if (!$jenis_tiket_to_edit) {
    set_flash_message('danger', 'Data jenis tiket dengan ID ' . $id_jenis_tiket . ' tidak ditemukan.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
    exit;
}

// 6. Set judul halaman
$pageTitle = "Edit Jenis Tiket: " . e($jenis_tiket_to_edit['nama_layanan_display'] ?? 'N/A');

// 7. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 8. Data untuk repopulasi form
$session_form_data_key = 'flash_form_data_edit_jenis_tiket_' . $id_jenis_tiket; // Key unik
$input_nama_layanan = $_SESSION[$session_form_data_key]['nama_layanan_display'] ?? $jenis_tiket_to_edit['nama_layanan_display'] ?? '';
$input_tipe_hari = $_SESSION[$session_form_data_key]['tipe_hari'] ?? $jenis_tiket_to_edit['tipe_hari'] ?? '';
$input_harga = $_SESSION[$session_form_data_key]['harga'] ?? $jenis_tiket_to_edit['harga'] ?? '';
$input_deskripsi = $_SESSION[$session_form_data_key]['deskripsi'] ?? $jenis_tiket_to_edit['deskripsi'] ?? '';
$input_wisata_id = $_SESSION[$session_form_data_key]['wisata_id'] ?? $jenis_tiket_to_edit['wisata_id'] ?? '';
$input_aktif = $_SESSION[$session_form_data_key]['aktif'] ?? $jenis_tiket_to_edit['aktif'] ?? 1; // Default ke 1 jika tidak ada

unset($_SESSION[$session_form_data_key]);

$allowed_tipe_hari_form = (class_exists('JenisTiket') && defined('JenisTiket::ALLOWED_TIPE_HARI')) ? JenisTiket::ALLOWED_TIPE_HARI : ['Hari Kerja', 'Hari Libur', 'Semua Hari'];

$daftar_wisata = [];
if (class_exists('Wisata') && method_exists('Wisata', 'getAll')) {
    $daftar_wisata = Wisata::getAll('nama_wisata ASC');
    if ($daftar_wisata === false) $daftar_wisata = [];
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php') ?>"><i class="fas fa-tags"></i> Kelola Jenis Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit: <?= e($jenis_tiket_to_edit['nama_layanan_display'] . ' - ' . $jenis_tiket_to_edit['tipe_hari']) ?></li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Formulir Edit Jenis Tiket</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); // Dipanggil sekali saja 
        ?>

        <form action="<?= e(ADMIN_URL . '/jenis_tiket/proses_edit_jenis_tiket.php') ?>" method="POST" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="id_jenis_tiket" value="<?= e($id_jenis_tiket) ?>"> <?php // Ganti nama 'id' menjadi 'id_jenis_tiket' agar lebih jelas 
                                                                                            ?>
            <input type="hidden" name="action" value="edit">


            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_layanan_display" class="form-label">Nama Layanan Tiket <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_layanan_display" name="nama_layanan_display" value="<?= e($input_nama_layanan) ?>" placeholder="Contoh: Tiket Masuk Wisata, Tiket Camping" required>
                    <div class="invalid-feedback">Nama layanan tiket wajib diisi.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tipe_hari" class="form-label">Tipe Hari <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipe_hari" name="tipe_hari" required>
                        <option value="" disabled>-- Pilih Tipe Hari --</option>
                        <?php foreach ($allowed_tipe_hari_form as $tipe_value): ?>
                            <option value="<?= e($tipe_value) ?>" <?= ($input_tipe_hari == $tipe_value) ? 'selected' : '' ?>><?= e($tipe_value) ?></option>
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
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Jelaskan singkat mengenai jenis tiket ini..."><?= e($input_deskripsi) // Hati-hati jika deskripsi mengandung HTML 
                                                                                                                                                    ?></textarea>
            </div>

            <div class="mb-3">
                <label for="wisata_id" class="form-label">Terkait Destinasi Wisata (Opsional)</label>
                <select class="form-select" id="wisata_id" name="wisata_id">
                    <option value="">-- Tidak Terkait Langsung / Berlaku Umum --</option>
                    <?php if (!empty($daftar_wisata)): ?>
                        <?php foreach ($daftar_wisata as $wisata_item): ?>
                            <option value="<?= e($wisata_item['id']) ?>" <?= ($input_wisata_id == $wisata_item['id']) ? 'selected' : '' ?>>
                                <?= e($wisata_item['nama_wisata']) // Pastikan key ini ada dari Wisata::getAll() 
                                ?>
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
                <a href="<?= e(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php') ?>" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
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