<?php
include_once './config/Koneksi.php';

class BookingController
{
    public static function create($data)
    {
        global $conn;
        $nama = mysqli_real_escape_string($conn, $data['nama']);
        $email = mysqli_real_escape_string($conn, $data['email']);
        $tanggal = mysqli_real_escape_string($conn, $data['tanggal']);
        $jumlah = (int)$data['jumlah'];
        $pesan = mysqli_real_escape_string($conn, $data['pesan']);

        $query = "INSERT INTO bookings (nama, email, tanggal, jumlah, pesan) 
                  VALUES ('$nama', '$email', '$tanggal', $jumlah, '$pesan')";
        return mysqli_query($conn, $query);
    }

    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM bookings ORDER BY tanggal DESC");
        return $result;
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM bookings WHERE id=$id");
    }
}
