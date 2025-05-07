<?php
require_once __DIR__ . '/../config/Koneksi.php';

class Artikel
{
    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM artikel ORDER BY id DESC");
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public static function insert($data)
    {
        global $conn;
        $judul = $data['judul'];
        $konten = $data['konten'];
        $query = "INSERT INTO artikel (judul, konten) VALUES ('$judul', '$konten')";
        return mysqli_query($conn, $query);
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM artikel WHERE id = $id");
    }
}
