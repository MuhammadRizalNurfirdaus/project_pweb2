<?php
require_once '../../config/Koneksi.php';
include '../template/header_admin.php';

// DESCRIBE output is for 'contacts' (plural). Assuming 'created_at' column exists.
$query = "SELECT id, nama, email, pesan, created_at FROM contacts ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<div class="container mt-5">
    <h3>Kelola Pesan Kontak</h3>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Pesan</th>
                <th>Tanggal Diterima</th>
                <!-- Add Aksi column if needed, e.g., for deletion -->
            </tr>
        </thead>
        <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['pesan'])) ?></td>
                        <td><?= htmlspecialchars(date('d M Y H:i', strtotime($row['created_at']))) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5" class="text-center">Belum ada pesan kontak.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once '../../template/footer.php'; ?>