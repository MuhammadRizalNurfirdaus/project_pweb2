<?php
include "../../config/Koneksi.php";
include '../template/header_admin.php';


$query = mysqli_query($conn, "SELECT * FROM galeri");

echo "<div class='container mt-5'>";
echo "<h2>Kelola Galeri</h2>";
echo "<a href='tambah_foto.php' class='btn btn-success mb-3'>Tambah Foto</a>";
echo "<div class='row'>";
while ($data = mysqli_fetch_array($query)) {
    echo "<div class='col-md-3 mb-4'>
            <div class='card'>
                <img src='../../public/img/{$data['file']}' class='card-img-top' alt='{$data['nama']}'>
                <div class='card-body text-center'>
                    <p class='card-text'>{$data['nama']}</p>
                    <a href='hapus_foto.php?id={$data['id']}' class='btn btn-danger btn-sm'>Hapus</a>
                </div>
            </div>
          </div>";
}
echo "</div></div>";

include "../../template/footer.php";
