<?php
include "../../config/Koneksi.php";
include '../template/header_admin.php';

// DESCRIBE galeri has: id, nama_file, keterangan.
// Assuming 'created_at' for ordering if it exists, otherwise use 'id'.
// Let's assume no created_at for now based on DESCRIBE.
$query = mysqli_query($conn, "SELECT id, nama_file, keterangan FROM galeri ORDER BY id DESC");

echo "<div class='container mt-5'>";
echo "<h2>Kelola Galeri</h2>";
echo "<a href='tambah_foto.php' class='btn btn-success mb-3'>Tambah Foto</a>";
echo "<div class='row'>";
if ($query && mysqli_num_rows($query) > 0) {
    while ($data = mysqli_fetch_assoc($query)) {
        echo "<div class='col-md-3 mb-4'>
                <div class='card'>
                    <img src='../../public/img/" . htmlspecialchars($data['nama_file']) . "' class='card-img-top' alt='" . htmlspecialchars($data['keterangan']) . "' style='height: 200px; object-fit: cover;'>
                    <div class='card-body text-center'>
                        <p class='card-text'>" . htmlspecialchars($data['keterangan']) . "</p>
                        <a href='edit_foto.php?id={$data['id']}' class='btn btn-warning btn-sm'>Edit</a>
                        <a href='hapus_foto.php?id={$data['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus foto ini?\")'>Hapus</a>
                    </div>
                </div>
              </div>";
    }
} else {
    echo "<div class='col-12'><p class='text-center'>Belum ada foto di galeri.</p></div>";
}
echo "</div></div>";

include "../../template/footer.php";
