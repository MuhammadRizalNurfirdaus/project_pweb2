<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\galeri\edit_foto.php

// LANGKAH 1: Sertakan konfigurasi
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/galeri/edit_foto.php");
    exit("Kesalahan konfigurasi server.");
}

// LANGKAH 2: Otentikasi Admin
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di edit_foto.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// LANGKAH 3: Pastikan Controller dan Model Galeri ada
if (!class_exists('GaleriController') || !method_exists('GaleriController', 'getById') || !method_exists('GaleriController', 'handleUpdateFoto')) {
    error_log("FATAL ERROR di edit_foto.php: GaleriController atau metode yang dibutuhkan tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen galeri tidak dapat dimuat.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'galeri/kelola_galeri.php');
    exit;
}

// LANGKAH 4: Validasi ID Foto dari URL
$id_foto = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($id_foto <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'ID foto tidak valid atau tidak disediakan.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'galeri/kelola_galeri.php');
    exit;
}

// Variabel untuk form dan data
$current_nama_file_db = ''; // Akan diisi dari DB
$current_keterangan_db = ''; // Akan diisi dari DB
$session_form_data_key = 'flash_form_data_edit_foto_' . $id_foto;

// LANGKAH 5: Proses Form Jika Ada POST Request
if (is_post() && isset($_POST['submit_edit_foto'])) {
    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true)) {
        set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa.');
        redirect(ADMIN_URL . 'galeri/edit_foto.php?id=' . $id_foto);
        exit;
    }

    $keterangan_form_post = trim(input('keterangan', '', 'POST'));
    $gambar_action = input('gambar_action', 'keep', 'POST'); // 'keep', 'remove', 'change'
    $new_uploaded_filename = null; // Nama file baru yang berhasil diupload ke server
    $error_message_form = '';

    // Ambil nama file lama dari DB untuk perbandingan dan penghapusan jika diganti
    $foto_data_sebelum_update_post = GaleriController::getById($id_foto);
    if (!$foto_data_sebelum_update_post) {
        set_flash_message('danger', "Foto galeri dengan ID {$id_foto} tidak ditemukan lagi saat proses update.");
        redirect(ADMIN_URL . 'galeri/kelola_galeri.php');
        exit;
    }
    $current_nama_file_db_post = $foto_data_sebelum_update_post['nama_file'] ?? '';

    $_SESSION[$session_form_data_key] = ['keterangan' => $keterangan_form_post];

    // Validasi Keterangan (jika wajib)
    if (empty($keterangan_form_post)) {
        $error_message_form = "Keterangan foto wajib diisi.";
    }

    // Logika penanganan upload gambar baru atau penghapusan gambar
    if (empty($error_message_form)) {
        if ($gambar_action === 'remove') {
            $new_uploaded_filename = "REMOVE_IMAGE_FLAG"; // Signal untuk controller
        } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {
            $upload_path_galeri = defined('UPLOADS_GALERI_PATH') ? rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : null;
            if (!$upload_path_galeri || !is_writable($upload_path_galeri)) {
                $error_message_form = "Konfigurasi direktori unggah galeri bermasalah.";
            } else {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                $new_uploaded_filename_temp = "galeri_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload = $upload_path_galeri . $new_uploaded_filename_temp;
                $uploadOk = 1;
                $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
                if ($check === false) {
                    $error_message_form = "File baru bukan gambar.";
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_baru"]["size"] > 5 * 1024 * 1024) {
                    $error_message_form = "Ukuran file baru maks 5MB.";
                    $uploadOk = 0;
                }
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    $error_message_form = "Format file tidak diizinkan.";
                    $uploadOk = 0;
                }
                if ($uploadOk == 1) {
                    if (move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                        $new_uploaded_filename = $new_uploaded_filename_temp;
                    } else {
                        $error_message_form = "Gagal unggah file baru.";
                    }
                }
            }
        } elseif ($gambar_action === 'change' && (!isset($_FILES['gambar_baru']) || $_FILES['gambar_baru']['error'] == UPLOAD_ERR_NO_FILE || empty($_FILES['gambar_baru']['name']))) {
            $error_message_form = "Anda memilih ganti gambar, tapi tidak ada file baru diunggah.";
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message_form = "Error unggah file: " . $_FILES['gambar_baru']['error'];
        }
    }

    if (empty($error_message_form)) {
        $data_to_controller = [
            'id' => $id_foto,
            'keterangan' => $keterangan_form_post,
            'nama_file_baru_uploaded' => $new_uploaded_filename,
            'nama_file_lama_db' => $current_nama_file_db_post // Gunakan nama file dari DB sebelum potensi update
        ];
        $update_result = GaleriController::handleUpdateFoto($data_to_controller);
        if ($update_result === true) {
            unset($_SESSION[$session_form_data_key]);
            set_flash_message('success', 'Foto galeri berhasil diperbarui!');
            redirect(ADMIN_URL . 'galeri/kelola_galeri.php');
            exit;
        } else {
            $error_message_form = is_string($update_result) ? $update_result : "Gagal memperbarui foto galeri.";
            if ($gambar_action === 'change' && $new_uploaded_filename && isset($target_file_upload) && file_exists($target_file_upload)) {
                @unlink($target_file_upload);
            }
        }
    }

    if (!empty($error_message_form)) {
        set_flash_message('danger', $error_message_form);
        redirect(ADMIN_URL . 'galeri/edit_foto.php?id=' . $id_foto);
        exit;
    }
}

// LANGKAH 6: Ambil Data Terbaru untuk Tampilan
$foto_galeri_data_display = GaleriController::getById($id_foto);
if (!$foto_galeri_data_display) {
    set_flash_message('danger', "Foto galeri dengan ID {$id_foto} tidak ditemukan.");
    redirect(ADMIN_URL . 'galeri/kelola_galeri.php');
    exit;
}
$keterangan_display = $_SESSION[$session_form_data_key]['keterangan'] ?? $foto_galeri_data_display['keterangan'] ?? '';
$current_gambar_filename_display = $foto_galeri_data_display['nama_file'] ?? '';
if (isset($_SESSION[$session_form_data_key])) unset($_SESSION[$session_form_data_key]);

// LANGKAH 7: Set Judul Halaman
$pageTitle = "Edit Foto: " . e($keterangan_display ?: ($current_gambar_filename_display ?: 'ID #' . $id_foto));

// LANGKAH 8: Sertakan Header Admin
$header_admin_path = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : (defined('ROOT_PATH') ? ROOT_PATH . '/template/header_admin.php' : null);
if (!$header_admin_path || !file_exists($header_admin_path)) {
    error_log("FATAL ERROR di edit_foto.php: Path header admin tidak valid.");
    exit("Error kritis: Komponen tampilan header tidak dapat dimuat.");
}
require_once $header_admin_path;
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'galeri/kelola_galeri.php') ?>"><i class="fas fa-images"></i> Kelola Galeri</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Foto</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Edit Foto: <?= e($keterangan_display ?: ($current_gambar_filename_display ?: 'ID #' . $id_foto)) ?></h1>
    <a href="<?= e(ADMIN_URL . 'galeri/kelola_galeri.php') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Formulir Edit Foto Galeri</h6>
    </div>
    <div class="card-body">
        <form action="<?= e(ADMIN_URL . 'galeri/edit_foto.php?id=' . $id_foto) ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan Foto <span class="text-danger">*</span></label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3" required><?= e($keterangan_display) ?></textarea>
                <div class="invalid-feedback">Keterangan foto wajib diisi.</div>
            </div>

            <div class="mb-4 border p-3 rounded">
                <p class="fw-bold mb-2">Gambar Foto</p> <?php // Label diubah 
                                                        ?>
                <?php if (!empty($current_gambar_filename_display)): ?>
                    <div class="mb-2">
                        <p class="mb-1 small text-muted">Gambar Saat Ini:</p>
                        <img src="<?= e((defined('UPLOADS_GALERI_URL') ? UPLOADS_GALERI_URL : BASE_URL . 'public/uploads/galeri/') . $current_gambar_filename_display) ?>"
                            alt="Foto saat ini: <?= e($keterangan_display) ?>"
                            class="img-thumbnail mb-2"
                            style="max-width: 300px; max-height: 250px; object-fit: contain; border: 1px solid var(--admin-border-color);">
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-2 fst-italic">Belum ada gambar untuk item galeri ini.</p>
                <?php endif; ?>

                <label for="gambar_action" class="form-label">Tindakan untuk Gambar:</label>
                <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUpload(this.value)" style="max-width: 300px;">
                    <option value="keep" selected>Pertahankan Gambar Saat Ini</option>
                    <option value="change">Ganti dengan Gambar Baru</option>
                    <?php if (!empty($current_gambar_filename_display)): ?>
                        <option value="remove">Hapus Gambar Saat Ini</option>
                    <?php endif; ?>
                </select>

                <div id="new-image-upload-section" style="display:none;" class="mt-2">
                    <label for="gambar_baru" class="form-label">Pilih File Gambar Baru (Maks 5MB):</label>
                    <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif, image/webp">
                    <small class="form-text text-muted">Format: JPG, PNG, JPEG, GIF, WEBP.</small>
                    <div class="invalid-feedback">Silakan pilih gambar baru jika Anda memilih untuk mengganti.</div>
                </div>
            </div>

            <hr>
            <div class="mt-3">
                <button type="submit" name="submit_edit_foto" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
                <a href="<?= e(ADMIN_URL . 'galeri/kelola_galeri.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$footer_admin_path_edit = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer_admin.php' : (defined('ROOT_PATH') ? ROOT_PATH . '/template/footer_admin.php' : null);
if (!$footer_admin_path_edit || !file_exists($footer_admin_path_edit)) {
    error_log("FATAL ERROR di edit_foto.php: Path footer admin tidak valid.");
} else {
    require_once $footer_admin_path_edit;
}
?>