<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\edit_artikel.php

// LANGKAH 1: Sertakan konfigurasi (HARUS PALING ATAS)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di admin/artikel/edit_artikel.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan.");
}

// LANGKAH 2: Otentikasi Admin
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di edit_artikel.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// LANGKAH 3: Pastikan Controller dan Model Artikel ada
$model_artikel_ok = false;
if (class_exists('Artikel')) {
    if (method_exists('Artikel', 'findById') && method_exists('Artikel', 'update') && method_exists('Artikel', 'getLastError')) {
        $model_artikel_ok = true;
    } else {
        error_log("FATAL ERROR di edit_artikel.php: Metode penting di Model Artikel tidak ditemukan.");
    }
} else {
    error_log("FATAL ERROR di edit_artikel.php: Kelas Model Artikel tidak ditemukan.");
}

$controller_artikel_ok = false;
if (class_exists('ArtikelController')) {
    if (method_exists('ArtikelController', 'getById') && method_exists('ArtikelController', 'handleUpdateArtikel')) {
        $controller_artikel_ok = true;
    } else {
        error_log("FATAL ERROR di edit_artikel.php: Metode penting di ArtikelController tidak ditemukan.");
    }
} else {
    error_log("FATAL ERROR di edit_artikel.php: ArtikelController tidak ditemukan.");
}

if (!$model_artikel_ok || !$controller_artikel_ok) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data artikel tidak lengkap.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'dashboard.php');
    exit;
}

// LANGKAH 4: Validasi ID Artikel dari URL
$artikel_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($artikel_id <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Artikel tidak valid atau tidak disertakan.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'artikel/kelola_artikel.php');
    exit;
}

// Variabel untuk repopulasi form dan data artikel
$judul_display = '';
$isi_display = '';
$current_gambar_filename = '';
$artikel_data_display = null; // Akan diisi nanti
$session_form_data_key = 'flash_form_data_edit_artikel_' . $artikel_id;


// LANGKAH 5: Proses Form Jika Ada POST Request (Edit Artikel)
if (is_post() && isset($_POST['submit_edit_artikel'])) {
    $sesi_token_saat_post_edit = $_SESSION['csrf_token'] ?? 'TIDAK ADA DI SESI SAAT POST (EDIT)';
    $post_token_saat_post_edit = $_POST['csrf_token'] ?? 'TIDAK ADA DI POST (EDIT)';
    error_log("Edit Artikel - Proses POST. Sesi CSRF: {$sesi_token_saat_post_edit}, POST CSRF: {$post_token_saat_post_edit}");

    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true)) {
        set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa saat update.');
        error_log("Edit Artikel - Kegagalan Verifikasi CSRF. Sesi: {$sesi_token_saat_post_edit}, POST: {$post_token_saat_post_edit}");
        redirect(ADMIN_URL . 'artikel/edit_artikel.php?id=' . $artikel_id);
        exit;
    }
    error_log("Edit Artikel - CSRF Token berhasil diverifikasi saat POST.");

    $judul_form_post = trim(input('judul', '', 'POST'));
    $isi_form_post = input('isi', '', 'POST');
    $gambar_action = input('gambar_action', 'keep', 'POST');
    $gambar_new_name_to_save = null;

    $artikel_data_for_old_image = Artikel::findById($artikel_id);
    $old_gambar_to_check = $artikel_data_for_old_image['gambar'] ?? '';

    $_SESSION[$session_form_data_key] = ['judul' => $judul_form_post, 'isi' => $isi_form_post];
    $error_message_form = '';

    if (empty($judul_form_post) || empty(trim($isi_form_post))) {
        $error_message_form = "Judul dan Isi artikel wajib diisi.";
    } else {
        $upload_path_artikel = defined('UPLOADS_ARTIKEL_PATH') ? rtrim(UPLOADS_ARTIKEL_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : null;
        $target_file_upload = '';

        if ($gambar_action === 'remove') {
            $gambar_new_name_to_save = "REMOVE_IMAGE_FLAG";
        } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {
            if (!$upload_path_artikel || !is_writable($upload_path_artikel)) {
                $error_message_form = "Konfigurasi direktori unggah artikel bermasalah atau tidak dapat ditulis.";
                error_log("Error Upload Edit: UPLOADS_ARTIKEL_PATH tidak terdefinisi atau tidak writable. Path: " . ($upload_path_artikel ?: "Belum terdefinisi"));
            } else {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                $gambar_new_name_to_save = "artikel_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload = $upload_path_artikel . $gambar_new_name_to_save;
                $uploadOk = 1;
                $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
                if ($check === false) {
                    $error_message_form = "File baru bukan gambar.";
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_baru"]["size"] > 3 * 1024 * 1024) {
                    $error_message_form = "Ukuran file baru terlalu besar (maks 3MB).";
                    $uploadOk = 0;
                }
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    $error_message_form = "Format file baru tidak diizinkan (JPG, PNG, JPEG, GIF, WEBP).";
                    $uploadOk = 0;
                }
                if ($uploadOk == 1) {
                    if (!move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                        $error_message_form = "Gagal mengunggah file gambar baru. Periksa izin folder.";
                        error_log("Gagal move_uploaded_file ke: " . $target_file_upload . " Error PHP: " . ($_FILES['gambar_baru']['error'] ?? 'Tidak ada info error'));
                        $gambar_new_name_to_save = null;
                    }
                } else {
                    $gambar_new_name_to_save = null;
                }
            }
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_OK) {
            $error_message_form = "Terjadi kesalahan saat mengunggah file baru. Kode Error: " . $_FILES['gambar_baru']['error'];
        }

        if (empty($error_message_form)) {
            $data_to_update_controller = [
                'id' => $artikel_id,
                'judul' => $judul_form_post,
                'isi' => $isi_form_post,
                'gambar_baru_filename' => $gambar_new_name_to_save,
                'gambar_lama_filename' => $old_gambar_to_check
            ];
            $update_result = ArtikelController::handleUpdateArtikel($data_to_update_controller);
            if ($update_result === true) {
                unset($_SESSION[$session_form_data_key]);
                set_flash_message('success', 'Artikel berhasil diperbarui!');
                redirect(ADMIN_URL . 'artikel/kelola_artikel.php');
            } elseif (is_string($update_result)) {
                $error_message_form = $update_result;
            } else {
                $error_message_form = "Gagal memperbarui artikel." . (Artikel::getLastError() ? " Info DB: " . Artikel::getLastError() : "");
                if ($gambar_new_name_to_save && $gambar_new_name_to_save !== "REMOVE_IMAGE_FLAG" && !empty($target_file_upload) && file_exists($target_file_upload)) {
                    @unlink($target_file_upload);
                    error_log("Rollback Upload Edit: Menghapus gambar {$target_file_upload} karena update DB gagal.");
                }
            }
        }
    }
    if (!empty($error_message_form)) {
        set_flash_message('danger', $error_message_form);
        redirect(ADMIN_URL . 'artikel/edit_artikel.php?id=' . $artikel_id);
        exit;
    }
} // Akhir dari is_post()


// LANGKAH 6: Ambil Data Artikel untuk Tampilan (SETELAH BLOK POST)
$artikel_data_display = ArtikelController::getById($artikel_id);
if (!$artikel_data_display) {
    set_flash_message('danger', "Artikel dengan ID {$artikel_id} tidak ditemukan lagi.");
    redirect(ADMIN_URL . 'artikel/kelola_artikel.php');
    exit;
}

// Repopulasi form dari data sesi (jika ada error POST sebelumnya) atau dari database
$judul_display = $_SESSION[$session_form_data_key]['judul'] ?? $artikel_data_display['judul'] ?? '';
$isi_display = $_SESSION[$session_form_data_key]['isi'] ?? $artikel_data_display['isi'] ?? '';
$current_gambar_filename = $artikel_data_display['gambar'] ?? '';

if (isset($_SESSION[$session_form_data_key])) unset($_SESSION[$session_form_data_key]);

// LANGKAH 7: Set Judul Halaman spesifik (SEBELUM INCLUDE HEADER)
$pageTitle = "Edit Artikel: " . e($artikel_data_display['judul'] ?? 'Tidak Ditemukan');

// LANGKAH 8: Sertakan Header Admin (SEKARANG HANYA SEKALI DAN DI TEMPAT YANG BENAR)
$header_admin_path_final = defined('VIEWS_PATH') ? VIEWS_PATH . '/header_admin.php' : (defined('ROOT_PATH') ? ROOT_PATH . '/template/header_admin.php' : null);
if (!$header_admin_path_final || !file_exists($header_admin_path_final)) {
    error_log("FATAL ERROR di edit_artikel.php: Path header admin tidak valid. Path: " . ($header_admin_path_final ?? 'Tidak terdefinisi'));
    exit("Error kritis: Komponen tampilan header tidak dapat dimuat.");
}
require_once $header_admin_path_final;
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'artikel/kelola_artikel.php') ?>"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Artikel</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Edit Artikel: <?= e($artikel_data_display['judul'] ?? '') ?></h5>
        <a href="<?= e(ADMIN_URL . 'artikel/kelola_artikel.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <?php // display_flash_message() sudah dipanggil di header_admin.php 
        ?>
        <form action="<?= e(ADMIN_URL . 'artikel/edit_artikel.php?id=' . $artikel_id) ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT && isset($_SESSION['csrf_token'])): ?>
                <!-- Sesi CSRF saat form dibuat (Edit): <?= htmlspecialchars($_SESSION['csrf_token']) ?> -->
            <?php endif; ?>

            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= e($judul_display) ?>" required>
                <div class="invalid-feedback">Judul artikel wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= e($isi_display) ?></textarea>
                <div class="invalid-feedback">Isi artikel wajib diisi.</div>
            </div>

            <div class="mb-4 border p-3 rounded">
                <p class="fw-bold mb-2">Gambar Artikel</p>
                <?php if (!empty($current_gambar_filename)): ?>
                    <div class="mb-2">
                        <p class="mb-1 small text-muted">Gambar Saat Ini:</p>
                        <img src="<?= e((defined('UPLOADS_ARTIKEL_URL') ? UPLOADS_ARTIKEL_URL : BASE_URL . 'public/uploads/artikel/') . $current_gambar_filename) ?>"
                            alt="Gambar Artikel: <?= e($artikel_data_display['judul'] ?? '') ?>"
                            class="img-thumbnail mb-2"
                            style="max-width: 250px; max-height: 200px; object-fit: contain; border:1px solid #ddd;">
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-2 fst-italic">Belum ada gambar untuk artikel ini.</p>
                <?php endif; ?>

                <label for="gambar_action" class="form-label">Tindakan untuk Gambar:</label>
                <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUpload(this.value)" style="max-width: 300px;">
                    <option value="keep" selected>Pertahankan Gambar Saat Ini</option>
                    <?php if (!empty($current_gambar_filename)): ?>
                        <option value="remove">Hapus Gambar Saat Ini</option>
                    <?php endif; ?>
                    <option value="change">Ganti dengan Gambar Baru</option>
                </select>

                <div id="new-image-upload-section" style="display:none;" class="mt-3">
                    <label for="gambar_baru" class="form-label">Pilih Gambar Baru (Maks 3MB):</label>
                    <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif, image/webp">
                    <small class="form-text text-muted">Format: JPG, PNG, JPEG, GIF, WEBP.</small>
                    <div class="invalid-feedback">Silakan pilih gambar baru jika Anda memilih untuk mengganti.</div>
                </div>
            </div>

            <hr>
            <div class="mt-3">
                <button type="submit" name="submit_edit_artikel" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
                <a href="<?= e(ADMIN_URL . 'artikel/kelola_artikel.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>
<?php
$footer_path_edit = defined('VIEWS_PATH') ? VIEWS_PATH . '/footer_admin.php' : (defined('ROOT_PATH') ? ROOT_PATH . '/template/footer_admin.php' : null);
if (!$footer_path_edit || !file_exists($footer_path_edit)) {
    error_log("FATAL ERROR di edit_artikel.php: Path footer admin tidak valid. Path: " . ($footer_path_edit ?? 'Tidak terdefinisi'));
} else {
    require_once $footer_path_edit;
}
?>