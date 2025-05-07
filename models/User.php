<?php
require_once __DIR__ . '/../config/Koneksi.php';

class User
{
    public static function register($data)
    {
        global $conn;
        $nama = $data['nama'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $no_hp = $data['no_hp'];
        $alamat = $data['alamat'];
        $query = "INSERT INTO users (nama, email, password, no_hp, alamat) VALUES ('$nama', '$email', '$password', '$no_hp', '$alamat')";
        return mysqli_query($conn, $query);
    }

    public static function login($email, $password)
    {
        global $conn;
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }
}
