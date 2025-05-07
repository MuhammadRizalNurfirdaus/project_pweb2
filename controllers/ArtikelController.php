<?php
include_once './config/Koneksi.php';

class ArtikelController
{
    public static function tambah($judul, $konten)
    {
        global $conn;
        $judul = mysqli_real_escape_string($conn, $judul);
        $konten = mysqli_real_escape_string($conn, $konten);
        $query = "INSERT INTO artikel (judul, konten) VALUES ('$judul', '$konten')";
        return mysqli_query($conn, $query);
    }

    public static function getAll()
    {
        global $conn;
        return mysqli_query($conn, "SELECT * FROM artikel ORDER BY id DESC");
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM artikel WHERE id=$id");
    }
}
