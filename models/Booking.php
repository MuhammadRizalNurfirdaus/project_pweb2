<?php
require_once __DIR__ . '/../config/Koneksi.php';

class Booking
{
    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM booking ORDER BY tanggal DESC");
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public static function insert($data)
    {
        global $conn;
        $nama = $data['nama'];
        $email = $data['email'];
        $tanggal = $data['tanggal'];
        $jumlah_orang = $data['jumlah_orang'];
        $query = "INSERT INTO booking (nama, email, tanggal, jumlah_orang) VALUES ('$nama', '$email', '$tanggal', '$jumlah_orang')";
        return mysqli_query($conn, $query);
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM booking WHERE id = $id");
    }
}
