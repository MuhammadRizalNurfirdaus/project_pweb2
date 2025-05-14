<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\edit_foto.php

// 1. Sertakan konfigurasi dan Controller/Model yang relevan
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/edit_foto.php");
    exit("Kesalahan konfigurasi server.");
}
require_admin(); // Pastikan hanya admin yang bisa akses

// Diasumsikan config.php sudah memuat GaleriController.php dan Galeri.php (Model)
// serta sudah memanggil Galeri::init($conn, UPLOADS_GALERI_PATH)
if (!class_exists('GaleriController') || !method_exists('GaleriController', 'getById') || !method_exists('GaleriController', 'update')) {
    error_log("FATAL ERROR di edit_foto.php: GaleriController atau metode yang dibutuhkan tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen galeri tidak dapat dimuat.');
    redirect(ADMIN_URL . '/galeri/kelola_galeri.php');
    exit;
}

// Variabel awal
$error_message = '';
$foto_galeri_data = null;
$id_foto = null;

// Validasi dan ambil ID foto dari URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_foto = (int)$_GET['id'];
    $foto_galeri_data = GaleriController::getById($id_foto);

    if (!$foto_galeri_data) {
        set_flash_message('danger', 'Foto dengan ID tersebut tidak ditemukan.');
        redirect(ADMIN_URL . '/galeri/kelola_galeri.php');
        // exit sudah ada di redirect()
    }
} else {
    set_flash_message('danger', 'ID foto tidak valid atau tidak disediakan.');
    redirect(ADMIN_URL . '/galeri/kelola_galeri.php');
    // exit sudah ada di redirect()
}

// Inisialisasi variabel form dari data yang ada atau dari flash session
$session_form_data_key = 'flash_form_data_edit_foto_' . $id_foto;
$keterangan_form = $_SESSION[$session_form_data_key]['keterangan'] ?? $foto_galeri_data['keterangan'] ?? '';
// Nama file gambar saat ini, tidak diambil dari session flash data gambar
$current_nama_file_db = $foto_galeri_data['nama_file'] ?? '';
unset($_SESSION[$session_form_data_key]);


// Proses form jika ada POST request
if (is_post() && $id_foto) { // Pastikan $id_foto valid dari GET
    // Validasi CSRF Token
    if (!function_exists('verify_csrf_token') || !verify_csrf_token(null, true)) {
        set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa.');
        redirect(ADMIN_URL . '/galeri/edit_foto.php?id=' . $id_foto);
        exit;
    }

    $keterangan_form_post = trim(input('keterangan', '', 'post'));
    $gambar_action = input('gambar_action', 'keep', 'post');
    $new_uploaded_filename = null; // Nama file yang baru diupload ke server
    $final_filename_for_db = $current_nama_file_db; // Defaultnya adalah gambar lama
    $old_image_to_delete_if_replaced = null; // File lama yang akan dihapus JIKA penggantian berhasil

    // Simpan input ke session untuk repopulasi jika ada error di bawah
    $_SESSION['flash_form_data_edit_foto_' . $id_foto] = ['keterangan' => $keterangan_form_post];

    // Cek apakah ada perubahan yang dilakukan
    $keterangan_changed = ($keterangan_form_post !== $foto_galeri_data['keterangan']);
    $gambar_action_chosen = ($gambar_action !== 'keep');
    $new_file_uploaded = (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name']));

    if (!$keterangan_changed && !$gambar_action_chosen && !$new_file_uploaded && $gambar_action === 'keep') {
        $error_message = "Tidak ada perubahan yang dilakukan. Setidaknya ubah keterangan atau pilih tindakan untuk gambar.";
    } else {
        // Logika penanganan upload gambar baru atau penghapusan gambar
        if ($gambar_action === 'remove' && !empty($current_nama_file_db)) {
            $final_filename_for_db = null; // Akan di-set NULL di DB
            $old_image_to_delete_if_replaced = $current_nama_file_db;
        } elseif ($gambar_action === 'change' && $new_file_uploaded) {
            $target_dir_upload = UPLOADS_GALERI_PATH . '/'; // Dari config.php
            if (!is_dir($target_dir_upload)) {
                if (!mkdir($target_dir_upload, 0775, true) && !is_dir($target_dir_upload)) {
                    $error_message = "Gagal membuat direktori unggah.";
                }
            }

            if (empty($error_message) && is_writable($target_dir_upload)) {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                $new_uploaded_filename_temp = "galeri_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload = $target_dir_upload . $new_uploaded_filename_temp;
                $uploadOk = 1;

                $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
                if ($check === false) {
                    $error_message = "File baru bukan gambar.";
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_baru"]["size"] > 5242880) {
                    $error_message = "Ukuran file baru terlalu besar (maks 5MB).";
                    $uploadOk = 0;
                } // 5MB
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    $error_message = "Format file baru tidak diizinkan (JPG, PNG, JPEG, GIF, WEBP).";
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    if (move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                        $final_filename_for_db = $new_uploaded_filename_temp; // Gunakan nama file baru untuk DB
                        if (!empty($current_nama_file_db)) {
                            $old_image_to_delete_if_replaced = $current_nama_file_db;
                        }
                    } else {
                        $error_message = "Gagal memindahkan file gambar baru.";
                    }
                }
            } elseif (empty($error_message)) {
                $error_message = "Direktori upload tidak writable.";
            }
        } elseif ($gambar_action === 'change' && !$new_file_uploaded) {
            $error_message = "Anda memilih untuk mengganti gambar, tetapi tidak ada file baru yang diunggah atau file tidak valid.";
        }

        if (empty($error_message)) {
            $update_result = GaleriController::update(
                $id_foto,
                $keterangan_form_post,
                $final_filename_for_db, // Ini adalah nama file yang akan disimpan ke DB (bisa null, baru, atau lama jika 'keep')
                $old_image_to_delete_if_replaced // Ini adalah file lama yang akan dihapus dari server jika ada penggantian/penghapusan
            );

            if ($update_result === true) {
                unset($_SESSION['flash_form_data_edit_foto_' . $id_foto]);
                set_flash_message('success', 'Foto galeri berhasil diperbarui!');
                redirect(ADMIN_URL . '/galeri/kelola_galeri.php');
            } else {
                // Jika Controller mengembalikan string error, gunakan itu
                $error_message_from_controller = is_string($update_result) ? $update_result : "Gagal memperbarui foto galeri di database.";
                $error_message = $error_message_from_controller . (method_exists('Galeri', 'getLastError') ? " DB Info: " . Galeri::getLastError() : "");

                // Rollback upload jika update DB gagal tapi file baru sudah terlanjur diupload
                if ($gambar_action === 'change' && $new_file_uploaded && isset($target_file_upload) && file_exists($target_file_upload) && $final_filename_for_db === $new_uploaded_filename_temp) {
                    @unlink($target_file_upload);
                    error_log("Rollback Upload: Menghapus gambar baru {$target_file_upload} karena update DB galeri gagal.");
                }
            }
        }
    }
    // Jika ada $error_message setelah proses POST, set flash message dan redirect kembali ke form edit
    if (!empty($error_message)) {
        set_flash_message('danger', $error_message);
        redirect(ADMIN_URL . '/galeri/edit_foto.php?id=' . $id_foto);
        exit;
    }
}

// Set judul halaman lagi jika ada perubahan pada $foto_galeri_data setelah POST (meskipun biasanya redirect)
$pageTitle = "Edit Foto Galeri: " . e($foto_galeri_data['keterangan'] ?: 'Tanpa Keterangan');

// Sertakan header admin (HANYA JIKA BELUM DIMUAT)
// Ini penting untuk dipanggil setelah semua potensi redirect agar flash message bekerja
if (!defined('HEADER_ADMIN_LOADED_EDIT_FOTO')) { // Gunakan define yang unik
    require_once ROOT_PATH . '/template/header_admin.php';
    define('HEADER_ADMIN_LOADED_EDIT_FOTO', true);
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/galeri/kelola_galeri.php') ?>"><i class="fas fa-images"></i> Kelola Galeri</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Foto</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Edit Foto: <?= e($foto_galeri_data['keterangan'] ?: ($current_nama_file_db ?: 'Tanpa Judul')) ?></h5>
        <a href="<?= e(ADMIN_URL . '/galeri/kelola_galeri.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <?php // display_flash_message() sudah dipanggil di header_admin.php 
        ?>
        <?php if (!empty($error_message) && !isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-danger" role="alert"><?= e($error_message) ?></div>
        <?php endif; ?>

        <?php if ($id_foto && $foto_galeri_data): ?>
            <form action="<?= e(ADMIN_URL . '/galeri/edit_foto.php?id=' . $id_foto) ?>" method="post" enctype="multipart/form-data" novalidate>
                <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Foto Saat Ini:</label>
                    <div>
                        <?php if (!empty($current_nama_file_db) && file_exists(UPLOADS_GALERI_PATH . '/' . $current_nama_file_db)): ?>
                            <img src="<?= e(BASE_URL . 'public/uploads/galeri/' . $current_nama_file_db) ?>"
                                alt="Foto saat ini: <?= e($keterangan_form) ?>"
                                class="img-thumbnail mb-2"
                                style="max-width: 300px; max-height: 250px; object-fit: contain; border: 1px solid var(--admin-border-color);">
                        <?php elseif (!empty($current_nama_file_db)): ?>
                            <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> File gambar "<?= e($current_nama_file_db) ?>" tidak ditemukan di server (<?= e(UPLOADS_GALERI_PATH) ?>).</p>
                        <?php else: ?>
                            <p class="text-muted fst-italic">Tidak ada gambar untuk item galeri ini.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan Foto</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= e($keterangan_form) ?></textarea>
                </div>

                <div class="mb-4 border p-3 rounded">
                    <label for="gambar_action" class="form-label fw-bold">Tindakan untuk Gambar:</label>
                    <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUpload(this.value)" style="max-width: 300px;">
                        <option value="keep" selected>Pertahankan Gambar Saat Ini</option>
                        <option value="change">Ganti dengan Gambar Baru</option>
                        <?php if (!empty($current_nama_file_db)): ?>
                            <option value="remove">Hapus Gambar Saat Ini</option>
                        <?php endif; ?>
                    </select>

                    <div id="new-image-upload-section" style="display:none;" class="mt-3">
                        <label for="gambar_baru" class="form-label">Pilih File Gambar Baru (Maks 5MB):</label>
                        <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif, image/webp">
                        <small class="form-text text-muted">Format: JPG, PNG, JPEG, GIF, WEBP.</small>
                    </div>
                </div>

                <hr>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                    <a href="<?= e(ADMIN_URL . '/galeri/kelola_galeri.php') ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Batal
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Data foto tidak dapat dimuat atau ID tidak valid.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleNewImageUpload(action) {
        const newImageSection = document.getElementById('new-image-upload-section');
        const gambarBaruInput = document.getElementById('gambar_baru');
        if (action === 'change') {
            newImageSection.style.display = 'block';
            // gambarBaruInput.required = true; // Dihapus, validasi di PHP lebih baik jika 'change' dipilih tapi file kosong
        } else {
            newImageSection.style.display = 'none';
            // gambarBaruInput.required = false;
            gambarBaruInput.value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        if (gambarActionSelect) {
            toggleNewImageUpload(gambarActionSelect.value);
        }
    });
</script>

<?php
$footer_admin_path = ROOT_PATH . '/template/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/galeri/edit_foto.php.");
} else {
    require_once $footer_admin_path;
}
?>