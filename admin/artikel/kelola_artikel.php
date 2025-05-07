<?php
include '../../config/Koneksi.php';
include '../template/header_admin.php';


$query = mysqli_query($conn, "SELECT * FROM artikel ORDER BY created_at DESC");
?>

<div class="container mt-5">
    <h2>Kelola Artikel</h2>
    <a href="tambah_artikel.php" class="btn btn-success mb-3">Tambah Artikel</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Judul</th>
                <th>Konten</th>
                <th>Rating</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($query)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['judul']) ?></td>
                    <td><?= htmlspecialchars(substr($row['konten'], 0, 100)) ?>...</td>
                    <td><?= $row['rating'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <a href="hapus_artikel.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                            onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../../template/footer.php'; ?>