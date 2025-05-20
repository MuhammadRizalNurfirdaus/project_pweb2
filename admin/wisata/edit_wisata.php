<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\wisata\edit_wisata.php

// LANGKAH 1: Sertakan konfigurasi (HARUS PALING ATAS)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di edit_wisata.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// LANGKAH 2: Otentikasi Admin
if (function_exists('require_admin')) {
    require_admin();
} else {
    error_log("FATAL ERROR di edit_wisata.php: Fungsi require_admin() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Akses ditolak.');
        if (defined('AUTH_URL') && function_exists('redirect')) redirect(AUTH_URL . 'login.php');
        else exit('Akses ditolak.');
    }
}

// LANGKAH 3: Pastikan Controller dan Model Wisata ada
if (!class_exists('WisataController') || !method_exists('WisataController', 'getById') || !method_exists('WisataController', 'handleUpdateWisata')) {
    error_log("FATAL ERROR di edit_wisata.php: WisataController atau metode yang dibutuhkan tidak ditemukan.");
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data destinasi tidak dapat dimuat.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'wisata/kelola_wisata.php');
    exit;
}
if (!class_exists('Wisata') || !method_exists('Wisata', 'findById') || !method_exists('Wisata', 'update')) {
    error_log("FATAL ERROR di edit_wisata.php: Model Wisata atau metode yang dibutuhkan tidak ditemukan.");
    // Tidak redirect agar error development terlihat, tapi controller check akan gagal jika model tidak siap
}


// LANGKAH 4: Validasi ID Wisata dari URL
$id_wisata = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($id_wisata <= 0) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Destinasi Wisata tidak valid atau tidak disertakan.');
    if (function_exists('redirect') && defined('ADMIN_URL')) redirect(ADMIN_URL . 'wisata/kelola_wisata.php');
    exit;
}

$session_form_data_key = 'flash_form_data_edit_wisata_' . $id_wisata;

// LANGKAH 5: Proses Form Jika Ada POST Request
if (is_post() && isset($_POST['submit_edit_wisata'])) {
    if (!function_exists('verify_csrf_token') || !verify_csrf_token('csrf_token', true)) {
        set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa saat update.');
        redirect(ADMIN_URL . 'wisata/edit_wisata.php?id=' . $id_wisata);
        exit;
    }

    $nama_form = trim(input('nama', '', 'POST'));
    $deskripsi_form = input('deskripsi', '', 'POST');
    $lokasi_form = trim(input('lokasi', '', 'POST'));
    $gambar_action_from_form = input('gambar_action', 'keep', 'POST'); // Ambil aksi gambar dari form
    $new_uploaded_filename_on_server = null; // Nama file baru yang berhasil diupload ke server
    $error_message_form = '';

    $wisata_data_for_old_image_check = WisataController::getById($id_wisata);
    if (!$wisata_data_for_old_image_check) {
        set_flash_message('danger', "Kesalahan: Data destinasi wisata dengan ID {$id_wisata} tidak ditemukan saat mencoba memproses update.");
        redirect(ADMIN_URL . 'wisata/kelola_wisata.php');
        exit;
    }
    $current_gambar_filename_db_on_post = $wisata_data_for_old_image_check['gambar'] ?? '';

    $_SESSION[$session_form_data_key] = ['nama' => $nama_form, 'deskripsi' => $deskripsi_form, 'lokasi' => $lokasi_form];

    if (empty($nama_form)) {
        $error_message_form = "Nama Destinasi wajib diisi.";
    } elseif (empty(trim($deskripsi_form))) {
        $error_message_form = "Deskripsi wajib diisi.";
    } else {
        $upload_dir_wisata = defined('UPLOADS_WISATA_PATH') ? rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : null;
        $target_file_upload_path = '';

        if ($gambar_action_from_form === 'remove') {
            $new_uploaded_filename_on_server = "REMOVE_IMAGE_FLAG"; // Signal untuk controller
        } elseif ($gambar_action_from_form === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {
            if (!$upload_dir_wisata || !is_writable($upload_dir_wisata)) {
                $error_message_form = "Konfigurasi direktori unggah wisata bermasalah.";
            } else {
                $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
                $new_uploaded_filename_temp = "wisata_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload_path = $upload_dir_wisata . $new_uploaded_filename_temp;
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
                    if (move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file_upload_path)) {
                        $new_uploaded_filename_on_server = $new_uploaded_filename_temp;
                    } else {
                        $error_message_form = "Gagal unggah file baru.";
                    }
                }
            }
        } elseif ($gambar_action_from_form === 'change' && (!isset($_FILES['gambar_baru']) || $_FILES['gambar_baru']['error'] == UPLOAD_ERR_NO_FILE || empty($_FILES['gambar_baru']['name']))) {
            $error_message_form = "Anda memilih untuk mengganti gambar, tetapi tidak ada file baru yang diunggah.";
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_OK) {
            $error_message_form = "Error unggah file: " . $_FILES['gambar_baru']['error'];
        }

        if (empty($error_message_form)) {
            // PERBAIKAN PEMANGGILAN CONTROLLER: Kirim satu array
            $data_to_controller = [
                'id' => $id_wisata,
                'nama' => $nama_form,
                'deskripsi' => $deskripsi_form,
                'lokasi' => $lokasi_form,
                'gambar_action_chosen' => $gambar_action_from_form, // Kirim aksi yang dipilih
                'new_uploaded_filename' => $new_uploaded_filename_on_server, // Hasil upload
                'current_db_filename' => $current_gambar_filename_db_on_post // Gambar lama dari DB
            ];

            $update_result = WisataController::handleUpdateWisata($data_to_controller);

            if ($update_result === true) {
                unset($_SESSION[$session_form_data_key]);
                set_flash_message('success', 'Data destinasi wisata "' . e($nama_form) . '" berhasil diperbarui!');
                redirect(ADMIN_URL . 'wisata/kelola_wisata.php');
                exit;
            } else {
                $error_from_controller = is_string($update_result) ? $update_result : "Gagal memperbarui destinasi wisata.";
                if ($error_from_controller === 'missing_nama') $error_message_form = "Nama destinasi wajib diisi.";
                elseif ($error_from_controller === 'missing_deskripsi') $error_message_form = "Deskripsi wajib diisi.";
                else $error_message_form = $error_from_controller;

                if ($gambar_action_from_form === 'change' && $new_uploaded_filename_on_server && isset($target_file_upload_path) && file_exists($target_file_upload_path)) {
                    @unlink($target_file_upload_path);
                }
            }
        }
    }
    if (!empty($error_message_form)) {
        set_flash_message('danger', $error_message_form);
        redirect(ADMIN_URL . 'wisata/edit_wisata.php?id=' . $id_wisata);
        exit;
    }
}

$wisata_data_display = WisataController::getById($id_wisata);
if (!$wisata_data_display) {
    set_flash_message('danger', "Data destinasi wisata dengan ID {$id_wisata} tidak ditemukan.");
    redirect(ADMIN_URL . 'wisata/kelola_wisata.php');
    exit;
}

$nama_display = $_SESSION[$session_form_data_key]['nama'] ?? $wisata_data_display['nama'] ?? '';
$deskripsi_display = $_SESSION[$session_form_data_key]['deskripsi'] ?? $wisata_data_display['deskripsi'] ?? '';
$lokasi_display = $_SESSION[$session_form_data_key]['lokasi'] ?? $wisata_data_display['lokasi'] ?? '';
$current_gambar_filename_display = $wisata_data_display['gambar'] ?? null;
if (isset($_SESSION[$session_form_data_key])) unset($_SESSION[$session_form_data_key]);

$pageTitle = "Edit Destinasi: " . e($nama_display ?: 'ID #' . $id_wisata);
require_once ROOT_PATH . '/template/header_admin.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . 'wisata/kelola_wisata.php') ?>"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-edit"></i> Edit Destinasi</li>
    </ol>
</nav>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0" style="color: var(--admin-text-primary);">Edit Destinasi: <?= e($nama_display ?: 'ID #' . $id_wisata) ?></h1>
    <a href="<?= e(ADMIN_URL . 'wisata/kelola_wisata.php') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Formulir Edit Destinasi Wisata</h6>
    </div>
    <div class="card-body">
        <form action="<?= e(ADMIN_URL . 'wisata/edit_wisata.php?id=' . $id_wisata) ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Destinasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="nama" name="nama" value="<?= e($nama_display) ?>" required>
                        <div class="invalid-feedback">Nama destinasi wajib diisi.</div>
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="10" required><?= e($deskripsi_display) ?></textarea>
                        <div class="invalid-feedback">Deskripsi wajib diisi.</div>
                    </div>
                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi/Alamat <span class="text-muted">(Opsional)</span></label>
                        <textarea class="form-control" id="lokasi" name="lokasi" rows="3"><?= e($lokasi_display) ?></textarea>
                        <small class="form-text text-muted">Contoh: Desa Cilengkrang, Kec. Pasaleman, Kab. Kuningan Jawa Barat 45552.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Gambar Destinasi</div>
                        <div class="card-body text-center">
                            <?php if (!empty($current_gambar_filename_display) && defined('UPLOADS_WISATA_PATH') && file_exists(UPLOADS_WISATA_PATH . DIRECTORY_SEPARATOR . $current_gambar_filename_display)): ?>
                                <img src="<?= e((defined('UPLOADS_WISATA_URL') ? UPLOADS_WISATA_URL : BASE_URL . 'public/uploads/wisata/') . $current_gambar_filename_display) ?>?t=<?= time() ?>"
                                    alt="Gambar Saat Ini: <?= e($nama_display) ?>" class="img-fluid img-thumbnail mb-2" style="max-height: 200px; object-fit: cover;">
                                <p class="small text-muted mb-1">Saat Ini: <?= e($current_gambar_filename_display) ?></p>
                            <?php elseif (!empty($current_gambar_filename_display)): ?>
                                <p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> File gambar "<?= e($current_gambar_filename_display) ?>" tidak ditemukan.</p>
                            <?php else: ?>
                                <i class="fas fa-image fa-5x text-muted mb-2"></i>
                                <p class="text-muted">Belum ada gambar.</p>
                            <?php endif; ?>

                            <label for="gambar_action" class="form-label mt-2">Tindakan untuk Gambar:</label>
                            <select name="gambar_action" id="gambar_action" class="form-select form-select-sm mb-2" onchange="toggleNewImageUploadWisata(this.value)">
                                <option value="keep" selected>Pertahankan Gambar</option>
                                <option value="change">Ganti dengan Gambar Baru</option>
                                <?php if (!empty($current_gambar_filename_display)): ?>
                                <?php endif; ?>
                            </select>
                            <div id="new-image-upload-section-wisata" style="display:none;" class="mt-2">
                                <label for="gambar_baru" class="form-label">Pilih Gambar Baru (Maks 5MB):</label>
                                <input type="file" class="form-control form-control-sm" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif, image/webp">
                                <small class="form-text text-muted">Format: JPG, PNG, GIF, WEBP.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end">
                <a href="<?= e(ADMIN_URL . 'wisata/kelola_wisata.php') ?>" class="btn btn-secondary me-2">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
                <button type="submit" name="submit_edit_wisata" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once ROOT_PATH . '/template/footer_admin.php';
?>

<script>
    function toggleNewImageUploadWisata(action) {
        const newImageSection = document.getElementById('new-image-upload-section-wisata');
        const gambarBaruInput = document.getElementById('gambar_baru');
        if (newImageSection && gambarBaruInput) {
            if (action === 'change') {
                newImageSection.style.display = 'block';
            } else {
                newImageSection.style.display = 'none';
                gambarBaruInput.value = '';
            }
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const gambarActionSelect = document.getElementById('gambar_action');
        if (gambarActionSelect) {
            toggleNewImageUploadWisata(gambarActionSelect.value);
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
    });
</script>