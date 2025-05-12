<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\edit_alat.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/SewaAlatController.php';
// require_admin(); // Pastikan admin sudah login

$page_title = "Edit Alat Sewa";

// 1. Validasi ID dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    set_flash_message('danger', 'ID Alat Sewa tidak valid.');
    redirect('admin/alat_sewa/kelola_alat.php');
}
$id_alat = (int)$_GET['id'];

// 2. Ambil data alat yang akan diedit
$alat_sewa = SewaAlatController::getById($id_alat);

if (!$alat_sewa) {
    set_flash_message('danger', 'Data alat sewa tidak ditemukan.');
    redirect('admin/alat_sewa/kelola_alat.php');
}

include_once __DIR__ . '/../../template/header_admin.php';

// Ambil data form dari session untuk repopulasi jika ada error sebelumnya,
// ATAU dari data yang ada di database jika ini adalah load pertama.
$fs_nama_item = $_SESSION['form_data_alat_sewa_edit']['nama_item'] ?? $alat_sewa['nama_item'];
$fs_kategori_alat = $_SESSION['form_data_alat_sewa_edit']['kategori_alat'] ?? $alat_sewa['kategori_alat'];
$fs_deskripsi = $_SESSION['form_data_alat_sewa_edit']['deskripsi'] ?? $alat_sewa['deskripsi'];
$fs_harga_sewa = $_SESSION['form_data_alat_sewa_edit']['harga_sewa'] ?? $alat_sewa['harga_sewa'];
$fs_durasi_harga_sewa = $_SESSION['form_data_alat_sewa_edit']['durasi_harga_sewa'] ?? $alat_sewa['durasi_harga_sewa'];
$fs_satuan_durasi_harga = $_SESSION['form_data_alat_sewa_edit']['satuan_durasi_harga'] ?? $alat_sewa['satuan_durasi_harga'];
$fs_stok_tersedia = $_SESSION['form_data_alat_sewa_edit']['stok_tersedia'] ?? $alat_sewa['stok_tersedia'];
$fs_kondisi_alat = $_SESSION['form_data_alat_sewa_edit']['kondisi_alat'] ?? $alat_sewa['kondisi_alat'];
$current_gambar_alat = $alat_sewa['gambar_alat']; // Nama file gambar saat ini dari DB

unset($_SESSION['form_data_alat_sewa_edit']); // Hapus setelah digunakan
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/alat_sewa/kelola_alat.php"><i class="fas fa-tools"></i> Kelola Alat Sewa</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Alat: <?= e($alat_sewa['nama_item']) ?></li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark"> <!-- Warna header bisa disesuaikan -->
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Edit Alat Sewa</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>

        <form action="<?= e($base_url) ?>admin/alat_sewa/proses_edit_alat.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= e($id_alat) ?>">
            <input type="hidden" name="gambar_lama" value="<?= e($current_gambar_alat) ?>"> <!-- Kirim nama gambar lama -->

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="nama_item" class="form-label">Nama Item Alat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="nama_item" name="nama_item" value="<?= e($fs_nama_item) ?>" required>
                        <div class="invalid-feedback">Nama item alat wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label for="kategori_alat" class="form-label">Kategori Alat (Opsional)</label>
                        <input type="text" class="form-control" id="kategori_alat" name="kategori_alat" value="<?= e($fs_kategori_alat) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= e($fs_deskripsi) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 mb-3">
                            <label for="harga_sewa" class="form-label">Harga Sewa (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga_sewa" name="harga_sewa" value="<?= e($fs_harga_sewa) ?>" required min="0">
                            <div class="invalid-feedback">Harga sewa wajib diisi dan tidak boleh negatif.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="durasi_harga_sewa" class="form-label">Durasi Harga <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="durasi_harga_sewa" name="durasi_harga_sewa" value="<?= e($fs_durasi_harga_sewa) ?>" required min="1">
                            <div class="invalid-feedback">Durasi untuk harga wajib diisi (minimal 1).</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="satuan_durasi_harga" class="form-label">Satuan Durasi <span class="text-danger">*</span></label>
                            <select class="form-select" id="satuan_durasi_harga" name="satuan_durasi_harga" required>
                                <option value="Hari" <?= ($fs_satuan_durasi_harga == 'Hari') ? 'selected' : '' ?>>Per Hari</option>
                                <option value="Jam" <?= ($fs_satuan_durasi_harga == 'Jam') ? 'selected' : '' ?>>Per Jam</option>
                                <option value="Peminjaman" <?= ($fs_satuan_durasi_harga == 'Peminjaman') ? 'selected' : '' ?>>Per Peminjaman (Flat)</option>
                            </select>
                            <div class="invalid-feedback">Pilih satuan durasi.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="stok_tersedia" class="form-label">Stok Tersedia <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok_tersedia" name="stok_tersedia" value="<?= e($fs_stok_tersedia) ?>" required min="0">
                            <div class="invalid-feedback">Stok wajib diisi dan tidak boleh negatif.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kondisi_alat" class="form-label">Kondisi Alat</label>
                            <select class="form-select" id="kondisi_alat" name="kondisi_alat">
                                <option value="Baik" <?= ($fs_kondisi_alat == 'Baik') ? 'selected' : '' ?>>Baik</option>
                                <option value="Rusak Ringan" <?= ($fs_kondisi_alat == 'Rusak Ringan') ? 'selected' : '' ?>>Rusak Ringan</option>
                                <option value="Perlu Perbaikan" <?= ($fs_kondisi_alat == 'Perlu Perbaikan') ? 'selected' : '' ?>>Perlu Perbaikan</option>
                                <option value="Hilang" <?= ($fs_kondisi_alat == 'Hilang') ? 'selected' : '' ?>>Hilang</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Gambar Alat</div>
                        <div class="card-body text-center">
                            <?php
                            $gambar_alat_url = '#';
                            $gambar_display_style = 'none';
                            $icon_display_style = 'block';
                            if (!empty($current_gambar_alat) && file_exists((defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH . '/' : __DIR__ . "/../../public/uploads/alat_sewa/") . $current_gambar_alat)) {
                                $gambar_alat_url = (isset($base_url) ? $base_url . "public/uploads/alat_sewa/" . e($current_gambar_alat) : '#') . '?t=' . time();
                                $gambar_display_style = 'block';
                                $icon_display_style = 'none';
                            }
                            ?>
                            <img id="gambar_preview" src="<?= $gambar_alat_url ?>" alt="Preview Gambar Alat" class="img-fluid img-thumbnail mb-2" style="max-height: 180px; object-fit: cover; display: <?= $gambar_display_style ?>;">
                            <i id="icon_placeholder" class="fas fa-camera fa-5x text-muted mb-2" style="display: <?= $icon_display_style ?>;"></i>
                            <?php if (!empty($current_gambar_alat) && $gambar_display_style === 'block'): ?>
                                <p class="small text-muted mb-1">Saat Ini: <?= e($current_gambar_alat) ?></p>
                            <?php elseif ($gambar_display_style === 'none'): ?>
                                <p class="text-muted small">Belum ada gambar.</p>
                            <?php endif; ?>

                            <label for="gambar_action" class="form-label mt-2">Tindakan untuk Gambar:</label>
                            <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUploadAlat(this.value)">
                                <option value="keep" selected>Pertahankan Gambar</option>
                                <?php if (!empty($current_gambar_alat)): ?>
                                    <option value="remove">Hapus Gambar Saat Ini</option>
                                <?php endif; ?>
                                <option value="change">Ganti dengan Gambar Baru</option>
                            </select>

                            <div id="new-image-upload-section-alat" style="display:none;" class="mt-2">
                                <label for="gambar_alat_baru" class="form-label">Pilih Gambar Baru:</label>
                                <input type="file" class="form-control form-control-sm" id="gambar_alat_baru" name="gambar_alat_baru" accept="image/png, image/jpeg, image/gif, image/webp" onchange="previewEditImage(event)">
                                <small class="form-text text-muted">Format: JPG, PNG, GIF, WEBP. Maks 2MB.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <a href="<?= e($base_url) ?>admin/alat_sewa/kelola_alat.php" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php
include_once __DIR__ . '/../../template/footer_admin.php';
?>

<script>
    // Script untuk menampilkan/menyembunyikan input file gambar baru
    function toggleNewImageUploadAlat(action) {
        const newImageSection = document.getElementById('new-image-upload-section-alat');
        const gambarBaruInput = document.getElementById('gambar_alat_baru');
        if (action === 'change') {
            newImageSection.style.display = 'block';
        } else {
            newImageSection.style.display = 'none';
            gambarBaruInput.value = ''; // Bersihkan pilihan file jika disembunyikan
            // Jika batal ganti, kembalikan preview ke gambar lama atau placeholder
            const currentGambarURL = '<?= !empty($current_gambar_alat) && file_exists((defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH . '/' : __DIR__ . "/../../public/uploads/alat_sewa/") . $current_gambar_alat) ? $base_url . "public/uploads/alat_sewa/" . e($current_gambar_alat) . "?t=" . time() : "#" ?>';
            const imageField = document.getElementById("gambar_preview");
            const iconPlaceholder = document.getElementById("icon_placeholder");
            if (currentGambarURL !== '#') {
                imageField.src = currentGambarURL;
                imageField.style.display = 'block';
                iconPlaceholder.style.display = 'none';
            } else {
                imageField.src = '#';
                imageField.style.display = 'none';
                iconPlaceholder.style.display = 'block';
            }
        }
    }

    // Panggil saat load untuk inisialisasi tampilan berdasarkan pilihan awal (keep)
    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        if (gambarActionSelect) {
            // Tidak perlu panggil toggle di sini karena defaultnya 'keep' dan section sudah display:none
            // toggleNewImageUploadAlat(gambarActionSelect.value); 
        }
    });

    // Script untuk preview gambar saat edit
    function previewEditImage(event) {
        var reader = new FileReader();
        var imageField = document.getElementById("gambar_preview");
        var iconPlaceholder = document.getElementById("icon_placeholder");

        reader.onload = function() {
            if (reader.readyState == 2) {
                imageField.src = reader.result;
                imageField.style.display = 'block';
                iconPlaceholder.style.display = 'none';
            }
        }

        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            // Jika user batal pilih file, jangan langsung kembalikan ke gambar lama,
            // biarkan gambar yang dipilih sebelumnya (jika ada) atau placeholder.
            // Logika di toggleNewImageUploadAlat('keep') akan menangani jika user kembali ke 'keep'
        }
    }

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
    })();
</script>