<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();
?>
<?php $title = 'Kelola Galeri';
include '../template/header.php'; ?>
<h2>Kelola Galeri Foto</h2>
<a href="tambah_foto.php">Tambah Foto</a>
<table>
    <tr>
        <th>Wisata</th>
        <th>Nama File</th>
        <th>Aksi</th>
    </tr>
    <?php
    $stmt = $conn->prepare(
        "SELECT g.id, w.nama AS wisata, g.nama_file 
   FROM galeri g 
   JOIN wisata w ON g.wisata_id=w.id"
    );
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$row['wisata']}</td>
            <td>{$row['nama_file']}</td>
            <td>
              <a href='hapus_foto.php?id={$row['id']}'>Hapus</a>
            </td>
          </tr>";
    }
    ?>
</table>
<?php include '../template/footer.php'; ?>