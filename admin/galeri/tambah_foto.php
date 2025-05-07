<?php
include "../../template/header.php";
?>

<div class="container mt-5">
    <h2>Tambah Foto ke Galeri</h2>
    <form method="post" enctype="multipart/form-data" action="proses_tambah_foto.php">
        <div class="mb-3">
            <label for="nama" class="form-label">Nama Foto</label>
            <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="file" class="form-label">File Gambar</label>
            <input type="file" name="file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Tambah</button>
    </form>
</div>

<?php include "../../template/footer.php"; ?>