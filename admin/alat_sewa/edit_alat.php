<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\edit_alat.php

// 1. Sertakan konfigurasi utama
if (!@require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/alat_sewa/edit_alat.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Otentikasi Admin
if (!function_exists('require_admin')) {
    error_log("FATAL ERROR di edit_alat.php: Fungsi require_admin() tidak ditemukan. Periksa pemuatan helpers.");
    http_response_code(500);
    exit("Kesalahan sistem: Komponen otorisasi tidak tersedia. (Error Code: ADM_AUTH_NF_EDIT)");
}
require_admin();

// 3. Pastikan SewaAlatController dan metode yang dibutuhkan ada
if (
    !class_exists('SewaAlatController') ||
    !method_exists('SewaAlatController', 'getById') ||
    !method_exists('SewaAlatController', 'handleUpdateAlat')
) {
    error_log("FATAL ERROR di edit_alat.php: SewaAlatController atau metode yang dibutuhkan tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen data alat sewa tidak dapat dimuat (SAC_NF_EDIT).');
    redirect(ADMIN_URL . 'alat_sewa/kelola_alat.php');
    exit;
}
if (!defined('SewaAlat::ALLOWED_DURATION_UNITS') || !defined('SewaAlat::ALLOWED_CONDITIONS')) {
    error_log("FATAL ERROR di edit_alat.php: Konstanta dari Model SewaAlat tidak terdefinisi.");
    set_flash_message('danger', 'Kesalahan sistem: Konfigurasi data alat sewa tidak lengkap (SAM_CONST_NF_EDIT).');
    redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// 4. Validasi ID dari URL
$id_alat = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($id_alat <= 0) {
    set_flash_message('danger', 'ID Alat Sewa tidak valid atau tidak disertakan.');
    redirect(ADMIN_URL . 'alat_sewa/kelola_alat.php');
    exit;
}

// 5. Ambil data alat yang akan diedit
$alat_sewa_data_awal = SewaAlatController::getById($id_alat);
if (!$alat_sewa_data_awal) {
    set_flash_message('danger', 'Data alat sewa dengan ID ' . e($id_alat) . ' tidak ditemukan.');
    redirect(ADMIN_URL . 'alat_sewa/kelola_alat.php');
    exit;
}

// 6. Set judul halaman (SEBELUM include header)
$pageTitle = "Edit Alat Sewa: " . e($alat_sewa_data_awal['nama_item'] ?? 'N/A');

// 7. Sertakan header admin
require_once ROOT_PATH . '/template/header_admin.php';

// 8. Data untuk repopulasi form
$session_form_data_key = 'flash_form_data_edit_alat_sewa_' . $id_alat;

// Pastikan nilai numerik adalah numerik atau string kosong untuk input value
$harga_sewa_val = $alat_sewa_data_awal['harga_sewa'] ?? '1';
if (isset($_SESSION[$session_form_data_key]['harga_sewa']) && is_numeric($_SESSION[$session_form_data_key]['harga_sewa'])) {
    $harga_sewa_val = $_SESSION[$session_form_data_key]['harga_sewa'];
}

$durasi_harga_sewa_val = $alat_sewa_data_awal['durasi_harga_sewa'] ?? '1';
if (isset($_SESSION[$session_form_data_key]['durasi_harga_sewa']) && is_numeric($_SESSION[$session_form_data_key]['durasi_harga_sewa'])) {
    $durasi_harga_sewa_val = $_SESSION[$session_form_data_key]['durasi_harga_sewa'];
}

$stok_tersedia_val = $alat_sewa_data_awal['stok_tersedia'] ?? '1';
if (isset($_SESSION[$session_form_data_key]['stok_tersedia']) && is_numeric($_SESSION[$session_form_data_key]['stok_tersedia'])) {
    $stok_tersedia_val = $_SESSION[$session_form_data_key]['stok_tersedia'];
}


$fs_nama_item = $_SESSION[$session_form_data_key]['nama_item'] ?? $alat_sewa_data_awal['nama_item'] ?? '';
$fs_kategori_alat = $_SESSION[$session_form_data_key]['kategori_alat'] ?? $alat_sewa_data_awal['kategori_alat'] ?? '';
$fs_deskripsi = $_SESSION[$session_form_data_key]['deskripsi'] ?? $alat_sewa_data_awal['deskripsi'] ?? '';
$fs_harga_sewa = $harga_sewa_val;
$fs_durasi_harga_sewa = $durasi_harga_sewa_val;
$fs_satuan_durasi_harga = $_SESSION[$session_form_data_key]['satuan_durasi_harga'] ?? $alat_sewa_data_awal['satuan_durasi_harga'] ?? 'Hari';
$fs_stok_tersedia = $stok_tersedia_val;
$fs_kondisi_alat = $_SESSION[$session_form_data_key]['kondisi_alat'] ?? $alat_sewa_data_awal['kondisi_alat'] ?? 'Baik';
$current_gambar_alat_db = $alat_sewa_data_awal['gambar_alat'] ?? null;

if (isset($_SESSION[$session_form_data_key])) {
    unset($_SESSION[$session_form_data_key]);
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'alat_sewa/kelola_alat.php') ?>"><i class="fas fa-tools"></i> Kelola Alat Sewa</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Alat: <?= e($alat_sewa_data_awal['nama_item']) ?></li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Edit Alat Sewa</h1>
    <a href="<?= e(ADMIN_URL . 'alat_sewa/kelola_alat.php') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>


<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Edit Alat Sewa</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>

        <form action="<?= e(ADMIN_URL . 'alat_sewa/proses_edit_alat.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="id_alat" value="<?= e($id_alat) ?>">
            <input type="hidden" name="gambar_lama_db" value="<?= e($current_gambar_alat_db) ?>">

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="nama_item" class="form-label">Nama Item Alat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="nama_item" name="nama_item" value="<?= e($fs_nama_item) ?>" required>
                        <div class="invalid-feedback">Nama item alat wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label for="kategori_alat" class="form-label">Kategori Alat <span class="text-muted">(Opsional)</span></label>
                        <input type="text" class="form-control" id="kategori_alat" name="kategori_alat" value="<?= e($fs_kategori_alat) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi <span class="text-muted">(Opsional)</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= e($fs_deskripsi) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 mb-3">
                            <label for="harga_sewa" class="form-label">Harga Sewa (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga_sewa" name="harga_sewa" value="<?= e($fs_harga_sewa) ?>" required min="1" step="any">
                            <div class="invalid-feedback">Harga sewa wajib diisi (minimal Rp 1).</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="durasi_harga_sewa" class="form-label">Durasi Harga <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="durasi_harga_sewa" name="durasi_harga_sewa" value="<?= e($fs_durasi_harga_sewa) ?>" required min="1">
                            <div class="invalid-feedback">Durasi harga wajib diisi (minimal 1).</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="satuan_durasi_harga" class="form-label">Satuan Durasi <span class="text-danger">*</span></label>
                            <select class="form-select" id="satuan_durasi_harga" name="satuan_durasi_harga" required>
                                <?php $allowed_durations = SewaAlat::ALLOWED_DURATION_UNITS; ?>
                                <?php foreach ($allowed_durations as $satuan): ?>
                                    <option value="<?= e($satuan) ?>" <?= ($fs_satuan_durasi_harga == $satuan) ? 'selected' : '' ?>><?= e($satuan) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Pilih satuan durasi.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="stok_tersedia" class="form-label">Stok Tersedia <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok_tersedia" name="stok_tersedia" value="<?= e($fs_stok_tersedia) ?>" required min="1">
                            <div class="invalid-feedback">Stok wajib diisi (minimal 1 unit).</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kondisi_alat" class="form-label">Kondisi Alat <span class="text-danger">*</span></label>
                            <select class="form-select" id="kondisi_alat" name="kondisi_alat" required>
                                <?php $allowed_conditions = SewaAlat::ALLOWED_CONDITIONS; ?>
                                <?php foreach ($allowed_conditions as $kondisi): ?>
                                    <option value="<?= e($kondisi) ?>" <?= ($fs_kondisi_alat == $kondisi) ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $kondisi))) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Pilih kondisi alat.</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Gambar Alat</div>
                        <div class="card-body text-center">
                            <?php
                            $gambar_alat_url_display = '#';
                            $gambar_display_style = 'none';
                            $icon_display_style = 'block';
                            $base_uploads_alat_url = defined('UPLOADS_ALAT_SEWA_URL') ? UPLOADS_ALAT_SEWA_URL : (defined('BASE_URL') ? BASE_URL . 'public/uploads/alat_sewa/' : './public/uploads/alat_sewa/');
                            $base_uploads_alat_path = defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH : ROOT_PATH . '/public/uploads/alat_sewa/';

                            if (!empty($current_gambar_alat_db) && file_exists($base_uploads_alat_path . DIRECTORY_SEPARATOR . $current_gambar_alat_db)) {
                                $gambar_alat_url_display = rtrim($base_uploads_alat_url, '/') . '/' . rawurlencode($current_gambar_alat_db) . '?t=' . time();
                                $gambar_display_style = 'block';
                                $icon_display_style = 'none';
                            }
                            ?>
                            <img id="gambar_preview"
                                src="<?= e($gambar_alat_url_display) ?>"
                                alt="Preview Gambar Alat"
                                class="img-fluid img-thumbnail mb-2"
                                style="max-height: 180px; object-fit: cover; display: <?= $gambar_display_style ?>;"
                                data-original-src="<?= e($gambar_alat_url_display) ?>"
                                data-placeholder-icon-display="<?= $icon_display_style ?>"
                                data-placeholder-img-display="<?= $gambar_display_style ?>">
                            <i id="icon_placeholder" class="fas fa-camera fa-5x text-muted mb-2" style="display: <?= $icon_display_style ?>;"></i>

                            <p class="small mb-1" id="status_gambar_info">
                                <?php
                                if (!empty($current_gambar_alat_db) && $gambar_display_style === 'block'):
                                    echo 'Saat Ini: ' . e($current_gambar_alat_db);
                                elseif (!empty($current_gambar_alat_db) && $gambar_display_style === 'none'):
                                    echo '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> File gambar "' . e($current_gambar_alat_db) . '" tidak ditemukan.</span>';
                                else:
                                    echo 'Belum ada gambar.';
                                endif;
                                ?>
                            </p>

                            <label for="gambar_action" class="form-label mt-2">Tindakan untuk Gambar:</label>
                            <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUploadAlat(this.value)">
                                <option value="keep" selected>Pertahankan Gambar</option>
                                <option value="change">Ganti dengan Gambar Baru</option>
                                <?php if (!empty($current_gambar_alat_db) && $gambar_display_style === 'block'): ?>
                                    <option value="remove">Hapus Gambar Saat Ini</option>
                                <?php endif; ?>
                            </select>

                            <div id="new-image-upload-section-alat" style="display:none;" class="mt-2">
                                <label for="gambar_alat_baru" class="form-label">Pilih Gambar Baru (Maks 2MB):</label>
                                <input type="file" class="form-control form-control-sm" id="gambar_alat_baru" name="gambar_alat_baru" accept="image/png, image/jpeg, image/gif, image/webp" onchange="previewEditImageAlat(event)">
                                <small class="form-text text-muted">Format: JPG, PNG, GIF, WEBP.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <a href="<?= e(ADMIN_URL . 'alat_sewa/kelola_alat.php') ?>" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" name="submit_edit_alat" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>

<script>
    function toggleNewImageUploadAlat(action) {
        const newImageSection = document.getElementById('new-image-upload-section-alat');
        const gambarBaruInput = document.getElementById('gambar_alat_baru');
        const imageField = document.getElementById("gambar_preview");
        const iconPlaceholder = document.getElementById("icon_placeholder");
        const statusGambarInfo = document.getElementById("status_gambar_info");

        if (action === 'change') {
            newImageSection.style.display = 'block';
        } else {
            newImageSection.style.display = 'none';
            if (gambarBaruInput) gambarBaruInput.value = '';

            const originalSrc = imageField.dataset.originalSrc || '#';
            const originalIconDisplay = imageField.dataset.placeholderIconDisplay || 'block';
            const originalImgDisplay = imageField.dataset.placeholderImgDisplay || 'none';

            imageField.src = originalSrc;
            imageField.style.display = originalImgDisplay;
            iconPlaceholder.style.display = originalIconDisplay;

            if (statusGambarInfo) {
                if (action === 'remove' && originalSrc !== '#') {
                    statusGambarInfo.innerHTML = '<span class="text-warning">Gambar akan dihapus saat disimpan.</span>';
                    imageField.style.display = 'none';
                    iconPlaceholder.style.display = 'block';
                } else if (originalSrc === '#') {
                    statusGambarInfo.textContent = 'Belum ada gambar.';
                    statusGambarInfo.className = 'small mb-1'; // Reset class
                } else if (document.getElementById('gambar_action').value === 'keep') { // Cek lagi jika user kembali ke 'keep'
                    statusGambarInfo.innerHTML = 'Saat Ini: <?= e($current_gambar_alat_db ?? '') ?>';
                    statusGambarInfo.className = 'small text-muted mb-1';
                }
            }
        }
    }

    function previewEditImageAlat(event) {
        const reader = new FileReader();
        const imageField = document.getElementById("gambar_preview");
        const iconPlaceholder = document.getElementById("icon_placeholder");
        const statusGambarInfo = document.getElementById("status_gambar_info");

        reader.onload = function() {
            if (reader.readyState == 2) {
                imageField.src = reader.result;
                imageField.style.display = 'block';
                iconPlaceholder.style.display = 'none';
                if (statusGambarInfo) statusGambarInfo.textContent = 'Preview gambar baru.';
            }
        }
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            const originalSrc = imageField.dataset.originalSrc || '#';
            const originalIconDisplay = imageField.dataset.placeholderIconDisplay || 'block';
            const originalImgDisplay = imageField.dataset.placeholderImgDisplay || 'none';

            imageField.src = originalSrc;
            imageField.style.display = originalImgDisplay;
            iconPlaceholder.style.display = originalIconDisplay;
            if (statusGambarInfo) {
                if (originalSrc === '#') {
                    statusGambarInfo.textContent = 'Belum ada gambar.';
                } else if (document.getElementById('gambar_action').value !== 'remove') {
                    statusGambarInfo.innerHTML = 'Saat Ini: <?= e($current_gambar_alat_db ?? '') ?>';
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        const imageField = document.getElementById("gambar_preview");
        const iconPlaceholder = document.getElementById("icon_placeholder");

        if (imageField && !imageField.dataset.originalSrc) imageField.dataset.originalSrc = imageField.src || '#';
        if (imageField && !imageField.dataset.placeholderImgDisplay) imageField.dataset.placeholderImgDisplay = imageField.style.display || 'none';
        if (iconPlaceholder && !imageField.dataset.placeholderIconDisplay) imageField.dataset.placeholderIconDisplay = iconPlaceholder.style.display || 'block';

        if (gambarActionSelect) {
            toggleNewImageUploadAlat(gambarActionSelect.value);
        }

        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        var satuanDurasiSelectEdit = document.getElementById('satuan_durasi_harga');
        var durasiHargaInputEdit = document.getElementById('durasi_harga_sewa');

        function toggleDurasiInputEdit() {
            if (satuanDurasiSelectEdit.value === 'Peminjaman') {
                durasiHargaInputEdit.value = '1';
                durasiHargaInputEdit.readOnly = true;
                durasiHargaInputEdit.required = false;
            } else {
                durasiHargaInputEdit.readOnly = false;
                durasiHargaInputEdit.required = true;
            }
        }

        if (satuanDurasiSelectEdit && durasiHargaInputEdit) {
            satuanDurasiSelectEdit.addEventListener('change', toggleDurasiInputEdit);
            toggleDurasiInputEdit();
        }
    });
</script>