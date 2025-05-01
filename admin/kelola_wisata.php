<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');
?>
<?php $title = 'Kelola Wisata';
include '../template/header.php'; ?>
<h2>Kelola Wisata</h2>
<a href="tambah_wisata.php">Tambah Wisata</a>
<table>
    <tr>
        <th>Nama</th>
        <th>Harga</th>
        <th>Aksi</th>
    </tr>
    <?php
    require_once '../config/Koneksi.php';
    $conn = Koneksi::getInstance();
    $stmt = $conn->prepare("SELECT * FROM wisata");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        echo "<tr>
              <td>" . $row['nama'] . "</td>
              <td>" . $row['harga'] . "</td>
              <td>
                <a href='edit_wisata.php?id=" . $row['id'] . "'>Edit</a> |
                <a href='hapus_wisata.php?id=" . $row['id'] . "'>Hapus</a>
              </td>
            </tr>";
    }
    ?>
</table>
<?php include '../template/footer.php'; ?>