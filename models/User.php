<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\User.php
// Skrip pemanggil HARUS sudah include config.php agar global $conn tersedia.

class User
{
    private static $table_name = "users";

    public static function login($email, $password)
    {
        global $conn; // Dari config.php

        $sql = "SELECT id, nama, email, password, role FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (User Login): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Jangan kembalikan hash password
            return $user; // Mengembalikan array data user
        }
        return false;
    }
    // Anda bisa menambahkan method register, findById, dll. di sini
}
