<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');
?>
<?php $title = 'Tambah Wisata';
include '../template/header.php'; ?>
<h2>Tambah Wisata</h2>
<form action="proses_tambah_wisata.php" method="POST">
    <label for="nama">Nama Wisata:</label>
    <input type="text" name="nama" required><br>
    <label for="harga">Harga:</label>
    <input type="number" name="harga" required><br>
    <label for="lokasi">Lokasi:</label>
    <input type="text" name="lokasi" required><br>
    <label for="deskripsi">Deskripsi:</label>
    <textarea name="deskripsi" required></textarea><br>
    <label for="gambar">Gambar (URL atau path):</label>
    <input type="text" name="gambar" required><br>
    <button type="submit">Tambah Wisata</button>
</form>
<?php include '../template/footer.php'; ?>