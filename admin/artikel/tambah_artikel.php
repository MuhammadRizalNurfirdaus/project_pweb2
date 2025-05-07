<?php include '../template/header_admin.php';
?>

<div class="container mt-5">
    <h2>Tambah Artikel</h2>
    <form action="proses_tambah_artikel.php" method="post">
        <div class="mb-3">
            <label for="judul" class="form-label">Judul</label>
            <input type="text" class="form-control" name="judul" required>
        </div>
        <div class="mb-3">
            <label for="isi" class="form-label">Isi</label>
            <textarea class="form-control" name="isi" rows="5" required></textarea>
        </div>
        <div class="mb-3">
            <label for="penulis" class="form-label">Penulis</label>
            <input type="text" class="form-control" name="penulis">
        </div>
        <div class="mb-3">
            <label for="rating" class="form-label">Rating (1-5)</label>
            <select class="form-control" name="rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Tambah</button>
    </form>
</div>

<?php include_once '../../template/footer.php'; ?>