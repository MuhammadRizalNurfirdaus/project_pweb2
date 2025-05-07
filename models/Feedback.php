<?php
require_once __DIR__ . '/../config/Koneksi.php';

class Feedback
{
    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM feedback ORDER BY id DESC");
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public static function insert($data)
    {
        global $conn;
        $nama = $data['nama'];
        $rating = $data['rating'];
        $komentar = $data['komentar'];
        $query = "INSERT INTO feedback (nama, rating, komentar) VALUES ('$nama', '$rating', '$komentar')";
        return mysqli_query($conn, $query);
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM feedback WHERE id = $id");
    }
}
