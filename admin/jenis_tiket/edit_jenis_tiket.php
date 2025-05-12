<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\edit_jenis_tiket.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JenisTiketController.php';
require_admin();
// require_once __DIR__ . '/../../models/Wisata.php'; // Jika ingin dropdown destinasi wisata

$page_title = "Edit Jenis Tiket";

// Validasi ID dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    set_flash_message('danger', 'ID Jenis Tiket tidak valid.');
    redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
}
$id_jenis_tiket = (int)$_GET['id'];

// Ambil data jenis tiket yang akan diedit
$jenis_tiket = JenisTiketController::getById($id_jenis_tiket);

if (!$jenis_tiket) {
    set_flash_message('danger', 'Data jenis tiket tidak ditemukan.');
    redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
}

include_once __DIR__ . '/../../template/header_admin.php';

// Ambil data form dari session untuk repopulasi jika ada error sebelumnya,
// ATAU dari data yang ada di database jika ini adalah load pertama.
$nama_layanan_val = $_SESSION['form_data_jenis_tiket']['nama_layanan_display'] ?? $jenis_tiket['nama_layanan_display'];
$tipe_hari_val = $_SESSION['form_data_jenis_tiket']['tipe_hari'] ?? $jenis_tiket['tipe_hari'];
$harga_val = $_SESSION['form_data_jenis_tiket']['harga'] ?? $jenis_tiket['harga'];
$deskripsi_val = $_SESSION['form_data_jenis_tiket']['deskripsi'] ?? $jenis_tiket['deskripsi'];
$wisata_id_val = $_SESSION['form_data_jenis_tiket']['wisata_id'] ?? $jenis_tiket['wisata_id'];
$aktif_val = $_SESSION['form_data_jenis_tiket']['aktif'] ?? $jenis_tiket['aktif'];

unset($_SESSION['form_data_jenis_tiket']); // Hapus setelah digunakan

// Ambil daftar wisata untuk dropdown (opsional)
// $daftar_wisata = class_exists('Wisata') ? Wisata::getAll() : [];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/jenis_tiket/kelola_jenis_tiket.php"><i class="fas fa-tags"></i> Kelola Jenis Tiket</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Jenis Tiket: <?= e($jenis_tiket['nama_layanan_display'] . ' - ' . $jenis_tiket['tipe_hari']) ?></li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark"> <!-- Warna header bisa disesuaikan -->
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Edit Jenis Tiket</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>

        <form action="<?= $base_url ?>admin/jenis_tiket/proses_edit_jenis_tiket.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= e($id_jenis_tiket) ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_layanan_display" class="form-label">Nama Layanan Tiket <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_layanan_display" name="nama_layanan_display" value="<?= e($nama_layanan_val) ?>" placeholder="Contoh: Tiket Masuk Wisata, Tiket Camping" required>
                    <div class="invalid-feedback">Nama layanan tiket wajib diisi.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tipe_hari" class="form-label">Tipe Hari <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipe_hari" name="tipe_hari" required>
                        <option value="" disabled>-- Pilih Tipe Hari --</option>
                        <option value="Hari Kerja" <?= ($tipe_hari_val == 'Hari Kerja') ? 'selected' : '' ?>>Hari Kerja</option>
                        <option value="Hari Libur" <?= ($tipe_hari_val == 'Hari Libur') ? 'selected' : '' ?>>Hari Libur</option>
                        <option value="Semua Hari" <?= ($tipe_hari_val == 'Semua Hari') ? 'selected' : '' ?>>Semua Hari (Harga Flat)</option>
                    </select>
                    <div class="invalid-feedback">Silakan pilih tipe hari.</div>
                </div>
            </div>

            <div class="mb-3">
                <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?= e($harga_val) ?>" placeholder="Contoh: 20000" required min="0">
                <div class="invalid-feedback">Harga wajib diisi dan harus angka non-negatif.</div>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi Singkat (Opsional)</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Jelaskan singkat mengenai jenis tiket ini..."><?= e($deskripsi_val) ?></textarea>
            </div>

            <!-- Opsional: Jika jenis tiket terikat ke wisata tertentu -->
            <!--
            <div class="mb-3">
                <label for="wisata_id" class="form-label">Terkait Destinasi Wisata (Opsional)</label>
                <select class="form-select" id="wisata_id" name="wisata_id">
                    <option value="">-- Tidak Terkait Langsung / Berlaku Umum --</option>
                    <?php //if (!empty($daftar_wisata)): 
                    ?>
                        <?php //foreach ($daftar_wisata as $wisata): 
                        ?>
                            <option value="<? //= e($wisata['id']) 
                                            ?>" <? //= ($wisata_id_val == $wisata['id']) ? 'selected' : '' 
                                                ?>>
                                <? //= e($wisata['nama_wisata']) 
                                ?>
                            </option>
                        <?php //endforeach; 
                        ?>
                    <?php //endif; 
                    ?>
                </select>
                <small class="form-text text-muted">Pilih jika jenis tiket ini hanya berlaku untuk destinasi tertentu.</small>
            </div>
            -->

            <div class="mb-3 form-check">
                <input type="hidden" name="aktif" value="0"> <!-- Fallback jika checkbox tidak dikirim -->
                <input class="form-check-input" type="checkbox" value="1" id="aktif" name="aktif" <?= ($aktif_val == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="aktif">
                    Aktifkan Jenis Tiket Ini (Bisa Dipesan)
                </label>
            </div>

            <hr>
            <div class="d-flex justify-content-end">
                <a href="<?= $base_url ?>admin/jenis_tiket/kelola_jenis_tiket.php" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php
include_once __DIR__ . '/../../template/footer_admin.php';
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