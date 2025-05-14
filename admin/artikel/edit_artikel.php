<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\edit_artikel.php

// LANGKAH 1: Sertakan konfigurasi
// Ini akan memuat BASE_URL, ADMIN_URL, helpers, auth_helpers, flash_message, koneksi DB,
// dan idealnya juga memuat model serta memanggil setDbConnection/init untuk model.
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503); // Service Unavailable
    error_log("FATAL ERROR di admin/artikel/edit_artikel.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan.");
}

// LANGKAH 2: Otentikasi Admin
require_admin(); // Fungsi ini akan redirect jika bukan admin

// LANGKAH 3: Sertakan Controller dan Model Artikel jika belum dimuat oleh config
if (!class_exists('ArtikelController')) {
    $controllerPath = CONTROLLERS_PATH . '/ArtikelController.php';
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        error_log("FATAL ERROR di admin/artikel/edit_artikel.php: ArtikelController.php tidak ditemukan di " . $controllerPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen artikel inti tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}
// Model Artikel juga diasumsikan sudah dimuat oleh config atau controller,
// atau dimuat di sini jika perlu dan belum.
if (!class_exists('Artikel')) {
    $modelPath = MODELS_PATH . '/Artikel.php';
    if (file_exists($modelPath)) {
        require_once $modelPath;
    } else {
        error_log("FATAL ERROR di admin/artikel/edit_artikel.php: Model Artikel.php tidak ditemukan.");
        // Lanjutkan dengan hati-hati jika ArtikelController tidak secara eksplisit butuh kelas Artikel di sini
    }
}


// LANGKAH 4: Validasi ID Artikel dari URL dan Ambil Data Artikel
$artikel_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if ($artikel_id <= 0) {
    set_flash_message('danger', 'ID Artikel tidak valid atau tidak disertakan.');
    redirect(ADMIN_URL . '/artikel/kelola_artikel.php'); // Redirect menggunakan helper
    // exit sudah ada di redirect()
}

// Ambil data artikel yang akan diedit
$artikel_data = null;
if (method_exists('ArtikelController', 'getById')) {
    $artikel_data = ArtikelController::getById($artikel_id);
} else {
    error_log("Error di edit_artikel.php: Metode ArtikelController::getById() tidak ada.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi pengambilan data artikel tidak tersedia.');
    redirect(ADMIN_URL . '/artikel/kelola_artikel.php');
}

if (!$artikel_data) {
    set_flash_message('danger', "Artikel dengan ID {$artikel_id} tidak ditemukan.");
    redirect(ADMIN_URL . '/artikel/kelola_artikel.php');
}

// Inisialisasi variabel untuk form dari data yang ada atau dari flash session jika ada error sebelumnya
$session_form_data_key = 'flash_form_data_edit_artikel_' . $artikel_id;

$judul_form = $_SESSION[$session_form_data_key]['judul'] ?? $artikel_data['judul'] ?? '';
$isi_form = $_SESSION[$session_form_data_key]['isi'] ?? $artikel_data['isi'] ?? '';
// Nama file gambar saat ini, tidak diambil dari session flash data gambar
$current_gambar_filename = $artikel_data['gambar'] ?? '';

unset($_SESSION[$session_form_data_key]); // Hapus flash data setelah digunakan

// LANGKAH 5: Proses Form Jika Ada POST Request (Edit Artikel)
$error_message = ''; // Untuk error spesifik di halaman ini
$success_message = ''; // Jarang digunakan jika redirect setelah sukses

if (is_post()) {
    // Validasi CSRF Token
    if (!function_exists('verify_csrf_token') || !verify_csrf_token(null, true)) {
        set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa.');
        redirect(ADMIN_URL . '/artikel/edit_artikel.php?id=' . $artikel_id); // Kembali ke form edit
        exit;
    }

    $judul_form_post = trim(input('judul', '', 'post'));
    $isi_form_post = trim(input('isi', '', 'post')); // Isi bisa jadi mengandung HTML, jangan strip_tags di sini jika pakai WYSIWYG
    $gambar_action = input('gambar_action', 'keep', 'post');
    $gambar_new_name_to_save = null; // Akan diisi jika ada gambar baru yang valid
    $old_gambar_to_check = $current_gambar_filename; // Gambar lama sebelum update

    // Simpan input ke session untuk repopulasi jika ada error di bawah
    $_SESSION['flash_form_data_edit_artikel_' . $artikel_id] = [
        'judul' => $judul_form_post,
        'isi' => $isi_form_post
        // Tidak perlu simpan info file gambar ke session untuk repopulasi upload
    ];


    if (empty($judul_form_post) || empty($isi_form_post)) {
        $error_message = "Judul dan Isi artikel wajib diisi.";
    } else {
        // Logika penanganan upload gambar baru atau penghapusan gambar
        $upload_path_artikel = UPLOADS_ARTIKEL_PATH . '/'; // Pastikan UPLOADS_ARTIKEL_PATH dari config.php

        if ($gambar_action === 'remove') {
            $gambar_new_name_to_save = "REMOVE_IMAGE_FLAG"; // Signal khusus untuk controller
        } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {

            if (!is_dir($upload_path_artikel)) {
                if (!mkdir($upload_path_artikel, 0775, true)) {
                    $error_message = "Gagal membuat direktori upload. Hubungi administrator.";
                    error_log("Gagal membuat direktori: " . $upload_path_artikel);
                }
            }

            if (empty($error_message) && is_writable($upload_path_artikel)) {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                // Buat nama file yang lebih aman dan unik
                $gambar_new_name_to_save = "artikel_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload = $upload_path_artikel . $gambar_new_name_to_save;
                $uploadOk = 1;

                $check = @getimagesize($_FILES["gambar_baru"]["tmp_name"]);
                if ($check === false) {
                    $error_message = "File baru bukan gambar.";
                    $uploadOk = 0;
                }
                if ($_FILES["gambar_baru"]["size"] > 2097152) {
                    $error_message = "Ukuran file baru terlalu besar (maks 2MB).";
                    $uploadOk = 0;
                } // 2MB
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    $error_message = "Format file baru tidak diizinkan (JPG, PNG, JPEG, GIF, WEBP).";
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    if (!move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload)) {
                        $error_message = "Gagal mengunggah file gambar baru. Periksa izin folder.";
                        error_log("Gagal move_uploaded_file ke: " . $target_file_upload . " Error PHP: " . ($_FILES['gambar_baru']['error'] ?? 'Tidak ada info error'));
                        $gambar_new_name_to_save = null;
                    }
                } else {
                    $gambar_new_name_to_save = null;
                }
            } elseif (empty($error_message)) {
                $error_message = "Direktori upload tidak writable.";
                error_log("Direktori upload artikel tidak writable: " . $upload_path_artikel);
            }
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_OK) {
            $upload_errors = [ /* ... array kode error upload ... */];
            $error_message = "Terjadi kesalahan saat mengunggah file baru. " . ($upload_errors[$_FILES['gambar_baru']['error']] ?? "Kode Error: " . $_FILES['gambar_baru']['error']);
        }


        if (empty($error_message)) {
            $data_to_update = [
                'id' => $artikel_id,
                'judul' => $judul_form_post,
                'isi' => $isi_form_post, // Controller/Model akan menangani sanitasi lebih lanjut jika perlu
                'gambar_baru' => $gambar_new_name_to_save, // Bisa null, nama file, atau "REMOVE_IMAGE_FLAG"
                'gambar_lama' => $old_gambar_to_check
            ];

            if (method_exists('ArtikelController', 'handleUpdateArtikel')) { // Asumsi ada metode ini di Controller
                $update_result = ArtikelController::handleUpdateArtikel($data_to_update);
                if ($update_result === true) {
                    unset($_SESSION['flash_form_data_edit_artikel_' . $artikel_id]);
                    set_flash_message('success', 'Artikel berhasil diperbarui!');
                    redirect(ADMIN_URL . '/artikel/kelola_artikel.php');
                } elseif (is_string($update_result)) { // Jika controller return pesan error spesifik
                    $error_message = $update_result;
                } else { // Jika controller return false umum
                    $error_message = "Gagal memperbarui artikel di database." . (method_exists('Artikel', 'getLastError') ? " DB Info: " . Artikel::getLastError() : "");
                    // Rollback upload jika update DB gagal
                    if ($gambar_new_name_to_save && $gambar_new_name_to_save !== "REMOVE_IMAGE_FLAG" && isset($target_file_upload) && file_exists($target_file_upload)) {
                        @unlink($target_file_upload);
                        error_log("Rollback Upload: Menghapus gambar baru {$target_file_upload} karena update DB gagal.");
                    }
                }
            } else {
                $error_message = "Kesalahan sistem: Fungsi update artikel tidak tersedia.";
                error_log("FATAL ERROR: Metode ArtikelController::handleUpdateArtikel() tidak ditemukan.");
            }
        }
    }
    // Jika ada $error_message setelah proses POST, set flash message dan redirect kembali ke form edit
    if (!empty($error_message)) {
        set_flash_message('danger', $error_message);
        redirect(ADMIN_URL . '/artikel/edit_artikel.php?id=' . $artikel_id);
        exit;
    }
}


// LANGKAH 6: Set Judul Halaman (Mungkin perlu di-set ulang jika judul artikel berubah setelah POST gagal)
// Di sini, $pageTitle sudah diset sebelum blok POST, jadi akan menggunakan judul lama jika POST gagal.
// Jika ingin judul dinamis setelah POST gagal dan judul diubah, Anda perlu mengambil ulang $artikel_data atau set $pageTitle lagi.

// LANGKAH 7: Sertakan Header Admin
// Path yang BENAR dari admin/artikel/edit_artikel.php ke template/header_admin.php adalah '../../template/header_admin.php'
// $page_title sudah di-set di atas, di sini $pageTitle yang dipakai di tag <title>
if (!defined('HEADER_ADMIN_LOADED')) { // Mencegah include header dua kali jika ada kesalahan logika
    require_once ROOT_PATH . '/template/header_admin.php';
    define('HEADER_ADMIN_LOADED', true);
}

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/artikel/kelola_artikel.php') ?>"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Artikel</li>
    </ol>
</nav>

<div class="card shadow mb-4"> <!-- Menggunakan mb-4, bukan shadow-sm -->
    <div class="card-header py-3 d-flex justify-content-between align-items-center"> <!-- bg-light dihilangkan agar ikut tema -->
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Edit Artikel: <?= e($artikel_data['judul']) ?></h5>
        <a href="<?= e(ADMIN_URL . '/artikel/kelola_artikel.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <?php // display_flash_message() sudah dipanggil di header_admin.php 
        // Jika Anda memindahkannya ke sini, pastikan hanya dipanggil sekali.
        ?>
        <?php if (!empty($error_message) && !isset($_SESSION['flash_message'])): // Tampilkan error_message jika belum ada flash message (agar tidak duplikat) 
        ?>
            <div class="alert alert-danger" role="alert"><?= e($error_message) ?></div>
        <?php endif; ?>

        <form action="<?= e(ADMIN_URL . '/artikel/edit_artikel.php?id=' . $artikel_id) ?>" method="post" enctype="multipart/form-data" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= e($judul_form) ?>" required>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= e($isi_form) ?></textarea>
                <small class="form-text text-muted">Anda dapat menggunakan WYSIWYG editor jika diintegrasikan.</small>
            </div>

            <div class="mb-4 border p-3 rounded">
                <p class="fw-bold mb-2">Gambar Artikel</p>
                <?php if (!empty($current_gambar_filename)): ?>
                    <div class="mb-2">
                        <p class="mb-1 small text-muted">Gambar Saat Ini:</p>
                        <img src="<?= e(BASE_URL . 'public/uploads/artikel/' . $current_gambar_filename) ?>"
                            alt="Gambar Artikel: <?= e($artikel_data['judul']) ?>"
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
                    <label for="gambar_baru" class="form-label">Pilih Gambar Baru (Maks 2MB):</label>
                    <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif, image/webp">
                    <small class="form-text text-muted">Format: JPG, PNG, JPEG, GIF, WEBP.</small>
                </div>
            </div>

            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
                <a href="<?= e(ADMIN_URL . '/artikel/kelola_artikel.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleNewImageUpload(action) {
        const newImageSection = document.getElementById('new-image-upload-section');
        const gambarBaruInput = document.getElementById('gambar_baru');
        if (action === 'change') {
            newImageSection.style.display = 'block';
            gambarBaruInput.setAttribute('required', 'required');
        } else {
            newImageSection.style.display = 'none';
            gambarBaruInput.removeAttribute('required');
            gambarBaruInput.value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        if (gambarActionSelect) {
            toggleNewImageUpload(gambarActionSelect.value); // Inisialisasi saat halaman dimuat
        }
    });
</script>

<?php
$footer_admin_path = ROOT_PATH . '/template/footer_admin.php';
if (!file_exists($footer_admin_path)) {
    error_log("ERROR: Gagal memuat template/footer_admin.php dari admin/artikel/edit_artikel.php. Path: " . htmlspecialchars($footer_admin_path));
} else {
    require_once $footer_admin_path;
}
?>