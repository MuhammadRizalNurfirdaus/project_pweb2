<?php
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

$nama = trim($_POST['nama']);
$email = trim($_POST['email']);
$pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Cek email
$stmt = $conn->prepare("SELECT id FROM user WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo 'Email sudah terdaftar. <a href="register.php">Coba lagi</a>';
    exit;
}

// Simpan
$stmt = $conn->prepare("INSERT INTO user(nama_lengkap,email,password) VALUES(?,?,?)");
$stmt->bind_param("sss", $nama, $email, $pass);
if ($stmt->execute()) {
    echo 'Registrasi sukses. <a href="login.php">Login sekarang</a>';
} else {
    echo 'Gagal registrasi. <a href="register.php">Coba lagi</a>';
}
