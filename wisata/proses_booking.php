<?php
session_start();
if ($_SESSION['role'] != 'user') header('Location: ../auth/login.php');

require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

if (isset($_POST['wisata_id'], $_POST['jumlah'], $_POST['tanggal'])) {
    $wisata_id = $_POST['wisata_id'];
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO booking (user_id, wisata_id, jumlah_orang, tanggal_booking) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $wisata_id, $jumlah, $tanggal);
    if ($stmt->execute()) {
        echo "Booking berhasil! <a href='booking_saya.php'>Lihat booking saya</a>";
    } else {
        echo "Gagal melakukan booking. <a href='javascript:history.back();'>Coba lagi</a>";
    }
} else {
    echo "Data tidak lengkap. <a href='javascript:history.back();'>Coba lagi</a>";
}
