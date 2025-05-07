<?php
include_once './config/Koneksi.php';

class GaleriController
{
    public static function getAll()
    {
        global $conn;
        return mysqli_query($conn, "SELECT * FROM galeri ORDER BY id DESC");
    }

    public static function tambah($nama_file, $judul)
    {
        global $conn;
        $judul = mysqli_real_escape_string($conn, $judul);
        $query = "INSERT INTO galeri (gambar, judul) VALUES ('$nama_file', '$judul')";
        return mysqli_query($conn, $query);
    }

    public static function hapus($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM galeri WHERE id = $id");
    }
}
