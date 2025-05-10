<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/ArtikelController.php';
include_once __DIR__ . '/../template/header_admin.php'; // Handles admin session check

$error_message = '';
$success_message = '';
$artikel = null;
$current_gambar = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'ID Artikel tidak valid.'];
    header('Location: ' . $base_url . 'admin/artikel/kelola_artikel.php');
    exit();
}

$id = (int)$_GET['id'];
$artikel = ArtikelController::getById($id);

if (!$artikel) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Artikel tidak ditemukan.'];
    header('Location: ' . $base_url . 'admin/artikel/kelola_artikel.php');
    exit();
}

$judul = $artikel['judul'];
$isi = $artikel['isi'];
$current_gambar = $artikel['gambar'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $isi = trim($_POST['isi']);
    $gambar_new_name = null; // This will hold the new filename if uploaded, or "REMOVE", or remain null
    $gambar_action = isset($_POST['gambar_action']) ? $_POST['gambar_action'] : 'keep'; // 'keep', 'remove', 'change'


    if (empty($judul) || empty($isi)) {
        $error_message = "Judul dan Isi artikel wajib diisi.";
    } else {
        if ($gambar_action === 'remove') {
            $gambar_new_name = "REMOVE"; // Signal to controller to remove
        } elseif ($gambar_action === 'change' && isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar_baru']['name'])) {
            // Handle file upload for new image
            $target_dir = __DIR__ . "/../../public/uploads/artikel/";
            $imageFileType = strtolower(pathinfo($_FILES["gambar_baru"]["name"], PATHINFO_EXTENSION));
            $gambar_new_name = "artikel_" . uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $gambar_new_name;
            $uploadOk = 1;

            $check = getimagesize($_FILES["gambar_baru"]["tmp_name"]);
            if ($check === false) {
                $error_message = "File baru bukan gambar.";
                $uploadOk = 0;
            }
            if ($_FILES["gambar_baru"]["size"] > 2000000) {
                $error_message = "Ukuran file baru terlalu besar (maks 2MB).";
                $uploadOk = 0;
            }
            $allowed_formats = ["jpg", "png", "jpeg", "gif"];
            if (!in_array($imageFileType, $allowed_formats)) {
                $error_message = "Format file baru tidak diizinkan.";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["gambar_baru"]["tmp_name"], $target_file)) {
                    $error_message = "Gagal mengunggah file baru.";
                    $gambar_new_name = null;
                }
            } else {
                $gambar_new_name = null;
            }
        } elseif (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_message = "Terjadi error saat unggah file baru: " . $_FILES['gambar_baru']['error'];
        }


        if (empty($error_message)) {
            if (ArtikelController::update($id, $judul, $isi, $gambar_new_name, $current_gambar)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Artikel berhasil diperbarui!'];
                header('Location: ' . $base_url . 'admin/artikel/kelola_artikel.php');
                exit();
            } else {
                $error_message = "Gagal memperbarui artikel.";
                // If DB update fails but a new image was uploaded, attempt to delete it
                if ($gambar_new_name && $gambar_new_name !== "REMOVE" && isset($target_file) && file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        }
    }
}
?>

<div class="container mt-5">
    <h2>Edit Artikel: <?= htmlspecialchars($artikel['judul']) ?></h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form action="<?= $base_url ?>admin/artikel/edit_artikel.php?id=<?= $id ?>" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="judul" class="form-label">Judul Artikel</label>
            <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($judul) ?>" required>
        </div>
        <div class="mb-3">
            <label for="isi" class="form-label">Isi Artikel</label>
            <textarea class="form-control" id="isi" name="isi" rows="10" required><?= htmlspecialchars($isi) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Gambar Artikel Saat Ini:</label>
            <div>
                <?php if (!empty($current_gambar)): ?>
                    <img src="<?= $base_url ?>public/uploads/artikel/<?= htmlspecialchars($current_gambar) ?>" alt="Gambar Saat Ini" style="max-width: 200px; max-height: 200px; margin-bottom: 10px;">
                <?php else: ?>
                    <p class="text-muted">Tidak ada gambar saat ini.</p>
                <?php endif; ?>
            </div>

            <label for="gambar_action" class="form-label">Tindakan untuk Gambar:</label>
            <select name="gambar_action" id="gambar_action" class="form-select mb-2" onchange="toggleNewImageUpload(this.value)">
                <option value="keep" selected>Pertahankan Gambar Saat Ini</option>
                <option value="remove" <?= (empty($current_gambar)) ? 'disabled' : '' ?>>Hapus Gambar Saat Ini</option>
                <option value="change">Ganti dengan Gambar Baru</option>
            </select>

            <div id="new-image-upload-section" style="display:none;">
                <label for="gambar_baru" class="form-label">Pilih Gambar Baru:</label>
                <input type="file" class="form-control" id="gambar_baru" name="gambar_baru" accept="image/png, image/jpeg, image/gif">
                <small class="form-text text-muted">Ukuran maks: 2MB. Format: JPG, PNG, GIF.</small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
        <a href="<?= $base_url ?>admin/artikel/kelola_artikel.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Batal
        </a>
    </form>
</div>
<script>
    function toggleNewImageUpload(action) {
        const newImageSection = document.getElementById('new-image-upload-section');
        if (action === 'change') {
            newImageSection.style.display = 'block';
        } else {
            newImageSection.style.display = 'none';
        }
    }
    // Initialize on page load
    toggleNewImageUpload(document.getElementById('gambar_action').value);
</script>
<?php include_once __DIR__ . '/../template/footer_admin.php'; ?>