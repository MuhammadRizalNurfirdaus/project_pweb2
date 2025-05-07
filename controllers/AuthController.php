<?php
include_once './config/Koneksi.php';

class AuthController
{
    public static function login($email, $password)
    {
        global $conn;
        $email = mysqli_real_escape_string($conn, $email);
        $password = mysqli_real_escape_string($conn, $password);

        $query = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public static function register($data)
    {
        global $conn;
        $nama = mysqli_real_escape_string($conn, $data['nama']);
        $email = mysqli_real_escape_string($conn, $data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $no_hp = mysqli_real_escape_string($conn, $data['no_hp']);
        $alamat = mysqli_real_escape_string($conn, $data['alamat']);

        $query = "INSERT INTO users (nama, email, password, no_hp, alamat) 
                  VALUES ('$nama', '$email', '$password', '$no_hp', '$alamat')";
        return mysqli_query($conn, $query);
    }
}
