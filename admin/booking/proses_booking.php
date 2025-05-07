<?php
include "../../config/Koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $no_hp = htmlspecialchars($_POST['no_hp']);
    $jumlah_tiket = intval($_POST['jumlah_tiket']);
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'];
    $catatan = htmlspecialchars($_POST['catatan']);

    if ($nama && $email && $no_hp && $jumlah_tiket && $tanggal_kunjungan) {
        $query = "INSERT INTO booking (nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan)
                  VALUES ('$nama', '$email', '$no_hp', $jumlah_tiket, '$tanggal_kunjungan', '$catatan')";

        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Booking berhasil!'); window.location.href='../../booking.php';</script>";
        } else {
            echo "<script>alert('Gagal melakukan booking.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Harap lengkapi semua data.'); window.history.back();</script>";
    }
} else {
    header("Location: ../../booking.php");
    exit;
}
