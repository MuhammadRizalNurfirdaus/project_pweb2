<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\tambah_alat.php

require_once __DIR__ . '/../../config/config.php';
// require_admin(); // Pastikan admin sudah login

$page_title = "Tambah Alat Sewa Baru";
include_once __DIR__ . '/../../template/header_admin.php';

// Ambil data form dari session untuk repopulasi jika ada error sebelumnya
$fs_nama_item = $_SESSION['form_data_alat_sewa']['nama_item'] ?? '';
$fs_kategori_alat = $_SESSION['form_data_alat_sewa']['kategori_alat'] ?? '';
$fs_deskripsi = $_SESSION['form_data_alat_sewa']['deskripsi'] ?? '';
$fs_harga_sewa = $_SESSION['form_data_alat_sewa']['harga_sewa'] ?? '';
$fs_durasi_harga_sewa = $_SESSION['form_data_alat_sewa']['durasi_harga_sewa'] ?? 1;
$fs_satuan_durasi_harga = $_SESSION['form_data_alat_sewa']['satuan_durasi_harga'] ?? 'Hari';
$fs_stok_tersedia = $_SESSION['form_data_alat_sewa']['stok_tersedia'] ?? 0;
$fs_kondisi_alat = $_SESSION['form_data_alat_sewa']['kondisi_alat'] ?? 'Baik';

unset($_SESSION['form_data_alat_sewa']); // Hapus setelah digunakan
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e($base_url) ?>admin/alat_sewa/kelola_alat.php"><i class="fas fa-tools"></i> Kelola Alat Sewa</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Alat Sewa</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Form Tambah Alat Sewa Baru</h5>
    </div>
    <div class="card-body">
        <?php display_flash_message(); ?>

        <form action="<?= e($base_url) ?>admin/alat_sewa/proses_tambah_alat.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="nama_item" class="form-label">Nama Item Alat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="nama_item" name="nama_item" value="<?= e($fs_nama_item) ?>" placeholder="Contoh: Tenda Dome Kap. 4 Orang" required>
                        <div class="invalid-feedback">Nama item alat wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label for="kategori_alat" class="form-label">Kategori Alat (Opsional)</label>
                        <input type="text" class="form-control" id="kategori_alat" name="kategori_alat" value="<?= e($fs_kategori_alat) ?>" placeholder="Contoh: Peralatan Tidur, Peralatan Masak">
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" placeholder="Jelaskan detail dan spesifikasi alat..."><?= e($fs_deskripsi) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 mb-3">
                            <label for="harga_sewa" class="form-label">Harga Sewa (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga_sewa" name="harga_sewa" value="<?= e($fs_harga_sewa) ?>" placeholder="Contoh: 50000" required min="0">
                            <div class="invalid-feedback">Harga sewa wajib diisi dan tidak boleh negatif.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="durasi_harga_sewa" class="form-label">Durasi Harga <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="durasi_harga_sewa" name="durasi_harga_sewa" value="<?= e($fs_durasi_harga_sewa) ?>" required min="1" value="1">
                            <div class="invalid-feedback">Durasi untuk harga wajib diisi (minimal 1).</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="satuan_durasi_harga" class="form-label">Satuan Durasi <span class="text-danger">*</span></label>
                            <select class="form-select" id="satuan_durasi_harga" name="satuan_durasi_harga" required>
                                <option value="Hari" <?= ($fs_satuan_durasi_harga == 'Hari') ? 'selected' : '' ?>>Per Hari</option>
                                <option value="Jam" <?= ($fs_satuan_durasi_harga == 'Jam') ? 'selected' : '' ?>>Per Jam</option>
                                <option value="Peminjaman" <?= ($fs_satuan_durasi_harga == 'Peminjaman') ? 'selected' : '' ?>>Per Peminjaman (Flat)</option>
                            </select>
                            <div class="invalid-feedback">Pilih satuan durasi untuk harga.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="stok_tersedia" class="form-label">Stok Tersedia <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok_tersedia" name="stok_tersedia" value="<?= e($fs_stok_tersedia) ?>" placeholder="Jumlah unit yang tersedia" required min="0">
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
                        <div class="card-header">Gambar Alat (Opsional)</div>
                        <div class="card-body text-center">
                            <img id="gambar_preview" src="#" alt="Preview Gambar Alat" class="img-fluid img-thumbnail mb-2" style="max-height: 200px; object-fit: cover; display: none;">
                            <i id="icon_placeholder" class="fas fa-camera fa-5x text-muted mb-2"></i> <!-- Placeholder Ikon -->
                            <input type="file" class="form-control form-control-sm" id="gambar_alat" name="gambar_alat" accept="image/png, image/jpeg, image/gif, image/webp" onchange="previewImage(event)">
                            <small class="form-text text-muted">Format: JPG, PNG, GIF, WEBP. Maks 2MB.</small>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <a href="<?= e($base_url) ?>admin/alat_sewa/kelola_alat.php" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i>Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Alat Sewa</button>
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
    })();

    // Script untuk preview gambar sebelum upload
    function previewImage(event) {
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
            imageField.src = '#';
            imageField.style.display = 'none';
            iconPlaceholder.style.display = 'block';
        }
    }
</script>