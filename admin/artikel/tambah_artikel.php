<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\artikel\tambah_artikel.php

// 1. Include Config and Model
require_once __DIR__ . '/../../config/config.php'; // Provides $conn, $base_url, and all helpers
require_once __DIR__ . '/../../models/Artikel.php';  // Load the Artikel Model

// 2. Include Admin Header (this should also call require_admin() from auth_helpers.php)
// require_admin() should be called within header_admin.php or right after including config.php
include_once __DIR__ . '/../template/header_admin.php';

// Initialize variables for form pre-filling and error messages
$judul_input = '';
$isi_input = '';
// Flash messages will be handled by display_flash_message() in header_admin.php or below

// 3. Process POST Request (if form submitted)
if (is_post()) { // Using helper from helpers.php
    $judul_input = input('judul'); // Using helper from helpers.php
    $isi_input = input('isi');     // Using helper
    $gambar_filename = null;       // Initialize gambar filename

    // --- Handle File Upload for 'gambar' ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $target_dir = __DIR__ . "/../../public/uploads/artikel/"; // Use absolute path for uploads
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true) && !is_dir($target_dir)) { // Check if mkdir succeeded
                set_flash_message('danger', 'Gagal membuat direktori unggah.');
                // No redirect here, let the form re-display with the error
            }
        }

        if (is_dir($target_dir) && is_writable($target_dir)) { // Proceed only if directory is fine
            $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
            $gambar_filename = "artikel_" . uniqid() . '.' . $imageFileType; // Unique filename
            $target_file = $target_dir . $gambar_filename;
            $uploadOk = 1;

            // Basic Image Validations
            $check = getimagesize($_FILES["gambar"]["tmp_name"]);
            if ($check === false) {
                set_flash_message('danger', 'File yang diunggah bukan gambar.');
                $uploadOk = 0;
            }
            if ($_FILES["gambar"]["size"] > 2000000) { // 2MB limit
                set_flash_message('danger', 'Maaf, ukuran file terlalu besar (maks 2MB).');
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($imageFileType, $allowed_formats)) {
                set_flash_message('danger', 'Maaf, hanya format JPG, JPEG, PNG & GIF yang diperbolehkan.');
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                    set_flash_message('danger', 'Maaf, terjadi error saat mengunggah file Anda.');
                    $gambar_filename = null; // Reset if upload failed
                }
            } else {
                $gambar_filename = null; // Reset if validation failed
            }
        } else {
            set_flash_message('danger', 'Direktori unggah tidak dapat ditulis atau tidak ada.');
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other specific upload errors if needed
        set_flash_message('danger', 'Terjadi kesalahan saat mengunggah gambar. Kode Error: ' . $_FILES['gambar']['error']);
    }
    // --- End File Upload Handling ---


    // Validate other inputs (only proceed if no critical upload error has set a flash message already)
    if (empty($_SESSION['flash_message'])) { // Check if an upload error already occurred
        if (empty($judul_input) || empty($isi_input)) {
            set_flash_message('danger', 'Judul dan Isi artikel wajib diisi.');
        } else {
            // For Artikel model (uses constructor injection for $conn)
            $artikel_model = new Artikel($conn); // Pass the $conn from config.php

            $artikel_model->judul = $judul_input;
            $artikel_model->isi = $isi_input;
            $artikel_model->gambar = $gambar_filename; // This will be null if upload failed or no file

            if ($artikel_model->create()) {
                set_flash_message('success', 'Artikel berhasil ditambahkan!');
                redirect('admin/artikel/kelola_artikel.php'); // Using helper from helpers.php
            } else {
                set_flash_message('danger', 'Gagal menambahkan artikel ke database.');
                // If DB insert failed but image was uploaded, attempt to delete uploaded image
                if ($gambar_filename && isset($target_file) && file_exists($target_file)) {
                    unlink($target_file);
                }
                // No redirect here, let the form re-display with values and error
            }
        }
    }
    // If there was an error, the script continues to display the form below,
    // and display_flash_message() (called in header or here) will show the error.
    // To ensure the form fields are re-populated after an error without redirecting:
    // The variables $judul_input, $isi_input are already set.
}
?>

<div class="container mt-4">
    <h2>Tambah Artikel Baru</h2>

    <?php
    // 4. Display Flash Message (if not already in header_admin.php)
    // If header_admin.php already calls display_flash_message(), you can remove this line.
    echo display_flash_message();
    ?>

    <form action="<?= $base_url ?>admin/artikel/tambah_artikel.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="judul" class="form-label">Judul Artikel</label>
            <input type="text" class="form-control" id="judul" name="judul" value="<?= e($judul_input) ?>" required>
        </div>
        <div class="mb-3">
            <label for="isi" class="form-label">Isi Artikel</label>
            <textarea class="form-control" id="isi" name="isi" rows="10" required><?= e($isi_input) ?></textarea>
            <!-- Consider using a WYSIWYG editor like CKEditor here for rich text -->
        </div>
        <div class="mb-3">
            <label for="gambar" class="form-label">Gambar Artikel (Opsional)</label>
            <input type="file" class="form-control" id="gambar" name="gambar" accept="image/png, image/jpeg, image/gif">
            <small class="form-text text-muted">Ukuran maks: 2MB. Format: JPG, PNG, GIF.</small>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Artikel
        </button>
        <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Batal
        </a>
    </form>
</div>

<?php
// 5. Include Admin Footer
include_once __DIR__ . '/../template/footer_admin.php';
?>