<?php
include_once './config/Koneksi.php';

class ContactController
{
    public static function create($data)
    {
        global $conn;
        $nama = mysqli_real_escape_string($conn, $data['nama']);
        $email = mysqli_real_escape_string($conn, $data['email']);
        $pesan = mysqli_real_escape_string($conn, $data['pesan']);

        $query = "INSERT INTO contacts (nama, email, pesan) 
                  VALUES ('$nama', '$email', '$pesan')";
        return mysqli_query($conn, $query);
    }

    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM contacts ORDER BY id DESC");
        return $result;
    }

    public static function delete($id)
    {
        global $conn;
        return mysqli_query($conn, "DELETE FROM contacts WHERE id=$id");
    }
}
