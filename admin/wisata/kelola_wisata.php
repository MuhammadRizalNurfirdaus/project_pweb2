<?php
include "../../config/Koneksi.php";
include '../template/header_admin.php';


$query = mysqli_query($conn, "SELECT * FROM wisata");

echo "<div class='container mt-5'>";
echo "<h2>Kelola Data Wisata</h2>";
echo "<a href='tambah_wisata.php' class='btn btn-success mb-3'>Tambah Wisata</a>";
echo "<table class='table table-bordered'>";
echo "<tr><th>ID</th><th>Nama</th><th>Deskripsi</th><th>Gambar</th><th>Aksi</th></tr>";

while ($data = mysqli_fetch_array($query)) {
  echo "<tr>
            <td>{$data['id']}</td>
            <td>{$data['nama']}</td>
            <td>{$data['deskripsi']}</td>
            <td><img src='../../public/img/{$data['gambar']}' width='100'></td>
            <td>
                <a href='edit_wisata.php?id={$data['id']}' class='btn btn-warning btn-sm'>Edit</a>
                <a href='hapus_wisata.php?id={$data['id']}' class='btn btn-danger btn-sm'>Hapus</a>
            </td>
          </tr>";
}
echo "</table></div>";

include "../../template/footer.php";
