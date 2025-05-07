<?php
include "../../config/Koneksi.php";

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM galeri WHERE id='$id'");

header("Location: kelola_galeri.php");
