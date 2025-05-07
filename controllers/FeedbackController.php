<?php
include_once './config/Koneksi.php';

class FeedbackController
{
    public static function kirim($data)
    {
        global $conn;
        $nama = mysqli_real_escape_string($conn, $data['nama']);
        $email = mysqli_real_escape_string($conn, $data['email']);
        $rating = (int)$data['rating'];
        $komentar = mysqli_real_escape_string($conn, $data['komentar']);

        $query = "INSERT INTO feedback (nama, email, rating, komentar) 
                  VALUES ('$nama', '$email', $rating, '$komentar')";
        return mysqli_query($conn, $query);
    }

    public static function getAll()
    {
        global $conn;
        return mysqli_query($conn, "SELECT * FROM feedback ORDER BY id DESC");
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM feedback WHERE id=$id");
    }
}
