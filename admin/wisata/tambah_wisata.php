<?php include '../template/header_admin.php'; ?>
<div class="container mt-5">
    <h2>Tambah Wisata Baru</h2>
    <form action="proses_tambah_wisata.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="nama" class="form-label">Nama Wisata</label>
            <input type="text" id="nama" name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5" required></textarea>
        </div>
        <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <input type="text" id="lokasi" name="lokasi" class="form-control">
        </div>
        <div class="mb-3">
            <label for="harga" class="form-label">Harga (Contoh: 50000)</label>
            <input type="number" id="harga" name="harga" class="form-control" min="0" step="1000">
        </div>
        <div class="mb-3">
            <label for="gambar" class="form-label">Gambar</label>
            <input type="file" id="gambar" name="gambar" class="form-control" accept="image/*">
            <small class="form-text text-muted">Kosongkan jika tidak ingin mengganti gambar.</small>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Wisata</button>
    </form>
</div>
<?php include "../../template/footer.php"; ?>