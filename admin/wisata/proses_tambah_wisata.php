<?php
include "../../config/Koneksi.php";

$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];
$gambar = $_FILES['gambar']['name'];
$tmp = $_FILES['gambar']['tmp_name'];

move_uploaded_file($tmp, "../../public/img/" . $gambar);

$query = "INSERT INTO wisata (nama, deskripsi, gambar) VALUES ('$nama', '$deskripsi', '$gambar')";
mysqli_query($conn, $query);

header("Location: kelola_wisata.php");
