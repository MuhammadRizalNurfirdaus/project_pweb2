<?php
require_once '../../config/Koneksi.php';
include '../template/header_admin.php';


$query = "SELECT * FROM contact ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="container mt-5">
    <h3>Kelola Pesan Kontak</h3>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Pesan</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['pesan'])) ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../template/footer.php'; ?>