<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');

require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

if (isset($_POST['nama'], $_POST['harga'], $_POST['lokasi'], $_POST['deskripsi'], $_POST['gambar'])) {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $gambar = $_POST['gambar'];

    $stmt = $conn->prepare("INSERT INTO wisata (nama, harga, lokasi, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $nama, $harga, $lokasi, $deskripsi, $gambar);
    if ($stmt->execute()) {
        echo "Wisata berhasil ditambahkan! <a href='kelola_wisata.php'>Kembali ke Kelola Wisata</a>";
    } else {
        echo "Gagal menambah wisata. <a href='tambah_wisata.php'>Coba lagi</a>";
    }
}
