<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');

require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

if (isset($_POST['id'], $_POST['nama'], $_POST['harga'], $_POST['lokasi'], $_POST['deskripsi'], $_POST['gambar'])) {
    $wisata_id = $_POST['id'];
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $gambar = $_POST['gambar'];

    $stmt = $conn->prepare("UPDATE wisata SET nama=?, harga=?, lokasi=?, deskripsi=?, gambar=? WHERE id=?");
    $stmt->bind_param("sisssi", $nama, $harga, $lokasi, $deskripsi, $gambar, $wisata_id);
    if ($stmt->execute()) {
        echo "Wisata berhasil diperbarui! <a href='kelola_wisata.php'>Kembali ke Kelola Wisata</a>";
    } else {
        echo "Gagal memperbarui wisata. <a href='edit_wisata.php?id=$wisata_id'>Coba lagi</a>";
    }
}
