<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');

require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

if (isset($_GET['id'])) {
    $wisata_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM wisata WHERE id=?");
    $stmt->bind_param("i", $wisata_id);
    if ($stmt->execute()) {
        echo "Wisata berhasil dihapus! <a href='kelola_wisata.php'>Kembali ke Kelola Wisata</a>";
    } else {
        echo "Gagal menghapus wisata. <a href='kelola_wisata.php'>Coba lagi</a>";
    }
} else {
    echo "ID Wisata tidak ditemukan!";
}
