<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\tambah_artikel.php

// 1. Include Config (memuat $conn, helpers termasuk is_post, input, display_flash_message, e, redirect)
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/artikel/tambah_artikel.php");
    exit("Kesalahan konfigurasi server. Tidak dapat memuat file penting.");
}

// 2. Panggil fungsi otentikasi admin
require_admin();

// 3. Sertakan Model Artikel
// Diasumsikan config.php sudah memuat Artikel.php dan memanggil Artikel::init()
if (!class_exists('Artikel')) {
    $artikelModelPath = MODELS_PATH . '/Artikel.php';
    if (file_exists($artikelModelPath)) {
        require_once $artikelModelPath;
        // Jika Artikel::init() belum dipanggil di config.php, dan $conn tersedia (kurang ideal di sini):
        // if (isset($conn) && $conn instanceof mysqli && method_exists('Artikel', 'init') && defined('UPLOADS_ARTIKEL_PATH')) {
        //     Artikel::init($conn, UPLOADS_ARTIKEL_PATH);
        // }
    } else {
        error_log("FATAL ERROR di tambah_artikel.php: Model Artikel.php tidak ditemukan di " . $artikelModelPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen data artikel tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php'); // Redirect jika model inti gagal dimuat
        exit;
    }
}


// LANGKAH BARU: Proses POST Request SEBELUM output HTML apapun (SEBELUM HEADER)
$judul_input_val = ''; // Untuk repopulasi
$isi_input_val = '';   // Untuk repopulasi
$session_form_data_key = 'flash_form_data_artikel';

if (is_post()) { // Menggunakan fungsi helper is_post()
    // Validasi CSRF Token
    if (!function_exists('verify_csrf_token') || !verify_csrf_token(null, true)) { // Unset token setelah verifikasi
        set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa.');
        redirect(ADMIN_URL . '/artikel/tambah_artikel.php'); // Redirect kembali ke form tambah
        exit;
    }

    $judul_from_post = input('judul', '', 'post');
    $isi_from_post = input('isi', '', 'post'); // Untuk WYSIWYG, jangan strip tags di sini
    $gambar_filename = null;

    // Simpan input ke session untuk repopulasi JIKA ada error di bawah
    $_SESSION[$session_form_data_key] = [
        'judul' => $judul_from_post,
        'isi' => $isi_from_post, // Simpan mentah jika WYSIWYG
    ];

    $upload_error = false; // Flag untuk error upload

    // --- Handle File Upload ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        if (!defined('UPLOADS_ARTIKEL_PATH')) {
            set_flash_message('danger', 'Konfigurasi direktori unggah artikel tidak ditemukan.');
            $upload_error = true;
        } else {
            $target_dir_upload = UPLOADS_ARTIKEL_PATH . DIRECTORY_SEPARATOR;

            if (!is_dir($target_dir_upload)) {
                if (!@mkdir($target_dir_upload, 0775, true) && !is_dir($target_dir_upload)) {
                    set_flash_message('danger', 'Gagal membuat direktori unggah. Periksa izin folder.');
                    $upload_error = true;
                }
            }

            if (!$upload_error && is_writable($target_dir_upload)) {
                $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                $gambar_filename = "artikel_" . uniqid() . '_' . time() . '.' . $imageFileType;
                $target_file_upload = $target_dir_upload . $gambar_filename;
                $uploadOk = 1;

                $check = @getimagesize($_FILES["gambar"]["tmp_name"]);
                if ($check === false) {
                    set_flash_message('danger', 'File bukan format gambar yang valid.');
                    $uploadOk = 0;
                }
                if ($_FILES["gambar"]["size"] > 3097152) {
                    set_flash_message('danger', 'Ukuran file gambar maksimal 3MB.');
                    $uploadOk = 0;
                } // 3MB
                $allowed_formats = ["jpg", "png", "jpeg", "gif", "webp"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    set_flash_message('danger', 'Hanya format JPG, JPEG, PNG, GIF, WEBP yang diizinkan.');
                    $uploadOk = 0;
                }

                if ($uploadOk == 1) {
                    if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file_upload)) {
                        set_flash_message('danger', 'Gagal mengunggah file gambar. Pastikan folder writable.');
                        error_log("Gagal move_uploaded_file ke: " . $target_file_upload);
                        $gambar_filename = null; // Reset jika gagal upload
                        $upload_error = true;
                    }
                } else {
                    $gambar_filename = null; // Reset jika validasi file gagal
                    $upload_error = true;
                }
            } elseif (!$upload_error) {
                set_flash_message('danger', 'Direktori unggah artikel tidak dapat ditulis.');
                error_log("Direktori unggah artikel tidak writable: " . $target_dir_upload);
                $upload_error = true;
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] != UPLOAD_ERR_OK) {
        set_flash_message('danger', 'Terjadi kesalahan saat mengunggah gambar. Kode Error: ' . $_FILES['gambar']['error']);
        $upload_error = true;
    }
    // --- End File Upload Handling ---

    // Hanya lanjutkan ke penyimpanan DB jika tidak ada error upload dan validasi dasar terpenuhi
    if (!$upload_error) {
        if (empty($judul_from_post) || empty($isi_from_post)) { // Validasi judul dan isi
            set_flash_message('danger', 'Judul dan Isi artikel wajib diisi.');
        } else {
            if (method_exists('Artikel', 'create')) {
                $data_to_save = [
                    'judul' => $judul_from_post,
                    'isi' => $isi_from_post, // Isi bisa mengandung HTML jika pakai WYSIWYG
                    'gambar' => $gambar_filename // Bisa null jika tidak ada gambar atau gagal upload
                ];

                $new_artikel_id = Artikel::create($data_to_save);

                if ($new_artikel_id) {
                    unset($_SESSION[$session_form_data_key]); // Hapus data form dari session jika sukses
                    set_flash_message('success', 'Artikel baru berhasil ditambahkan!');
                    redirect(ADMIN_URL . '/artikel/kelola_artikel.php'); // Ini akan exit
                } else {
                    $db_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Tidak diketahui';
                    set_flash_message('danger', 'Gagal menambahkan artikel ke database. ' . $db_error);
                    // Hapus file gambar yang mungkin sudah terunggah jika penyimpanan DB gagal
                    if ($gambar_filename && isset($target_file_upload) && file_exists($target_file_upload)) {
                        @unlink($target_file_upload);
                        error_log("Rollback Tambah Artikel: Menghapus file gambar {$target_file_upload} karena gagal simpan DB.");
                    }
                }
            } else {
                set_flash_message('danger', 'Kesalahan sistem: Fungsi pembuatan artikel tidak tersedia.');
                error_log("FATAL ERROR: Metode Artikel::create() tidak ditemukan saat tambah artikel.");
            }
        }
    }
    // Jika ada pesan error (baik dari upload atau validasi), redirect kembali ke form tambah
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'danger') {
        redirect(ADMIN_URL . '/artikel/tambah_artikel.php'); // Ini akan exit
    }
} // End of is_post()


// Set judul halaman (bisa di-override sebelum header jika $pageTitle didefinisikan di atas)
$pageTitle = "Tambah Artikel Baru";

// Sertakan header admin (SEKARANG SETELAH BLOK POST)
require_once ROOT_PATH . '/template/header_admin.php';

// Ambil data dari session untuk repopulasi (jika ada redirect dari blok POST di atas karena error)
if (isset($_SESSION[$session_form_data_key])) {
    $input_nama = ''; // Tidak ada field 'nama' di form artikel
    $input_nama_lengkap = ''; // Tidak ada field 'nama_lengkap' di form artikel
    $input_judul = $_SESSION[$session_form_data_key]['judul'] ?? '';
    $input_isi = $_SESSION[$session_form_data_key]['isi'] ?? '';
    // Tidak perlu repopulasi file input
    unset($_SESSION[$session_form_data_key]);
} else {
    $input_judul = '';
    $input_isi = '';
}

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/dashboard.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL . '/artikel/kelola_artikel.php') ?>"><i class="fas fa-newspaper"></i> Kelola Artikel</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-plus-circle"></i> Tambah Artikel</li>
    </ol>
</nav>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Formulir Tambah Artikel Baru</h6>
    </div>
    <div class="card-body">
        <?php // display_flash_message() sudah dipanggil di header_admin.php 
        ?>
        <form action="<?= e(ADMIN_URL . '/artikel/tambah_artikel.php') ?>" method="post" enctype="multipart/form-data" novalidate>
            <?php if (function_exists('generate_csrf_token_input')) echo generate_csrf_token_input(); ?>
            <input type="hidden" name="action" value="tambah"> <!-- Meskipun tidak digunakan di proses_user.php, bisa berguna jika ada banyak aksi -->

            <div class="mb-3">
                <label for="judul" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= e($input_judul) ?>" required>
                <div class="invalid-feedback">Judul artikel wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Artikel <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="10" required><?= e($input_isi) // Escape di sini aman jika isi adalah plain text. Jika WYSIWYG, pertimbangkan. 
                                                                                        ?></textarea>
                <div class="invalid-feedback">Isi artikel wajib diisi.</div>
                <small class="form-text text-muted">Anda dapat menggunakan format HTML jika diperlukan.</small>
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Gambar Artikel <span class="text-muted">(Opsional, Maks 2MB)</span></label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/png, image/jpeg, image/gif, image/webp">
                <small class="form-text text-muted">Format yang diizinkan: JPG, JPEG, PNG, GIF, WEBP.</small>
            </div>
            <hr>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Simpan Artikel
                </button>
                <a href="<?= e(ADMIN_URL . '/artikel/kelola_artikel.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Sertakan Admin Footer
require_once ROOT_PATH . '/template/footer_admin.php';
?>