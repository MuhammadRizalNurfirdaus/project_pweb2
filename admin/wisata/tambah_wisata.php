<?php include '../template/header_admin.php';
?>

<div class="container mt-5">
    <h2>Tambah Wisata Baru</h2>
    <form action="proses_tambah_wisata.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Nama Wisata</label>
            <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label>Gambar</label>
            <input type="file" name="gambar" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>

<?php include "../../template/footer.php"; ?>