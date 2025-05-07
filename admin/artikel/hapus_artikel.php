<?php
include '../../config/koneksi.php';

$id = (int) $_GET['id'];
mysqli_query($conn, "DELETE FROM artikel WHERE id = $id");

header('Location: kelola_artikel.php');
exit;
