<?php
include "../../config/Koneksi.php";

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM wisata WHERE id='$id'");

header("Location: kelola_wisata.php");
