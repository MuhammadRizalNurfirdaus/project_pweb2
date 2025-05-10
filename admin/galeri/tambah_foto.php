<?php include '../template/header_admin.php'; ?>
<div class="container mt-5">
    <h2>Tambah Foto ke Galeri</h2>
    <form method="post" enctype="multipart/form-data" action="proses_tambah_foto.php">
        <div class="mb-3">
            <label for="keterangan" class="form-label">Keterangan Foto</label>
            <input type="text" id="keterangan" name="keterangan" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="file" class="form-label">File Gambar</label>
            <input type="file" id="file" name="nama_file" class="form-control" required accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Tambah Foto</button>
    </form>
</div>
<?php include "../../template/footer.php"; ?>